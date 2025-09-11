<template>
  <div class="admin-setting" :class="themeClass">
    <!-- Top Bar -->
    <header class="topbar">
      <h1 class="brand">Admin Center</h1>
      <div class="actions">
        <input class="search" type="search" placeholder="Search settings..." />
        <button class="btn primary">Save</button>
      </div>
    </header>

    <div class="layout">
      <!-- Sidebar -->
      <aside class="sidebar">
        <nav>
          <ul>
            <li :class="{ active: activeTab === 'overview' }" @click="activeTab = 'overview'">
              <span>Overview</span>
            </li>
            <li :class="{ active: activeTab === 'users' }" @click="activeTab = 'users'">
              <span>Users</span>
            </li>
            <li :class="{ active: activeTab === 'security' }" @click="activeTab = 'security'">
              <span>Security</span>
            </li>
            <li :class="{ active: activeTab === 'preferences' }" @click="activeTab = 'preferences'">
              <span>Preferences</span>
            </li>
            <!-- New: Achievements management -->
            <li :class="{ active: activeTab === 'achievements' }" @click="activeTab = 'achievements'">
              <span>Achievements</span>
            </li>
          </ul>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="content">
        <!-- Overview -->
        <section v-if="activeTab === 'overview'" class="panel-grid">
          <div class="card stat">
            <div class="stat-title">Active Users</div>
            <div class="stat-value">1,248</div>
            <div class="stat-trend up">+4.2% this week</div>
          </div>
          <div class="card stat">
            <div class="stat-title">Requests Today</div>
            <div class="stat-value">312</div>
            <div class="stat-trend down">-1.1% vs yesterday</div>
          </div>
          <div class="card stat">
            <div class="stat-title">Errors</div>
            <div class="stat-value">3</div>
            <div class="stat-trend neutral">Stable</div>
          </div>
          <div class="card stat">
            <div class="stat-title">Avg. Response</div>
            <div class="stat-value">182ms</div>
            <div class="stat-trend up">+2.4% faster</div>
          </div>

          <div class="card wide">
            <div class="card-header">
              <h3>Recent Activity</h3>
              <button class="btn ghost" @click="mockRefresh">Refresh</button>
            </div>
            <ul class="activity">
              <li v-for="(item, i) in activities" :key="i">
                <span class="dot" :class="item.type" />
                <div class="activity-main">
                  <div class="title">{{ item.title }}</div>
                  <div class="meta">{{ item.time }}</div>
                </div>
              </li>
            </ul>
          </div>
        </section>

        <!-- Users -->
        <section v-else-if="activeTab === 'users'" class="panel">
          <div class="card form">
            <h3>User Management</h3>
            <div class="grid-2">
              <label>
                <span>Default Role</span>
                <select v-model="form.defaultRole">
                  <option value="kid">Kid</option>
                  <option value="parent">Parent</option>
                  <option value="admin">Admin</option>
                </select>
              </label>
              <label>
                <span>Max Kids per Family</span>
                <input type="number" min="1" v-model.number="form.maxKids" />
              </label>
            </div>
            <div class="grid-2">
              <label>
                <span>Invite by Email</span>
                <input type="email" v-model="form.inviteEmail" placeholder="name@example.com" />
              </label>
              <div class="actions-end">
                <button class="btn" @click="sendInvite">Send Invite</button>
              </div>
            </div>
          </div>
        </section>

        <!-- Security -->
        <section v-else-if="activeTab === 'security'" class="panel">
          <div class="card form">
            <h3>Security</h3>
            <div class="grid-2">
              <label>
                <span>Require 2FA</span>
                <select v-model="form.require2FA">
                  <option :value="true">Enabled</option>
                  <option :value="false">Disabled</option>
                </select>
              </label>
              <label>
                <span>Session Timeout (mins)</span>
                <input type="number" min="5" v-model.number="form.sessionTimeout" />
              </label>
            </div>
            <div class="grid-1">
              <label class="switch">
                <input type="checkbox" v-model="form.lockdownMode" />
                <span>Lockdown Mode</span>
              </label>
            </div>
          </div>
        </section>

        <!-- Achievements Management -->
        <section v-else-if="activeTab === 'achievements'" class="panel">
          <AchievementsManager />
        </section>

        <!-- Preferences -->
        <section v-else-if="activeTab === 'preferences'" class="panel">
          <div class="card form">
            <h3>Preferences</h3>
            <div class="grid-2">
              <label>
                <span>Theme</span>
                <select v-model="form.theme">
                  <option value="light">Light</option>
                  <option value="dark">Dark</option>
                  <option value="system">System</option>
                </select>
              </label>
              <label>
                <span>Language</span>
                <select v-model="form.language">
                  <option value="en">English</option>
                  <option value="vi">Tiếng Việt</option>
                </select>
              </label>
            </div>
          </div>
        </section>
          <!--              todo: section quản lý thành tích hiển thị curd thành tích chung của hệ thống-->
      </main>
    </div>
  </div>
</template>

