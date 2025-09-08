<template>
  <div class="kid-dashboard-v2">
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
export default {
  name: 'KidDashboardV2'
};
</script>

<style scoped>
.kid-dashboard-v2 { padding: 1.5rem; }
.title { margin: 0 0 1.25rem; font-size: 1.6rem; text-align: center; }
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
@media (max-width: 700px) { .menu-grid { grid-template-columns: repeat(2, 1fr); } }
</style>
