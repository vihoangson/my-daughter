<template>
  <div v-if="modelValue && request" class="modal d-block" style="background-color: rgba(0,0,0,0.7); z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ request.title }}</h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>
        <div class="modal-body">
          <div class="text-center mb-3">
            <img :src="request.image_url" :alt="request.title" class="img-fluid rounded" style="max-height:60vh;object-fit:contain;" />
          </div>
          <div class="row g-3 small">
            <div class="col-md-6">
              <strong>Loại:</strong>
              <span :class="typeClass" class="badge ms-1">{{ typeLabel }}</span>
            </div>
            <div class="col-md-6">
              <strong>Trạng thái:</strong>
              <span class="badge ms-1" :class="statusClass">{{ request.status }}</span>
            </div>
            <div class="col-12">
              <strong>Mô tả:</strong>
              <div class="mt-1">{{ request.description || 'Không có mô tả' }}</div>
            </div>
            <div class="col-md-6">
              <strong>Ngày tạo:</strong>
              <div class="mt-1">{{ formatDate(request.created_at) }}</div>
            </div>
            <div class="col-md-6" v-if="request.scheduled_time">
              <strong>Lịch dự kiến:</strong>
              <div class="mt-1">{{ formatDate(request.scheduled_time) }}</div>
            </div>
            <div class="col-12" v-if="request.parent_note">
              <strong>Ghi chú phụ huynh:</strong>
              <div class="mt-1">{{ request.parent_note }}</div>
            </div>
          </div>
          <slot name="extra" :request="request"></slot>
        </div>
        <div class="modal-footer">
            <slot name="actions" :close="close" :request="request">
              <button type="button" class="btn btn-secondary" @click="close">Đóng</button>
            </slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  request: { type: Object, default: null }
});
const emit = defineEmits(['update:modelValue','closed']);

const close = () => {
  emit('update:modelValue', false);
  emit('closed');
};

const formatDate = (d) => {
  if(!d) return '-';
  const dt = new Date(d);
  return dt.toLocaleDateString('vi-VN') + ' ' + dt.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
};

const typeLabel = computed(()=>{
  if(!props.request) return '';
  const map = { toy:'Đồ chơi', food:'Món ăn', playground:'Khu vui chơi', activity:'Hoạt động' };
  return map[props.request.type] || props.request.type;
});
const typeClass = computed(()=>{
  if(!props.request) return 'bg-secondary';
  const map = { toy:'bg-primary', food:'bg-success', playground:'bg-info', activity:'bg-warning' };
  return map[props.request.type] || 'bg-secondary';
});
const statusClass = computed(()=>{
  if(!props.request) return 'bg-secondary';
  const s = props.request.status;
  if(s==='pending') return 'bg-warning text-dark';
  if(s==='approved') return 'bg-success';
  if(s==='rejected') return 'bg-danger';
  if(s==='completed') return 'bg-info';
  return 'bg-secondary';
});
</script>

<style scoped>
.modal { display:block; }
</style>

