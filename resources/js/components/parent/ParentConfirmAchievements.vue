<template>
  <div class="parent-confirm-achievements">
    <Breadcrumbs />
    <div class="header-bar">
      <h2>Thành tích của trẻ</h2>
      <div class="filters">
        <input v-model="search" type="text" placeholder="Tìm thành tích..." class="filter-input" />
        <select v-model="filterCategory" class="filter-input">
          <option value="">-- Tất cả nhóm --</option>
          <option v-for="c in categories" :key="c" :value="c">{{ c || 'Không nhóm' }}</option>
        </select>
        <select v-model="filterAchieved" class="filter-input">
          <option value="all">Trạng thái: Tất cả</option>
          <option value="achieved">Đã đạt (ít nhất 1 trẻ)</option>
          <option value="none">Chưa ai đạt</option>
        </select>
        <select v-model="filterKid" class="filter-input">
          <option value="">Tất cả trẻ</option>
          <option v-for="k in kids" :key="k.id" :value="k.id">{{ k.name || ('Kid #' + k.id) }}</option>
        </select>
        <button class="btn small" @click="reload" :disabled="loading">{{ loading? '...' : '↻' }}</button>
      </div>
    </div>

    <div v-if="error" class="error-box">{{ error }}</div>
    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>
    <div v-else-if="!filteredAchievements.length" class="empty">Không có thành tích phù hợp.</div>

    <div class="achievements-grid">
      <div v-for="a in filteredAchievements" :key="a.id" class="achievement-card" :class="{hasImage: !!a.image_path}">
        <div class="card-head">
          <div class="title" :title="a.name">{{ a.name }}</div>
          <span class="badge cat" v-if="a.category">{{ a.category }}</span>
          <button class="icon-btn" @click="toggleExpand(a)">{{ expanded.has(a.id)? '−' : '+' }}</button>
        </div>
        <div class="progress-line" v-if="kids.length">
          <div class="bar"><div class="fill" :style="{width: achievedPercent(a)+'%'}"></div></div>
          <div class="pct">{{ achievedCount(a) }}/{{ kids.length }}</div>
        </div>
        <div class="note" v-if="a.note && expanded.has(a.id)">{{ a.note }}</div>
        <div class="image-wrapper" v-if="a.image_path && expanded.has(a.id)">
          <img :src="storageUrl(a.image_path)" :alt="a.name" />
        </div>
        <div class="kids-row" :class="{wrap: expanded.has(a.id)}">
          <div v-for="k in kids" :key="k.id" class="kid-pill" :class="pillClass(a,k)" @click="toggleKid(a,k)" :title="pillTooltip(a,k)">
            <span class="kid-name">{{ shortKidName(k) }}</span>
            <span v-if="isAchieved(a,k)" class="icon">✓</span>
            <span v-else class="icon">•</span>
            <div v-if="togglingId === a.id+'-'+k.id" class="mini-spinner"></div>
          </div>
        </div>
        <div v-if="expanded.has(a.id)" class="kid-notes" v-for="k in kids" :key="a.id+'-nt-'+k.id" v-show="isAchieved(a,k)">
          <div v-if="kidEntry(a,k)?.kid_note" class="kid-note-line"><strong>{{ k.name || ('Kid #' + k.id) }}:</strong> {{ kidEntry(a,k).kid_note }}</div>
        </div>
      </div>
    </div>

    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Breadcrumbs from '../common/Breadcrumbs.vue';

const achievements = ref([]);
const kids = ref([]);
const loading = ref(false);
const error = ref('');
const toast = ref({ message:'', type:'ok' });
const togglingId = ref(null);
const expanded = ref(new Set());

// Filters
const search = ref('');
const filterCategory = ref('');
const filterAchieved = ref('all');
const filterKid = ref('');

const showToast = (m,t='ok',ttl=2200)=>{ toast.value={message:m,type:t}; clearTimeout(showToast._t); showToast._t=setTimeout(()=>toast.value.message='',ttl); };

const storageUrl = (p)=> p && (p.startsWith('http') ? p : '/storage/' + p);

