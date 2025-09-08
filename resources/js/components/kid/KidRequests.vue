<template>
  <div class="kid-requests-wrapper">
    <h1 class="page-title">Yêu cầu của con</h1>

    <div class="top-actions">
      <button class="toggle-form-btn" @click="toggleForm">
        <span v-if="!showForm">➕ Thêm yêu cầu mới</span>
        <span v-else>✖ Ẩn form</span>
      </button>
    </div>

    <div class="layout" :class="{ 'single-column': !showForm }">
      <!-- Form (toggle) -->
      <div v-if="showForm" class="panel form-panel">
        <h2 class="panel-title">Tạo yêu cầu mới</h2>
        <form @submit.prevent="submitRequest" class="request-form" novalidate>
          <div class="form-group">
            <label>Tiêu đề <span class="req">*</span></label>
            <input v-model.trim="form.title" type="text" required maxlength="100" placeholder="VD: Con muốn đồ chơi lego"/>
          </div>
          <div class="form-group">
            <label>Mô tả</label>
            <textarea v-model.trim="form.description" rows="3" placeholder="Viết rõ hơn..."/>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Loại <span class="req">*</span></label>
              <select v-model="form.type" required>
                <option disabled value="">-- Chọn --</option>
                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Phân loại</label>
              <select v-model="form.classification">
                <option value="">(Trống)</option>
                <option value="need">Nhu cầu</option>
                <option value="want">Mong muốn</option>
              </select>
              <button type="button" class="mini-btn" :disabled="classifying || !canAutoClassify" @click="autoClassify">
                <span v-if="!classifying">Tự phân loại</span>
                <span v-else>...</span>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label>Thời gian (nếu có)</label>
            <input v-model="form.scheduled_time" type="datetime-local" />
          </div>
          <div class="form-group">
            <label>Hình ảnh minh họa</label>
            <input type="file" accept="image/*" @change="onFileChange" />
            <div v-if="imagePreview" class="img-preview">
              <img :src="imagePreview" alt="preview" />
              <button type="button" class="clear-btn" @click="clearImage">X</button>
            </div>
          </div>
          <div class="actions">
            <button type="submit" class="submit-btn" :disabled="submitting">{{ submitting ? 'Đang gửi...' : 'Gửi yêu cầu' }}</button>
            <button type="button" class="reset-btn" @click="resetForm" :disabled="submitting">Xóa</button>
          </div>
          <p v-if="error" class="error-msg">{{ error }}</p>
          <p v-if="success" class="success-msg">{{ success }}</p>
        </form>
      </div>

      <!-- List -->
      <div class="panel list-panel">
        <div class="panel-head">
          <h2 class="panel-title">Yêu cầu đã gửi</h2>
          <div class="panel-actions">
            <button class="refresh-btn" @click="fetchRequests" :disabled="loadingList">Làm mới</button>
          </div>
        </div>
        <div v-if="loadingList" class="loading-box">Đang tải...</div>
        <div v-else-if="requests.length === 0" class="empty">Chưa có yêu cầu nào.</div>
        <div v-else class="requests-grid">
          <div v-for="r in requests" :key="r.id" class="request-card" :class="r.status">
            <div class="imgbox" v-if="r.image_url">
              <img :src="r.image_url" :alt="r.title" loading="lazy" />
            </div>
            <div class="card-body">
              <div class="row1">
                <h3 class="r-title">{{ r.title }}</h3>
                <span class="status" :class="'st-' + r.status">{{ statusLabel(r.status) }}</span>
              </div>
              <p v-if="r.description" class="desc">{{ r.description }}</p>
              <div class="meta-line">
                <span class="badge type">{{ typeLabel(r.type) }}</span>
                <span v-if="r.classification" class="badge classification" :class="'cl-' + r.classification">{{ classificationLabel(r.classification) }}</span>
                <span v-if="r.scheduled_time" class="badge time">🕒 {{ formatTime(r.scheduled_time) }}</span>
              </div>
              <div v-if="r.parent_note" class="parent-note">Ghi chú bố mẹ: {{ r.parent_note }}</div>
              <div class="row-actions" v-if="r.status === 'pending' && !r.classification">
                <button class="mini-outline" :disabled="r._classifying" @click="autoClassifyExisting(r)">
                  {{ r._classifying ? '...' : 'Tự phân loại' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  name: 'KidRequests',
  data() {
    return {
      showForm: false,
      form: {
        title: '',
        description: '',
        type: '',
        scheduled_time: '',
        classification: '',
        image: null,
      },
      types: [
        { value: 'toy', label: 'Đồ chơi' },
        { value: 'food', label: 'Đồ ăn' },
        { value: 'playground', label: 'Sân chơi' },
        { value: 'activity', label: 'Hoạt động' },
      ],
      requests: [],
      loadingList: false,
      submitting: false,
      classifying: false,
      error: '',
      success: '',
      imagePreview: null,
    };
  },
  computed: {
    canAutoClassify() { return this.form.title || this.form.description; }
  },
  mounted() { this.fetchRequests(); },
  methods: {
    toggleForm() { this.showForm = !this.showForm; },
    async fetchRequests() {
      this.loadingList = true;
      try {
        const { data } = await axios.get('/api/kid/requests');
        this.requests = Array.isArray(data) ? data : (data.data || []);
      } catch (e) { console.error(e); } finally { this.loadingList = false; }
    },
    onFileChange(e) {
      const file = e.target.files[0];
      if (!file) return;
      this.form.image = file;
      const reader = new FileReader();
      reader.onload = ev => { this.imagePreview = ev.target.result; };
      reader.readAsDataURL(file);
    },
    clearImage() { this.form.image = null; this.imagePreview = null; },
    resetForm() { this.form = { title:'', description:'', type:'', scheduled_time:'', classification:'', image:null }; this.imagePreview=null; this.error=''; this.success=''; },
    validate() { if(!this.form.title) return 'Thiếu tiêu đề'; if(!this.form.type) return 'Chọn loại yêu cầu'; return ''; },
    async submitRequest() {
      this.error=''; this.success='';
      const msg = this.validate(); if(msg){ this.error=msg; return; }
      this.submitting = true;
      try {
        const fd = new FormData();
        Object.entries(this.form).forEach(([k,v])=>{ if(v) fd.append(k,v); });
        await axios.post('/api/kid/requests', fd, { headers:{ 'Content-Type':'multipart/form-data' } });
        this.success = 'Đã gửi yêu cầu!';
        this.resetForm();
        await this.fetchRequests();
      } catch(e){ console.error(e); this.error = e?.response?.data?.message || 'Lỗi gửi yêu cầu'; }
      finally { this.submitting=false; }
    },
    async autoClassify() {
      if(!this.canAutoClassify) return;
      this.classifying=true; this.error='';
      try {
        const text = (this.form.title + ' ' + this.form.description).toLowerCase();
        this.form.classification = /học|sách|bút|vở|ăn|sức khỏe|khỏe/.test(text) ? 'need' : 'want';
      } finally { this.classifying=false; }
    },
    async autoClassifyExisting(r){
      if(!r.id) return; r._classifying=true;
      try { const { data } = await axios.post(`/api/kid/requests/${r.id}/classify`); if(data?.classification) r.classification=data.classification; }
      catch(e){ console.error(e); }
      finally { r._classifying=false; }
    },
    statusLabel(st){ switch(st){ case 'pending': return 'Chờ duyệt'; case 'approved': return 'Đã duyệt'; case 'rejected': return 'Bị từ chối'; case 'completed': return 'Hoàn thành'; default: return st; }},
    typeLabel(t){ const f=this.types.find(x=>x.value===t); return f?f.label:t; },
    classificationLabel(c){ return c==='need' ? 'Nhu cầu' : (c==='want' ? 'Mong muốn' : c); },
    formatTime(dt){ try { return new Date(dt).toLocaleString('vi-VN'); } catch { return dt; } }
  }
};
</script>

<style scoped>
.kid-requests-wrapper { padding: 1rem 1.25rem 2rem; }
.page-title { text-align: center; margin: 0 0 1.2rem; font-size: 1.8rem; }
.top-actions { display:flex; justify-content:center; margin:-.3rem 0 1rem; }
.toggle-form-btn { background:#ff7f50; border:2px solid #ff9f7a; color:#fff; font-weight:700; padding:.65rem 1.1rem; border-radius:14px; cursor:pointer; font-size:.9rem; box-shadow:0 3px 8px rgba(0,0,0,0.15); }
.toggle-form-btn:hover { background:#ff986f; }
.layout { display: grid; gap: 1.25rem; grid-template-columns: 340px 1fr; align-items: start; }
.layout.single-column { grid-template-columns: 1fr; }
.panel { background: #ffffff; border: 2px solid #ececf3; border-radius: 16px; padding: 1rem 1.1rem 1.3rem; box-shadow: 0 3px 8px rgba(0,0,0,0.05); }
.panel-title { margin: 0 0 .9rem; font-size: 1.1rem; font-weight: 700; }
.form-panel { position: sticky; top: .75rem; animation: fadeSlide .25s ease; }
@keyframes fadeSlide { from { opacity:0; transform:translateY(-6px);} to { opacity:1; transform:translateY(0);} }
.request-form { display: flex; flex-direction: column; gap: .85rem; }
.form-group { display: flex; flex-direction: column; gap: .35rem; }
.form-row { display: flex; gap: .75rem; }
.form-row .form-group { flex: 1; }
label { font-weight: 600; font-size: .86rem; }
.req { color: #d94141; }
input[type=text], textarea, select, input[type=datetime-local] { border: 2px solid #dcdce5; border-radius: 10px; padding: .55rem .7rem; font-size: .85rem; background:#fafbff; }
input:focus, textarea:focus, select:focus { outline: 2px solid #8bb8ff; border-color: #8bb8ff; background:#fff; }
textarea { resize: vertical; }
.mini-btn { margin-top: .4rem; background:#6a8dff; border:none; color:#fff; padding:.3rem .55rem; font-size:.7rem; border-radius:6px; cursor:pointer; }
.mini-btn:disabled { opacity:.5; cursor: default; }
.actions { display:flex; gap:.6rem; }
.submit-btn { flex:1; background:#4caf50; color:#fff; border:none; padding:.65rem .9rem; border-radius:10px; font-weight:600; cursor:pointer; }
.submit-btn:disabled { opacity:.6; }
.reset-btn { background:#ffb347; border:none; padding:.65rem .9rem; border-radius:10px; font-weight:600; cursor:pointer; }
.error-msg { color:#d93025; font-size:.8rem; margin:.2rem 0 0; }
.success-msg { color:#1b7f32; font-size:.8rem; margin:.2rem 0 0; }
.img-preview { position:relative; margin-top:.4rem; width:100%; max-width:240px; border:2px solid #dcdce5; border-radius:12px; overflow:hidden; }
.img-preview img { display:block; width:100%; height:auto; }
.clear-btn { position:absolute; top:4px; right:4px; background:#ff4d4f; color:#fff; border:none; border-radius:50%; width:28px; height:28px; font-weight:700; cursor:pointer; }
.list-panel { }
.panel-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:.4rem; }
.panel-actions { display:flex; gap:.5rem; }
.refresh-btn { background:#2196f3; border:none; color:#fff; padding:.5rem .9rem; border-radius:10px; font-weight:600; cursor:pointer; }
.loading-box { padding:1rem; font-style:italic; }
.empty { padding:1rem; color:#666; }
.requests-grid { display:grid; gap:1rem; grid-template-columns: repeat(auto-fill, minmax(250px,1fr)); }
.request-card { background:#f9faff; border:2px solid #e3e6f5; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; }
.request-card.pending { border-color:#ffe08a; }
.request-card.approved { border-color:#8ae68a; }
.request-card.rejected { border-color:#ff9d9d; }
.request-card.completed { border-color:#9dc9ff; }
.imgbox { width:100%; aspect-ratio: 4/3; background:#eef1f9; overflow:hidden; }
.imgbox img { width:100%; height:100%; object-fit:cover; }
.card-body { padding:.7rem .75rem .9rem; display:flex; flex-direction:column; gap:.45rem; flex:1; }
.row1 { display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem; }
.r-title { font-size:1rem; margin:0; flex:1; line-height:1.2; }
.status { font-size:.62rem; padding:.25rem .45rem; border-radius:6px; background:#d0d5e6; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.st-pending { background:#ffd87a; }
.st-approved { background:#6edb6e; }
.st-rejected { background:#ff8282; }
.st-completed { background:#7ab4ff; }
.desc { font-size:.75rem; margin:0; color:#333; }
.meta-line { display:flex; flex-wrap:wrap; gap:.4rem; }
.badge { font-size:.6rem; padding:.28rem .5rem; border-radius:999px; background:#dde2f2; font-weight:600; letter-spacing:.3px; }
.badge.type { background:#ffd6a2; }
.badge.classification { background:#ffe4f0; }
.badge.classification.cl-need { background:#c7f7c7; }
.badge.classification.cl-want { background:#fbd0ff; }
.badge.time { background:#d4ecff; }
.parent-note { font-size:.65rem; background:#fff5c4; padding:.4rem .5rem; border-radius:8px; }
.row-actions { margin-top:auto; }
.mini-outline { background:#fff; border:2px solid #6a8dff; color:#3751a8; padding:.35rem .6rem; border-radius:8px; font-size:.65rem; font-weight:600; cursor:pointer; }
.mini-outline:disabled { opacity:.5; cursor:default; }
@media (max-width: 980px){ .layout { grid-template-columns: 1fr; } }
</style>
