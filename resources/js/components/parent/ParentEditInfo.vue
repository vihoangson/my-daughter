<template>
  <div class="parent-edit-info">
    <Breadcrumbs />
    <h2>Chỉnh sửa thông tin phụ huynh</h2>

    <div v-if="loading" class="loading">Đang tải thông tin...</div>
    <div v-else>
      <form @submit.prevent="save" class="profile-form" novalidate>
        <div class="form-grid">
          <div class="field">
            <label>Tên <span class="req">*</span></label>
            <input v-model.trim="form.name" type="text" required :class="{'invalid': submitTried && !form.name}" placeholder="Nhập tên" />
            <small v-if="submitTried && !form.name" class="err">Tên là bắt buộc</small>
          </div>
          <div class="field">
            <label>Điện thoại</label>
            <input v-model.trim="form.phone" type="text" maxlength="20" placeholder="Ví dụ: 0901234567" />
          </div>
          <div class="field wide">
            <label>Địa chỉ</label>
            <input v-model.trim="form.address" type="text" maxlength="255" placeholder="Địa chỉ" />
          </div>
          <div class="field wide">
            <label>Ảnh đại diện</label>
            <div class="avatar-row">
              <div class="avatar-preview" v-if="preview || currentAvatarUrl">
                <img :src="preview || currentAvatarUrl" alt="avatar" />
                <button v-if="preview" type="button" class="mini-btn" @click="removeNewAvatar">×</button>
              </div>
              <input ref="avatarInput" type="file" accept="image/*" @change="onAvatarChange" />
            </div>
            <small class="hint">Tối đa 2MB. Định dạng: JPG, PNG, GIF, WEBP</small>
          </div>
          <div class="field wide">
            <label>Ghi chú hệ thống</label>
            <div class="readonly-box">Email: <strong>{{ profile.email }}</strong></div>
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn primary" :disabled="saving">
            <span v-if="saving" class="spinner"></span>
            <span v-else>Lưu thay đổi</span>
          </button>
          <button type="button" class="btn outline" :disabled="saving" @click="resetForm">Khôi phục</button>
        </div>
      </form>
    </div>

    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Breadcrumbs from '../common/Breadcrumbs.vue';

const loading = ref(true);
const saving = ref(false);
const submitTried = ref(false);
const profile = ref({});
const form = ref({ name:'', phone:'', address:'', avatar:null });
const preview = ref(null);
const avatarInput = ref(null);
const toast = ref({ message:'', type:'ok' });
let toastTimer = null;

const showToast = (msg,type='ok',ttl=2500)=>{ toast.value={message:msg,type}; clearTimeout(toastTimer); toastTimer=setTimeout(()=>toast.value.message='',ttl); };

const fetchProfile = async ()=>{
  loading.value = true;
  try {
    const { data } = await axios.get('/api/parent/profile');
    profile.value = data.profile || data || {};
    form.value.name = profile.value.name || '';
    form.value.phone = profile.value.phone || '';
    form.value.address = profile.value.address || '';
  } catch(e){
    console.error(e); showToast(e.response?.data?.message || 'Lỗi tải hồ sơ','err');
  } finally { loading.value=false; }
};
fetchProfile();

const currentAvatarUrl = computed(()=>{
  const a = profile.value.avatar;
  if(!a) return '';
  if(/^https?:/i.test(a)) return a;
  return '/storage/' + a;
});

