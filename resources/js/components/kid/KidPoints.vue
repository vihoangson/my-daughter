<template>
  <div class="kid-points-page">
    <h1 class="page-title">Điểm của con</h1>

    <div class="summary-panel">
      <div class="total-points" :class="{ negative: totalPoints < 0 }">
        <span class="label">Tổng điểm:</span>
        <span class="value">{{ totalPoints }}</span>
      </div>
      <div class="stats">
        <div class="stat-box reward">
          <div class="num">{{ stats.reward_count }}</div>
          <div class="txt">Lượt thưởng</div>
        </div>
        <div class="stat-box punishment">
          <div class="num">{{ stats.punishment_count }}</div>
          <div class="txt">Lượt phạt</div>
        </div>
        <div class="stat-box total">
          <div class="num">{{ stats.total_records }}</div>
          <div class="txt">Tổng ghi nhận</div>
        </div>
      </div>
      <div class="actions">
        <button class="refresh-btn" @click="fetchData" :disabled="loading">Làm mới</button>
      </div>
    </div>

    <div class="list-panel">
      <h2 class="panel-title">Chi tiết điểm</h2>
      <div v-if="loading" class="loading">Đang tải...</div>
      <div v-else-if="error" class="error">{{ error }}</div>
      <div v-else-if="records.length === 0" class="empty">Chưa có ghi nhận điểm nào.</div>
      <div v-else class="records-grid">
        <div v-for="r in records" :key="r.id" class="record-card" :class="r.type">
          <div class="points-bubble" :class="r.type">{{ r.type === 'reward' ? '+' : '-' }}{{ r.points }}</div>
          <div class="img-box" v-if="r.evidence_url">
            <img :src="r.evidence_url" :alt="r.description || 'evidence'" loading="lazy" />
          </div>
          <div class="card-body">
            <div class="row-top">
              <span class="type-badge" :class="'tb-' + r.type">{{ r.type === 'reward' ? 'Thưởng' : 'Phạt' }}</span>
              <span class="time">{{ formatTime(r.created_at) }}</span>
            </div>
            <p class="desc" v-if="r.description">{{ r.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'KidPoints',
  data() {
    return {
      loading: false,
      totalPoints: 0,
      stats: { reward_count: 0, punishment_count: 0, total_records: 0 },
      records: [],
      error: ''
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      this.loading = true; this.error='';
      try {
        const { data } = await axios.get('/api/kid/points');
        this.totalPoints = data.total_points ?? 0;
        this.stats = data.statistics || this.stats;
        this.records = data.records || [];
      } catch (e) {
        console.error(e);
        this.error = e?.response?.data?.message || 'Không tải được dữ liệu';
      } finally {
        this.loading = false;
      }
    },
    formatTime(ts) {
      try { return new Date(ts).toLocaleString('vi-VN'); } catch { return ts; }
    }
  }
};
</script>

<style scoped>
.kid-points-page { padding:1rem 1.2rem 2rem; display:flex; flex-direction:column; gap:1.2rem; }
.page-title { text-align:center; margin:0; font-size:1.9rem; }
.summary-panel { background:#fff; border:2px solid #e5e7f2; border-radius:18px; padding:1rem 1.1rem 1.2rem; display:flex; flex-direction:column; gap:.9rem; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
.total-points { font-size:1.5rem; font-weight:700; display:flex; align-items:center; gap:.6rem; }
.total-points .label { font-size:1rem; font-weight:600; color:#555; }
.total-points .value { background:#4caf50; color:#fff; padding:.35rem .85rem; border-radius:14px; min-width:70px; text-align:center; }
.total-points.negative .value { background:#e53935; }
.stats { display:flex; gap:.8rem; flex-wrap:wrap; }
.stat-box { flex:1; min-width:110px; background:#f5f7ff; border:2px solid #dee3f3; border-radius:14px; padding:.55rem .6rem; text-align:center; }
.stat-box .num { font-size:1.2rem; font-weight:700; }
.stat-box .txt { font-size:.7rem; letter-spacing:.5px; font-weight:600; text-transform:uppercase; color:#555; }
.stat-box.reward { background:#e5ffe7; border-color:#c4f2c6; }
.stat-box.punishment { background:#ffe5e5; border-color:#f4c1c1; }
.stat-box.total { background:#fff3d6; border-color:#f0d5a2; }
.actions { display:flex; justify-content:flex-end; }
.refresh-btn { background:#2196f3; color:#fff; border:none; padding:.55rem 1rem; border-radius:10px; cursor:pointer; font-weight:600; }
.list-panel { background:#fff; border:2px solid #e5e7f2; border-radius:18px; padding:1rem 1.1rem 1.4rem; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
.panel-title { margin:0 0 .9rem; font-size:1.2rem; font-weight:700; }
.loading, .empty, .error { padding:.8rem .4rem; font-size:.9rem; }
.error { color:#d93025; }
.records-grid { display:grid; gap:1rem; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); }
.record-card { position:relative; background:#f7f9ff; border:2px solid #dfe5f5; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; }
.record-card.reward { border-color:#b7edc0; background:#f1fff3; }
.record-card.punishment { border-color:#ffc2c2; background:#fff6f6; }
.points-bubble { position:absolute; top:6px; left:6px; background:#4caf50; color:#fff; font-weight:700; padding:.35rem .55rem; border-radius:10px; font-size:.8rem; box-shadow:0 2px 6px rgba(0,0,0,0.15); }
.points-bubble.punishment { background:#e53935; }
.img-box { width:100%; aspect-ratio:4/3; background:#e3e8f5; overflow:hidden; }
.img-box img { width:100%; height:100%; object-fit:cover; display:block; }
.card-body { padding:.6rem .7rem .75rem; display:flex; flex-direction:column; gap:.45rem; }
.row-top { display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
.type-badge { font-size:.6rem; padding:.28rem .55rem; border-radius:999px; background:#d0d7ea; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.type-badge.tb-reward { background:#83d991; }
.type-badge.tb-punishment { background:#ff8b8b; }
.time { font-size:.55rem; color:#555; font-weight:600; }
.desc { margin:0; font-size:.75rem; line-height:1.2; }
@media (max-width:700px){ .stats { flex-direction:row; } .summary-panel { gap:.7rem; } }
</style>