const fetchAll = async ()=>{
  loading.value = true; error.value='';
  try {
    const [achRes,kidsRes] = await Promise.all([
      axios.get('/api/parent/achievements'),
      axios.get('/api/parent/kids')
    ]);
    achievements.value = achRes.data || [];
    const kData = kidsRes.data;
    kids.value = Array.isArray(kData.kids) ? kData.kids : (Array.isArray(kData) ? kData : []);
  } catch(e){
    console.error(e); error.value = e.response?.data?.message || 'Lỗi tải dữ liệu';
  } finally { loading.value=false; }
};

fetchAll();

const categories = computed(()=> {
  const set = new Set(achievements.value.map(a=>a.category).filter(v=>v));
  return Array.from(set).sort();
});

const achievedCount = (a)=> a.kids?.filter(k=>k.achieved).length || 0;
const achievedPercent = (a)=> kids.value.length ? Math.round(achievedCount(a)/kids.value.length*100) : 0;

const kidEntry = (a,k)=> a.kids?.find(x=>x.id===k.id) || null;
const isAchieved = (a,k)=> !!kidEntry(a,k)?.achieved;

const pillClass = (a,k)=> ({ achieved: isAchieved(a,k), pending: !isAchieved(a,k) });
const pillTooltip = (a,k)=> (isAchieved(a,k)?'Bỏ xác nhận':'Xác nhận') + ' cho ' + (k.name || ('Kid #'+k.id));
const shortKidName = (k)=> (k.name || k.username || ('Kid'+k.id)).split(' ')[0].slice(0,10);

const filteredAchievements = computed(()=> achievements.value.filter(a=>{
  if (search.value && !a.name.toLowerCase().includes(search.value.toLowerCase())) return false;
  if (filterCategory.value && a.category !== filterCategory.value) return false;
  if (filterAchieved.value==='achieved' && achievedCount(a)===0) return false;
  if (filterAchieved.value==='none' && achievedCount(a)>0) return false;
  if (filterKid.value) {
    const kid = a.kids?.find(k=>k.id === filterKid.value*1);
    if (!kid) return false; // achievement not attached to kid yet counts as not shown under kid filter
  }
  return true;
}));

const reload = ()=> fetchAll();

const toggleExpand = (a)=>{ expanded.value.has(a.id) ? expanded.value.delete(a.id) : expanded.value.add(a.id); expanded.value = new Set(expanded.value); };

const toggleKid = async (a,k)=>{
  if (togglingId.value) return; // prevent concurrent
  togglingId.value = a.id+'-'+k.id;
  try {
    const { data } = await axios.post(`/api/parent/achievements/${a.id}/toggle-kid/${k.id}`);
    // update achievement locally
    let ach = achievements.value.find(x=>x.id===a.id);
    if (ach) {
      let existing = ach.kids.find(x=>x.id===k.id);
      if (existing) {
        existing.achieved = data.achieved;
        if (!data.achieved) { existing.kid_note = data.kid_note; }
      } else {
        ach.kids.push({ id:k.id, name:k.name, achieved:data.achieved, kid_note:data.kid_note });
      }
    }
    showToast((data.achieved?'Đã xác nhận ':'Hủy xác nhận ') + (k.name || 'Kid'),'ok');
  } catch(e){
    console.error(e); showToast(e.response?.data?.message || 'Lỗi cập nhật','err');
  } finally { togglingId.value=null; }
};
</script>