function onAvatarChange(e){
  const file = e.target.files[0];
  if(!file){ form.value.avatar=null; preview.value=null; return; }
  if(file.size > 2*1024*1024){ showToast('Ảnh vượt quá 2MB','err'); e.target.value=''; return; }
  if(!/image\//.test(file.type)){ showToast('File không phải ảnh','err'); e.target.value=''; return; }
  form.value.avatar = file;
  const reader = new FileReader();
  reader.onload = ev => { preview.value = ev.target.result; };
  reader.readAsDataURL(file);
}

function removeNewAvatar(){
  form.value.avatar = null;
  preview.value = null;
  if(avatarInput.value) avatarInput.value.value='';
}

function resetForm(){
  submitTried.value=false;
  form.value.name = profile.value.name || '';
  form.value.phone = profile.value.phone || '';
  form.value.address = profile.value.address || '';
  removeNewAvatar();
}

async function save(){
  submitTried.value=true;
  if(!form.value.name){ showToast('Vui lòng nhập tên','warn'); return; }
  saving.value = true;
  try {
    const fd = new FormData();
    fd.append('name', form.value.name);
    if(form.value.phone) fd.append('phone', form.value.phone);
    if(form.value.address) fd.append('address', form.value.address);
    if(form.value.avatar) fd.append('avatar', form.value.avatar);
    const { data } = await axios.post('/api/parent/profile', fd, { headers:{ 'Content-Type':'multipart/form-data' } });
    profile.value = data.profile || data;
    resetForm();
    showToast('Đã lưu thay đổi','ok');
  } catch(e){
    console.error(e);
    if(e.response?.data?.errors){
      const firstKey = Object.keys(e.response.data.errors)[0];
      showToast(e.response.data.errors[firstKey][0] || 'Lỗi lưu','err');
    } else {
      showToast(e.response?.data?.message || 'Lỗi lưu','err');
    }
  } finally { saving.value=false; }
}
</script>

<style scoped>
.parent-edit-info { padding:1rem 1.1rem 2.5rem; max-width:760px; }
.parent-edit-info h2 { margin:0 0 1rem; font-size:1.5rem; }
.loading { font-size:.9rem; color:#555; }
.profile-form { display:flex; flex-direction:column; gap:1.2rem; }
.form-grid { display:grid; gap:1rem .9rem; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); }
.field { display:flex; flex-direction:column; gap:.4rem; position:relative; }
.field.wide { grid-column:1/-1; }
label { font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:#374151; }
.req { color:#dc2626; }
input[type='text'] { padding:.55rem .65rem; border:1px solid #d1d5db; border-radius:8px; font-size:.85rem; background:#fff; }
input[type='text']:focus { outline:2px solid #2563eb33; border-color:#2563eb; }
input.invalid { border-color:#dc2626; }
.err { color:#dc2626; font-size:.65rem; }
.hint { font-size:.65rem; color:#6b7280; }
.readonly-box { background:#f1f5f9; padding:.55rem .7rem; border-radius:8px; font-size:.8rem; border:1px solid #e2e8f0; }
.avatar-row { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
.avatar-preview { position:relative; width:90px; height:90px; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; background:#fff; }
.avatar-preview img { width:100%; height:100%; object-fit:cover; }
.mini-btn { position:absolute; top:2px; right:2px; background:rgba(0,0,0,.6); color:#fff; border:none; width:22px; height:22px; border-radius:50%; cursor:pointer; font-weight:700; line-height:1; }
.actions { display:flex; gap:.75rem; }
.btn { cursor:pointer; border:none; border-radius:8px; font-weight:600; font-size:.8rem; padding:.65rem 1.1rem; display:inline-flex; align-items:center; gap:.4rem; }
.btn.primary { background:linear-gradient(90deg,#2563eb,#1d4ed8); color:#fff; }
.btn.outline { background:#fff; border:1px solid #d1d5db; color:#374151; }
.btn:disabled { opacity:.55; cursor:default; }
.spinner { width:16px; height:16px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.toast { position:fixed; bottom:1rem; right:1rem; background:#1f2937; color:#fff; padding:.6rem .85rem; border-radius:10px; font-size:.7rem; box-shadow:0 4px 14px rgba(0,0,0,.25); z-index:500; }
.toast.ok { background:#166534; }
.toast.err { background:#b91c1c; }
.toast.warn { background:#92400e; }
@media (prefers-color-scheme: dark){
  .parent-edit-info { color:#e5e7eb; }
  label { color:#9ca3af; }
  input[type='text'] { background:#1f2937; border-color:#374151; color:#e5e7eb; }
  .readonly-box { background:#1f2937; border-color:#374151; color:#e5e7eb; }
  .avatar-preview { border-color:#374151; }
  .btn.outline { background:#1f2937; border-color:#374151; color:#e5e7eb; }
  .toast { background:#374151; }
  .toast.ok { background:#166534; }
}
</style>
