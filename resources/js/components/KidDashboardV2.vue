<template>
  <!--    todo: hiển thị thông tin cần thiết: tổng Points, số thành tích đã đạt ...   -->
  <div class="kid-dashboard-v2">
    <div class="summary" v-if="!loading">
      <div class="kid-box">
        <div class="avatar-wrap">
          <img v-if="currentUser && currentUser.avatar_url" :src="currentUser.avatar_url" alt="avatar" class="kid-avatar" />
          <div v-else class="kid-avatar placeholder">👧</div>
        </div>
        <div class="info">
          <h2 class="kid-name">{{ currentUser?.name || 'Bạn nhỏ' }}</h2>
          <div class="tags">
            <span class="tag points" :class="{ negative: totalPoints < 0 }">Điểm: {{ totalPoints }}</span>
            <span class="tag ach">Thành tích: {{ achievedCount }}/{{ achievementsCount }}</span>

          </div>
        </div>
      </div>

      <button class="refresh" @click="fetchSummary" :disabled="loadingSummary">{{ loadingSummary ? '...' : 'Làm mới' }}</button>
    </div>
    <div v-else class="loading-box">Đang tải...</div>
    <div v-if="error" class="error-box">{{ error }}</div>

    <h1 class="title">Kid Dashboard</h1>
    <div class="menu-grid">
      <router-link class="menu-item game" :to="{ name: 'GameSelection' }">
        <span class="label">Game</span>
      </router-link>
      <router-link class="menu-item requests" :to="{ name: 'KidRequests' }">
        <span class="label">Quản yêu cầu</span>
      </router-link>
      <router-link class="menu-item points" :to="{ name: 'KidPoints' }">
        <span class="label">Quản lý điểm</span>
      </router-link>
      <router-link class="menu-item stocks" :to="{ name: 'KidStocks' }">
        <span class="label">Quản lý Cổ phiếu</span>
      </router-link>
      <router-link class="menu-item achievements" :to="{ name: 'KidAchievements' }">
        <span class="label">Quản lý Thành Tích</span>
      </router-link>
      <router-link class="menu-item profile" :to="{ name: 'KidProfile' }">
        <span class="label">Sửa thông tin cá nhân</span>
      </router-link>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  name: 'KidDashboardV2',
  data(){
    return {
      loading: true,
      loadingSummary: false,
      error: '',
      totalPoints: 0,
      achievementsCount: 0,
      achievedCount: 0,
      currentUser: window.currentUser || null
    };
  },
  mounted(){
    this.fetchSummary();
  },
  methods:{
    async fetchSummary(){
      this.loadingSummary = true; this.error='';
      const promises = [
        axios.get('/api/kid/points').catch(e=>({ __error:e })),
        axios.get('/api/kid/achievements').catch(e=>({ __error:e }))
      ];
      try {
        const [pointsRes, achRes] = await Promise.all(promises);
        if(pointsRes.__error){ throw pointsRes.__error; }
        if(achRes.__error){ throw achRes.__error; }
        const pData = pointsRes.data || {};
        this.totalPoints = pData.total_points ?? 0;
        const achievements = Array.isArray(achRes.data) ? achRes.data : [];
        this.achievementsCount = achievements.length;
        this.achievedCount = achievements.filter(a=>a.achieved).length;
      } catch(e){
        console.error(e);
        this.error = e?.response?.data?.message || 'Không tải được dữ liệu tóm tắt';
      } finally {
        this.loading = false;
        this.loadingSummary = false;
      }
    }
  }
};
</script>

<style scoped>
.kid-dashboard-v2 { padding: 1.5rem; display:flex; flex-direction:column; gap:1.2rem; }
.summary { background:#ffffff; border:2px solid #eceef5; border-radius:20px; padding:1rem 1.1rem 1.2rem; display:flex; flex-direction:column; gap:.9rem; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
.kid-box { display:flex; gap:1rem; align-items:center; }
.avatar-wrap { width:80px; height:80px; flex:0 0 80px; }
.kid-avatar { width:100%; height:100%; object-fit:cover; border-radius:22px; border:3px solid #dfe3f0; background:#f2f5fa; font-size:2.2rem; display:flex; align-items:center; justify-content:center; }
.kid-avatar.placeholder { font-weight:700; }
.info { flex:1; display:flex; flex-direction:column; gap:.4rem; }
.kid-name { margin:0; font-size:1.35rem; line-height:1.2; }
.tags { display:flex; flex-wrap:wrap; gap:.5rem; }
.tag { font-size:.7rem; font-weight:700; letter-spacing:.5px; padding:.4rem .65rem; border-radius:999px; background:#eef2ff; }
.tag.points { background:#4caf50; color:#fff; }
.tag.points.negative { background:#e53935; }
.tag.ach { background:#ffcc5c; }
.mini-stats { display:flex; gap:.7rem; }
.stat-card { flex:1; background:#f6f8ff; border:2px solid #e2e6f3; border-radius:16px; padding:.55rem .6rem .7rem; display:flex; flex-direction:column; align-items:center; gap:.2rem; }
.stat-card .num { font-size:1.1rem; font-weight:700; }
.stat-card .label { font-size:.55rem; font-weight:600; letter-spacing:.6px; }
.refresh { align-self:flex-end; background:#2196f3; color:#fff; border:none; padding:.5rem .9rem; border-radius:12px; font-weight:600; cursor:pointer; font-size:.8rem; }
.loading-box { padding:1rem; text-align:center; }
.error-box { background:#ffe2e2; color:#c62828; padding:.6rem .8rem; border:2px solid #ffb3b3; border-radius:12px; font-size:.8rem; }
.title { margin: 0 0 0.2rem; font-size: 1.6rem; text-align: center; }
.menu-grid { display: grid; gap: 1rem; grid-template-columns: repeat(5, 1fr); }
.menu-item { display: flex; align-items: center; justify-content: center; text-align: center; background: #f5f5f7; border: 2px solid #e0e0e5; border-radius: 12px; padding: 1rem; min-height: 110px; text-decoration: none; color: #222; font-weight: 600; transition: background .2s, transform .15s; }
.menu-item:hover { background: #e9f3ff; }
.menu-item:active { transform: scale(.96); }
.menu-item.game { background:#ffe4b3; }
.menu-item.requests { background:#ffd6e8; }
.menu-item.points { background:#d4f4ff; }
.menu-item.stocks { background:#e3ffd4; }
.menu-item.achievements { background:#f5d9ff; }
.menu-item.profile { background:#fff3c4; }
.menu-item .label { line-height: 1.3; }
@media (max-width: 1100px) { .menu-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 700px) { .menu-grid { grid-template-columns: repeat(2, 1fr); } .kid-box { flex-direction:row; } .avatar-wrap { width:60px; height:60px; flex-basis:60px; } .kid-name { font-size:1.15rem; } .mini-stats { gap:.5rem; } .stat-card { padding:.45rem .5rem .55rem; } }
</style>
