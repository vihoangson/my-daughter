<template>
  <div class="kid-profile-page">
    <h1 class="page-title">Hồ sơ của con</h1>

    <div v-if="!loading" class="info-header">
      <div class="info-avatar" @click="triggerAvatarFile">
        <img v-if="previewAvatar" :src="previewAvatar" alt="Avatar" />
        <div v-else class="placeholder">🙂</div>
      </div>
      <div class="info-meta">
        <h2 class="kid-name">{{ form.name || 'Chưa đặt tên' }}</h2>
        <p v-if="original.id" class="kid-id">ID: {{ original.id }}</p>
      </div>
    </div>

    <div class="profile-card" v-if="!loading">
      <form @submit.prevent="saveProfile" class="profile-form" novalidate>
        <div class="avatar-section">
          <div class="avatar-wrapper" :class="{ empty: !previewAvatar }" @click="triggerAvatarFile">
            <img v-if="previewAvatar" :src="previewAvatar" alt="Avatar" />
            <div v-else class="avatar-placeholder">🙂</div>
          </div>
          <div class="avatar-actions">
            <label class="upload-btn">
              <input ref="avatarInput" type="file" accept="image/*" @change="onAvatarChange" hidden />
              <span>Chọn ảnh</span>
            </label>
            <button type="button" class="small-btn" @click="clearAvatar" :disabled="!newAvatar && !previewAvatar">Xoá chọn</button>
          </div>
          <p class="hint">Ảnh vuông rõ nét giúp bố mẹ dễ nhận ra.</p>
        </div>

        <div class="fields">
          <div class="form-group">
            <label for="name">Tên của con <span class="req">*</span></label>
            <input id="name" v-model.trim="form.name" type="text" maxlength="100" required placeholder="Nhập tên" />
          </div>
        </div>

        <div class="actions">
            <button type="submit" class="save-btn" :disabled="saving">{{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}</button>
            <button type="button" class="reset-btn" @click="resetForm" :disabled="saving">Đặt lại</button>
        </div>
        <p v-if="error" class="error-msg">{{ error }}</p>
        <p v-if="success" class="success-msg">{{ success }}</p>
      </form>
    </div>

    <div v-else class="loading-box">Đang tải hồ sơ...</div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  name: 'KidProfile',
  data() {
    return {
      loading: true,
      saving: false,
      form: { name: '' },
      original: { name: '', avatar_url: null, id: null },
      previewAvatar: null,
      newAvatar: null,
      error: '',
      success: ''
    };
  },
  mounted() { this.fetchProfile(); },
  methods: {
    async fetchProfile() {
      this.loading = true; this.error='';
      try {
        const { data } = await axios.get('/api/kid/profile');
        if (data?.profile) {
          this.form.name = data.profile.name || '';
          this.original.name = this.form.name;
          this.original.id = data.profile.id || null;
          // Determine avatar URL: prefer top-level avatar_url, fallback to profile.avatar (relative path)
          const resolved = data.avatar_url || (data.profile.avatar ? this.buildStorageUrl(data.profile.avatar) : null);
          this.original.avatar_url = resolved;
          this.previewAvatar = resolved;
        }
      } catch { this.error = 'Không tải được hồ sơ.'; }
      finally { this.loading = false; }
    },
    buildStorageUrl(rel){
      if(!rel) return null;
      if(/^https?:\/\//i.test(rel)) return rel; // already full
      // assume stored in public storage
      return `/storage/${rel.replace(/^\//,'')}`;
    },
    triggerAvatarFile() { this.$refs.avatarInput && this.$refs.avatarInput.click(); },
    onAvatarChange(e) {
      const file = e.target.files[0]; if(!file) return;
      this.newAvatar = file;
      const reader = new FileReader(); reader.onload = ev => { this.previewAvatar = ev.target.result; }; reader.readAsDataURL(file);
    },
    clearAvatar() { this.newAvatar = null; this.previewAvatar = this.original.avatar_url || null; },
    resetForm() { this.form.name = this.original.name; this.clearAvatar(); this.error=''; this.success=''; },
    validate() { if(!this.form.name) return 'Tên không được để trống'; return ''; },
    async saveProfile() {
      this.error=''; this.success=''; const msg=this.validate(); if(msg){ this.error=msg; return; }
      this.saving=true;
      try {
        const fd=new FormData(); fd.append('name', this.form.name); if(this.newAvatar) fd.append('avatar', this.newAvatar);
        const { data } = await axios.post('/api/kid/profile/update', fd, { headers:{'Content-Type':'multipart/form-data'} });
        if(data?.profile){
          this.original.name = data.profile.name; this.original.avatar_url = data.avatar_url || null; this.original.id = data.profile.id || this.original.id;
          this.previewAvatar = this.original.avatar_url; this.newAvatar=null;
        }
        this.success='Đã lưu thay đổi!';
      } catch(e){ this.error = e?.response?.data?.message || 'Lưu thất bại.'; }
      finally { this.saving=false; }
    }
  }
};
</script>

<style scoped>
.kid-profile-page { padding:1rem 1.2rem 2rem; max-width:920px; margin:0 auto; }
.page-title { text-align:center; margin:0 0 1.1rem; font-size:1.9rem; }
.info-header { display:flex; align-items:center; gap:1rem; background:#fff; border:2px solid #e5e7f2; border-radius:18px; padding:.8rem 1rem; box-shadow:0 3px 10px rgba(0,0,0,0.04); margin:0 0 1rem; }
.info-avatar { width:72px; height:72px; border-radius:16px; overflow:hidden; background:#f2f5fb; display:flex; align-items:center; justify-content:center; border:2px solid #d9def2; cursor:pointer; }
.info-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
.info-avatar .placeholder { font-size:2rem; opacity:.45; }
.info-meta { display:flex; flex-direction:column; gap:.25rem; }
.kid-name { margin:0; font-size:1.25rem; line-height:1.2; }
.kid-id { margin:0; font-size:.7rem; letter-spacing:.5px; color:#666; font-weight:600; }
.profile-card { background:#fff; border:2px solid #e5e7f2; border-radius:22px; padding:1.2rem 1.4rem 1.6rem; box-shadow:0 4px 14px rgba(0,0,0,0.06); }
.profile-form { display:grid; grid-template-columns:220px 1fr; gap:1.4rem 1.6rem; align-items:start; }
@media (max-width:840px){ .profile-form { grid-template-columns:1fr; } .info-header { flex-direction:row; } }
.avatar-section { display:flex; flex-direction:column; align-items:center; gap:.6rem; }
.avatar-wrapper { width:180px; height:180px; border:3px solid #d9def2; border-radius:26px; overflow:hidden; background:#f2f5fb; display:flex; align-items:center; justify-content:center; position:relative; cursor:pointer; }
.avatar-wrapper.empty { border-style:dashed; }
.avatar-wrapper img { width:100%; height:100%; object-fit:cover; display:block; }
.avatar-placeholder { font-size:4rem; opacity:.4; }
.avatar-actions { display:flex; gap:.5rem; }
.upload-btn { background:#5d87ff; color:#fff; font-weight:600; padding:.55rem .95rem; border-radius:12px; cursor:pointer; font-size:.8rem; display:inline-flex; align-items:center; }
.small-btn { background:#ffb347; border:none; color:#272727; font-weight:600; padding:.5rem .85rem; border-radius:12px; cursor:pointer; font-size:.75rem; }
.small-btn:disabled { opacity:.5; cursor:default; }
.hint { font-size:.65rem; color:#666; text-align:center; max-width:180px; line-height:1.2; }
.fields { display:flex; flex-direction:column; gap:1rem; }
.form-group { display:flex; flex-direction:column; gap:.4rem; }
.form-group label { font-weight:600; font-size:.85rem; }
.req { color:#d94141; }
input[type=text] { border:2px solid #d9deea; border-radius:12px; padding:.65rem .8rem; font-size:.9rem; background:#fafbff; }
input[type=text]:focus { outline:2px solid #8bb8ff; border-color:#8bb8ff; background:#fff; }
.actions { grid-column:1 / -1; display:flex; gap:.8rem; flex-wrap:wrap; }
.save-btn { background:#4caf50; color:#fff; border:none; padding:.75rem 1.3rem; border-radius:14px; font-weight:700; cursor:pointer; font-size:.9rem; }
.save-btn:disabled { opacity:.6; }
.reset-btn { background:#ffd05d; border:none; padding:.75rem 1.1rem; border-radius:14px; font-weight:600; cursor:pointer; font-size:.85rem; }
.error-msg { color:#d93025; font-size:.75rem; margin:.25rem 0 0; }
.success-msg { color:#1b7f32; font-size:.75rem; margin:.25rem 0 0; }
.loading-box { text-align:center; padding:2rem 1rem; font-style:italic; }
@media (max-width:600px){ .page-title { font-size:1.6rem; } .avatar-wrapper { width:150px; height:150px; } .profile-card { padding:1rem 1rem 1.3rem; } .save-btn, .reset-btn { flex:1; text-align:center; } .info-header { padding:.65rem .75rem; } .kid-name { font-size:1.05rem; } }
</style>
