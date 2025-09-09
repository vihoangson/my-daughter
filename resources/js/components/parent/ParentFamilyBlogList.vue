<template>
  <div class="family-blog-list">
    <Breadcrumbs />
    <div class="top-bar">
      <h2 class="title">Blog gia đình</h2>
      <div class="actions">
        <router-link :to="{name:'ParentFamilyBlogNew'}" class="btn primary">➕ Bài viết mới</router-link>
        <router-link :to="{name:'ParentFamily'}" class="btn ghost">← Quay lại Gia đình</router-link>
      </div>
    </div>

    <div class="filters">
      <input v-model="search" @keyup.enter="fetchPosts" placeholder="Tìm tiêu đề, nội dung hoặc tag..." />
      <select v-model="filter.status" @change="fetchPosts">
        <option value="">-- Trạng thái --</option>
        <option value="draft">Nháp</option>
        <option value="published">Đã xuất bản</option>
        <option value="archived">Đã lưu trữ</option>
      </select>
      <select v-model="filter.visibility" @change="fetchPosts">
        <option value="">-- Quyền xem --</option>
        <option value="public">Công khai</option>
        <option value="members">Thành viên</option>
        <option value="private">Riêng tư</option>
      </select>
      <button class="btn" @click="fetchPosts" :disabled="loading">Lọc</button>
      <button class="btn ghost" @click="resetFilters" :disabled="loading">Đặt lại</button>
    </div>

    <div v-if="loading" class="loading">Đang tải...</div>
    <div v-else>
      <div v-if="!posts.length" class="empty">Chưa có bài viết.</div>
      <div v-else class="post-grid">
        <div v-for="p in posts" :key="p.id" class="post-card" :class="{'pinned':p.pinned}">
          <div class="head">
            <h3 class="post-title" @click="goView(p)">{{ p.title }}</h3>
            <span v-if="p.pinned" class="pin">📌</span>
          </div>
          <div class="meta">
            <span class="status" :class="p.status">{{ statusLabel(p.status) }}</span>
            <span class="vis" :class="p.visibility">{{ visibilityLabel(p.visibility) }}</span>
            <span v-if="p.published_at" class="pub">{{ formatDate(p.published_at) }}</span>
          </div>
          <p class="excerpt">{{ excerpt(p.content) }}</p>
          <div class="tags" v-if="p.tags && p.tags.length">
            <span class="tag" v-for="t in p.tags" :key="t">#{{ t }}</span>
          </div>
          <div class="row-actions">
            <button class="btn xs" @click="togglePin(p)" :disabled="p._busy">{{ p.pinned ? 'Bỏ ghim' : 'Ghim' }}</button>
            <button class="btn xs" @click="togglePublish(p)" :disabled="p._busy">{{ p.status==='published' ? 'Hạ xuống' : 'Xuất bản' }}</button>
            <router-link :to="{name:'ParentFamilyBlogEdit', params:{id:p.id}}" class="btn xs warn">Sửa</router-link>
            <button class="btn xs danger" @click="remove(p)" :disabled="p._busy">Xoá</button>
            <router-link :to="{name:'ParentFamilyBlogView', params:{id:p.id}}" class="btn xs ghost">Xem</router-link>
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
import { useRouter } from 'vue-router';

const router = useRouter();
const posts = ref([]);
const loading = ref(false);
const search = ref('');
const filter = reactive({ status:'', visibility:'' });
const toast = reactive({ message:'', type:'ok' });
let toastTimer=null;

function showToast(m,t='ok',ttl=2400){ toast.message=m; toast.type=t; clearTimeout(toastTimer); toastTimer=setTimeout(()=>toast.message='',ttl); }

