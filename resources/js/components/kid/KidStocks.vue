<template>
  <div class="kid-stocks-page">
    <h1 class="page-title">Cổ phiếu của con</h1>

    <!-- Summary -->
    <div class="summary-panel" v-if="summaryLoaded">
      <div class="summary-grid">
        <div class="sum-box balance">
          <div class="label">Acoin</div>
          <div class="value">{{ formatNum(summary.acoin_balance) }}</div>
        </div>
        <div class="sum-box invested">
          <div class="label">Đã đầu tư</div>
          <div class="value">{{ formatNum(summary.invested) }}</div>
        </div>
        <div class="sum-box current">
          <div class="label">Giá trị hiện tại</div>
          <div class="value">{{ formatNum(summary.current_value) }}</div>
        </div>
        <div class="sum-box unrealized" :class="{ pos: summary.unrealized_profit >=0, neg: summary.unrealized_profit < 0 }">
          <div class="label">Lãi/Lỗ chưa chốt</div>
          <div class="value">{{ formatSigned(summary.unrealized_profit) }}</div>
        </div>
        <div class="sum-box realized" :class="{ pos: summary.realized_profit >=0, neg: summary.realized_profit < 0 }">
          <div class="label">Lãi/Lỗ đã chốt</div>
          <div class="value">{{ formatSigned(summary.realized_profit) }}</div>
        </div>
        <div class="sum-box equity">
          <div class="label">Tổng tài sản</div>
            <div class="value">{{ formatNum(summary.equity) }}</div>
        </div>
      </div>
      <div class="summary-actions">
        <button class="btn-refresh" :disabled="loadingRefresh" @click="refreshPrices">{{ loadingRefresh? 'Đang làm mới...' : 'Làm mới giá' }}</button>
        <button class="btn-trades-toggle" @click="showTrades = !showTrades">Lịch sử ({{ trades.length }})</button>
      </div>
    </div>

    <div v-else class="loading-block">Đang tải tổng quan...</div>

    <div class="layout">
      <!-- Stocks List -->
      <div class="panel stocks-panel">
        <div class="panel-head">
          <h2 class="panel-title">Danh sách Cổ phiếu</h2>
          <div class="search-box">
            <input v-model.trim="stockFilter" placeholder="Tìm mã..." />
          </div>
        </div>
        <div v-if="loadingStocks" class="mini-loading">Đang tải...</div>
        <div v-else class="stocks-list">
          <div v-for="s in filteredStocks" :key="s.id" class="stock-row" :class="{ active: s.id === selectedStockId }" @click="selectStock(s)">
            <div class="code">{{ s.code }}</div>
            <div class="name">{{ s.name }}</div>
            <div class="price">{{ formatNum(s.current_price) }}</div>
          </div>
          <div v-if="filteredStocks.length===0" class="empty-small">Không có mã phù hợp.</div>
        </div>
      </div>

      <!-- Center: Selected / Trade -->
      <div class="panel trade-panel">
        <div v-if="selectedStock" class="selected-header">
          <div class="head-line">
            <h2>{{ selectedStock.code }} - {{ selectedStock.name }}</h2>
            <div class="price-tag">{{ formatNum(selectedStock.current_price) }}</div>
          </div>
          <div class="mini-chart" v-if="priceHistory.length > 1">
            <svg :viewBox="svgViewBox" preserveAspectRatio="none">
              <polyline :points="polyPoints" fill="none" stroke="#4a80ff" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" />
            </svg>
          </div>
          <div v-else class="chart-placeholder">(Chưa đủ dữ liệu giá)</div>
        </div>
        <div v-else class="placeholder-box">Chọn một mã cổ phiếu để giao dịch.</div>

        <div v-if="selectedStock" class="trade-actions">
          <div class="trade-box buy">
            <h3>Mua</h3>
            <div class="row-field">
              <label>Số lượng</label>
              <input type="number" min="1" v-model.number="buyQty" />
            </div>
            <div class="calc">Chi phí: <strong>{{ formatNum(buyCost) }}</strong></div>
            <button class="btn-buy" :disabled="!canBuy || buying" @click="doBuy">{{ buying? 'Đang mua...' : 'Mua' }}</button>
            <p v-if="buyError" class="err">{{ buyError }}</p>
          </div>
          <div class="trade-box sell">
            <h3>Bán</h3>
            <div class="row-field">
              <label>Số lượng</label>
              <input type="number" min="1" v-model.number="sellQty" />
            </div>
            <div class="calc">Thu được: <strong>{{ formatNum(sellProceeds) }}</strong></div>
            <button class="btn-sell" :disabled="!canSell || selling" @click="doSell">{{ selling? 'Đang bán...' : 'Bán' }}</button>
            <p v-if="sellError" class="err">{{ sellError }}</p>
          </div>
        </div>
      </div>

      <!-- Holdings -->
      <div class="panel holdings-panel">
        <div class="panel-head">
          <h2 class="panel-title">Danh mục của con</h2>
        </div>
        <div v-if="loadingHoldings" class="mini-loading">Đang tải...</div>
        <div v-else-if="holdings.length===0" class="empty-small">Chưa có cổ phiếu nào.</div>
        <div v-else class="holdings-list">
          <div v-for="h in holdings" :key="h.id" class="holding-row" :class="{ active: h.stock_id === selectedStockId }" @click="selectStock(h.stock)">
            <div class="code">{{ h.stock.code }}</div>
            <div class="qty">x{{ h.quantity }}</div>
            <div class="avg">Avg {{ formatNum(h.avg_price) }}</div>
            <div class="curr">{{ formatNum(h.stock.current_price) }}</div>
            <div class="unreal" :class="{ pos: unrealized(h) >=0, neg: unrealized(h) < 0 }">{{ formatSigned(unrealized(h)) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Trades history -->
    <div v-if="showTrades" class="panel trades-panel">
      <div class="panel-head">
        <h2 class="panel-title">Lịch sử giao dịch</h2>
        <button class="close-trades" @click="showTrades=false">Đóng</button>
      </div>
      <div v-if="loadingTrades" class="mini-loading">Đang tải...</div>
      <div v-else-if="trades.length===0" class="empty-small">Chưa có giao dịch.</div>
      <div v-else class="trades-list">
        <div v-for="t in trades" :key="t.id" class="trade-row" :class="t.type">
          <div class="time">#{{ t.id }}</div>
          <div class="code">{{ t.stock.code }}</div>
          <div class="type-badge" :class="t.type">{{ t.type==='buy'? 'Mua':'Bán' }}</div>
          <div class="qty">x{{ t.quantity }}</div>
          <div class="price">{{ formatNum(t.price) }}</div>
          <div class="total" :class="t.type">{{ t.type==='buy'? '-'+formatNum(t.total): '+'+formatNum(t.total) }}</div>
          <div class="profit" :class="{ pos: t.profit>=0, neg: t.profit<0 }" v-if="t.type==='sell'">{{ formatSigned(t.profit) }}</div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';
export default {
  name: 'KidStocks',
  data(){
    return {
      summaryLoaded:false,
      summary: { acoin_balance:0, invested:0, current_value:0, unrealized_profit:0, realized_profit:0, equity:0 },
      stocks: [],
      holdings: [],
      trades: [],
      selectedStockId: null,
      priceHistory: [],
      stockFilter:'',
      buyQty:1,
      sellQty:1,
      loadingStocks:false,
      loadingHoldings:false,
      loadingTrades:false,
      loadingRefresh:false,
      buying:false,
      selling:false,
      buyError:'',
      sellError:'',
      showTrades:false,
    };
  },
  computed:{
    filteredStocks(){
      if(!this.stockFilter) return this.stocks;
      const f = this.stockFilter.toLowerCase();
      return this.stocks.filter(s=> s.code.toLowerCase().includes(f) || (s.name||'').toLowerCase().includes(f));
    },
    selectedStock(){ return this.stocks.find(s=>s.id===this.selectedStockId) || null; },
    buyCost(){ return this.selectedStock ? this.buyQty * this.selectedStock.current_price : 0; },
    sellProceeds(){ return this.selectedStock ? this.sellQty * this.selectedStock.current_price : 0; },
    canBuy(){ return this.selectedStock && this.buyQty>0 && (this.buyCost <= this.summary.acoin_balance) && !this.buying; },
    canSell(){
      if(!this.selectedStock || this.sellQty<=0 || this.selling) return false;
      const hold = this.holdings.find(h=>h.stock_id===this.selectedStockId);
      return hold && hold.quantity >= this.sellQty;
    },
    svgViewBox(){
      if(this.priceHistory.length<2) return '0 0 100 40';
      return '0 0 100 40';
    },
    polyPoints(){
      if(this.priceHistory.length<2) return '';
      const prices = this.priceHistory.map(p=>p.price);
      const min = Math.min(...prices);
      const max = Math.max(...prices);
      const span = Math.max(1, max-min);
      const stepX = 100/(prices.length-1);
      return prices.map((p,i)=>{
        const normY = (p - min)/span; // 0..1
        const y = 40 - normY*38 -1; // padding
        const x = i*stepX;
        return x+','+y;
      }).join(' ');
    }
  },
  mounted(){
    this.init();
  },
  methods:{
    async init(){
      await Promise.all([this.fetchSummary(), this.fetchStocks(), this.fetchHoldings(), this.fetchTrades()]);
      this.summaryLoaded=true;
    },
    async fetchSummary(){
      try { const {data}= await axios.get('/api/kid/stocks/summary'); this.summary = data; } catch(e){ /* ignore */ }
    },
    async fetchStocks(){
      this.loadingStocks=true; try { const {data}= await axios.get('/api/kid/stocks'); this.stocks = data.stocks||[]; this.summary.acoin_balance = data.acoin_balance ?? this.summary.acoin_balance; } catch(e){} finally { this.loadingStocks=false; }
    },
    async fetchHoldings(){
      this.loadingHoldings=true; try { const {data}= await axios.get('/api/kid/stocks/holdings'); this.holdings = (data.holdings||[]).map(h=> ({...h, stock: h.stock||{}})); this.summary.acoin_balance = data.acoin_balance ?? this.summary.acoin_balance; } catch(e){} finally { this.loadingHoldings=false; }
    },
    async fetchTrades(){
      this.loadingTrades=true; try { const {data}= await axios.get('/api/kid/stocks/trades'); this.trades = data.trades||[]; this.summary.realized_profit = data.realized_profit ?? this.summary.realized_profit; } catch(e){} finally { this.loadingTrades=false; }
    },
    async loadHistory(stock){
      try { const {data}= await axios.get(`/api/kid/stocks/${stock.id}/prices`); this.priceHistory = data.history||[]; } catch(e){ this.priceHistory=[]; }
    },
    selectStock(stock){
      if(!stock) return; this.selectedStockId = stock.id; this.buyQty=1; this.sellQty=1; this.buyError=''; this.sellError=''; this.loadHistory(stock);
    },
    formatNum(n){ return (n||0).toLocaleString('vi-VN'); },
    formatSigned(n){ const v=n||0; return (v>=0? '+':'')+this.formatNum(v); },
    unrealized(h){ return (h.stock.current_price - h.avg_price)*h.quantity; },
    async refreshPrices(){
      if(this.loadingRefresh) return; this.loadingRefresh=true;
      try { const {data}= await axios.post('/api/kid/stocks/refresh'); this.stocks = data.stocks||this.stocks; await Promise.all([this.fetchSummary(), this.fetchHoldings()]); if(this.selectedStock){ const st = this.stocks.find(s=>s.id===this.selectedStockId); if(st) await this.loadHistory(st); }
      } catch(e){} finally { this.loadingRefresh=false; }
    },
    async doBuy(){
      if(!this.canBuy) return; this.buying=true; this.buyError='';
      try { const {data}= await axios.post('/api/kid/stocks/buy',{ stock_id:this.selectedStockId, quantity:this.buyQty });
        this.summary.acoin_balance = data.acoin_balance ?? this.summary.acoin_balance;
        this.holdings = (data.holdings||[]).map(h=>({...h, stock:h.stock||{}}));
        this.stocks = data.stocks||this.stocks;
        await this.fetchSummary();
      } catch(e){ this.buyError = e?.response?.data?.message || 'Lỗi mua'; }
      finally { this.buying=false; }
    },
    async doSell(){
      if(!this.canSell) return; this.selling=true; this.sellError='';
      try { const {data}= await axios.post('/api/kid/stocks/sell',{ stock_id:this.selectedStockId, quantity:this.sellQty });
        this.summary.acoin_balance = data.acoin_balance ?? this.summary.acoin_balance;
        this.holdings = (data.holdings||[]).map(h=>({...h, stock:h.stock||{}}));
        this.stocks = data.stocks||this.stocks;
        await Promise.all([this.fetchSummary(), this.fetchTrades()]);
      } catch(e){ this.sellError = e?.response?.data?.message || 'Lỗi bán'; }
      finally { this.selling=false; }
    }
  }
};
</script>

<style scoped>
.kid-stocks-page { padding:1rem 1.1rem 2rem; display:flex; flex-direction:column; gap:1rem; }
.page-title { text-align:center; margin:0 0 .4rem; font-size:1.9rem; }
.summary-panel { background:#fff; border:2px solid #e5e7f2; border-radius:20px; padding:.9rem 1rem 1rem; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
.summary-grid { display:grid; gap:.6rem; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); }
.sum-box { background:#f5f7ff; border:2px solid #dfe4f4; border-radius:14px; padding:.55rem .6rem; display:flex; flex-direction:column; gap:.25rem; }
.sum-box .label { font-size:.65rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#555; }
.sum-box .value { font-size:1rem; font-weight:700; }
.sum-box.unrealized.pos .value, .sum-box.realized.pos .value { color:#18863d; }
.sum-box.unrealized.neg .value, .sum-box.realized.neg .value { color:#d93a2f; }
.summary-actions { margin-top:.6rem; display:flex; gap:.6rem; flex-wrap:wrap; }
.btn-refresh, .btn-trades-toggle { background:#4a80ff; color:#fff; border:none; padding:.55rem 1rem; border-radius:12px; font-weight:600; cursor:pointer; font-size:.8rem; }
.btn-trades-toggle { background:#ff9f43; }
.loading-block { text-align:center; font-style:italic; padding:1rem 0; }
.layout { display:grid; gap:1rem; grid-template-columns: 260px 1fr 280px; align-items:start; }
@media (max-width:1200px){ .layout { grid-template-columns:240px 1fr 250px; } }
@media (max-width:980px){ .layout { grid-template-columns:1fr; } }
.panel { background:#fff; border:2px solid #e5e7f2; border-radius:20px; padding:.9rem .95rem 1.1rem; box-shadow:0 4px 10px rgba(0,0,0,0.05); display:flex; flex-direction:column; gap:.7rem; }
.panel-head { display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
.panel-title { margin:0; font-size:1rem; font-weight:700; }
.search-box input { border:2px solid #dfe3f2; border-radius:10px; padding:.4rem .6rem; font-size:.75rem; }
.stocks-list { display:flex; flex-direction:column; gap:.4rem; max-height:420px; overflow:auto; }
.stock-row { display:grid; grid-template-columns:60px 1fr 70px; align-items:center; gap:.4rem; background:#f6f8ff; border:2px solid #e2e6f3; border-radius:12px; padding:.4rem .55rem; cursor:pointer; font-size:.75rem; font-weight:600; }
.stock-row.active { border-color:#4a80ff; background:#e9f0ff; }
.stock-row:hover { background:#eef3ff; }
.stock-row .code { font-weight:700; }
.trade-panel { min-height:380px; }
.placeholder-box { text-align:center; font-size:.85rem; opacity:.7; margin-top:2rem; }
.selected-header { display:flex; flex-direction:column; gap:.4rem; }
.head-line { display:flex; align-items:center; justify-content:space-between; gap:.6rem; }
.head-line h2 { margin:0; font-size:1.05rem; }
.price-tag { background:#4a80ff; color:#fff; font-weight:700; padding:.35rem .7rem; border-radius:10px; font-size:.85rem; }
.mini-chart { width:100%; height:70px; }
.mini-chart svg { width:100%; height:100%; }
.chart-placeholder { font-size:.65rem; opacity:.6; }
.trade-actions { display:grid; gap:.7rem; grid-template-columns:1fr 1fr; }
.trade-box { background:#f6f8ff; border:2px solid #dfe4f5; border-radius:16px; padding:.6rem .7rem .75rem; display:flex; flex-direction:column; gap:.5rem; }
.trade-box h3 { margin:0; font-size:.85rem; font-weight:700; }
.row-field { display:flex; flex-direction:column; gap:.25rem; }
.row-field input { border:2px solid #d4d9e8; border-radius:10px; padding:.45rem .55rem; font-size:.75rem; }
.calc { font-size:.7rem; font-weight:600; }
.btn-buy, .btn-sell { border:none; padding:.55rem .75rem; font-weight:700; font-size:.8rem; border-radius:10px; cursor:pointer; }
.btn-buy { background:#34b466; color:#fff; }
.btn-buy:disabled { opacity:.55; }
.btn-sell { background:#ff5f56; color:#fff; }
.btn-sell:disabled { opacity:.55; }
.err { color:#d93025; font-size:.6rem; margin:0; }
.holdings-list { display:flex; flex-direction:column; gap:.4rem; max-height:420px; overflow:auto; }
.holding-row { display:grid; grid-template-columns:60px 50px 1fr 60px 70px; gap:.35rem; align-items:center; background:#f6f8ff; border:2px solid #e2e6f3; border-radius:12px; padding:.4rem .55rem; font-size:.65rem; cursor:pointer; font-weight:600; }
.holding-row.active { border-color:#4a80ff; background:#e9f0ff; }
.holding-row .unreal.pos { color:#18863d; }
.holding-row .unreal.neg { color:#d93a2f; }
.trades-panel { margin-top:1rem; }
.trades-list { display:flex; flex-direction:column; gap:.45rem; max-height:360px; overflow:auto; }
.trade-row { display:grid; grid-template-columns:50px 60px 60px 55px 70px 80px 70px; gap:.35rem; align-items:center; background:#f6f8ff; border:2px solid #e2e6f3; border-radius:12px; padding:.4rem .55rem; font-size:.6rem; font-weight:600; }
.trade-row.buy { border-color:#b6f2c7; }
.trade-row.sell { border-color:#ffc2c2; }
.type-badge { padding:.25rem .45rem; border-radius:8px; font-size:.55rem; background:#d0d7ea; }
.type-badge.buy { background:#9de7b1; }
.type-badge.sell { background:#ffb9b9; }
.trade-row .total.buy { color:#d93a2f; }
.trade-row .total.sell { color:#18863d; }
.profit.pos { color:#18863d; }
.profit.neg { color:#d93a2f; }
.close-trades { background:#ff8a3d; color:#fff; border:none; padding:.45rem .8rem; border-radius:10px; font-weight:600; cursor:pointer; font-size:.7rem; }
.mini-loading { font-size:.7rem; font-style:italic; }
.empty-small { font-size:.65rem; opacity:.6; padding:.4rem 0; text-align:center; }
@media (max-width:700px){
  .page-title { font-size:1.6rem; }
  .trade-actions { grid-template-columns:1fr; }
  .holding-row { grid-template-columns:50px 45px 1fr 55px 60px; }
  .trade-row { grid-template-columns:40px 50px 50px 45px 60px 70px 60px; }
}
</style>