<style scoped>
.parent-confirm-achievements { padding:1rem 1rem 3rem; }
.header-bar { display:flex; flex-wrap:wrap; gap:.75rem; align-items:center; justify-content:space-between; margin-bottom:1rem; }
.header-bar h2 { margin:0; font-size:1.4rem; }
.filters { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
.filter-input { padding:.45rem .55rem; border:1px solid #d1d5db; border-radius:6px; font-size:.8rem; }
.btn { cursor:pointer; border:none; background:#2563eb; color:#fff; font-weight:600; border-radius:6px; padding:.5rem .85rem; font-size:.75rem; display:inline-flex; align-items:center; }
.btn.small { padding:.45rem .65rem; }
.btn:disabled { opacity:.55; cursor:default; }
.error-box { background:#fee2e2; color:#991b1b; padding:.6rem .8rem; border:1px solid #fecaca; border-radius:6px; font-size:.75rem; margin-bottom:.75rem; }
.loading { font-size:.85rem; color:#555; padding:.5rem 0; }
.empty { font-size:.85rem; padding:1rem 0; color:#666; }
.achievements-grid { display:grid; gap:1rem; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); }
.achievement-card { position:relative; background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:.8rem .85rem 1rem; display:flex; flex-direction:column; gap:.55rem; box-shadow:0 1px 2px rgba(0,0,0,.05); transition:.15s; }
.achievement-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.12); transform:translateY(-2px); }
.card-head { display:flex; align-items:center; gap:.5rem; }
.title { font-size:.95rem; font-weight:600; flex:1; line-height:1.2; }
.badge.cat { background:#e0f2fe; color:#075985; font-size:.6rem; padding:.25rem .45rem; border-radius:10px; font-weight:600; letter-spacing:.4px; }
.icon-btn { border:none; background:#f1f5f9; width:26px; height:26px; border-radius:6px; cursor:pointer; font-weight:700; display:flex; align-items:center; justify-content:center; }
.icon-btn:hover { background:#e2e8f0; }
.note { font-size:.7rem; color:#374151; line-height:1.2; }
.image-wrapper { text-align:center; }
.image-wrapper img { max-width:100%; max-height:140px; border-radius:8px; object-fit:cover; border:1px solid #e5e7eb; }
.progress-line { display:flex; align-items:center; gap:.5rem; }
.bar { flex:1; height:6px; background:#f1f5f9; border-radius:4px; overflow:hidden; }
.fill { height:100%; background:linear-gradient(90deg,#2563eb,#1d4ed8); }
.pct { font-size:.65rem; font-weight:600; color:#1e3a8a; min-width:32px; text-align:right; }
.kids-row { display:flex; gap:.4rem; overflow-x:auto; scrollbar-width:thin; }
.kids-row.wrap { flex-wrap:wrap; overflow:visible; }
.kid-pill { position:relative; flex:0 0 auto; padding:.35rem .55rem; border:1px solid #cbd5e1; background:#f8fafc; font-size:.65rem; border-radius:20px; cursor:pointer; display:flex; gap:.35rem; align-items:center; font-weight:600; min-width:54px; justify-content:center; }
.kid-pill.achieved { background:#dcfce7; border-color:#86efac; color:#166534; }
.kid-pill.pending { background:#f1f5f9; }
.kid-pill:hover { box-shadow:0 0 0 2px #2563eb33; }
.kid-name { max-width:60px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.icon { font-size:.7rem; }
.mini-spinner { position:absolute; inset:0; background:rgba(255,255,255,.65); border-radius:20px; display:flex; align-items:center; justify-content:center; }
.mini-spinner:before { content:''; width:14px; height:14px; border:2px solid #2563eb55; border-top-color:#2563eb; border-radius:50%; animation:spin .7s linear infinite; }
.kid-notes { font-size:.65rem; line-height:1.2; display:flex; flex-direction:column; gap:.2rem; margin-top:.2rem; }
.kid-note-line { background:#f8fafc; padding:.3rem .45rem; border-radius:6px; border:1px solid #e2e8f0; }
.toast { position:fixed; bottom:1rem; left:50%; transform:translateX(-50%); background:#1f2937; color:#fff; padding:.6rem .85rem; border-radius:10px; font-size:.7rem; box-shadow:0 4px 14px rgba(0,0,0,.25); z-index:500; }
.toast.ok { background:#166534; }
.toast.err { background:#b91c1c; }
.toast.warn { background:#92400e; }
@keyframes spin { to { transform:rotate(360deg); } }
@media (max-width:640px){ .achievements-grid { grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); } .filters { width:100%; } }
@media (prefers-color-scheme: dark){
  .parent-confirm-achievements { color:#e5e7eb; }
  .achievement-card { background:#1f2937; border-color:#374151; }
  .icon-btn { background:#334155; color:#e2e8f0; }
  .icon-btn:hover { background:#475569; }
  .note { color:#cbd5e1; }
  .kid-pill.pending { background:#334155; border-color:#475569; color:#e2e8f0; }
  .kid-note-line { background:#334155; border-color:#475569; }
  .bar { background:#334155; }
  .fill { background:linear-gradient(90deg,#3b82f6,#1d4ed8); }
  .badge.cat { background:#082f49; color:#7dd3fc; }
}
</style>
