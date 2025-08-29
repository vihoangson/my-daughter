<template>
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-warning">
      <h5 class="mb-0">Thị Trường Cổ Phiếu Vui Nhộn</h5>
      <div>
        <span class="badge bg-dark me-2">Acoin: {{ balance }}</span>
        <button class="btn btn-sm btn-outline-light" @click="refreshPrices" :disabled="loadingRefresh">
          <span v-if="loadingRefresh" class="spinner-border spinner-border-sm"></span>
          <span v-else>Cập nhật giá</span>
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-lg-7 mb-3">
          <h6>Danh sách cổ phiếu</h6>
          <div class="table-responsive" v-if="stocks.length">
            <table class="table table-sm table-hover align-middle">
              <thead>
                <tr>
                  <th>Mã</th>
                  <th>Tên</th>
                  <th>Giá</th>
                  <th>Mua</th>
                  <th>Bán</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in stocks" :key="s.id">
                  <td>
                    <button class="btn btn-link p-0 fw-bold" @click="openHistory(s)" title="Xem biểu đồ giá">{{ s.code }}</button>
                  </td>
                  <td>{{ s.name }}</td>
                  <td>
                    <span :class="priceClass(s)" class="me-2">{{ s.current_price }}</span>
                    <button class="btn btn-xs btn-outline-secondary btn-sm" @click="openHistory(s)" title="Biểu đồ">
                      <i class="fas fa-chart-line"></i>
                    </button>
                  </td>
                  <td style="width:110px;">
                    <div class="input-group input-group-sm">
                      <input type="number" min="1" v-model.number="buyQty[s.id]" class="form-control" />
                      <button class="btn btn-success" @click="buy(s)" :disabled="busy">M</button>
                    </div>
                  </td>
                  <td style="width:110px;">
                    <div class="input-group input-group-sm">
                      <input type="number" min="1" v-model.number="sellQty[s.id]" class="form-control" />
                      <button class="btn btn-danger" @click="sell(s)" :disabled="busy">B</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-muted small">Đang tải...</div>
        </div>
        <div class="col-lg-5">
          <h6>Danh mục của bạn</h6>
          <div v-if="holdings.length" class="table-responsive">
            <table class="table table-sm table-striped align-middle">
              <thead>
                <tr>
                  <th>Mã</th>
                  <th>SL</th>
                  <th>Giá TB</th>
                  <th>Giá Hiện</th>
                  <th>Lãi/Lỗ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="h in holdings" :key="h.id">
                  <td>{{ h.stock.code }}</td>
                  <td>{{ h.quantity }}</td>
                  <td>{{ h.avg_price }}</td>
                  <td>{{ h.stock.current_price }}</td>
                  <td :class="pnlClass(h)">{{ pnlValue(h) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-muted small">Chưa có cổ phiếu nào. Hãy thử mua nhé!</div>
          <div class="mt-3">
            <button class="btn btn-outline-secondary btn-sm" @click="loadAll" :disabled="busy">Làm mới dữ liệu</button>
          </div>
        </div>
      </div>

      <!-- Summary Row -->
      <div class="row mt-4">
        <div class="col-md-12">
          <h6>Tổng quan lợi nhuận</h6>
          <div class="row g-3 small">
            <div class="col-6 col-lg-2">
              <div class="border rounded p-2 text-center bg-light">
                <div class="fw-bold">Đã đầu tư</div>
                <div>{{ summary.invested }}</div>
              </div>
            </div>
            <div class="col-6 col-lg-2">
              <div class="border rounded p-2 text-center bg-light">
                <div class="fw-bold">Giá trị HT</div>
                <div>{{ summary.current_value }}</div>
              </div>
            </div>
            <div class="col-6 col-lg-2">
              <div class="border rounded p-2 text-center bg-light">
                <div class="fw-bold">Lãi/Lỗ chưa TT</div>
                <div :class="profitClass(summary.unrealized_profit)">{{ formatSigned(summary.unrealized_profit) }}</div>
              </div>
            </div>
            <div class="col-6 col-lg-2">
              <div class="border rounded p-2 text-center bg-light">
                <div class="fw-bold">Lãi/Lỗ đã TT</div>
                <div :class="profitClass(summary.realized_profit)">{{ formatSigned(summary.realized_profit) }}</div>
              </div>
            </div>
            <div class="col-6 col-lg-2">
              <div class="border rounded p-2 text-center bg-light">
                <div class="fw-bold">Acoin</div>
                <div>{{ summary.acoin_balance }}</div>
              </div>
            </div>
            <div class="col-6 col-lg-2">
              <div class="border rounded p-2 text-center bg-light">
                <div class="fw-bold">Tổng Tài Sản</div>
                <div>{{ summary.equity }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Trade History -->
      <div class="row mt-4">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Lịch sử giao dịch</h6>
            <button class="btn btn-sm btn-outline-primary" @click="loadTrades" :disabled="loadingTrades">Tải lại</button>
          </div>
          <div v-if="loadingTrades" class="text-muted small">Đang tải...</div>
          <div v-else-if="!trades.length" class="text-muted small">Chưa có giao dịch.</div>
          <div v-else class="table-responsive" style="max-height:260px;">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light" style="position:sticky; top:0;">
                <tr>
                  <th>Thời gian</th>
                  <th>Loại</th>
                  <th>Mã</th>
                  <th>SL</th>
                  <th>Giá</th>
                  <th>Tổng</th>
                  <th>Lãi/Lỗ</th>
                  <th>Số dư sau</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in trades" :key="t.id">
                  <td>{{ formatDate(t.created_at) }}</td>
                  <td>
                    <span :class="t.type==='buy' ? 'badge bg-success' : 'badge bg-danger'">{{ t.type==='buy'?'Mua':'Bán' }}</span>
                  </td>
                  <td>{{ t.stock.code }}</td>
                  <td>{{ t.quantity }}</td>
                  <td>{{ t.price }}</td>
                  <td>{{ t.total }}</td>
                  <td :class="profitClass(t.profit)">{{ t.type==='sell' ? formatSigned(t.profit) : '-' }}</td>
                  <td>{{ t.balance_after }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-2 small">Lãi/Lỗ đã thực hiện: <strong :class="profitClass(realizedProfit)">{{ formatSigned(realizedProfit) }}</strong></div>
        </div>
      </div>

      <div v-if="message" class="alert mt-3" :class="{'alert-success': success, 'alert-danger': !success}">{{ message }}</div>

      <!-- Price History Modal -->
      <div v-if="showHistory" class="modal d-block" style="background:rgba(0,0,0,0.6);">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Biểu đồ giá: {{ historyStock?.code }} - {{ historyStock?.name }}</h5>
              <button class="btn-close" @click="closeHistory"></button>
            </div>
            <div class="modal-body">
              <div v-if="historyLoading" class="text-center py-4">
                <div class="spinner-border text-primary"></div>
                <div class="mt-2 small">Đang tải dữ liệu...</div>
              </div>
              <div v-else>
                <div v-if="!priceHistory.length" class="text-muted small">Chưa có dữ liệu.</div>
                <div v-else>
                  <div class="d-flex flex-wrap mb-2 small">
                    <div class="me-3">Điểm: {{ priceHistory.length }}</div>
                    <div class="me-3">Giá mới nhất: <strong>{{ priceHistory[priceHistory.length-1].price }}</strong></div>
                    <div class="me-3" v-if="priceHistory.length>1">Thay đổi: <span :class="profitClass(latestChange)">{{ formatSigned(latestChange) }}</span></div>
                    <div class="me-3" v-if="priceHistory.length>1">Min: {{ minPrice }} / Max: {{ maxPrice }}</div>
                  </div>
                  <div class="border rounded p-2 bg-light position-relative" style="height:220px;">
                    <svg :viewBox="'0 0 '+chartWidth+' '+chartHeight" preserveAspectRatio="none" style="width:100%;height:100%;">
                      <defs>
                        <linearGradient :id="gradientId" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stop-color="#28a745" stop-opacity="0.4" />
                          <stop offset="100%" stop-color="#28a745" stop-opacity="0" />
                        </linearGradient>
                      </defs>
                      <polyline :points="polylinePoints" fill="none" stroke="#28a745" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                      <polygon v-if="areaPoints" :points="areaPoints" fill="url(#'+gradientId+')" />
                      <!-- Horizontal guide lines -->
                      <g stroke="#ccc" stroke-dasharray="3,3" stroke-width="1">
                        <line v-for="g in guideLines" :key="g" :x1="0" :x2="chartWidth" :y1="g" :y2="g" />
                      </g>
                      <!-- Last point marker -->
                      <circle v-if="lastPoint" :cx="lastPoint.x" :cy="lastPoint.y" r="3" fill="#dc3545" />
                    </svg>
                  </div>
                  <div class="small text-muted mt-2">Biểu đồ chứa tối đa 200 điểm gần nhất. Giá cập nhật khi bạn bấm "Cập nhật giá".</div>
                  <div class="mt-3">
                    <button class="btn btn-sm btn-outline-secondary me-2" @click="reloadHistory" :disabled="historyLoading">Tải lại</button>
                    <button class="btn btn-sm btn-primary" @click="closeHistory">Đóng</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';

const stocks = ref([]);
const holdings = ref([]);
const balance = ref(0);
const buyQty = reactive({});
const sellQty = reactive({});
const busy = ref(false);
const loadingRefresh = ref(false);
const message = ref('');
const success = ref(true);

const summary = reactive({ invested:0, current_value:0, unrealized_profit:0, realized_profit:0, acoin_balance:0, equity:0 });
const trades = ref([]);
const realizedProfit = ref(0);
const loadingTrades = ref(false);

const showHistory = ref(false);
const historyLoading = ref(false);
const priceHistory = ref([]); // {price, captured_at}
const historyStock = ref(null);
const gradientId = 'grad-'+Math.random().toString(36).slice(2);

const chartWidth = 600; // logical SVG width
const chartHeight = 180; // logical SVG height (padding below for axis not drawn)
const polylinePoints = computed(()=>{
  if (!priceHistory.value.length) return '';
  const prices = priceHistory.value.map(p=>p.price);
  const min = Math.min(...prices);
  const max = Math.max(...prices);
  const span = max-min || 1;
  const step = chartWidth / Math.max(1, prices.length-1);
  return prices.map((p,i)=> (i*step).toFixed(2)+','+( (chartHeight- ((p-min)/span)*chartHeight).toFixed(2)) ).join(' ');
});
const areaPoints = computed(()=>{
  if (!polylinePoints.value) return '';
  const firstX = '0,'+chartHeight;
  const lastX = chartWidth+','+chartHeight;
  return firstX+' '+polylinePoints.value+' '+lastX;
});
const lastPoint = computed(()=>{
  if (!priceHistory.value.length) return null;
  const pts = polylinePoints.value.split(' ');
  const last = pts[pts.length-1].split(',');
  return { x: parseFloat(last[0]), y: parseFloat(last[1]) };
});
const minPrice = computed(()=> priceHistory.value.length ? Math.min(...priceHistory.value.map(p=>p.price)) : 0);
const maxPrice = computed(()=> priceHistory.value.length ? Math.max(...priceHistory.value.map(p=>p.price)) : 0);
const latestChange = computed(()=>{
  if (priceHistory.value.length < 2) return 0;
  const a = priceHistory.value[priceHistory.value.length-2].price;
  const b = priceHistory.value[priceHistory.value.length-1].price;
  return b-a;
});
const guideLines = computed(()=>{
  // 4 guide lines
  return [0.25,0.5,0.75].map(r=> (chartHeight*r).toFixed(2));
});

const showMsg = (msg, ok=true)=>{ message.value = msg; success.value = ok; if(msg) setTimeout(()=>message.value='',3000); };

const loadStocks = async () => {
  try {
    const r = await axios.get('/api/kid/stocks');
    stocks.value = r.data.stocks;
    balance.value = r.data.acoin_balance;
    // set default 0 for inputs
    stocks.value.forEach(s => {
      if (buyQty[s.id] === undefined) buyQty[s.id] = 0;
      if (sellQty[s.id] === undefined) sellQty[s.id] = 0;
    });
  } catch(e){ console.error(e); }
};
const loadHoldings = async () => {
  try {
    const r = await axios.get('/api/kid/stocks/holdings');
    holdings.value = r.data.holdings;
    balance.value = r.data.acoin_balance;
  } catch(e){ console.error(e); }
};
const loadSummary = async () => {
  try {
    const r = await axios.get('/api/kid/stocks/summary');
    Object.assign(summary, r.data);
    balance.value = r.data.acoin_balance;
  } catch(e){ console.error(e); }
};
const loadTrades = async () => {
  loadingTrades.value = true;
  try {
    const r = await axios.get('/api/kid/stocks/trades');
    trades.value = r.data.trades;
    realizedProfit.value = r.data.realized_profit;
  } catch(e){ console.error(e); } finally { loadingTrades.value=false; }
};

const loadAll = async ()=>{ await Promise.all([loadStocks(), loadHoldings(), loadSummary(), loadTrades()]); };

const buy = async (s) => {
  const q = buyQty[s.id] || 0;
  if (!q || q < 1) return showMsg('Số lượng mua không hợp lệ', false);
  busy.value = true;
  try {
    const r = await axios.post('/api/kid/stocks/buy', { stock_id: s.id, quantity: q });
    applyState(r.data);
    buyQty[s.id] = 0; // reset to 0 after buy
    await Promise.all([loadSummary(), loadTrades()]);
    showMsg('Mua thành công!');
  } catch(e){
    showMsg(e.response?.data?.message || 'Lỗi mua', false);
  } finally { busy.value = false; }
};
const sell = async (s) => {
  const q = sellQty[s.id] || 0;
  if (!q || q < 1) return showMsg('Số lượng bán không hợp lệ', false);
  busy.value = true;
  try {
    const r = await axios.post('/api/kid/stocks/sell', { stock_id: s.id, quantity: q });
    applyState(r.data);
    sellQty[s.id] = 0; // reset to 0 after sell
    await Promise.all([loadSummary(), loadTrades()]);
    showMsg('Bán thành công!');
  } catch(e){
    showMsg(e.response?.data?.message || 'Lỗi bán', false);
  } finally { busy.value = false; }
};

const refreshPrices = async () => {
  loadingRefresh.value = true;
  try {
    const r = await axios.post('/api/kid/stocks/refresh');
    stocks.value = r.data.stocks;
    await Promise.all([loadHoldings(), loadSummary()]);
    showMsg('Đã cập nhật giá');
  } catch(e){ showMsg('Lỗi cập nhật giá', false); } finally { loadingRefresh.value = false; }
};

const applyState = (data) => {
  if (data.stocks) stocks.value = data.stocks;
  if (data.holdings) holdings.value = data.holdings;
  if (data.acoin_balance !== undefined) { balance.value = data.acoin_balance; summary.acoin_balance = data.acoin_balance; }
};

const pnlValue = (h) => {
  const diff = (h.stock.current_price - h.avg_price) * h.quantity;
  return diff > 0 ? '+'+diff : diff;
};
const pnlClass = (h) => {
  const diff = (h.stock.current_price - h.avg_price) * h.quantity;
  return diff > 0 ? 'text-success' : (diff < 0 ? 'text-danger' : '');
};
const priceClass = (s) => {
  if (s.current_price > s.base_price) return 'text-success';
  if (s.current_price < s.base_price) return 'text-danger';
  return '';
};
const formatSigned = (v) => { if (v === null || v === undefined) return '0'; return v > 0 ? '+'+v : v; };
const profitClass = (v) => v > 0 ? 'text-success fw-semibold' : (v < 0 ? 'text-danger fw-semibold' : '');
const formatDate = (d) => new Date(d).toLocaleString('vi-VN', { hour12:false });

const openHistory = async (stock) => {
  historyStock.value = stock;
  showHistory.value = true;
  await fetchHistory();
};
const closeHistory = () => { showHistory.value=false; priceHistory.value=[]; historyStock.value=null; };
const fetchHistory = async () => {
  if (!historyStock.value) return;
  historyLoading.value = true;
  try {
    const r = await axios.get(`/api/kid/stocks/${historyStock.value.id}/prices`);
    priceHistory.value = r.data.history;
    // sort ascending just in case
    priceHistory.value.sort((a,b)=> new Date(a.captured_at)-new Date(b.captured_at));
  } catch(e){ console.error(e); } finally { historyLoading.value=false; }
};
const reloadHistory = fetchHistory;

loadAll();
</script>
<style scoped>
.table td, .table th { vertical-align: middle; }
.btn-xs { padding: 0 .4rem; font-size: .65rem; line-height: 1.1; }
</style>
