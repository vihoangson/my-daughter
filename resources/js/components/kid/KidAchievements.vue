<template>
  <div class="kid-achievements-page">
    <h1 class="page-title">Thành tích của con</h1>

    <!-- Filters / Summary -->
    <div class="filter-bar">
      <div class="filters">
        <button
          v-for="f in filterOptions"
          :key="f.value"
          :class="['filter-btn', { active: selectedFilter === f.value }]"
          @click="selectedFilter = f.value"
        >
          {{ f.label }}
          <span class="count" v-if="f.value !== 'all'">{{ countByFilter(f.value) }}</span>
        </button>
      </div>
      <div class="right-actions">
        <button class="refresh-btn" :disabled="loading" @click="fetchAchievements">Làm mới</button>
      </div>
    </div>

    <!-- Content -->
    <div class="content-wrap">
      <div v-if="loading" class="loading">Đang tải...</div>
      <div v-else-if="error" class="error">{{ error }}</div>
      <div v-else-if="filteredAchievements.length === 0" class="empty">Không có thành tích phù hợp.</div>
      <div v-else class="achievements-grid">
        <div
          v-for="a in filteredAchievements"
          :key="a.id"
          class="ach-card"
          :class="{ achieved: a.achieved }"
        >
          <div class="img-box" v-if="a.image_url">
            <img :src="a.image_url" :alt="a.name" loading="lazy" />
            <div class="ach-badge" v-if="a.achieved">ĐÃ HOÀN THÀNH</div>
          </div>
          <div class="img-box placeholder" v-else>
            <span>🎯</span>
            <div class="ach-badge" v-if="a.achieved">ĐÃ HOÀN THÀNH</div>
          </div>
          <div class="card-body">
            <h2 class="ach-name">{{ a.name }}</h2>
            <div class="meta-line">
              <span v-if="a.category" class="badge cat">{{ a.category }}</span>
              <span class="badge status" :class="a.achieved ? 'done' : 'pending'">{{ a.achieved ? 'Đã đạt' : 'Chưa đạt' }}</span>
            </div>
            <p v-if="a.note" class="note">{{ a.note }}</p>
            <p v-if="a.kid_note" class="kid-note">Ghi chú của con: {{ a.kid_note }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'KidAchievements',
  data() {
    return {
      achievements: [],
      loading: false,
      error: '',
      selectedFilter: 'all',
      filterOptions: [
        { value: 'all', label: 'Tất cả' },
        { value: 'achieved', label: 'Đã đạt' },
        { value: 'not', label: 'Chưa đạt' }
      ]
    };
  },
  computed: {
    filteredAchievements() {
      if (this.selectedFilter === 'all') return this.achievements;
      if (this.selectedFilter === 'achieved') return this.achievements.filter(a => a.achieved);
      if (this.selectedFilter === 'not') return this.achievements.filter(a => !a.achieved);
      return this.achievements;
    }
  },
  mounted() {
    this.fetchAchievements();
  },
  methods: {
    async fetchAchievements() {
      this.loading = true; this.error = '';
      try {
        const { data } = await axios.get('/api/kid/achievements');
        // data expected array
        this.achievements = Array.isArray(data) ? data : [];
      } catch (e) {
        console.error(e);
        this.error = e?.response?.data?.message || 'Không tải được danh sách thành tích';
      } finally {
        this.loading = false;
      }
    },
    countByFilter(filter) {
      if (filter === 'achieved') return this.achievements.filter(a => a.achieved).length;
      if (filter === 'not') return this.achievements.filter(a => !a.achieved).length;
      return this.achievements.length;
    }
  }
};
</script>

<style scoped>
.kid-achievements-page { padding:1rem 1.1rem 2rem; display:flex; flex-direction:column; gap:1rem; }
.page-title { text-align:center; margin:0 0 .4rem; font-size:1.9rem; }
.filter-bar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; background:#fff; border:2px solid #e6e8f2; border-radius:18px; padding:.7rem .85rem; box-shadow:0 4px 10px rgba(0,0,0,0.04); }
.filters { display:flex; gap:.55rem; flex-wrap:wrap; }
.filter-btn { position:relative; background:#eef2ff; border:2px solid #d3daf3; padding:.5rem .9rem; border-radius:14px; cursor:pointer; font-weight:600; font-size:.8rem; letter-spacing:.3px; display:flex; align-items:center; gap:.4rem; }
.filter-btn.active { background:#567dff; border-color:#567dff; color:#fff; }
.filter-btn .count { background:rgba(255,255,255,0.85); color:#222; padding:.1rem .45rem; border-radius:10px; font-size:.65rem; font-weight:700; }
.right-actions { margin-left:auto; }
.refresh-btn { background:#2196f3; color:#fff; border:none; padding:.55rem 1rem; font-weight:600; border-radius:12px; cursor:pointer; }
.content-wrap { background:#fff; border:2px solid #e6e8f2; border-radius:20px; padding:1rem 1rem 1.4rem; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
.loading, .empty, .error { padding:1rem .5rem; font-size:.95rem; text-align:center; }
.error { color:#d93025; }
.achievements-grid { display:grid; gap:1rem; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); }
.ach-card { position:relative; background:#f6f8ff; border:2px solid #dfe3f3; border-radius:18px; overflow:hidden; display:flex; flex-direction:column; }
.ach-card.achieved { border-color:#9de6a7; background:#f0fff2; }
.img-box { position:relative; width:100%; aspect-ratio:4/3; background:#e4e9f5; display:flex; align-items:center; justify-content:center; }
.img-box.placeholder span { font-size:3rem; opacity:.5; }
.img-box img { width:100%; height:100%; object-fit:cover; display:block; }
.ach-badge { position:absolute; bottom:6px; left:6px; background:#32a852; color:#fff; font-size:.6rem; font-weight:700; padding:.32rem .55rem; border-radius:8px; letter-spacing:.5px; box-shadow:0 2px 6px rgba(0,0,0,0.15); }
.card-body { padding:.65rem .75rem .85rem; display:flex; flex-direction:column; gap:.45rem; }
.ach-name { margin:0; font-size:1.05rem; line-height:1.2; }
.meta-line { display:flex; flex-wrap:wrap; gap:.4rem; }
.badge { font-size:.58rem; padding:.3rem .55rem; border-radius:999px; background:#dde3f4; font-weight:600; letter-spacing:.4px; text-transform:uppercase; }
.badge.cat { background:#ffe2b3; }
.badge.status.done { background:#9de6a7; }
.badge.status.pending { background:#ffcdcd; }
.note { margin:0; font-size:.72rem; line-height:1.25; color:#333; }
.kid-note { margin:0; font-size:.7rem; background:#fff5c9; padding:.4rem .5rem; border-radius:10px; }
@media (max-width:700px){ .filter-btn { font-size:.72rem; } .page-title { font-size:1.6rem; } }
</style>
