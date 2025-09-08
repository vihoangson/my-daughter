<template>
  <div class="bank-loan-wrapper">
    <div class="header-row">
      <h2 class="title">Ngân hàng - Khoản vay Acoin</h2>
      <slot name="close-btn"></slot>
    </div>

    <div class="kpi-grid" v-if="!loading.loans">
      <div class="kpi-box">
        <div class="kpi-label">Tổng khoản vay</div>
        <div class="kpi-value">{{ formatNum(totalPrincipal) }}</div>
      </div>
      <div class="kpi-box">
        <div class="kpi-label">Lãi tích luỹ</div>
        <div class="kpi-value">{{ formatNum(totalAccruedInterest) }}</div>
      </div>
      <div class="kpi-box">
        <div class="kpi-label">Còn phải trả</div>
        <div class="kpi-value highlight">{{ formatNum(totalOutstanding) }}</div>
      </div>
    </div>
    <div v-else class="loading-line">Đang tải dữ liệu khoản vay...</div>

    <div class="tabs">
      <button :class="['tab-btn', activeTab==='list' && 'active']" @click="activeTab='list'">Khoản vay hiện tại</button>
      <button :class="['tab-btn', activeTab==='new' && 'active']" @click="activeTab='new'">Tạo khoản vay</button>
      <button :class="['tab-btn', activeTab==='history' && 'active']" @click="openHistory">Lịch sử</button>
    </div>

    <!-- List loans -->
    <div v-show="activeTab==='list'" class="tab-section">
      <div v-if="loading.loans" class="mini-loading">Đang tải...</div>
      <div v-else-if="loans.length===0" class="empty-box">Chưa có khoản vay nào.</div>
      <div v-else class="loan-list">
        <div v-for="loan in loans" :key="loan.id" class="loan-card" :class="loan.status">
          <div class="row top-line">
            <div class="code">#{{ loan.id }}</div>
            <div class="status-badge" :class="loan.status">{{ statusLabel(loan.status) }}</div>
          </div>
          <div class="grid-fields">
            <div>
              <div class="lbl">Gốc ban đầu</div>
              <div class="val">{{ formatNum(loan.principal) }}</div>
            </div>
            <div>
              <div class="lbl">Lãi suất</div>
              <div class="val">{{ loan.interest_rate }}% / {{ ratePeriodLabel(loan.rate_period || 'term') }}</div>
            </div>
            <div>
              <div class="lbl">Đã trả</div>
              <div class="val">{{ formatNum(loan.paid_amount || 0) }}</div>
            </div>
            <div>
              <div class="lbl">Lãi tích luỹ</div>
              <div class="val">{{ formatNum(accruedInterest(loan)) }}</div>
            </div>
            <div>
              <div class="lbl">Còn lại</div>
              <div class="val highlight">{{ formatNum(outstanding(loan)) }}</div>
            </div>
            <div>
              <div class="lbl">Đáo hạn</div>
              <div class="val">{{ formatDate(loan.due_date) }}</div>
            </div>
          </div>
          <div class="actions">
            <button class="btn small" @click="repayPrompt(loan)" :disabled="isSettled(loan) || loading.repayId===loan.id">{{ isSettled(loan)? 'Đã tất toán' : (loading.repayId===loan.id? 'Đang trả...' : 'Trả bớt') }}</button>
            <button class="btn small ghost" @click="repayAllPrompt(loan)" :disabled="isSettled(loan) || loading.repayId===loan.id">Trả hết</button>
          </div>
        </div>
      </div>
    </div>

    <!-- New loan -->
    <div v-show="activeTab==='new'" class="tab-section new-loan">
      <form @submit.prevent="createLoan">
        <div class="field-row">
          <label>Số tiền vay (Acoin)</label>
          <input type="number" min="1" v-model.number="form.amount" required />
        </div>
        <div class="field-row">
          <label>Thời hạn (ngày)</label>
            <input type="number" min="1" v-model.number="form.term_days" required />
        </div>
        <div class="field-grid">
          <div class="field-row">
            <label>Lãi suất (%)</label>
            <input type="number" step="0.1" min="0" v-model.number="form.interest_rate" />
          </div>
          <div class="field-row">
            <label>Chu kỳ lãi</label>
            <select v-model="form.rate_period">
              <option value="term">Toàn kỳ</option>
              <option value="day">Mỗi ngày</option>
              <option value="week">Mỗi tuần</option>
            </select>
          </div>
        </div>
        <div class="calc-box">
          <div>Ước tính lãi: <strong>{{ formatNum(estimateInterest) }}</strong></div>
          <div>Tổng phải trả: <strong>{{ formatNum(form.amount + estimateInterest) }}</strong></div>
        </div>
        <div class="actions create">
          <button class="btn primary" type="submit" :disabled="loading.create">{{ loading.create? 'Đang tạo...' : 'Tạo khoản vay' }}</button>
          <button type="button" class="btn ghost" @click="resetForm" :disabled="loading.create">Xoá</button>
        </div>
        <p v-if="errors.create" class="err-line">{{ errors.create }}</p>
        <p v-if="messages.create" class="ok-line">{{ messages.create }}</p>
      </form>
    </div>

    <!-- History (lazy) -->
    <div v-show="activeTab==='history'" class="tab-section history">
      <div v-if="loading.history" class="mini-loading">Đang tải lịch sử...</div>
      <div v-else-if="history.length===0" class="empty-box">Chưa có lịch sử giao dịch.</div>
      <div v-else class="history-table-wrapper">
        <table class="history-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Loại</th>
              <th>Số tiền</th>
              <th>Lãi</th>
              <th>Ngày</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="h in history" :key="h.id">
              <td>{{ h.id }}</td>
              <td>{{ historyTypeLabel(h.type) }}</td>
              <td>{{ formatNum(h.amount) }}</td>
              <td>{{ formatNum(h.interest || 0) }}</td>
              <td>{{ formatDate(h.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Simple repay dialog -->
    <div v-if="repayDialog.show" class="inline-dialog">
      <div class="dialog-box">
        <h3 class="dialog-title">{{ repayDialog.full? 'Trả hết khoản vay' : 'Trả bớt khoản vay' }}</h3>
        <p class="dialog-info">Khoản vay #{{ repayDialog.loan?.id }} - Còn lại: <strong>{{ formatNum(outstanding(repayDialog.loan)) }}</strong></p>
        <div v-if="!repayDialog.full" class="field-row">
          <label>Số tiền muốn trả</label>
          <input type="number" min="1" :max="Math.floor(outstanding(repayDialog.loan))" v-model.number="repayDialog.amount" />
        </div>
        <div class="actions">
          <button class="btn primary" :disabled="loading.repayId===repayDialog.loan?.id" @click="confirmRepay">{{ loading.repayId===repayDialog.loan?.id? 'Đang xử lý...' : 'Xác nhận' }}</button>
          <button class="btn ghost" @click="closeRepayDialog" :disabled="loading.repayId===repayDialog.loan?.id">Huỷ</button>
        </div>
        <p v-if="errors.repay" class="err-line">{{ errors.repay }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  name: 'BankLoan',
  emits: ['updated'],
  props: {
    autoLoad: { type: Boolean, default: true }
  },
  data(){
    return {
      activeTab: 'list',
      loans: [],
      history: [],
      form: { amount: 0, term_days: 30, interest_rate: 5, rate_period: 'term' },
      errors: { create: '', repay: '' },
      messages: { create: '' },
      loading: { loans: false, create: false, repayId: null, history: false },
      repayDialog: { show: false, loan: null, amount: 0, full: false }
    };
  },
  computed: {
    totalPrincipal(){ return this.loans.reduce((s,l)=> s + (l.principal||0), 0); },
    totalAccruedInterest(){ return this.loans.reduce((s,l)=> s + this.accruedInterest(l), 0); },
    totalOutstanding(){ return this.loans.reduce((s,l)=> s + this.outstanding(l), 0); },
    estimateInterest(){
      if(!this.form.amount || !this.form.interest_rate) return 0;
      const rate = this.form.interest_rate/100;
      switch(this.form.rate_period){
        case 'day': return Math.round(this.form.amount * rate * this.form.term_days);
        case 'week': return Math.round(this.form.amount * rate * (this.form.term_days/7));
        default: return Math.round(this.form.amount * rate);
      }
    }
  },
  mounted(){
    if(this.autoLoad) this.fetchLoans();
  },
  methods: {
    formatNum(n){ return (n||0).toLocaleString('vi-VN'); },
    formatDate(d){ if(!d) return '-'; const dt = new Date(d); return dt.toLocaleDateString('vi-VN'); },
    statusLabel(st){ return { active:'Đang vay', settled:'Đã tất toán', overdue:'Quá hạn' }[st] || st; },
    historyTypeLabel(t){ return { loan:'Vay', repay:'Trả', interest:'Lãi' }[t] || t; },
    ratePeriodLabel(p){ return { term:'/kỳ', day:'/ngày', week:'/tuần' }[p] || ''; },
    isSettled(loan){ return loan.status === 'settled'; },
    accruedInterest(loan){ return loan.accrued_interest ?? 0; },
    outstanding(loan){
      const principalLeft = (loan.principal || 0) - (loan.paid_amount || 0);
      return Math.max(0, principalLeft + this.accruedInterest(loan) - (loan.paid_interest||0));
    },
    async fetchLoans(){
      this.loading.loans = true;
      try { const {data} = await axios.get('/api/kid/bank/loans'); this.loans = data.loans || data || []; }
      catch(e){ /* silently */ }
      finally { this.loading.loans=false; }
    },
    async fetchHistory(){
      if(this.loading.history) return; this.loading.history=true;
      try { const {data} = await axios.get('/api/kid/bank/loans/history'); this.history = data.history || data || []; }
      catch(e){}
      finally { this.loading.history=false; }
    },
    openHistory(){
      this.activeTab='history';
      if(this.history.length===0) this.fetchHistory();
    },
    resetForm(){
      this.form = { amount:0, term_days:30, interest_rate:5, rate_period:'term' };
      this.errors.create=''; this.messages.create='';
    },
    async createLoan(){
      if(this.loading.create) return; this.errors.create=''; this.messages.create='';
      if(this.form.amount<=0){ this.errors.create='Số tiền không hợp lệ'; return; }
      this.loading.create=true;
      try {
        const payload = { amount:this.form.amount, term_days:this.form.term_days, interest_rate:this.form.interest_rate, rate_period:this.form.rate_period };
        const {data} = await axios.post('/api/kid/bank/loans', payload);
        this.messages.create='Tạo khoản vay thành công';
        if(data.loan) this.loans.unshift(data.loan);
        else await this.fetchLoans();
        this.$emit('updated');
      } catch(e){ this.errors.create = e?.response?.data?.message || 'Lỗi tạo khoản vay'; }
      finally { this.loading.create=false; }
    },
    repayPrompt(loan){
      this.repayDialog = { show:true, loan, amount: Math.min(loan.principal - (loan.paid_amount||0),  Math.max(1, Math.round(this.outstanding(loan)*0.2))), full:false };
      this.errors.repay='';
    },
    repayAllPrompt(loan){
      this.repayDialog = { show:true, loan, amount: this.outstanding(loan), full:true };
      this.errors.repay='';
    },
    closeRepayDialog(){ this.repayDialog.show=false; },
    async confirmRepay(){
      const loan = this.repayDialog.loan; if(!loan) return;
      const amount = this.repayDialog.full? this.outstanding(loan) : this.repayDialog.amount;
      if(amount<=0){ this.errors.repay='Số tiền không hợp lệ'; return; }
      this.loading.repayId = loan.id; this.errors.repay='';
      try {
        const {data} = await axios.post(`/api/kid/bank/loans/${loan.id}/repay`, { amount, full: this.repayDialog.full?1:0 });
        if(data.loan){
          const idx = this.loans.findIndex(l=>l.id===loan.id);
            if(idx>-1) this.$set ? this.$set(this.loans, idx, data.loan) : (this.loans[idx]=data.loan);
        } else {
          await this.fetchLoans();
        }
        this.closeRepayDialog();
        this.$emit('updated');
      } catch(e){ this.errors.repay = e?.response?.data?.message || 'Lỗi trả nợ'; }
      finally { this.loading.repayId=null; }
    }
  }
};
</script>

<style scoped>
.bank-loan-wrapper { display:flex; flex-direction:column; gap:1rem; max-width:840px; }
.header-row { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
.title { margin:0; font-size:1.15rem; font-weight:700; }
.kpi-grid { display:grid; gap:.6rem; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); }
.kpi-box { background:#fff; border:2px solid #e5e8f0; border-radius:14px; padding:.55rem .65rem; display:flex; flex-direction:column; gap:.25rem; }
.kpi-label { font-size:.6rem; text-transform:uppercase; font-weight:600; letter-spacing:.5px; color:#666; }
.kpi-value { font-size:.95rem; font-weight:700; }
.kpi-value.highlight { color:#1a6ad9; }
.tabs { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.25rem; }
.tab-btn { border:2px solid #d5dbea; background:#f5f8ff; padding:.5rem .9rem; font-size:.75rem; font-weight:600; border-radius:30px; cursor:pointer; }
.tab-btn.active { background:#1f6bff; color:#fff; border-color:#1f6bff; }
.tab-section { background:#fff; border:2px solid #e6e9f2; border-radius:18px; padding:.85rem .95rem 1rem; }
.loan-list { display:grid; gap:.7rem; }
.loan-card { background:#f7f9fe; border:2px solid #dfe5f2; border-radius:16px; padding:.7rem .75rem .75rem; display:flex; flex-direction:column; gap:.55rem; }
.loan-card.overdue { border-color:#ff9e8d; }
.loan-card.settled { opacity:.75; }
.top-line { display:flex; align-items:center; justify-content:space-between; }
.code { font-weight:700; font-size:.8rem; }
.status-badge { font-size:.6rem; padding:.25rem .55rem; border-radius:10px; font-weight:600; background:#d7deed; }
.status-badge.active { background:#c4ebd1; }
.status-badge.overdue { background:#ffd1c9; }
.status-badge.settled { background:#d1d7e6; }
.grid-fields { display:grid; gap:.45rem .9rem; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); }
.lbl { font-size:.55rem; text-transform:uppercase; letter-spacing:.5px; color:#667; font-weight:600; }
.val { font-size:.75rem; font-weight:600; }
.val.highlight { color:#174fbd; }
.actions { display:flex; gap:.5rem; flex-wrap:wrap; }
.field-row { display:flex; flex-direction:column; gap:.3rem; margin-bottom:.65rem; }
.field-row label { font-size:.7rem; font-weight:600; letter-spacing:.3px; }
.field-row input, .field-row select { border:2px solid #d7ddea; border-radius:10px; padding:.45rem .55rem; font-size:.75rem; background:#fff; }
.field-grid { display:grid; gap:.7rem; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); margin-bottom:.4rem; }
.calc-box { background:#f1f6ff; border:2px solid #d8e4f7; border-radius:14px; padding:.55rem .7rem; font-size:.7rem; font-weight:600; display:flex; flex-direction:column; gap:.35rem; margin-bottom:.7rem; }
.btn { border:none; background:#4a7dff; color:#fff; font-weight:600; font-size:.7rem; padding:.55rem .9rem; border-radius:12px; cursor:pointer; }
.btn.primary { background:#2d6bff; }
.btn.ghost { background:#eef3fb; color:#234c9b; }
.btn.small { font-size:.65rem; padding:.4rem .65rem; }
.btn:disabled { opacity:.55; cursor:default; }
.ok-line { color:#1d7a36; font-size:.65rem; margin:.25rem 0 0; }
.err-line { color:#d93025; font-size:.65rem; margin:.25rem 0 0; }
.empty-box { text-align:center; font-size:.7rem; opacity:.7; padding:.6rem 0; }
.mini-loading, .loading-line { font-size:.7rem; font-style:italic; }
.inline-dialog { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,.35); z-index:1000; }
.dialog-box { background:#fff; border:2px solid #dbe2ef; border-radius:20px; padding:1rem 1.1rem 1rem; width:100%; max-width:360px; display:flex; flex-direction:column; gap:.7rem; box-shadow:0 6px 22px rgba(0,0,0,.15); }
.dialog-title { margin:0; font-size:1rem; font-weight:700; }
.dialog-info { margin:0; font-size:.7rem; }
.history-table-wrapper { max-height:280px; overflow:auto; border:2px solid #e4e9f3; border-radius:14px; }
.history-table { width:100%; border-collapse:collapse; font-size:.65rem; }
.history-table th, .history-table td { padding:.45rem .55rem; text-align:left; border-bottom:1px solid #e5e9f4; }
.history-table th { background:#f3f7fd; font-weight:700; font-size:.6rem; letter-spacing:.4px; text-transform:uppercase; }
.history-table tbody tr:last-child td { border-bottom:none; }
@media (max-width:640px){
  .grid-fields { grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); }
  .kpi-grid { grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); }
  .title { font-size:1rem; }
}
</style>

