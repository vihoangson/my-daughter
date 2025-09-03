<template>
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">
      <h5 class="mb-0"><i class="fas fa-history me-2"></i>Lịch sử yêu cầu</h5>
    </div>
    <div class="card-body">
      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2">Đang tải dữ liệu...</div>
      </div>

      <div v-else-if="!requests.length" class="text-center py-4">
        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
        <p class="text-muted">Bạn chưa gửi yêu cầu nào</p>
      </div>

      <div v-else>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Ngày</th>
                <th>Tiêu đề</th>
                <th>Loại</th>
                <th>Trạng thái</th>
                <th>Thời gian thực hiện</th>
                <th>Hình</th>
                <th>Chi tiết</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="request in requests" :key="request.id">
                <td>{{ formatDate(request.created_at) }}</td>
                <td>{{ request.title }}</td>
                <td>
                  <span :class="getTypeClass(request.type)" class="badge">{{ getTypeLabel(request.type) }}</span>
                </td>
                <td>
                  <span :class="getStatusClass(request.status)" class="badge">{{ getStatusLabel(request.status) }}</span>
                </td>
                <td>
                  <span v-if="request.scheduled_time">{{ formatDate(request.scheduled_time) }}</span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <div v-if="request.image_url" class="thumbnail-wrapper" style="width:52px;">
                    <img
                      :src="request.image_url"
                      :alt="'Ảnh yêu cầu: ' + request.title"
                      class="img-fluid rounded border"
                      style="cursor:pointer;max-height:48px;object-fit:cover;"
                      @click="openImageModal(request)"
                      @error="onImageError($event, request)"
                    />
                  </div>
                  <span v-else class="text-muted small">Không có</span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" @click="viewDetails(request)">
                    <i class="fas fa-eye"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Request Details Modal -->
    <div v-if="showModal" class="modal d-block" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Chi tiết yêu cầu</h5>
            <button type="button" class="btn-close" @click="showModal = false"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedRequest">
              <div class="mb-3">
                <h5 class="mb-1">{{ selectedRequest.title }}</h5>
                <span
                  :class="getTypeClass(selectedRequest.type)"
                  class="badge me-2">
                  {{ getTypeLabel(selectedRequest.type) }}
                </span>
                <span
                  :class="getStatusClass(selectedRequest.status)"
                  class="badge">
                  {{ getStatusLabel(selectedRequest.status) }}
                </span>
              </div>

              <div class="mb-3">
                <strong>Mô tả:</strong>
                <p class="mb-0">{{ selectedRequest.description || 'Không có mô tả' }}</p>
              </div>

              <div class="mb-3">
                <strong>Ngày yêu cầu:</strong>
                <p class="mb-0">{{ formatDate(selectedRequest.created_at) }}</p>
              </div>

              <div v-if="selectedRequest.parent" class="mb-3">
                <strong>Phụ huynh tiếp nhận:</strong>
                <div class="d-flex align-items-center">
                  <img
                    :src="selectedRequest.parent.avatar_url || defaultAvatar"
                    alt="Parent avatar"
                    class="rounded-circle me-2"
                    style="width: 30px; height: 30px;"
                    @error="handleAvatarError"
                  >
                  <span>{{ selectedRequest.parent.name }}</span>
                </div>
              </div>

              <div v-if="selectedRequest.status !== 'pending'" class="mb-3">
                <strong>Thời gian thực hiện:</strong>
                <p class="mb-0">
                  <span v-if="selectedRequest.scheduled_time">
                    {{ formatDate(selectedRequest.scheduled_time) }}
                  </span>
                  <span v-else class="text-muted">Chưa xác định</span>
                </p>
              </div>

              <div v-if="selectedRequest.parent_note" class="mb-3">
                <strong>Ghi chú từ phụ huynh:</strong>
                <p class="mb-0">{{ selectedRequest.parent_note }}</p>
              </div>

              <div v-if="selectedRequest.image_url" class="mb-3">
                <strong>Hình minh họa:</strong>
                <div>
                  <img
                    :src="selectedRequest.image_url"
                    :alt="'Ảnh yêu cầu: ' + selectedRequest.title"
                    class="img-fluid rounded border"
                    style="max-height:260px;cursor:pointer;object-fit:contain;background:#f8f9fa;"
                    @click="openImageModal(selectedRequest)"
                  />
                  <div class="form-text">Bấm vào ảnh để xem lớn</div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showModal = false">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Full Image Modal -->
    <RequestImageModal
      v-model="showImageModal"
      :request="imageModalRequest"
      @closed="imageModalRequest = null"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import RequestImageModal from '../common/RequestImageModal.vue';

const props = defineProps({
  refreshTrigger: {
    type: Number,
    default: 0
  }
});

const loading = ref(true);
const requests = ref([]);
const showModal = ref(false);
const selectedRequest = ref(null);
const showImageModal = ref(false);
const imageModalRequest = ref(null);

const defaultAvatar = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjBGMEYwIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iMzgiIHI9IjEyIiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0yNSA3NUM0MCA2NSA2MCA2NSA3NSA3NVY3NUgyNVoiIGZpbGw9IiNDQ0MiLz4KPC9zdmc+';

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const handleAvatarError = (event) => {
  event.target.src = defaultAvatar;
};

const getTypeLabel = (type) => {
  switch (type) {
    case 'toy': return 'Đồ chơi';
    case 'food': return 'Món ăn';
    case 'playground': return 'Khu vui chơi';
    case 'activity': return 'Hoạt động';
    default: return type;
  }
};

const getTypeClass = (type) => {
  switch (type) {
    case 'toy': return 'bg-primary';
    case 'food': return 'bg-success';
    case 'playground': return 'bg-warning text-dark';
    case 'activity': return 'bg-info';
    default: return 'bg-secondary';
  }
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'pending': return 'Đang chờ';
    case 'approved': return 'Đã chấp nhận';
    case 'rejected': return 'Đã từ chối';
    case 'completed': return 'Đã hoàn thành';
    default: return status;
  }
};

const getStatusClass = (status) => {
  switch (status) {
    case 'pending': return 'bg-warning text-dark';
    case 'approved': return 'bg-success';
    case 'rejected': return 'bg-danger';
    case 'completed': return 'bg-info';
    default: return 'bg-secondary';
  }
};

const viewDetails = (request) => {
  selectedRequest.value = request;
  showModal.value = true;
};

const onImageError = (e, request) => {
  e.target.style.opacity = 0.3;
  e.target.title = 'Không tải được ảnh';
};

const openImageModal = (request) => {
  if (!request || !request.image_url) return;
  imageModalRequest.value = request;
  showImageModal.value = true;
};
const closeImageModal = () => {
  showImageModal.value = false;
  imageModalRequest.value = null;
};

const fetchRequests = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/kid/requests');
    requests.value = response.data;
  } catch (error) {
    console.error('Error fetching requests:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchRequests();
});

// Watch for refresh trigger from parent component
watch(() => props.refreshTrigger, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    fetchRequests();
  }
});
</script>

<style scoped>
.thumbnail-wrapper img:hover { filter: brightness(0.9); }
</style>