async function fetchPosts(){
  loading.value = true;
  try {
    const { data } = await axios.get('/api/parent/family/blog-posts',{ params:{ search:search.value||undefined, status:filter.status||undefined, visibility:filter.visibility||undefined }});
    const arr = data.posts?.data || data.posts || [];
    posts.value = arr.map(p=>({...p,_busy:false}));
  } catch(e){ showToast(e.response?.data?.message||'Lỗi tải','err'); }
  finally{ loading.value=false; }
}
function resetFilters(){ search.value=''; filter.status=''; filter.visibility=''; fetchPosts(); }
function excerpt(html){ if(!html) return ''; const txt = html.replace(/<[^>]*>/g,''); return txt.length>120? txt.slice(0,117)+'...':txt; }
function formatDate(dt){ if(!dt) return ''; return new Date(dt).toLocaleString('vi-VN',{hour12:false}); }
function statusLabel(s){ return {draft:'Nháp', published:'Đã xuất bản', archived:'Lưu trữ'}[s]||s; }
function visibilityLabel(v){ return {public:'Công khai', members:'Thành viên', private:'Riêng tư'}[v]||v; }
function goView(p){ router.push({name:'ParentFamilyBlogView', params:{id:p.id}}); }
async function togglePin(p){ p._busy=true; try{ await axios.put(`/api/parent/family/blog-posts/${p.id}`,{ pinned:!p.pinned }); p.pinned=!p.pinned; showToast(p.pinned?'Đã ghim':'Đã bỏ ghim'); }catch(e){ showToast('Lỗi','err'); } finally{ p._busy=false; } }
async function togglePublish(p){ p._busy=true; try{ const target = p.status==='published'?'draft':'published'; await axios.put(`/api/parent/family/blog-posts/${p.id}`,{ status:target }); p.status=target; if(target==='published'&&!p.published_at) p.published_at=new Date().toISOString(); showToast(target==='published'?'Đã xuất bản':'Đưa về nháp'); }catch(e){ showToast('Lỗi','err'); } finally{ p._busy=false; } }
async function remove(p){ if(!confirm('Xoá bài viết này?')) return; p._busy=true; try{ await axios.delete(`/api/parent/family/blog-posts/${p.id}`); posts.value = posts.value.filter(x=>x.id!==p.id); showToast('Đã xoá'); }catch(e){ showToast('Lỗi xoá','err'); } finally{ p._busy=false; } }

onMounted(fetchPosts);
</script>

<style scoped>
.family-blog-list { padding:1.2rem 1.4rem 2.2rem; }
.top-bar { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
.title { margin:0; font-size:1.55rem; }
.actions { display:flex; gap:.6rem; }
.filters { display:flex; flex-wrap:wrap; gap:.6rem; margin:1rem 0 1.1rem; }
.filters input, .filters select { padding:.5rem .65rem; border:1px solid #c9d1d9; border-radius:8px; font-size:.8rem; background:#fff; }
.filters input:focus, .filters select:focus { outline:2px solid #2563eb33; border-color:#2563eb; }
.post-grid { display:grid; gap:1rem; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); }
.post-card { background:#fff; border:1px solid #d9e0e6; border-radius:14px; padding:.85rem .9rem 1rem; display:flex; flex-direction:column; gap:.5rem; position:relative; box-shadow:0 1px 2px rgba(0,0,0,.05); }
.post-card.pinned { border-color:#f59e0b; }
.head { display:flex; align-items:flex-start; gap:.4rem; }
.post-title { margin:0; font-size:1rem; line-height:1.2; cursor:pointer; flex:1; }
.post-title:hover { color:#2563eb; }
.pin { font-size:1rem; }
.meta { display:flex; flex-wrap:wrap; gap:.4rem; font-size:.6rem; text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
.status { padding:.2rem .45rem; border-radius:999px; background:#e2e8f0; }
.status.published { background:#d1fae5; color:#065f46; }
.status.draft { background:#fef3c7; color:#92400e; }
.status.archived { background:#e5e7eb; color:#374151; }
.vis { padding:.2rem .45rem; border-radius:999px; background:#e0f2fe; color:#05507a; }
.vis.private { background:#f3e8ff; color:#6b21a8; }
.vis.members { background:#e0f2fe; }
.pub { color:#64748b; }
.excerpt { margin:0; font-size:.75rem; color:#475569; line-height:1.3; }
.tags { display:flex; flex-wrap:wrap; gap:.25rem; }
.tag { background:#f1f5f9; padding:.2rem .45rem; font-size:.6rem; border-radius:999px; font-weight:600; }
.row-actions { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.4rem; }
.btn { border:none; background:#2563eb; color:#fff; padding:.5rem .85rem; border-radius:8px; font-size:.75rem; cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:.3rem; text-decoration:none; }
.btn.primary { background:#2563eb; }
.btn.ghost { background:#e2e8f0; color:#1e293b; }
.btn.ghost:hover { background:#cbd5e1; }
.btn.warn { background:#f59e0b; }
.btn.danger { background:#dc2626; }
.btn.xs { padding:.35rem .55rem; font-size:.63rem; }
.btn:disabled { opacity:.55; cursor:default; }
.loading { padding:1rem; }
.empty { padding:1rem; font-size:.8rem; color:#64748b; }
.toast { position:fixed; bottom:1rem; right:1rem; background:#1e293b; color:#fff; padding:.55rem .75rem; font-size:.7rem; border-radius:7px; box-shadow:0 2px 8px rgba(0,0,0,.25); }
.toast.err { background:#b91c1c; }
.toast.ok { background:#166534; }
</style>

