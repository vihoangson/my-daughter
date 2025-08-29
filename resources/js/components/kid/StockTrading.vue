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
                  <td><strong>{{ s.code }}</strong></td>
                  <td>{{ s.name }}</td>
                  <td>
                    <span :class="priceClass(s)">{{ s.current_price }}</span>
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
    </div>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue';
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

const showMsg = (msg, ok=true)=>{ message.value = msg; success.value = ok; if(msg) setTimeout(()=>message.value='',3000); };

const loadStocks = async () => {
  try {
    const r = await axios.get('/api/kid/stocks');
    stocks.value = r.data.stocks;
    balance.value = r.data.acoin_balance;
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
    buyQty[s.id] = 1;
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
    sellQty[s.id] = 1;
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

loadAll();
</script>
<style scoped>
.table td, .table th { vertical-align: middle; }
</style>
