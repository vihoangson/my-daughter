<template>
  <div class="admin-overview">
    <h2>Overview</h2>

    <div v-if="loading" class="card">Loading system statistics...</div>
    <div v-else class="grid">
      <div class="card">
        <div class="title">Families</div>
        <div class="value">{{ stats.families ?? 0 }}</div>
      </div>
      <div class="card">
        <div class="title">Users</div>
        <div class="value">{{ stats.users ?? 0 }}</div>
      </div>
      <div class="card">
        <div class="title">Requests (Total)</div>
        <div class="value">{{ stats.requests_total ?? 0 }}</div>
      </div>
      <div class="card">
        <div class="title">Requests Today</div>
        <div class="value">{{ stats.requests_today ?? 0 }}</div>
      </div>
      <div class="card">
        <div class="title">Achievements</div>
        <div class="value">{{ stats.achievements ?? 0 }}</div>
      </div>
      <div class="card">
        <div class="title">Pending Approvals</div>
        <div class="value">{{ (stats.requests_by_status && stats.requests_by_status.pending) ? stats.requests_by_status.pending : 0 }}</div>
      </div>
    </div>

    <div v-if="error" class="card" style="border-color:#fecaca;color:#b91c1c;">
      {{ error }}
    </div>

    <div class="card wide">
      <div class="header">
        <h3>AI Suggestion</h3>
      </div>
      <div class="ai-form">
        <input
          v-model="aiPrompt"
          type="text"
          class="ai-input"
          placeholder="Describe a goal for kids (e.g., Daily reading challenge)"
        />
        <button class="ai-btn" :disabled="aiLoading" @click="suggestAi">
          {{ aiLoading ? 'Generating...' : 'Suggest via AI' }}
        </button>
      </div>
      <div v-if="aiResult" class="ai-result">
        <div class="ai-row"><strong>Name:</strong> <span>{{ aiResult.name }}</span></div>
        <div class="ai-row"><strong>Category:</strong> <span>{{ aiResult.category }}</span></div>
        <div class="ai-row"><strong>Note:</strong> <span>{{ aiResult.note }}</span></div>
      </div>
    </div>

    <div class="card wide">
      <div class="header">
        <h3>Recent Activity</h3>
      </div>
      <ul class="activity">
        <li>
          <span class="dot success" />
          <div class="main">
            <div class="t">User John created a new family group</div>
            <div class="m">2m ago</div>
          </div>
        </li>
        <li>
          <span class="dot warning" />
          <div class="main">
            <div class="t">3 failed login attempts detected</div>
            <div class="m">18m ago</div>
          </div>
        </li>
        <li>
          <span class="dot info" />
          <div class="main">
            <div class="t">System backup completed</div>
            <div class="m">1h ago</div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  name: 'AdminOverview',
  data() {
    return {
      loading: false,
      error: '',
      stats: {
        families: 0,
        users: 0,
        requests_total: 0,
        requests_today: 0,
        achievements: 0,
        requests_by_status: null,
      },
      aiPrompt: '',
      aiLoading: false,
      aiResult: null,
    };
  },
  mounted() {
    this.loadStats();
  },
  methods: {
    async loadStats() {
      this.loading = true;
      this.error = '';
      try {
        const { data } = await axios.get('/api/admin/system-stats');
        this.stats = data || this.stats;
      } catch (e) {
        this.error = 'Failed to load system statistics.';
      } finally {
        this.loading = false;
      }
    },
    async suggestAi() {
      if (this.aiLoading) return;
      this.aiLoading = true;
      this.aiResult = null;
      try {
        const { data } = await axios.post('/api/ai/suggest-achievement', { prompt: this.aiPrompt });
        this.aiResult = data;
      } catch (e) {
        this.aiResult = { name: 'New Achievement', category: 'General', note: 'Try setting a simple, trackable goal for this week.' };
      } finally {
        this.aiLoading = false;
      }
    },
  },
};
</script>

<style scoped>
.admin-overview { display: grid; gap: 16px; }
.grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.card { background: var(--panel, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 12px; padding: 16px; }
.card.wide { grid-column: 1 / -1; }
.title { color: var(--muted, #64748b); font-size: 12px; }
.value { font-size: 24px; font-weight: 600; margin-top: 6px; }
.activity { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
.activity li { display: grid; grid-template-columns: 12px 1fr; gap: 10px; }
.dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 7px; }
.dot.success { background: #16a34a; }
.dot.warning { background: #d97706; }
.dot.info { background: #0284c7; }

.ai-form { display: grid; grid-template-columns: 1fr auto; gap: 10px; margin-bottom: 12px; }
.ai-input { background: var(--panel, #fff); border: 1px solid var(--border, #e5e7eb); padding: 10px 12px; border-radius: 10px; }
.ai-btn { background: var(--primary, #3b82f6); border: 1px solid var(--primary-600, #2563eb); color: #fff; padding: 10px 14px; border-radius: 10px; cursor: pointer; }
.ai-btn[disabled] { opacity: .6; cursor: not-allowed; }
.ai-result { display: grid; gap: 6px; }
.ai-row strong { display: inline-block; width: 90px; color: var(--muted, #64748b); }

@media (max-width: 1024px) { .grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .grid { grid-template-columns: 1fr; } }
</style>
