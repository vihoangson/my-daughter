<template>
  <div class="family-blog-editor">
    <Breadcrumbs />
    <div class="top-bar">
      <h2 class="title">{{ isEdit ? 'Sửa bài viết' : 'Bài viết mới' }}</h2>
      <div class="actions">
        <router-link :to="{name:'ParentFamilyBlogList'}" class="btn ghost">← Danh sách</router-link>
        <button class="btn" @click="save" :disabled="saving">{{ saving? 'Đang lưu...' : 'Lưu' }}</button>
      </div>
    </div>

    <form class="form" @submit.prevent="save">
      <label class="row">
        <span>Tiêu đề</span>
        <input v-model="form.title" required maxlength="180" />
      </label>
      <div class="grid-3">
        <label class="row">
          <span>Trạng thái</span>
          <select v-model="form.status">
            <option value="draft">Nháp</option>
            <option value="published">Xuất bản</option>
            <option value="archived">Lưu trữ</option>
          </select>
        </label>
        <label class="row">
          <span>Quyền xem</span>
          <select v-model="form.visibility">
            <option value="members">Thành viên</option>
            <option value="public">Công khai</option>
            <option value="private">Riêng tư</option>
          </select>
        </label>
        <label class="row chk">
          <span>Ghim</span>
          <input type="checkbox" v-model="form.pinned" />
        </label>
      </div>
      <label class="row">
        <span>Tags (phân tách bằng dấu phẩy)</span>
        <input v-model="tagInput" placeholder="ví dụ: sinh hoạt, cuối tuần" />
      </label>
      <label class="row">
        <span>Nội dung</span>
        <textarea v-model="form.content" rows="14" placeholder="Viết nội dung..." />
      </label>
      <div class="bottom-actions">
        <button type="submit" class="btn" :disabled="saving">{{ saving? 'Đang lưu...' : 'Lưu' }}</button>
        <button type="button" class="btn ghost" @click="toList">Huỷ</button>
      </div>
    </form>

    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Breadcrumbs from '../common/Breadcrumbs.vue';

const route = useRoute();
const router = useRouter();
const id = route.params.id;
const isEdit = computed(()=> !!id);
const saving = ref(false);
const loading = ref(false);
const toast = reactive({ message:'', type:'ok' });
let toastTimer=null;

const form = reactive({
  title:'',
  content:'',
  visibility:'members',
  status:'draft',
  pinned:false,
  tags:[]
});
const tagInput = ref('');

function showToast(m,t='ok',ttl=2400){toast.message=m;toast.type=t;clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.message='',ttl);}

async function load(){
  if(!isEdit.value) return;
  loading.value=true;
  try {
    const { data } = await axios.get(`/api/parent/family/blog-posts/${id}`);
    Object.assign(form, {
      title: data.post.title,
      content: data.post.content || '',
      visibility: data.post.visibility,
      status: data.post.status,
      pinned: !!data.post.pinned,
      tags: data.post.tags || []
    });
    tagInput.value = form.tags.join(', ');
  }catch(e){ showToast('Không tải được bài','err'); }
  finally{ loading.value=false; }
}

function prepare(){
  form.tags = tagInput.value.split(',').map(t=>t.trim()).filter(Boolean).slice(0,25);
}

async function save(){
  if(!form.title.trim()) { showToast('Tiêu đề bắt buộc','err'); return; }
  prepare();
  saving.value=true;
  try {
    const payload = { ...form, tags: form.tags };
    let res;
    if(isEdit.value){
      res = await axios.put(`/api/parent/family/blog-posts/${id}`, payload);
      showToast('Đã cập nhật');
    } else {
      res = await axios.post('/api/parent/family/blog-posts', payload);
      showToast('Đã tạo');
      router.replace({ name:'ParentFamilyBlogEdit', params:{ id: res.data.post.id }});
    }
  }catch(e){ showToast(e.response?.data?.message || 'Lỗi lưu','err'); }
  finally{ saving.value=false; }
}
function toList(){ router.push({name:'ParentFamilyBlogList'}); }

onMounted(load);
</script>

<style scoped>
.family-blog-editor { padding:1.2rem 1.4rem 2.2rem; }
.top-bar { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem; }
.title { margin:0; font-size:1.55rem; }
.actions { display:flex; gap:.6rem; }
.form { display:flex; flex-direction:column; gap:.9rem; max-width:960px; }
.row { display:flex; flex-direction:column; gap:.35rem; font-size:.8rem; font-weight:500; }
.row > span { color:#374151; }
input, select, textarea { padding:.55rem .65rem; border:1px solid #c9d1d9; border-radius:9px; font-size:.85rem; background:#fff; font-family:inherit; resize:vertical; }
textarea { line-height:1.4; }
input:focus, select:focus, textarea:focus { outline:2px solid #2563eb33; border-color:#2563eb; }
.grid-3 { display:grid; gap:.75rem; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); }
.chk { flex-direction:row; align-items:center; }
.chk span { flex:1; }
.bottom-actions { display:flex; gap:.6rem; }
.btn { border:none; background:#2563eb; color:#fff; padding:.55rem .9rem; border-radius:8px; font-size:.8rem; cursor:pointer; font-weight:600; text-decoration:none; }
.btn.ghost { background:#e2e8f0; color:#1e293b; }
.btn.ghost:hover { background:#cbd5e1; }
.btn:disabled { opacity:.55; cursor:default; }
.toast { position:fixed; bottom:1rem; right:1rem; background:#1e293b; color:#fff; padding:.55rem .75rem; font-size:.7rem; border-radius:7px; box-shadow:0 2px 8px rgba(0,0,0,.25); }
.toast.err { background:#b91c1c; }
.toast.ok { background:#166534; }
</style>

