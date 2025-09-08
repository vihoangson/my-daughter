<template>
  <div class="parent-dashboard-v2">
    <h1>Parent Dashboard</h1>
    <div class="menu-grid">
      <router-link class="menu-item" :to="{ name: 'ParentAddPoints' }">
        <span class="icon">➕</span>
        <span class="label">Cộng điểm</span>
      </router-link>
      <router-link class="menu-item" :to="{ name: 'ParentApproveRequests' }">
        <span class="icon">📝</span>
        <span class="label">Phê duyệt request</span>
      </router-link>
      <router-link class="menu-item" :to="{ name: 'ParentConfirmAchievements' }">
        <span class="icon">🏅</span>
        <span class="label">Xác nhận thành tích</span>
      </router-link>
      <router-link class="menu-item" :to="{ name: 'ParentEditInfo' }">
        <span class="icon">⚙️</span>
        <span class="label">Thay đổi thông tin</span>
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ParentDashboardV2'
};
</script>

<style scoped>
.parent-dashboard-v2 {
  padding: 1.5rem;
}
.parent-dashboard-v2 h1 {
  font-size: 1.8rem;
  margin: 0 0 1rem;
}
.menu-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fill,minmax(180px,1fr));
}
.menu-item {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 1.25rem .75rem;
  text-decoration: none;
  color: #222;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  box-shadow: 0 2px 4px rgba(0,0,0,.06);
  transition: background .15s, transform .15s, box-shadow .15s;
}
.menu-item:hover { background:#f5faff; transform: translateY(-2px); box-shadow:0 4px 10px rgba(0,0,0,.1); }
.menu-item:active { transform: translateY(0); }
.menu-item .icon { font-size: 2rem; margin-bottom: .5rem; }
.menu-item .label { text-align: center; line-height: 1.2; }
@media (prefers-color-scheme: dark) {
  .menu-item { background:#222; border-color:#333; color:#eee; }
  .menu-item:hover { background:#2a3340; }
}
</style>
