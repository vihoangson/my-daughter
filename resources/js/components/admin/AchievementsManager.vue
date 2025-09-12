<template>
  <section class="panel">
    <div class="card">
      <div class="card-header">
        <h3>Achievements</h3>
        <div class="ach-actions">
          <input class="search" v-model="achQuery" placeholder="Search..." />
          <button class="btn" @click="openCreateModal">New</button>
        </div>
      </div>
      <div class="toolbar">
        <button class="btn primary" @click="openCreateModal">Tạo mới</button>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th style="width:48px">#</th>
              <th>Name</th>
              <th style="width:160px">Category</th>
              <th>Note</th>
              <th style="width:100px">Image</th>
              <th style="width:170px"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="achLoading">
              <td colspan="6">Loading...</td>
            </tr>
            <tr v-for="(a, idx) in filteredAchievements" :key="a.id">
              <td>{{ idx + 1 }}</td>
              <td>{{ a.name }}</td>
              <td class="muted">{{ a.category || '-' }}</td>
              <td class="muted">{{ a.note?.slice(0,120) }}</td>
              <td>
                <img v-if="a.image_url" :src="a.image_url" alt="img" class="thumb" />
              </td>
              <td class="row-actions">
                <button class="btn" @click="editAchievement(a)">Edit</button>
                <button class="btn danger" @click="deleteAchievement(a)" :disabled="delBusyId === a.id">Delete</button>
              </td>
            </tr>
            <tr v-if="!achLoading && filteredAchievements.length === 0">
              <td colspan="6" class="muted">No achievements</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal for Create/Edit with AI suggestions -->
    <div v-if="modalOpen" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ achEditMode ? 'Edit Achievement' : 'Create Achievement' }}</h3>
          <button class="btn ghost" @click="closeModal">✕</button>
        </div>
        <div class="modal-body">
          <div class="modal-grid">
            <!-- Form -->
            <div class="modal-col">
              <div class="grid-2">
                <label>
                  <span>Name</span>
                  <input v-model="achForm.name" placeholder="Name" />
                </label>
                <label>
                  <span>Category</span>
                  <input v-model="achForm.category" placeholder="Category (optional)" />
                </label>
              </div>
              <div class="grid-1">
                <label>
                  <span>Note</span>
                  <input v-model="achForm.note" placeholder="Short note (optional)" />
                </label>
                <label>
                  <span>Image</span>
                  <input type="file" accept="image/*" @change="onImageChange" />
                </label>
                <label v-if="achEditMode && achForm.image_url" class="switch">
                  <input type="checkbox" v-model="achForm.remove_image" />
                  <span>Remove existing image</span>
                </label>
              </div>
            </div>

            <!-- AI Suggestions -->
            <div class="modal-col ai-panel">
              <div class="ai-header">
                <span>AI suggestions</span>
                <button class="btn small" :disabled="aiLoading" @click="aiSuggest">{{ aiLoading ? 'Thinking…' : 'Suggest' }}</button>
              </div>
              <label>
                <span>Describe what you want</span>
                <textarea v-model="aiPrompt" rows="4" placeholder="e.g., Weekly reading challenge for kids, 30 minutes per day"></textarea>
              </label>
              <div class="ai-suggestions" v-if="aiSuggestions">
                <div class="ai-item">
                  <div class="ai-label">Name</div>
                  <div class="ai-value">{{ aiSuggestions.name || '-' }}</div>
                  <button class="btn tiny" @click="applySuggestion('name', aiSuggestions.name)" :disabled="!aiSuggestions.name">Apply</button>
                </div>
                <div class="ai-item">
                  <div class="ai-label">Category</div>
                  <div class="ai-value">{{ aiSuggestions.category || '-' }}</div>
                  <button class="btn tiny" @click="applySuggestion('category', aiSuggestions.category)" :disabled="!aiSuggestions.category">Apply</button>
                </div>
                <div class="ai-item">
                  <div class="ai-label">Note</div>
                  <div class="ai-value">{{ aiSuggestions.note || '-' }}</div>
                  <button class="btn tiny" @click="applySuggestion('note', aiSuggestions.note)" :disabled="!aiSuggestions.note">Apply</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn" @click="closeModal" v-if="achEditMode">Cancel</button>
          <button class="btn primary" @click="saveAchievement" :disabled="saveBusy">
            {{ saveBusy ? 'Saving...' : (achEditMode ? 'Update' : 'Create') }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import axios from 'axios';
export default {
  name: 'AchievementsManager',
  data() {
    return {
      achLoading: false,
      achQuery: '',
      achievements: [],
      achForm: { id: null, name: '', category: '', note: '', image: null, image_url: null, remove_image: false },
      achEditMode: false,
      saveBusy: false,
      delBusyId: null,
      modalOpen: false,
      aiPrompt: '',
      aiLoading: false,
      aiSuggestions: null,
    };
  },
  computed: {
    filteredAchievements() {
      const q = this.achQuery.trim().toLowerCase();
      if (!q) return this.achievements;
      return this.achievements.filter(a =>
        (a.name || '').toLowerCase().includes(q) ||
        (a.category || '').toLowerCase().includes(q) ||
        (a.note || '').toLowerCase().includes(q)
      );
    },
  },
  mounted() {
    this.loadAchievements();
  },
  methods: {
    async loadAchievements() {
      this.achLoading = true;
      try {
        const { data } = await axios.get('/api/achievements');
        this.achievements = Array.isArray(data) ? data : (data.data || []);
      } catch (e) {
        console.error(e);
        alert('Failed to load achievements');
      } finally {
        this.achLoading = false;
      }
    },
    resetAchForm() {
      this.achForm = { id: null, name: '', category: '', note: '', image: null, image_url: null, remove_image: false };
      this.achEditMode = false;
    },
    onImageChange(e) {
      this.achForm.image = e.target.files && e.target.files[0] ? e.target.files[0] : null;
    },
    openCreateModal() {
      this.resetAchForm();
      this.modalOpen = true;
    },
    closeModal() {
      this.modalOpen = false;
    },
    editAchievement(a) {
      this.achForm = { id: a.id, name: a.name, category: a.category || '', note: a.note || '', image: null, image_url: a.image_url || null, remove_image: false };
      this.achEditMode = true;
      this.modalOpen = true;
    },
    applySuggestion(field, value) {
      if (!value) return;
      this.achForm[field] = value;
    },
    async aiSuggest() {
      this.aiLoading = true;
      try {
        // Try backend if available
        const prompt = this.aiPrompt || this.achForm.name || this.achForm.note || 'achievement idea';
        try {
          const { data } = await axios.post('/api/ai/suggest-achievement', { prompt });
          if (data && (data.name || data.category || data.note)) {
            this.aiSuggestions = data;
            return;
          }
        } catch(e) { /* fall back to local */ }

        // Local heuristic fallback
        const text = (this.aiPrompt || this.achForm.name || '').toLowerCase();
        const cat = text.includes('read') || text.includes('book') ? 'Reading' :
                    text.includes('math') ? 'Math' :
                    text.includes('music') ? 'Music' :
                    text.includes('puzzle') ? 'Puzzle' : 'General';
        const cap = s => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
        const name = this.achForm.name || cap((this.aiPrompt || 'New Achievement').slice(0, 40));
        const note = this.achForm.note || `Complete the ${cat.toLowerCase()} task consistently this week. Parent can adjust points/reward accordingly.`;
        this.aiSuggestions = { name, category: cat, note };
      } finally {
        this.aiLoading = false;
      }
    },
    async saveAchievement() {
      if (!this.achForm.name) return alert('Name is required');
      this.saveBusy = true;
      try {
        if (this.achEditMode && this.achForm.id) {
          const fd = this.buildFormData();
          fd.append('_method', 'PUT');
          await axios.post(`/api/achievements/${this.achForm.id}`, fd);
        } else {
          const fd = this.buildFormData();
          await axios.post('/api/achievements', fd);
        }
        await this.loadAchievements();
        this.resetAchForm();
        this.modalOpen = false;
      } catch (e) {
        console.error(e);
        alert('Failed to save achievement');
      } finally {
        this.saveBusy = false;
      }
    },
    async deleteAchievement(a) {
      if (!confirm(`Delete achievement "${a.name}"?`)) return;
      this.delBusyId = a.id;
      try {
        await axios.delete(`/api/achievements/${a.id}`);
        this.achievements = this.achievements.filter(x => x.id !== a.id);
      } catch (e) {
        console.error(e);
        alert('Failed to delete achievement');
      } finally {
        this.delBusyId = null;
      }
    },
    buildFormData() {
      const fd = new FormData();
      fd.append('name', this.achForm.name);
      if (this.achForm.category) fd.append('category', this.achForm.category);
      if (this.achForm.note) fd.append('note', this.achForm.note);
      if (this.achForm.image) fd.append('image', this.achForm.image);
      if (this.achEditMode && this.achForm.remove_image) fd.append('remove_image', '1');
      return fd;
    },
  }
};
</script>

<style scoped>
.card { background: var(--panel); border: 1px solid var(--border); border-radius: 14px; padding: 16px; }
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.search { background: var(--panel); border: 1px solid var(--border); color: var(--text); padding: 8px 12px; border-radius: 8px; min-width: 220px; }
.btn { background: var(--panel); border: 1px solid var(--border); color: var(--text); padding: 8px 12px; border-radius: 8px; cursor: pointer; transition: .2s ease; }
.btn:hover { transform: translateY(-1px); background: var(--hover); }
.btn.primary { background: var(--primary); border-color: var(--primary-600); color: #fff; }
.btn.danger { border-color: #fecaca; color: #b91c1c; }
.table-responsive { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border-bottom: 1px solid var(--border); padding: 10px; text-align: left; }
.table thead th { font-weight: 600; color: var(--muted); }
.row-actions { display: flex; gap: 8px; }
.thumb { width: 56px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); }
.grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.grid-1 { display: grid; gap: 12px; }
label { display: grid; gap: 6px; color: var(--muted); font-size: 13px; }
.switch { display: flex; align-items: center; gap: 10px; }
.actions-end { display: flex; align-items: end; justify-content: end; }
.muted { color: var(--muted); }

/***** Modal *****/
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.35); display: grid; place-items: center; z-index: 50; }
.modal { width: min(980px, 96vw); background: var(--panel); border: 1px solid var(--border); border-radius: 14px; box-shadow: 0 20px 50px rgba(0,0,0,.25); display: grid; grid-template-rows: auto 1fr auto; max-height: 90vh; }
.modal-header, .modal-footer { padding: 14px 16px; border-bottom: 1px solid var(--border); }
.modal-footer { border-top: 1px solid var(--border); border-bottom: none; display: flex; justify-content: flex-end; gap: 8px; }
.modal-body { padding: 16px; overflow: auto; }
.modal-grid { display: grid; grid-template-columns: 1fr 320px; gap: 16px; }
.modal-col { display: grid; gap: 12px; align-content: start; }
.ai-panel { background: var(--bg); border: 1px dashed var(--border); border-radius: 10px; padding: 10px; }
.ai-header { display: flex; justify-content: space-between; align-items: center; font-weight: 600; color: var(--muted); }
.ai-item { background: var(--panel); border: 1px solid var(--border); border-radius: 8px; padding: 8px; display: grid; gap: 6px; margin-top: 8px; }
.ai-label { font-size: 12px; color: var(--muted); }
.ai-value { font-size: 13px; }
textarea { background: var(--panel); border: 1px solid var(--border); color: var(--text); padding: 10px 12px; border-radius: 10px; }
.btn.small { padding: 6px 10px; font-size: 12px; }
.btn.tiny { padding: 4px 8px; font-size: 12px; }
.toolbar { display: flex; gap: 8px; margin: 8px 0 12px; }
</style>
