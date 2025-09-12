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
      <div class="ai-form ai-grid">
        <textarea
          v-model="question"
          rows="3"
          class="ai-input"
          placeholder="Ask a concise question (e.g., What’s a good weekly learning goal for 8-year-olds?)"
        />
        <div class="ai-actions">
          <button class="ai-btn" :disabled="aiLoading || !question.trim()" @click="askAi">
            {{ aiLoading ? 'Thinking…' : 'Ask AI' }}
          </button>
          <button class="btn ghost" :disabled="aiLoading && !aiAnswer" @click="clearAi">Clear</button>
        </div>
      </div>

      <div v-if="aiLoading" class="ai-skeleton">
        <div class="line" />
        <div class="line w-80" />
        <div class="line w-60" />
      </div>

      <div v-if="aiError" class="ai-error">{{ aiError }}</div>

      <div v-if="aiAnswer && !aiLoading" class="ai-result chat">
        <div class="bubble">
          <pre class="answer">{{ aiAnswer }}</pre>
          <div class="row">
            <small class="muted">AI response</small>
            <button class="btn ghost sm" @click="copyAnswer">Copy</button>
          </div>
        </div>
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
      question: '',
      aiLoading: false,
      aiAnswer: '',
      aiError: '',
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
    async askAi() {
      if (this.aiLoading) return;
      this.aiLoading = true;
      this.aiError = '';
      this.aiAnswer = '';
      try {
        const { data } = await axios.post('/api/ai/answer', { question: this.question });
        this.aiAnswer = (data && data.answer) ? data.answer : '';
        if (!this.aiAnswer) this.aiError = 'No answer returned.';
      } catch (e) {
        this.aiError = 'Failed to get AI answer.';
      } finally {
        this.aiLoading = false;
      }
    },
    clearAi() {
      this.question = '';
      this.aiAnswer = '';
      this.aiError = '';
    },
    async copyAnswer() {
      try {
        await navigator.clipboard.writeText(this.aiAnswer || '');
      } catch (_) { /* noop */ }
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
textarea.ai-input { resize: vertical; min-height: 88px; }
.ai-btn { background: var(--primary, #3b82f6); border: 1px solid var(--primary-600, #2563eb); color: #fff; padding: 10px 14px; border-radius: 10px; cursor: pointer; }
.ai-btn[disabled] { opacity: .6; cursor: not-allowed; }
.ai-result { display: grid; gap: 6px; }
.ai-row strong { display: inline-block; width: 90px; color: var(--muted, #64748b); }

.ai-grid { display: grid; gap: 10px; }
.ai-actions { display: flex; gap: 10px; }
.ai-skeleton { display: grid; gap: 8px; margin-top: 10px; }
.ai-skeleton .line { height: 12px; background: linear-gradient(90deg, rgba(148,163,184,.2), rgba(148,163,184,.35), rgba(148,163,184,.2)); background-size: 200% 100%; animation: shimmer 1.2s infinite; border-radius: 6px; }
.ai-skeleton .line.w-80 { width: 80%; }
.ai-skeleton .line.w-60 { width: 60%; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.ai-error { color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; padding: 10px; border-radius: 8px; margin-top: 10px; }
.chat .bubble { background: var(--hover, #f8fafc); border: 1px solid var(--border, #e5e7eb); border-radius: 12px; padding: 12px; }
.chat .answer { white-space: pre-wrap; margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; }
.chat .row { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; }
.muted { color: var(--muted, #64748b); }
.btn.sm { padding: 6px 8px; font-size: 12px; border-radius: 6px; }

@media (max-width: 1024px) { .grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .grid { grid-template-columns: 1fr; } }
</style>
