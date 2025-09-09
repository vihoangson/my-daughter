<template>
  <div class="parent-add-points">
    <Breadcrumbs />
    <h2 class="title">Hành vi & Điểm thưởng / phạt</h2>
    <div class="controls">
      <label>Chọn trẻ:
        <select v-model="selectedKid" class="kid-select" :disabled="loadingKids">
          <option disabled value="">-- chọn --</option>
          <option v-for="kid in kids" :key="kid.id" :value="kid.id">{{ kid.name || kid.username || ('Kid #' + kid.id) }}</option>
        </select>
      </label>
      <button class="refresh" @click="fetchKids" :disabled="loadingKids">{{ loadingKids ? '...' : '↻' }}</button>
      <button v-if="selectedKidObj" class="view-history" @click="showHistoryModal = true">Lịch sử điểm</button>
    </div>
    <div v-if="selectedKidObj" class="kid-summary">
      <span><strong>Tổng điểm:</strong> <span :class="{'neg': (selectedKidObj.total_points||0) < 0}">{{ selectedKidObj.total_points ?? 0 }}</span></span>
      <button class="mini-refresh" @click="fetchKids" :disabled="loadingKids || submittingId">↺</button>
    </div>
    <p v-if="loadingKids" class="hint">Đang tải danh sách trẻ...</p>
    <p v-else-if="!kids.length" class="hint">Chưa có trẻ nào hoặc không lấy được dữ liệu.</p>
    <p v-else-if="!selectedKid" class="hint">Hãy chọn một trẻ trước khi áp dụng hành vi.</p>

    <div class="section" v-for="section in sections" :key="section.key">
      <h3 :class="['section-title', section.type]">{{ section.title }}</h3>
      <div class="behavior-grid">
        <div v-for="b in section.behaviors" :key="b.id" class="behavior-card" :class="[b.type,{disabled:!selectedKid,working:submittingId===b.id}]">
          <div class="icon">{{ b.icon }}</div>
          <div class="label" :title="b.custom ? (b._customLabel || b.label) : b.label">
            <template v-if="b.custom">
              <input type="text" v-model="b._customLabel" placeholder="Mô tả..." class="custom-label-input" :disabled="submittingId===b.id" @click.stop />
            </template>
            <template v-else>{{ b.label }}</template>
          </div>
          <div class="points-line">
            <span class="pts" :class="b.type">{{ signSymbol(b.type) }}{{ b.points }}</span>
            <input type="number" min="1" max="100" v-model.number="b.points" class="pts-input" :disabled="submittingId===b.id" @click.stop />
          </div>
          <div class="comment-line">
            <input type="text" v-model="b._comment" class="comment-input" placeholder="Ghi chú (tuỳ chọn)" :disabled="submittingId===b.id" @click.stop />
          </div>
          <div class="actions">
            <button class="apply-btn" :disabled="!selectedKid||submittingId===b.id" @click="applyBehavior(b)">
              <span v-if="submittingId===b.id" class="spinner" />
              <span v-else>{{ b.type==='reward' ? 'Cộng' : 'Trừ' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>

    <div v-if="showHistoryModal" class="modal-bg" @click.self="showHistoryModal=false">
      <div class="modal-content">
        <h3>Lịch sử cộng/trừ điểm</h3>
        <button class="close-btn" @click="showHistoryModal=false">×</button>
        <div v-if="loadingHistory" class="loading">Đang tải...</div>
        <div v-else-if="!history.length" class="empty">Chưa có dữ liệu.</div>
        <ul v-else class="history-list">
          <li v-for="item in history" :key="item.id" :class="item.type">
            <span class="date">{{ formatDate(item.created_at) }}</span>
            <span class="desc">{{ item.description }}</span>
            <span class="pts" :class="item.type">{{ signSymbol(item.type) }}{{ item.points }}</span>
            <span v-if="item.comment" class="comment">({{ item.comment }})</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Breadcrumbs from '../common/Breadcrumbs.vue';

const kids = ref([]);
const loadingKids = ref(false);
const selectedKid = ref('');
const submittingId = ref(null);
const toast = ref({ message: '', type: '' });
let toastTimer = null;

const showToast = (msg,type='ok',ttl=2800)=>{toast.value={message:msg,type};clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.value.message='',ttl);};

// Return full flat list of behaviors (reward + punishment)
const baseBehaviors = () => ([
  // Self-directed reward behaviors
  { id:'self-start-hw', label:'Tự bắt đầu làm bài mà không cần nhắc', points:6, type:'reward', icon:'🚀' },
  { id:'prep-school', label:'Tự chuẩn bị đồ đi học tối hôm trước', points:5, type:'reward', icon:'🎒' },
  { id:'time-screen-self', label:'Tự tắt thiết bị đúng giờ', points:4, type:'reward', icon:'🕒' },
  { id:'ask-help', label:'Chủ động hỏi khi gặp bài khó', points:5, type:'reward', icon:'❓' },
  { id:'daily-goal', label:'Tự đặt & hoàn thành mục tiêu ngày', points:7, type:'reward', icon:'🎯' },
  { id:'finish-chores-early', label:'Hoàn thành việc nhà sớm trước khi nhắc', points:5, type:'reward', icon:'⚡' },
  { id:'double-check', label:'Tự kiểm tra lại bài trước khi nộp', points:4, type:'reward', icon:'🔍' },
  { id:'note-taking', label:'Tự ghi chú học tập gọn gàng', points:5, type:'reward', icon:'📝' },
  { id:'extra-reading', label:'Đọc thêm ngoài chương trình', points:6, type:'reward', icon:'📖' },
  { id:'healthy-snack', label:'Tự chọn đồ ăn nhẹ lành mạnh', points:3, type:'reward', icon:'🥗' },
  { id:'organize-study-plan', label:'Tự lên kế hoạch học tập tuần', points:8, type:'reward', icon:'🗓️' },
  { id:'self-clean-desk', label:'Tự dọn bàn học sau khi dùng', points:4, type:'reward', icon:'🧽' },
  { id:'mindfulness-break', label:'Tự nghỉ giải lao lành mạnh (không thiết bị)', points:3, type:'reward', icon:'🧘' },
  { id:'help-without-ask', label:'Chủ động giúp việc mà không ai nhắc', points:6, type:'reward', icon:'🤝' },
  { id:'manage-calendar', label:'Tự ghi & nhớ lịch hoạt động', points:5, type:'reward', icon:'📅' },
  // Custom OTHER reward
  { id:'other-reward', label:'Khác (cộng)', points:1, type:'reward', icon:'➕', custom:true },
  // Punishment behaviors
  { id:'miss-hw', label:'Không làm bài tập', points:5, type:'punishment', icon:'⌛' },
  { id:'argue', label:'Cãi lời', points:4, type:'punishment', icon:'⚠️' },
  { id:'fight', label:'Gây gổ / Đánh nhau', points:8, type:'punishment', icon:'🚫' },
  { id:'screen', label:'Dùng thiết bị quá giờ', points:3, type:'punishment', icon:'📱' },
  { id:'mess', label:'Không dọn phòng', points:4, type:'punishment', icon:'🧺' },
  { id:'lie', label:'Nói dối', points:7, type:'punishment', icon:'❗' },
  { id:'late', label:'Đi trễ / không đúng giờ', points:3, type:'punishment', icon:'🕒' },
  { id:'skip-chores', label:'Bỏ việc nhà được giao', points:4, type:'punishment', icon:'🙈' },
  { id:'rude', label:'Thái độ thô lỗ', points:5, type:'punishment', icon:'😠' },
  { id:'unsafe', label:'Hành vi không an toàn', points:6, type:'punishment', icon:'🚧' },
  { id:'waste', label:'Lãng phí (điện, nước, đồ ăn)', points:3, type:'punishment', icon:'💡' },
  { id:'disobey', label:'Không tuân thủ quy tắc đã thống nhất', points:5, type:'punishment', icon:'📜' },
  { id:'procrastinate', label:'Trì hoãn quá nhiều', points:4, type:'punishment', icon:'🐢' },
  { id:'tantrum', label:'Nổi nóng / mất kiểm soát', points:6, type:'punishment', icon:'💢' },
  { id:'misuse-device', label:'Dùng thiết bị sai mục đích', points:4, type:'punishment', icon:'🖥️' },
  { id:'unhealthy-eat', label:'Ăn vặt không lành mạnh quá mức', points:3, type:'punishment', icon:'🍬' },
  // Custom OTHER punishment
  { id:'other-punishment', label:'Khác (trừ)', points:1, type:'punishment', icon:'➖', custom:true }
]);

const signSymbol = (type)=> type==='reward'?'+':'-';

const fetchKids = async () => {
  loadingKids.value = true;
  try {
    const { data } = await axios.get('/api/parent/kids');
    let list = [];
    if (Array.isArray(data)) list = data; else if (Array.isArray(data.kids)) list = data.kids; else if (data.kids && Array.isArray(data.kids.data)) list = data.kids.data;
    kids.value = list;
    if (!selectedKid.value && kids.value.length === 1) selectedKid.value = kids.value[0].id;
    else if (selectedKid.value && !kids.value.find(k=>k.id===selectedKid.value)) selectedKid.value='';
  } catch(e){
    console.error(e);showToast(e.response?.status===403?'Không có quyền / hết phiên':'Không tải được danh sách trẻ','err');kids.value=[];selectedKid.value='';
  } finally { loadingKids.value=false; }
};
fetchKids();

// Sections built from base list
const sections = ref([
  { key:'encouraged', title:'Hành vi tự giác / chủ động', type:'reward', behaviors: baseBehaviors().filter(b=>b.type==='reward') },
  { key:'discouraged', title:'Hành vi cần hạn chế', type:'punishment', behaviors: baseBehaviors().filter(b=>b.type==='punishment') }
]);

const selectedKidObj = computed(()=> kids.value.find(k=>k.id === selectedKid.value) || null);

const applyBehavior = async (b) => {
  if(!selectedKid.value){ showToast('Chọn trẻ trước','warn'); return; }
  if(!b.points || b.points<1){ showToast('Điểm phải >=1','warn'); return; }
  if (b.custom && (!b._customLabel || !b._customLabel.trim())) { showToast('Nhập mô tả cho mục Khác','warn'); return; }
  submittingId.value = b.id;
  try {
    const fd = new FormData();
    fd.append('type', b.type==='reward'?'reward':'punishment');
    fd.append('points', b.points);
    const desc = b.custom ? (b._customLabel.trim()) : b.label;
    fd.append('description', desc);
    if (b._comment) fd.append('comment', b._comment);
    await axios.post(`/api/parent/kids/${selectedKid.value}/points`, fd);
    if (selectedKidObj.value) {
      const delta = b.type==='reward' ? b.points : -b.points;
      selectedKidObj.value.total_points = (selectedKidObj.value.total_points || 0) + delta;
    }
    showToast((b.type==='reward'?'Đã cộng ':'Đã trừ ')+b.points+' điểm','ok');
    if (b.custom) { b._customLabel=''; }
    b._comment = '';
  } catch(e){
    console.error(e);showToast(e.response?.data?.message || 'Lỗi áp dụng điểm','err');
  } finally { submittingId.value=null; }
};

const showHistoryModal = ref(false);
const history = ref([]);
const loadingHistory = ref(false);

watch(showHistoryModal, async (val) => {
  if (val && selectedKid.value) {
    loadingHistory.value = true;
    try {
      const { data } = await axios.get(`/api/parent/kids/${selectedKid.value}/points/history`);
      history.value = Array.isArray(data) ? data : (data.history || []);
    } catch(e) {
      history.value = [];
    } finally {
      loadingHistory.value = false;
    }
  }
});

function formatDate(dt) {
  if (!dt) return '';
  const d = new Date(dt);
  return d.toLocaleString('vi-VN', { hour12: false });
}
</script>

<style scoped>
.parent-add-points { padding:1rem; }
.title { margin:0 0 1rem; font-size:1.4rem; }
.controls { display:flex; gap:.5rem; align-items:center; margin-bottom:.5rem; }
.kid-select { padding:.35rem .5rem; }
.refresh { padding:.35rem .6rem; cursor:pointer; }
.hint { font-size:.85rem; color:#666; margin:.25rem 0 1rem; }
.section { margin-top:1.25rem; }
.section-title { font-size:1.05rem; margin:0 0 .5rem; font-weight:600; }
.section-title.reward { color:#147d35; }
.section-title.punishment { color:#b32d2d; }
.behavior-grid { display:grid; gap:.75rem; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); }
.behavior-card { background:#fff; border:1px solid #ddd; border-radius:10px; padding:.75rem .6rem .85rem; display:flex; flex-direction:column; gap:.4rem; box-shadow:0 1px 2px rgba(0,0,0,.06); transition:.15s; }
.behavior-card.reward { border-color:#cfe8d7; }
.behavior-card.punishment { border-color:#f3c9c9; }
.behavior-card.reward:hover { box-shadow:0 2px 6px rgba(20,125,53,.25); }
.behavior-card.punishment:hover { box-shadow:0 2px 6px rgba(179,45,45,.25); }
.behavior-card.disabled { opacity:.55; pointer-events:none; }
.behavior-card.working { outline:2px solid #4096ff; }
.icon { font-size:1.9rem; }
.label { font-size:.9rem; font-weight:600; min-height:2.4em; }
.points-line { display:flex; align-items:center; gap:.4rem; }
.pts { font-weight:700; font-size:.95rem; }
.pts.reward { color:#147d35; }
.pts.punishment { color:#b32d2d; }
.pts-input { width:60px; padding:.25rem .35rem; font-size:.8rem; }
.comment-line { margin-top: .2rem; }
.comment-input { width: 100%; padding: .25rem .35rem; font-size: .8rem; border: 1px solid #ccc; border-radius: 5px; }
.actions { margin-top:auto; display:flex; }
.apply-btn { flex:1; padding:.45rem .5rem; font-size:.8rem; border:none; border-radius:6px; cursor:pointer; font-weight:600; color:#fff; background:linear-gradient(90deg,#2563eb,#1d4ed8); display:flex; align-items:center; justify-content:center; }
.behavior-card.punishment .apply-btn { background:linear-gradient(90deg,#dc2626,#b91c1c); }
.apply-btn:disabled { opacity:.6; cursor:default; }
.spinner { width:14px; height:14px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.toast { position:fixed; bottom:1rem; right:1rem; background:#222; color:#fff; padding:.65rem .9rem; border-radius:8px; font-size:.8rem; box-shadow:0 2px 8px rgba(0,0,0,.25); }
.toast.ok { background:#166534; }
.toast.err { background:#b91c1c; }
.toast.warn { background:#92400e; }
.kid-summary { display:flex; align-items:center; gap:.75rem; font-size:.9rem; background:#f5f7fa; padding:.5rem .75rem; border:1px solid #d9e1e8; border-radius:8px; margin-bottom:.6rem; }
.kid-summary .neg { color:#b91c1c; }
.mini-refresh { padding:.25rem .45rem; font-size:.75rem; border:1px solid #ccc; background:#fff; border-radius:5px; cursor:pointer; }
.mini-refresh:disabled { opacity:.5; cursor:default; }
.view-history { padding:.35rem .7rem; background:#f3f4f6; border:1px solid #bcd; border-radius:6px; font-size:.9rem; cursor:pointer; margin-left:.5rem; }
.view-history:hover { background:#e0e7ef; }
.modal-bg { position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,.18); z-index:1000; display:flex; align-items:center; justify-content:center; }
.modal-content { background:#fff; border-radius:10px; padding:1.2rem 1.5rem 1.2rem 1.2rem; min-width:340px; max-width:95vw; max-height:80vh; overflow:auto; position:relative; box-shadow:0 4px 24px rgba(0,0,0,.13); }
.close-btn { position:absolute; top:.7rem; right:.9rem; font-size:1.3rem; background:none; border:none; cursor:pointer; color:#888; }
.close-btn:hover { color:#b91c1c; }
.history-list { list-style:none; padding:0; margin:0; }
.history-list li { display:flex; align-items:center; gap:.7rem; padding:.4rem 0; border-bottom:1px solid #f0f0f0; font-size:.97rem; }
.history-list li.reward .pts { color:#147d35; }
.history-list li.punishment .pts { color:#b32d2d; }
.history-list .date { color:#888; font-size:.85em; min-width:110px; }
.history-list .desc { flex:1; }
.history-list .comment { color:#555; font-size:.85em; margin-left:.3em; }
.loading, .empty { padding:1.2em 0; text-align:center; color:#888; }
@media (max-width: 600px) { .modal-content { min-width:0; padding:1rem; } }
@media (prefers-color-scheme: dark){ .behavior-card { background:#1f2937; border-color:#374151; } .behavior-card.reward { border-color:#264a34; } .behavior-card.punishment { border-color:#4b2a2a; } .parent-add-points { color:#e5e7eb; } .toast { background:#374151; } }
.custom-label-input { width:100%; padding:.25rem .35rem; font-size:.75rem; border:1px solid #ccc; border-radius:5px; }
</style>