<script>
import themeConfig from '../../config/theme';
import axios from 'axios';
import AchievementsManager from './AchievementsManager.vue';
export default {
  name: 'AdminSetting',
  components: { AchievementsManager },
  data() {
    return {
      activeTab: 'overview',
      activities: [
        { title: 'User John created a new family group', time: '2m ago', type: 'success' },
        { title: '3 failed login attempts detected', time: '18m ago', type: 'warning' },
        { title: 'System backup completed', time: '1h ago', type: 'info' },
      ],
      form: {
        defaultRole: 'parent',
        maxKids: 3,
        inviteEmail: '',
        require2FA: true,
        sessionTimeout: 30,
        lockdownMode: false,
        theme: 'light',
        language: 'en',
      },
      theme: themeConfig.theme || 'light',
    };
  },
  computed: {
    themeClass() {
      return this.theme === 'light' ? 'theme-light' : 'theme-dark';
    },
  },
  methods: {
    mockRefresh() {
      this.activities.unshift({
        title: 'Settings synced',
        time: 'just now',
        type: 'info',
      });
    },
    sendInvite() {
      if (!this.form.inviteEmail) return alert('Please enter an email');
      alert(`Invite sent to ${this.form.inviteEmail}`);
      this.form.inviteEmail = '';
    },
  }
};
</script>

<style scoped>
/* Theme variables */
.theme-light {
  --bg: #f8fafc;
  --panel: #ffffff;
  --muted: #64748b;
  --text: #0f172a;
  --primary: #3b82f6;
  --primary-600: #2563eb;
  --border: #e2e8f0;
  --success: #16a34a;
  --warning: #d97706;
  --info: #0284c7;
  --danger: #dc2626;
  --hover: #f1f5f9;
}
.theme-dark {
  --bg: #0b1220;
  --panel: #111827;
  --muted: #94a3b8;
  --text: #e5e7eb;
  --primary: #6366f1;
  --primary-600: #5457e0;
  --border: #1f2937;
  --success: #22c55e;
  --warning: #f59e0b;
  --info: #3b82f6;
  --danger: #ef4444;
  --hover: #0b1324;
}

.admin-setting { background: var(--bg); min-height: 100vh; color: var(--text); }
.topbar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid var(--border); background: var(--panel); backdrop-filter: blur(8px); }
.brand { font-size: 18px; letter-spacing: .5px; }
.actions { display: flex; gap: 12px; align-items: center; }
.search { background: var(--panel); border: 1px solid var(--border); color: var(--text); padding: 8px 12px; border-radius: 8px; min-width: 220px; }
.btn { background: var(--panel); border: 1px solid var(--border); color: var(--text); padding: 8px 12px; border-radius: 8px; cursor: pointer; transition: .2s ease; }
.btn:hover { transform: translateY(-1px); background: var(--hover); }
.btn.primary { background: var(--primary); border-color: var(--primary-600); color: #fff; }
.btn.ghost { background: transparent; }

.layout { display: grid; grid-template-columns: 240px 1fr; gap: 0; }
.sidebar { border-right: 1px solid var(--border); padding: 16px; background: var(--panel); min-height: calc(100vh - 56px); }
.sidebar ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; }
.sidebar li { padding: 10px 12px; border-radius: 8px; color: var(--muted); cursor: pointer; }
.sidebar li.active, .sidebar li:hover { background: var(--hover); color: var(--text); }

.content { padding: 20px; display: grid; gap: 20px; }
.panel-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 16px; }
.card { background: var(--panel); border: 1px solid var(--border); border-radius: 14px; padding: 16px; }
.stat { grid-column: span 3; }
.wide { grid-column: span 12; }
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.stat-title { color: var(--muted); font-size: 12px; letter-spacing: .4px; }
.stat-value { font-size: 26px; font-weight: 600; margin-top: 6px; color: var(--text); }
.stat-trend { margin-top: 4px; font-size: 12px; }
.stat-trend.up { color: var(--success); }
.stat-trend.down { color: var(--danger); }
.stat-trend.neutral { color: var(--muted); }

.activity { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
.activity li { display: grid; grid-template-columns: 12px 1fr; gap: 10px; align-items: start; }
.dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 7px; }
.dot.success { background: var(--success); }
.dot.warning { background: var(--warning); }
.dot.info { background: var(--info); }

.panel .form { display: grid; gap: 16px; }
.grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.grid-1 { display: grid; gap: 12px; }
label { display: grid; gap: 6px; color: var(--muted); font-size: 13px; }
input, select { background: var(--panel); border: 1px solid var(--border); color: var(--text); padding: 10px 12px; border-radius: 10px; }
.switch { display: flex; align-items: center; gap: 10px; }
.actions-end { display: flex; align-items: end; justify-content: end; }

.table-responsive { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border-bottom: 1px solid var(--border); padding: 10px; text-align: left; }
.table thead th { font-weight: 600; color: var(--muted); }
.row-actions { display: flex; gap: 8px; }
.badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 12px; }
.badge-success { background: rgba(22,163,74,.12); color: #15803d; }
.badge-muted { background: rgba(100,116,139,.12); color: #475569; }
.btn.danger { border-color: #fecaca; color: #b91c1c; }
.thumb { width: 56px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); }

@media (max-width: 1024px) {
  .stat { grid-column: span 6; }
}
@media (max-width: 640px) {
  .layout { grid-template-columns: 1fr; }
  .sidebar { min-height: auto; order: 2; }
  .content { order: 1; }
  .stat { grid-column: span 12; }
}
</style>
