<template>
  <div class="container py-4">
    <h2 class="mb-3">Phân loại yêu cầu</h2>
    <p class="text-muted small mb-4">Kéo thả mỗi request vào cột "Thứ cần" hoặc "Thứ muốn" để lưu classification.</p>

    <div v-if="loading" class="alert alert-info py-2">Đang tải...</div>
    <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>

    <div v-if="unclassifiedRequests.length" class="mb-4">
      <h5 class="mb-2">Các request chưa phân loại</h5>
      <div class="list-group">
        <div v-for="r in unclassifiedRequests" :key="r.id" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center draggable-item" draggable="true"
             @dragstart="onDragStart(r)">
          <div>
            <strong>{{ r.title }}</strong>
            <div class="small text-muted">Loại: {{ r.type }} • Trạng thái: {{ r.status }}</div>
          </div>
          <span class="badge bg-secondary">Chưa</span>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-6">
        <div class="classification-column" :class="{ 'dropping': dropTarget==='need' }" @dragover.prevent="onDragOver('need')" @dragleave="onDragLeave" @drop.prevent="onDrop('need')">
          <h5 class="text-center mb-3">Thứ cần</h5>
          <transition-group name="fade" tag="div" class="d-flex flex-column gap-2">
            <div v-for="r in needRequests" :key="r.id" class="card p-2 position-relative">
              <button type="button" class="btn-close position-absolute top-0 end-0 mt-1 me-1 btn-sm" aria-label="Reset" @click="resetClassification(r)"></button>
              <div class="fw-bold">{{ r.title }}</div>
              <div class="small text-muted">{{ r.description || '—' }}</div>
            </div>
          </transition-group>
          <div v-if="!needRequests.length" class="text-center text-muted small py-3">Kéo thả vào đây</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="classification-column" :class="{ 'dropping': dropTarget==='want' }" @dragover.prevent="onDragOver('want')" @dragleave="onDragLeave" @drop.prevent="onDrop('want')">
          <h5 class="text-center mb-3">Thứ muốn</h5>
          <transition-group name="fade" tag="div" class="d-flex flex-column gap-2">
            <div v-for="r in wantRequests" :key="r.id" class="card p-2 position-relative">
              <button type="button" class="btn-close position-absolute top-0 end-0 mt-1 me-1 btn-sm" aria-label="Reset" @click="resetClassification(r)"></button>
              <div class="fw-bold">{{ r.title }}</div>
              <div class="small text-muted">{{ r.description || '—' }}</div>
            </div>
          </transition-group>
          <div v-if="!wantRequests.length" class="text-center text-muted small py-3">Kéo thả vào đây</div>
        </div>
      </div>
    </div>

    <div class="mt-4">
      <button class="btn btn-outline-secondary btn-sm" @click="reload" :disabled="loading">Tải lại</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'KidRequestClassifier',
  data() {
    return {
      all: [],
      loading: false,
      error: null,
      dragItem: null,
      dropTarget: null,
    };
  },
  computed: {
    unclassifiedRequests() { return this.all.filter(r => !r.classification); },
    needRequests() { return this.all.filter(r => r.classification === 'need'); },
    wantRequests() { return this.all.filter(r => r.classification === 'want'); },
  },
  created() {
    this.load();
  },
  methods: {
    async load() {
      this.loading = true; this.error = null;
      try {
        const { data } = await axios.get('/api/kid/requests');
        this.all = data;
      } catch (e) {
        this.error = 'Không tải được danh sách';
      } finally { this.loading = false; }
    },
    reload() { this.load(); },
    onDragStart(r) { this.dragItem = r; },
    onDragOver(target) { this.dropTarget = target; },
    onDragLeave() { this.dropTarget = null; },
    async onDrop(target) {
      if (!this.dragItem) return;
      const item = this.dragItem;
      this.dragItem = null; this.dropTarget = null;
      const previous = item.classification;
      item.classification = target; // optimistic
      try {
        await axios.post(`/api/kid/requests/${item.id}/classify`, { classification: target });
      } catch (e) {
        item.classification = previous;
        alert('Lỗi khi lưu classification');
      }
    },
    async resetClassification(r) {
      const previous = r.classification;
      r.classification = null;
      try {
        await axios.post(`/api/kid/requests/${r.id}/classify`, { classification: previous === 'need' ? 'want' : 'need' });
        // Immediately flip twice to effectively unset? Instead reload list.
        await this.load();
      } catch (e) {
        r.classification = previous;
      }
    }
  }
};
</script>

<style scoped>
.classification-column { border: 2px dashed #adb5bd; padding: 12px; min-height: 250px; border-radius: 8px; background: #f8f9fa; transition: background .2s, border-color .2s; }
.classification-column.dropping { background: #e7f5ff; border-color: #339af0; }
.draggable-item { cursor: grab; }
.fade-enter-active, .fade-leave-active { transition: all .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(4px); }
</style>

