<template>
  <div class="parent-approve-requests">
    <div class="header-bar">
      <h2>Phê duyệt yêu cầu</h2>
      <div class="actions-inline">
        <button class="btn small" @click="fetchAll" :disabled="loading">{{ loading? 'Đang tải...' : '↻ Tải lại' }}</button>
        <select v-model="filterStatus" class="filter-select">
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="completed">Completed</option>
          <option value="all">Tất cả</option>
        </select>
        <input v-model="search" type="text" placeholder="Tìm tiêu đề..." class="search-box" />
      </div>
    </div>

    <div v-if="error" class="error-box">{{ error }}</div>

    <div v-if="!loading && !filteredRequests.length" class="empty">Không có yêu cầu phù hợp.</div>

    <div class="requests-grid">
      <div
        v-for="req in filteredRequests"
        :key="req.id"
        class="request-card"
        :class="req.status"
        @click="openDetail(req)"
      >
        <div class="top-line">
            <span class="status-badge" :class="req.status">{{ statusLabel(req.status) }}</span>
            <span v-if="req.classification" class="classification" :class="req.classification">{{ classifyLabel(req.classification) }}</span>
        </div>
        <div class="title" :title="req.title">{{ req.title }}</div>
        <div class="meta">
          <span class="kid" v-if="req.child">👦 {{ req.child.name || ('Kid #' + req.child.id) }}</span>
          <span class="type">📌 {{ typeLabel(req.type) }}</span>
        </div>
        <div class="desc" v-if="req.description">{{ truncate(req.description, 90) }}</div>
        <div class="schedule" v-if="req.scheduled_time">⏱ {{ formatDate(req.scheduled_time) }}</div>
        <div class="card-actions" @click.stop>
          <button
            v-if="req.status==='pending'"
            class="btn approve"
            :disabled="actingId===req.id"
            @click="quickAction(req,'approved')"
          >✔</button>
          <button
            v-if="req.status==='pending'"
            class="btn reject"
            :disabled="actingId===req.id"
            @click="quickAction(req,'rejected')"
          >✖</button>
          <button
            v-if="req.status==='approved'"
            class="btn done"
            :disabled="actingId===req.id"
            @click="quickAction(req,'completed')"
          >✓ Hoàn tất</button>
        </div>
        <div class="spinner-overlay" v-if="actingId===req.id"><div class="spinner" /></div>
      </div>
    </div>

    <!-- Detail Side Panel -->
    <div class="detail-panel" v-if="detail" :class="{open: !!detail}">
      <div class="panel-header">
        <h3>{{ detail.title }}</h3>
        <button class="close-btn" @click="detail=null">×</button>
      </div>
      <div class="panel-body">
        <div class="line"><strong>Trạng thái:</strong> <span :class="['status-inline', detail.status]">{{ statusLabel(detail.status) }}</span></div>
        <div class="line" v-if="detail.child"><strong>Trẻ:</strong> {{ detail.child.name || ('Kid #' + detail.child.id) }}</div>
        <div class="line"><strong>Loại:</strong> {{ typeLabel(detail.type) }}</div>
        <div class="line" v-if="detail.classification"><strong>Phân loại:</strong> {{ classifyLabel(detail.classification) }}</div>
        <div class="block" v-if="detail.description">
          <strong>Mô tả:</strong>
          <p class="pre">{{ detail.description }}</p>
        </div>
        <div class="block" v-if="detail.image">
          <strong>Hình ảnh:</strong>
          <img :src="storageUrl(detail.image)" class="preview" alt="Image" />
        </div>
        <div class="block">
          <label class="field-label">Ghi chú phụ huynh</label>
          <textarea v-model="form.parent_note" rows="3" class="input"></textarea>
        </div>
        <div class="block">
          <label class="field-label">Thời gian dự kiến (optional)</label>
          <input type="datetime-local" v-model="form.scheduled_time" class="input" />
        </div>
        <div class="action-row">
          <button class="btn approve wide" v-if="detail.status==='pending'" :disabled="saving" @click="submitStatus('approved')">Duyệt</button>
          <button class="btn reject wide" v-if="detail.status==='pending'" :disabled="saving" @click="submitStatus('rejected')">Từ chối</button>
          <button class="btn done wide" v-if="detail.status==='approved'" :disabled="saving" @click="submitStatus('completed')">Đánh dấu hoàn tất</button>
        </div>
        <div class="panel-footer">
          <button class="btn outline" :disabled="saving" @click="detail=null">Đóng</button>
          <button class="btn primary" :disabled="saving || !changed" @click="saveNote">Lưu ghi chú</button>
        </div>
        <div v-if="saving" class="saving-indicator"><div class="spinner small" /> Đang lưu...</div>
      </div>
    </div>

    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const requests = ref([]);
