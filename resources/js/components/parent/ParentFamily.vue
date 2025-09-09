<template>
  <div class="parent-family">
    <Breadcrumbs />
    <h2 class="page-title">Quản lý Gia đình</h2>

    <div v-if="loading" class="loading">Đang tải...</div>
    <div v-else>
      <div class="layout">
        <!-- Family core info -->
        <div class="card stretch">
          <h3 class="card-title">Thông tin chung</h3>
          <form @submit.prevent="updateFamily" class="form-grid">
            <label>
              <span>Tên gia đình</span>
              <input v-model="form.name" required maxlength="120" />
            </label>
            <label>
              <span>Khẩu hiệu / Motto</span>
              <input v-model="form.motto" maxlength="255" placeholder="(tuỳ chọn)" />
            </label>
            <label>
              <span>Timezone</span>
              <input v-model="form.timezone" placeholder="Asia/Ho_Chi_Minh" />
            </label>
            <label>
              <span>Quốc gia</span>
              <input v-model="form.country" maxlength="3" placeholder="VN" />
            </label>
            <div class="actions-row">
              <button type="submit" :disabled="saving">{{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}</button>
              <button type="button" class="secondary" @click="resetForm" :disabled="saving">Hoàn tác</button>
            </div>
          </form>
        </div>

        <!-- Invite code -->
        <div class="card invite">
            <h3 class="card-title">Mã mời</h3>
            <div class="invite-code" @click="copyInvite" :title="'Nhấp để copy'">{{ family.invite_code }}</div>
            <small>Dùng mã này để thêm thành viên (user khác cha mẹ / con).</small>
            <button class="w-full mt" @click="regenerateInvite" :disabled="regenLoading">{{ regenLoading ? 'Đang tạo...' : 'Tạo mã mới' }}</button>
        </div>

        <!-- Members -->
        <div class="card members stretch">
          <h3 class="card-title">Thành viên ({{ members.length }})</h3>
          <div v-if="!members.length" class="empty">Chưa có thành viên.</div>
          <ul class="member-list" v-else>
            <li v-for="m in members" :key="m.id" class="member-item" :class="m.type">
              <div class="avatar" :title="m.name || ('User #'+m.id)">
                <img v-if="m.avatar_url" :src="m.avatar_url" alt="avatar" />
                <span v-else>{{ initialOf(m.name) }}</span>
              </div>
              <div class="info">
                <span class="name">{{ m.name || 'User #'+m.id }}</span>
                <span class="role" v-if="m.id===family.primary_parent_id">(Chủ)</span>
                <span class="badge" :class="m.type">{{ typeLabel(m.type) }}</span>
              </div>
              <button v-if="m.id!==family.primary_parent_id" class="remove" @click="removeMember(m)">✕</button>
            </li>
          </ul>
          <div class="add-member-box">
            <h4>Thêm thành viên</h4>
            <div class="add-flex">
              <input v-model.number="addUserId" type="number" min="1" placeholder="Nhập user_id" />
              <button @click="addMember" :disabled="adding || !addUserId">{{ adding? 'Đang thêm...' : 'Thêm' }}</button>
            </div>
            <small class="hint">(Hoặc g���i mã mời để người khác tự gia nhập qua quy trình riêng)</small>
          </div>
        </div>
      </div>
    </div>

    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import Breadcrumbs from '../common/Breadcrumbs.vue';

const loading = ref(true);
const saving = ref(false);
const regenLoading = ref(false);
const adding = ref(false);
const family = reactive({ id:null, name:'', motto:'', timezone:'', country:'', invite_code:'', primary_parent_id:null });
const members = ref([]);
const form = reactive({ name:'', motto:'', timezone:'', country:'' });
const addUserId = ref('');
const toast = reactive({ message:'', type:'ok' });
let toastTimer = null;

function showToast(msg,type='ok',ttl=2600){
  toast.message=msg; toast.type=type; clearTimeout(toastTimer); toastTimer=setTimeout(()=>toast.message='',ttl);
}

function hydrate(data){
  Object.assign(family, data.family);
  members.value = data.family.users || [];
  resetForm();
}

async function fetchFamily(){
  loading.value = true;
  try {
    const { data } = await axios.get('/api/parent/family');
    hydrate(data);
  } catch(e){
    showToast(e.response?.data?.message || 'Lỗi tải gia đình','err');
  } finally { loading.value=false; }
}

function resetForm(){
  form.name = family.name || '';
  form.motto = family.motto || '';
  form.timezone = family.timezone || 'Asia/Ho_Chi_Minh';
  form.country = family.country || 'VN';
}

async function updateFamily(){
  saving.value = true;
  try {
    const { data } = await axios.put('/api/parent/family', form);
    hydrate(data);
    showToast('Đã lưu','ok');
  } catch(e){
    showToast(e.response?.data?.message || 'Lỗi lưu','err');
  } finally { saving.value=false; }
}

async function regenerateInvite(){
  regenLoading.value = true;
  try {
    const { data } = await axios.post('/api/parent/family/regenerate-invite');
    family.invite_code = data.invite_code;
    showToast('Đã tạo mã mới');
  } catch(e){
    showToast('Lỗi tạo mã','err');
  } finally { regenLoading.value=false; }
}

async function addMember(){
  if(!addUserId.value) return;
  adding.value = true;
  try {
    const { data } = await axios.post('/api/parent/family/members', { user_id: addUserId.value });
    members.value.push(data.member);
    addUserId.value='';
    showToast('Đã thêm thành viên');
  } catch(e){
    showToast(e.response?.data?.message || 'Lỗi thêm','err');
  } finally { adding.value=false; }
}

async function removeMember(m){
  if(!confirm('Xoá thành viên này?')) return;
  try {
    await axios.delete(`/api/parent/family/members/${m.id}`);
    members.value = members.value.filter(x=>x.id!==m.id);
    showToast('Đã xoá');
  } catch(e){
    showToast(e.response?.data?.message || 'Lỗi xoá','err');
  }
}

function copyInvite(){
  if(!family.invite_code) return;
  navigator.clipboard.writeText(family.invite_code).then(()=> showToast('Đã copy mã','ok')).catch(()=>{});
}

function typeLabel(t){
  if(t==='parent') return 'Parent';
  if(t==='child') return 'Child';
  return t;
}

function initialOf(name){
  if(!name) return '?';
  return name.trim().charAt(0).toUpperCase();
}

onMounted(fetchFamily);
</script>

<style scoped>
.parent-family { padding:1.2rem 1.4rem 2.2rem; }
.page-title { margin:0 0 1rem; font-size:1.55rem; }
.layout { display:grid; gap:1.2rem; grid-template-columns:repeat(auto-fit,minmax(290px,1fr)); align-items:start; }
.card { background:#fff; border:1px solid #d8dde3; border-radius:14px; padding:1rem 1rem 1.2rem; box-shadow:0 1px 2px rgba(0,0,0,.05); display:flex; flex-direction:column; gap:.75rem; position:relative; }
.card.stretch { grid-column:span 2; }
@media (max-width:860px){ .card.stretch { grid-column:span 1; } }
.card-title { margin:0; font-size:1.05rem; font-weight:600; }
.form-grid { display:grid; gap:.65rem; }
.form-grid label { display:flex; flex-direction:column; font-size:.8rem; gap:.25rem; font-weight:500; }
.form-grid input { padding:.5rem .55rem; border:1px solid #c5ccd3; border-radius:7px; font-size:.85rem; background:#fdfdfd; }
.form-grid input:focus { outline:2px solid #4b91ff33; border-color:#4b91ff; }
.actions-row { display:flex; gap:.6rem; margin-top:.4rem; }
button { cursor:pointer; border:none; background:#2563eb; color:#fff; padding:.55rem .9rem; font-size:.8rem; border-radius:7px; font-weight:600; letter-spacing:.3px; display:inline-flex; align-items:center; justify-content:center; }
button.secondary { background:#6b7280; }
button:disabled { opacity:.55; cursor:default; }
.invite-code { font-family:monospace; font-size:1.1rem; background:#f1f5f9; padding:.5rem .75rem; border-radius:8px; letter-spacing:2px; text-align:center; cursor:pointer; user-select:all; }
.invite small { font-size:.7rem; color:#596471; display:block; }
.mt { margin-top:.6rem; }
.member-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.4rem; max-height:360px; overflow:auto; }
.member-item { background:#f8fafc; border:1px solid #d6dde3; border-radius:8px; padding:.5rem .65rem; display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
.member-item .avatar { width:42px; height:42px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:.8rem; color:#334155; overflow:hidden; flex-shrink:0; box-shadow:0 0 0 1px #d6dde3; }
.member-item .avatar img { width:100%; height:100%; object-fit:cover; display:block; }
.member-item .info { display:flex; flex-direction:column; line-height:1.1; }
.member-item .name { font-weight:600; font-size:.85rem; }
.member-item .role { font-size:.65rem; color:#2563eb; font-weight:600; }
.badge { margin-top:.15rem; font-size:.55rem; padding:.2rem .45rem; border-radius:999px; background:#e2e8f0; font-weight:600; text-transform:uppercase; letter-spacing:.5px; display:inline-block; }
.badge.parent { background:#d1fae5; color:#065f46; }
.badge.child { background:#fef3c7; color:#92400e; }
.remove { background:#ef4444; padding:.35rem .55rem; font-size:.7rem; }
.add-member-box { margin-top:.8rem; border-top:1px dashed #d4dbe2; padding-top:.7rem; display:flex; flex-direction:column; gap:.55rem; }
.add-member-box h4 { margin:0; font-size:.83rem; font-weight:600; }
.add-flex { display:flex; gap:.4rem; }
.add-flex input { flex:1; padding:.45rem .55rem; border:1px solid #c5ccd3; border-radius:7px; font-size:.8rem; }
.add-flex input:focus { outline:2px solid #2563eb33; border-color:#2563eb; }
.hint { font-size:.65rem; color:#64748b; }
.loading { padding:1rem; }
.empty { font-size:.75rem; color:#64748b; }
.toast { position:fixed; bottom:1rem; right:1rem; background:#1e293b; color:#fff; padding:.65rem .9rem; font-size:.75rem; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.25); }
.toast.err { background:#b91c1c; }
.toast.ok { background:#166534; }
</style>
