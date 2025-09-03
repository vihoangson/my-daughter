<template>
  <div class="kid-achievements mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Huy hiệu thành tích</h5>
      <button class="btn btn-sm btn-outline-secondary" @click="load" :disabled="loading">
        <span v-if="loading" class="spinner-border spinner-border-sm me-1" />Tải lại
      </button>
    </div>
    <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
    <div v-if="!loading && achievements.length===0" class="text-muted fst-italic">Chưa có thành tích nào từ phụ huynh.</div>
    <div class="row g-3">
      <div v-for="a in achievements" :key="a.id" class="col-6 col-sm-4 col-md-3 col-lg-2">
        <div class="achievement-tile card h-100 text-center p-2" :class="{'not-achieved': !a.achieved}">
          <div class="ratio ratio-1x1 mb-2">
            <img v-if="a.image_url" :src="a.image_url" :alt="a.name" class="img-fluid rounded" />
            <div v-else class="placeholder-icon d-flex flex-column justify-content-center align-items-center rounded">
              <span class="fw-bold" style="font-size:11px">NO IMG</span>
            </div>
          </div>
          <div class="name small fw-semibold" :title="a.name">{{ a.name }}</div>
          <div class="note text-muted" v-if="a.note" :title="a.note">{{ shortNote(a.note) }}</div>
          <div class="status mt-1">
            <span v-if="a.achieved" class="badge text-bg-success">Đã đạt</span>
            <span v-else class="badge text-bg-secondary">Chưa đạt</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const achievements = ref([]);
const loading = ref(false);
const error = ref('');

const load = async () => {
  loading.value=true; error.value='';
  try {
    const { data } = await axios.get('/api/kid/achievements');
    achievements.value = data;
  } catch(e){
    error.value = e.response?.data?.message || 'Không tải được danh sách';
  } finally { loading.value=false; }
};

const shortNote = (n) => {
  if(!n) return '';
  return n.length>28 ? n.slice(0,25)+'...' : n;
};

onMounted(load);
</script>
<style scoped>
.achievement-tile { transition: .25s; border:1px solid #e3e6ef; box-shadow:0 1px 2px rgba(0,0,0,.05); }
.achievement-tile.not-achieved { filter: grayscale(1) brightness(.75); opacity:.7; }
.achievement-tile.not-achieved:hover { filter: grayscale(.8) brightness(.85); }
.achievement-tile img { object-fit:cover; width:100%; height:100%; }
.placeholder-icon { background: repeating-linear-gradient(45deg,#f3f3f3,#f3f3f3 6px,#ececec 6px,#ececec 12px); font-size:10px; }
.ratio { position:relative; width:100%; }
.ratio:before { content:""; display:block; padding-top:100%; }
.ratio > * { position:absolute; inset:0; }
</style>