const loading = ref(false);
const error = ref('');
const filterStatus = ref('pending');
const search = ref('');
const detail = ref(null);
const actingId = ref(null);
const toast = ref({ message:'', type:'ok' });
const saving = ref(false);
const form = ref({ parent_note:'', scheduled_time:'' });
const originalForm = ref({ parent_note:'', scheduled_time:'' });

const showToast = (m,t='ok',ttl=2500)=>{ toast.value={message:m,type:t}; clearTimeout(showToast._t); showToast._t=setTimeout(()=>toast.value.message='', ttl); };

const fetchAll = async ()=>{
  loading.value=true; error.value='';
  try {
    const { data } = await axios.get('/api/parent/requests');
    const list = Array.isArray(data) ? data : (data.requests || []);
    requests.value = list;
  } catch(e){
    console.error(e);
    error.value = e.response?.data?.message || 'Lỗi tải dữ liệu';
  } finally { loading.value=false; }
};
fetchAll();

const filteredRequests = computed(()=>{
  const q = search.value.trim().toLowerCase();
  return requests.value.filter(r=>{
    if (filterStatus.value !== 'all' && r.status !== filterStatus.value) return false;
    if (q && !r.title?.toLowerCase().includes(q)) return false;
    return true;
  });
});

const statusLabel = (s)=>({pending:'Chờ', approved:'Đã duyệt', rejected:'Từ chối', completed:'Hoàn tất'}[s]||s);
const typeLabel = (t)=>({toy:'Đồ chơi', food:'Đồ ăn', playground:'Khu vui chơi', activity:'Hoạt động'}[t]||t);
const classifyLabel = (c)=>({need:'Cần', want:'Muốn'}[c]||c);
const truncate = (txt,len)=> txt && txt.length>len ? txt.slice(0,len-1)+'…' : txt;
const formatDate = (d)=> new Date(d).toLocaleString();
const storageUrl = (p)=> p && (p.startsWith('http')? p : `/storage/${p}`);

const openDetail = (req)=>{
  detail.value = JSON.parse(JSON.stringify(req)); // deep clone
  form.value.parent_note = detail.value.parent_note || '';
  form.value.scheduled_time = detail.value.scheduled_time ? detail.value.scheduled_time.substring(0,16) : '';
  originalForm.value = { ...form.value };
};

const changed = computed(()=> form.value.parent_note !== originalForm.value.parent_note || form.value.scheduled_time !== originalForm.value.scheduled_time);

const applyLocalUpdate = (updated)=>{
  const idx = requests.value.findIndex(r=>r.id===updated.id);
  if (idx>=0) requests.value[idx] = { ...requests.value[idx], ...updated };
  if (detail.value && detail.value.id===updated.id) {
    detail.value = { ...detail.value, ...updated };
    form.value.parent_note = detail.value.parent_note||'';
    form.value.scheduled_time = detail.value.scheduled_time ? detail.value.scheduled_time.substring(0,16) : '';
    originalForm.value = { ...form.value };
  }
};

