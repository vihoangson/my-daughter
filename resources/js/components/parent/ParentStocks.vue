<template>
  <div class="parent-stocks">
    <Breadcrumbs />
    <h2>Quản lý chứng khoán của trẻ</h2>
    <div class="top-bar">
      <div class="block">
        <label>Chọn trẻ</label>
        <select v-model="selectedKid" @change="loadKidData()">
          <option value="">-- Chọn --</option>
          <option v-for="k in kids" :key="k.id" :value="k.id">{{ k.name || ('Kid #'+k.id) }}</option>
        </select>
      </div>
      <div class="block">
        <label>Thị trường</label>
        <button class="btn" @click="loadMarket" :disabled="loadingMarket">{{ loadingMarket ? 'Đang tải...' : '↻ Refresh giá' }}</button>
      </div>
      <div class="block grow">
        <label>Tìm mã</label>
        <input v-model="stockSearch" type="text" placeholder="Nhập code / tên" />
      </div>
    </div>

    <div class="panels">
      <div class="panel market">
        <div class="panel-head">
          <h3>Danh sách cổ phiếu</h3>
        </div>
        <div class="stocks-table-wrapper">
          <table class="stocks-table">
            <thead>
              <tr>
                <th>Mã</th>
                <th>Tên</th>
                <th>Giá</th>
                <th>Δ %</th>
                <th>Biểu đồ</th>
                <th>Điều chỉnh</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredStocks.length===0">
                <td colspan="6" class="empty">Không có cổ phiếu</td>
              </tr>
              <tr v-for="s in filteredStocks" :key="s.id" :class="{active: selectedStock && selectedStock.id===s.id}">
                <td @click="selectStock(s)">{{ s.code }}</td>
                <td class="name" @click="selectStock(s)">{{ s.name }}</td>
                <td @click="selectStock(s)">{{ s.current_price }}</td>
                <td :class="{pos: s.change_percent>0, neg: s.change_percent<0}" @click="selectStock(s)">{{ s.change_percent==null? '—' : (s.change_percent>0? '+'+s.change_percent : s.change_percent) }}%</td>
                <td>
                  <button class="mini-btn" @click.stop="viewPrices(s)">Xem</button>
                </td>
                <td class="adjust-cell">
                  <div v-if="editingId===s.id" class="edit-inline">
                    <input type="number" v-model.number="editPrice" min="1" :disabled="adjusting" />
                    <button class="mini-btn save" @click.stop="applyAdjust(s)" :disabled="adjusting || !validEdit">Lưu</button>
                    <button class="mini-btn cancel" @click.stop="cancelEdit" :disabled="adjusting">Hủy</button>
                  </div>
                  <div v-else>
                    <button class="mini-btn edit" @click.stop="startEdit(s)">Sửa</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="panel details" v-if="priceHistory">
        <div class="panel-head"><h3>Giá: {{ priceHistory.stock.code }} <span class="small">{{ priceHistory.stock.name }}</span></h3></div>
        <div class="price-chart">
          <div v-if="!priceHistory.history.length" class="empty">Chưa có dữ liệu</div>
          <svg v-else :viewBox="'0 0 '+chartWidth+' '+chartHeight" preserveAspectRatio="none">
            <polyline :points="chartPoints" fill="none" stroke="#2563eb" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
          </svg>
          <div class="chart-meta" v-if="priceHistory.history.length">
            <span>Min: {{ minPrice }}</span>
            <span>Max: {{ maxPrice }}</span>
            <span>Latest: {{ latestPrice }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="kid-section" v-if="selectedKid">
      <h3>Danh mục của {{ kidSummary?.kid_name || ('Kid #'+selectedKid) }}</h3>
      <div class="kid-summary-cards" v-if="kidSummary">
        <div class="sum-card">
          <span class="label">Acoin</span>
            <span class="value">{{ kidSummary.acoin_balance }}</span>
        </div>
        <div class="sum-card">
          <span class="label">Đầu tư</span>
          <span class="value">{{ kidSummary.invested }}</span>
        </div>
        <div class="sum-card">
          <span class="label">Giá trị hiện tại</span>
          <span class="value">{{ kidSummary.current_value }}</span>
        </div>
        <div class="sum-card" :class="{pos: kidSummary.unrealized_profit>0, neg: kidSummary.unrealized_profit<0}">
          <span class="label">Lãi/lỗ chưa thực</span>
          <span class="value">{{ kidSummary.unrealized_profit }}</span>
        </div>
        <div class="sum-card" :class="{pos: kidSummary.realized_profit>0, neg: kidSummary.realized_profit<0}">
          <span class="label">Lãi/lỗ đã thực</span>
          <span class="value">{{ kidSummary.realized_profit }}</span>
        </div>
        <div class="sum-card">
          <span class="label">Tổng vốn (equity)</span>
          <span class="value">{{ kidSummary.equity }}</span>
        </div>
      </div>

      <div class="kid-tabs">
        <button :class="['tab', {active: kidTab==='holdings'}]" @click="kidTab='holdings'; loadHoldings()">Holdings</button>
        <button :class="['tab', {active: kidTab==='trades'}]" @click="kidTab='trades'; loadTrades()">Trades</button>
      </div>

      <div v-if="kidTab==='holdings'" class="kid-panel">
        <table class="holdings-table">
          <thead>
            <tr>
              <th>Mã</th><th>Tên</th><th>SL</th><th>Giá TB</th><th>Giá Hiện</th><th>Lãi/Lỗ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="holdingsLoading"><td colspan="6">Đang tải...</td></tr>
            <tr v-else-if="!kidHoldings.length"><td colspan="6" class="empty">Chưa có cổ phiếu</td></tr>
            <tr v-for="h in kidHoldings" :key="h.id">
              <td>{{ h.stock.code }}</td>
              <td class="name">{{ h.stock.name }}</td>
              <td>{{ h.quantity }}</td>
              <td>{{ h.avg_price }}</td>
              <td>{{ h.stock.current_price }}</td>
              <td :class="{pos: (h.stock.current_price - h.avg_price)>0, neg:(h.stock.current_price - h.avg_price)<0}">{{ (h.stock.current_price - h.avg_price)*h.quantity }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="kidTab==='trades'" class="kid-panel">
        <table class="trades-table">
          <thead>
            <tr>
              <th>ID</th><th>Thời gian</th><th>Loại</th><th>Mã</th><th>SL</th><th>Giá</th><th>Tổng</th><th>Lãi/Lỗ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="tradesLoading"><td colspan="8">Đang tải...</td></tr>
            <tr v-else-if="!kidTrades.length"><td colspan="8" class="empty">Chưa có giao dịch</td></tr>
            <tr v-for="t in kidTrades" :key="t.id">
              <td>{{ t.id }}</td>
              <td>{{ formatDate(t.created_at) }}</td>
              <td :class="t.type">{{ t.type }}</td>
              <td>{{ t.stock.code }}</td>
              <td>{{ t.quantity }}</td>
              <td>{{ t.price }}</td>
              <td>{{ t.total }}</td>
              <td :class="{pos: t.profit>0, neg: t.profit<0}">{{ t.profit }}</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Breadcrumbs from '../common/Breadcrumbs.vue';

const kids = ref([]);
const selectedKid = ref('');
const stocks = ref([]);
const loadingMarket = ref(false);
const stockSearch = ref('');
const selectedStock = ref(null);
const priceHistory = ref(null);
const chartWidth = 300;
const chartHeight = 100;
const kidSummary = ref(null);
const kidTab = ref('holdings');
const kidHoldings = ref([]);
const kidTrades = ref([]);
const holdingsLoading = ref(false);
const tradesLoading = ref(false);
const toast = ref({message:'', type:'ok'});
let toastTimer = null;

const showToast = (m,t='ok',ttl=2200)=>{ toast.value={message:m,type:t}; clearTimeout(toastTimer); toastTimer=setTimeout(()=>toast.value.message='', ttl); };

async function loadKids(){
  try { const { data } = await axios.get('/api/parent/kids'); kids.value = data.kids || data || []; } catch(e){ console.error(e); }
}
loadKids();

async function loadMarket(){
  loadingMarket.value = true;
  try { const { data } = await axios.get('/api/parent/stocks'); stocks.value = data.stocks || []; } catch(e){ console.error(e); showToast('Lỗi tải thị trường','err'); }
  finally { loadingMarket.value=false; }
}
loadMarket();

const filteredStocks = computed(()=>{
  const q = stockSearch.value.trim().toLowerCase();
  if(!q) return stocks.value;
  return stocks.value.filter(s=> s.code.toLowerCase().includes(q) || s.name.toLowerCase().includes(q));
});

function selectStock(s){ selectedStock.value = s; }

async function viewPrices(s){
  try { const { data } = await axios.get(`/api/parent/stocks/${s.id}/prices`); priceHistory.value = data; selectedStock.value = s; }
  catch(e){ console.error(e); showToast('Lỗi tải giá','err'); }
}

const prices = computed(()=> priceHistory.value? priceHistory.value.history.map(h=>h.price): []);
const minPrice = computed(()=> prices.value.length? Math.min(...prices.value): 0);
const maxPrice = computed(()=> prices.value.length? Math.max(...prices.value): 0);
const latestPrice = computed(()=> prices.value.length? prices.value[prices.value.length-1]: 0);
const chartPoints = computed(()=> {
  if(!prices.value.length) return '';
  const minP = minPrice.value, maxP = maxPrice.value || 1;
  const span = Math.max(1, maxP - minP);
  return prices.value.map((p,i)=> {
    const x = (i/(prices.value.length-1))*chartWidth;
    const y = chartHeight - ((p - minP)/span)*chartHeight;
    return x+','+y;
  }).join(' ');
});

async function loadKidSummary(){
  if(!selectedKid.value) { kidSummary.value=null; return; }
  try { const { data } = await axios.get(`/api/parent/kids/${selectedKid.value}/stocks/summary`); kidSummary.value = data; }
  catch(e){ console.error(e); showToast('Lỗi tải tóm tắt','err'); }
}

async function loadHoldings(){
  if(!selectedKid.value) return; holdingsLoading.value=true;
  try { const { data } = await axios.get(`/api/parent/kids/${selectedKid.value}/stocks/holdings`); kidHoldings.value = data.holdings || []; }
  catch(e){ console.error(e); showToast('Lỗi holdings','err'); }
  finally { holdingsLoading.value=false; }
}

async function loadTrades(){
  if(!selectedKid.value) return; tradesLoading.value=true;
  try { const { data } = await axios.get(`/api/parent/kids/${selectedKid.value}/stocks/trades`); kidTrades.value = data.trades || []; }
  catch(e){ console.error(e); showToast('Lỗi trades','err'); }
  finally { tradesLoading.value=false; }
}

async function loadKidData(){
  await loadKidSummary();
  if(kidTab.value==='holdings') await loadHoldings(); else await loadTrades();
}

function formatDate(d){ return new Date(d).toLocaleString(); }

const editingId = ref(null);
const editPrice = ref(null);
const adjusting = ref(false);
const validEdit = computed(()=> editPrice.value && Number.isInteger(editPrice.value) && editPrice.value>0);

function startEdit(stock){
  editingId.value = stock.id;
  editPrice.value = stock.current_price;
}
function cancelEdit(){ editingId.value=null; editPrice.value=null; }
async function applyAdjust(stock){
  if(!validEdit.value) { showToast('Giá không hợp lệ','warn'); return; }
  adjusting.value=true;
  try {
    const { data } = await axios.post(`/api/parent/stocks/${stock.id}/adjust`, { price: editPrice.value });
    const updated = data.stock;
    // update local list
    const idx = stocks.value.findIndex(x=>x.id===stock.id);
    if(idx>=0) stocks.value[idx] = { ...stocks.value[idx], current_price: updated.current_price, change_percent: updated.change_percent };
    if(selectedStock.value && selectedStock.value.id===stock.id){
      selectedStock.value.current_price = updated.current_price;
    }
    showToast('Đã cập nhật giá','ok');
    cancelEdit();
  } catch(e){
    console.error(e);
    showToast(e.response?.data?.message || 'Lỗi cập nhật','err');
  } finally {
    adjusting.value=false;
  }
}
</script>

<style scoped>
.parent-stocks { padding:1rem 1rem 2.5rem; }
.parent-stocks h2 { margin:0 0 1rem; font-size:1.5rem; }
.top-bar { display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end; margin-bottom:1rem; }
.top-bar .block { display:flex; flex-direction:column; gap:.35rem; }
.top-bar label { font-size:.65rem; text-transform:uppercase; letter-spacing:.5px; font-weight:600; color:#374151; }
.top-bar select, .top-bar input { padding:.5rem .6rem; border:1px solid #d1d5db; border-radius:6px; font-size:.8rem; background:#fff; color:#111; }
.top-bar .grow { flex:1; }
.btn { padding:.55rem .85rem; background:#2563eb; border:none; color:#fff; border-radius:6px; font-size:.7rem; font-weight:600; cursor:pointer; }
.btn:disabled { opacity:.65; cursor:default; }
.panels { display:grid; gap:1rem; grid-template-columns: minmax(340px, 420px) 1fr; align-items:start; }
.panel { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:.8rem .9rem 1rem; display:flex; flex-direction:column; gap:.65rem; box-shadow:0 1px 2px rgba(0,0,0,.05); }
.panel-head h3 { margin:0; font-size:1rem; }
.stocks-table-wrapper { max-height:420px; overflow:auto; }
.stocks-table { width:100%; border-collapse:collapse; font-size:.72rem; }
.stocks-table th { text-align:left; position:sticky; top:0; background:#f1f5f9; font-weight:600; padding:.4rem .45rem; font-size:.6rem; letter-spacing:.5px; text-transform:uppercase; }
.stocks-table td { padding:.35rem .45rem; border-bottom:1px solid #f1f5f9; }
.stocks-table tr:hover { background:#f8fafc; cursor:pointer; }
.stocks-table tr.active { background:#eff6ff; }
.stocks-table td.name { font-weight:600; }
.stocks-table td .mini-btn { padding:.25rem .5rem; font-size:.6rem; border:1px solid #cbd5e1; background:#fff; border-radius:4px; cursor:pointer; }
.pos { color:#166534; }
.neg { color:#b91c1c; }
.empty { text-align:center; opacity:.7; }
.price-chart { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:.5rem; display:flex; flex-direction:column; gap:.4rem; }
.price-chart svg { width:100%; height:120px; }
.chart-meta { display:flex; gap:1rem; font-size:.6rem; text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
.kid-section { margin-top:1.5rem; }
.kid-summary-cards { display:grid; gap:.75rem; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); margin: .75rem 0 1rem; }
.sum-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:.6rem .7rem; display:flex; flex-direction:column; gap:.3rem; box-shadow:0 1px 2px rgba(0,0,0,.04); font-size:.7rem; }
.sum-card .label { font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:#475569; font-size:.58rem; }
.sum-card .value { font-size:.9rem; font-weight:700; }
.kid-tabs { display:flex; gap:.5rem; margin-bottom:.6rem; }
.tab { flex:0 0 auto; padding:.5rem .9rem; font-size:.7rem; border:1px solid #cbd5e1; background:#fff; border-radius:8px; cursor:pointer; font-weight:600; }
.tab.active { background:#2563eb; color:#fff; border-color:#2563eb; }
.kid-panel { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:.6rem .75rem; }
.holdings-table, .trades-table { width:100%; border-collapse:collapse; font-size:.7rem; }
.holdings-table th, .trades-table th { text-align:left; background:#f1f5f9; padding:.4rem .45rem; font-weight:600; font-size:.55rem; letter-spacing:.5px; text-transform:uppercase; }
.holdings-table td, .trades-table td { padding:.35rem .45rem; border-bottom:1px solid #f1f5f9; }
.trades-table td.sell { color:#b91c1c; font-weight:600; }
.trades-table td.buy { color:#166534; font-weight:600; }
.toast { position:fixed; bottom:1rem; right:1rem; background:#1f2937; color:#fff; padding:.55rem .75rem; font-size:.65rem; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,.25); }
.toast.err { background:#b91c1c; }
.toast.ok { background:#166534; }
.toast.warn { background:#92400e; }
.adjust-cell { min-width:110px; }
.edit-inline { display:flex; align-items:center; gap:.3rem; }
.edit-inline input { width:70px; padding:.25rem .35rem; font-size:.6rem; border:1px solid #cbd5e1; border-radius:4px; }
.mini-btn.edit { background:#fff; border:1px solid #2563eb; color:#2563eb; }
.mini-btn.save { background:#2563eb; color:#fff; border:1px solid #2563eb; }
.mini-btn.cancel { background:#fff; color:#555; border:1px solid #cbd5e1; }
.mini-btn:disabled { opacity:.5; cursor:default; }
@media (max-width:980px){ .panels { grid-template-columns:1fr; } .panel.market { order:1; } .panel.details { order:2; } }
</style>