const sendStatus = async (reqId, status, extra={})=>{
  actingId.value = reqId;
  try {
    const payload = { status, ...extra };
    const { data } = await axios.put(`/api/parent/requests/${reqId}/status`, payload);
    applyLocalUpdate(data);
    showToast('Cập nhật: '+statusLabel(status));
  } catch(e){
    console.error(e); showToast(e.response?.data?.message || 'Lỗi cập nhật','err');
  } finally { actingId.value=null; }
};

const quickAction = (req,status)=>{
  if (actingId.value) return;
  sendStatus(req.id, status);
};

const submitStatus = (status)=>{
  if (!detail.value) return;
  sendStatus(detail.value.id, status, prepareFormPayload());
};

const prepareFormPayload = ()=>{
  const payload = {};
  if (form.value.parent_note?.trim()) payload.parent_note = form.value.parent_note.trim();
  if (form.value.scheduled_time) payload.scheduled_time = form.value.scheduled_time;
  return payload;
};

const saveNote = ()=>{
  if (!detail.value) return;
  saving.value = true;
  sendStatus(detail.value.id, detail.value.status, prepareFormPayload())
    .finally(()=> saving.value=false);
};
</script>

<style scoped>
.parent-approve-requests { position:relative; padding:1rem 1rem 3rem; }
.header-bar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
.header-bar h2 { margin:0; font-size:1.4rem; }
.actions-inline { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
.search-box, .filter-select { padding:.45rem .55rem; border:1px solid #ccc; border-radius:6px; font-size:.85rem; }
.btn { cursor:pointer; border:none; border-radius:6px; background:#2563eb; color:#fff; padding:.45rem .75rem; font-size:.75rem; font-weight:600; display:inline-flex; align-items:center; gap:.35rem; }
.btn.small { padding:.4rem .6rem; }
.btn.outline { background:#fff; border:1px solid #ccc; color:#333; }
.btn.primary { background:#1d4ed8; }
.btn.approve { background:linear-gradient(90deg,#15803d,#166534); }
.btn.reject { background:linear-gradient(90deg,#dc2626,#b91c1c); }
.btn.done { background:linear-gradient(90deg,#0891b2,#0e7490); font-size:.7rem; }
.btn.wide { flex:1; justify-content:center; }
.btn:disabled { opacity:.55; cursor:default; }
.error-box { background:#fee2e2; color:#991b1b; padding:.6rem .8rem; border:1px solid #fecaca; border-radius:6px; font-size:.8rem; margin-bottom:.75rem; }
.empty { font-size:.85rem; color:#555; padding:1rem 0; }
.requests-grid { display:grid; gap:.9rem; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); }
.request-card { position:relative; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:.75rem .7rem .9rem; display:flex; flex-direction:column; gap:.4rem; box-shadow:0 1px 2px rgba(0,0,0,.05); transition:.15s; }
.request-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.12); transform:translateY(-2px); }
.request-card.pending { border-color:#fde68a; }
.request-card.approved { border-color:#bbf7d0; }
.request-card.rejected { border-color:#fecaca; }
.request-card.completed { border-color:#bfdbfe; }
.title { font-weight:600; font-size:.95rem; line-height:1.2; min-height:2.2em; }
.meta { font-size:.68rem; display:flex; gap:.5rem; flex-wrap:wrap; opacity:.8; }
.desc { font-size:.7rem; color:#444; line-height:1.15; }
.schedule { font-size:.65rem; color:#2563eb; }
.status-badge { font-size:.6rem; text-transform:uppercase; font-weight:700; letter-spacing:.5px; padding:.2rem .45rem; border-radius:12px; background:#e5e7eb; color:#111; }
.status-badge.pending { background:#fef3c7; color:#92400e; }
.status-badge.approved { background:#dcfce7; color:#166534; }
.status-badge.rejected { background:#fee2e2; color:#991b1b; }
.status-badge.completed { background:#dbeafe; color:#1e3a8a; }
.classification { font-size:.55rem; padding:.17rem .4rem; border-radius:10px; font-weight:600; background:#e0e7ff; color:#3730a3; }
.classification.need { background:#c7f9e5; color:#065f46; }
.classification.want { background:#fce7f3; color:#9d174d; }
.top-line { display:flex; justify-content:space-between; align-items:center; gap:.35rem; }
.card-actions { margin-top:auto; display:flex; gap:.35rem; }
.card-actions .btn { flex:1; justify-content:center; padding:.35rem 0; }
.spinner-overlay { position:absolute; inset:0; background:rgba(255,255,255,.65); backdrop-filter:blur(2px); display:flex; align-items:center; justify-content:center; border-radius:12px; }
.spinner { width:20px; height:20px; border:3px solid rgba(0,0,0,.15); border-top-color:#2563eb; border-radius:50%; animation:spin .8s linear infinite; }
.spinner.small { width:16px; height:16px; border-width:2px; }
@keyframes spin { to { transform:rotate(360deg); } }

/* Detail panel */
.detail-panel { position:fixed; top:0; right:0; width:360px; max-width:90%; height:100%; background:#ffffff; box-shadow:-4px 0 18px -4px rgba(0,0,0,.18); transform:translateX(100%); transition:.28s; display:flex; flex-direction:column; z-index:200; }
.detail-panel.open { transform:translateX(0); }
.panel-header { display:flex; align-items:center; justify-content:space-between; padding:.9rem .95rem; border-bottom:1px solid #e2e8f0; }
.panel-header h3 { margin:0; font-size:1.05rem; line-height:1.2; }
.close-btn { border:none; background:transparent; font-size:1.3rem; cursor:pointer; line-height:1; }
.panel-body { padding:.9rem .95rem 2.2rem; overflow-y:auto; font-size:.8rem; display:flex; flex-direction:column; gap:.75rem; }
.line { display:flex; gap:.5rem; flex-wrap:wrap; }
.block { display:flex; flex-direction:column; gap:.35rem; }
.field-label { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.input { width:100%; padding:.45rem .55rem; border:1px solid #d1d5db; border-radius:6px; background:#fff; font-size:.75rem; }
.input:focus { outline:2px solid #2563eb33; }
.pre { white-space:pre-wrap; margin:0; }
.preview { max-width:100%; border-radius:6px; border:1px solid #e5e7eb; }
.action-row { display:flex; gap:.5rem; }
.panel-footer { display:flex; gap:.6rem; justify-content:flex-end; margin-top:.5rem; }
.status-inline { font-weight:600; }
.status-inline.pending { color:#92400e; }
.status-inline.approved { color:#166534; }
.status-inline.rejected { color:#991b1b; }
.status-inline.completed { color:#1e3a8a; }
.saving-indicator { display:flex; gap:.5rem; align-items:center; font-size:.7rem; color:#2563eb; }

.toast { position:fixed; bottom:1rem; left:50%; transform:translateX(-50%); background:#1f2937; color:#fff; padding:.6rem .85rem; border-radius:10px; font-size:.7rem; box-shadow:0 4px 14px rgba(0,0,0,.25); z-index:500; }
.toast.err { background:#b91c1c; }
.toast.ok { background:#166534; }
.toast.warn { background:#92400e; }

@media (max-width:640px){
  .requests-grid { grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); }
  .detail-panel { width:100%; }
}
@media (prefers-color-scheme: dark){
  .parent-approve-requests { color:#e5e7eb; }
  .request-card { background:#1f2937; border-color:#374151; }
  .request-card.pending { border-color:#f59e0b55; }
  .request-card.approved { border-color:#10b98155; }
  .request-card.rejected { border-color:#ef444455; }
  .request-card.completed { border-color:#3b82f655; }
  .detail-panel { background:#111827; }
  .panel-header { border-color:#374151; }
  .input { background:#1f2937; color:#e7e7eb; border-color:#374151; }
  .preview { border-color:#374151; }
  .error-box { background:#7f1d1d; border-color:#991b1b; color:#fff; }
}
</style>
