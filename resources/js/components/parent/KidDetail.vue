<template>
  <div class="col-12">
    <!-- Kid Information Card -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-user me-2"></i>Thông tin chi tiết</h5>
          <div>
            <button class="btn btn-sm btn-outline-light me-2" @click="$router.push('/')">
              <i class="fas fa-home me-1"></i>Dashboard
            </button>
            <button class="btn btn-sm btn-outline-light" @click="$emit('back')">
              <i class="fas fa-arrow-left me-1"></i>Quay lại
            </button>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 text-center mb-4 mb-md-0">
            <img
              :src="kid.avatar_url || defaultAvatar"
              alt="Avatar"
              class="rounded-circle border mb-3"
              style="width: 150px; height: 150px; object-fit: cover;"
              @error="handleAvatarError"
            />
            <h4>{{ kid.name }}</h4>
            <p class="text-muted">{{ kid.email }}</p>
            <div class="mb-3">
              <span class="badge bg-success fs-6 mb-2">{{ kid.total_points || 0 }} điểm</span>
            </div>
          </div>
          <div class="col-md-8">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card bg-light">
                  <div class="card-body">
                    <h6 class="card-title">Thông tin chung</h6>
                    <div class="mb-2">
                      <strong>ID:</strong> {{ kid.id }}
                    </div>
                    <div class="mb-2">
                      <strong>Ngày tham gia:</strong> {{ formatDate(kid.created_at) }}
                    </div>
                    <div class="mb-2">
                      <strong>Cập nhật lần cuối:</strong> {{ formatDate(kid.updated_at) }}
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card bg-light">
                  <div class="card-body">
                    <h6 class="card-title">Thống kê</h6>
                    <div class="mb-2">
                      <strong>Tổng điểm:</strong> {{ kid.total_points || 0 }}
                    </div>
                    <div class="mb-2">
                      <strong>Yêu cầu đang chờ:</strong> {{ pendingRequests.length }}
                    </div>
                    <div class="mb-2">
                      <strong>Tổng số yêu cầu:</strong> {{ kidRequests.length }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Requests Management -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-info text-white">
            <ul class="nav nav-tabs card-header-tabs">
              <li class="nav-item">
                <a class="nav-link text-dark" :class="{ active: requestTab === 'pending' }" href="#" @click.prevent="requestTab = 'pending'">
                  <i class="fas fa-clock me-1"></i>Yêu cầu đang chờ ({{ pendingRequests.length }})
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link text-dark" :class="{ active: requestTab === 'processed' }" href="#" @click.prevent="requestTab = 'processed'">
                  <i class="fas fa-check-circle me-1"></i>Yêu cầu đã xử lý
                </a>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status"></div>
            </div>

            <!-- Pending Requests Tab -->
            <div v-else-if="requestTab === 'pending'">
              <div v-if="!pendingRequests.length" class="text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <p>Không có yêu cầu nào đang chờ xử lý</p>
              </div>
              <div v-else>
                <div v-for="request in pendingRequests" :key="request.id" class="card mb-3 border-warning">
                  <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                      <div>
                        <h5 class="card-title mb-1">{{ request.title }}</h5>
                        <div>
                          <span :class="getTypeClass(request.type)" class="badge me-2">
                            {{ getTypeLabel(request.type) }}
                          </span>
                          <span class="badge bg-warning">Đang chờ</span>
                        </div>
                      </div>
                      <div class="text-end">
                        <small class="text-muted d-block">Ngày yêu cầu:</small>
                        <strong>{{ formatDate(request.created_at) }}</strong>
                      </div>
                    </div>

                    <p class="card-text mb-3">{{ request.description || 'Không có mô tả' }}</p>

                    <form @submit.prevent="processRequest(request, 'approved')">
                      <div class="row g-3 mb-3">
                        <div class="col-md-6">
                          <label for="scheduledTime" class="form-label">Thời gian thực hiện</label>
                          <input
                            type="datetime-local"
                            class="form-control"
                            id="scheduledTime"
                            v-model="request.scheduledTime"
                          >
                        </div>
                        <div class="col-md-6">
                          <label for="parentNote" class="form-label">Ghi chú</label>
                          <input
                            type="text"
                            class="form-control"
                            id="parentNote"
                            v-model="request.parentNote"
                            placeholder="Nhập ghi chú (nếu có)"
                          >
                        </div>
                      </div>

                      <div class="d-flex justify-content-end gap-2">
                        <button
                          type="button"
                          class="btn btn-danger"
                          @click="processRequest(request, 'rejected')"
                          :disabled="processing"
                        >
                          <i class="fas fa-times me-1"></i>Từ chối
                        </button>
                        <button
                          type="submit"
                          class="btn btn-success"
                          :disabled="processing"
                        >
                          <i class="fas fa-check me-1"></i>Chấp nhận
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

            <!-- Processed Requests Tab -->
            <div v-else-if="requestTab === 'processed'">
              <div v-if="!processedRequests.length" class="text-center py-4">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <p class="text-muted">Chưa có yêu cầu nào được xử lý</p>
              </div>
              <div v-else>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Tiêu đề</th>
                        <th>Loại</th>
                        <th>Trạng thái</th>
                        <th>Ngày yêu cầu</th>
                        <th>Thời gian thực hiện</th>
                        <th>Chi tiết</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="request in processedRequests" :key="request.id">
                        <td>{{ request.title }}</td>
                        <td>
                          <span :class="getTypeClass(request.type)" class="badge">
                            {{ getTypeLabel(request.type) }}
                          </span>
                        </td>
                        <td>
                          <span :class="getStatusClass(request.status)" class="badge">
                            {{ getStatusLabel(request.status) }}
                          </span>
                        </td>
                        <td>{{ formatDate(request.created_at) }}</td>
                        <td>{{ formatDate(request.scheduled_time) || '-' }}</td>
                        <td>
                          <button class="btn btn-sm btn-primary" @click="viewRequestDetail(request)">
                            <i class="fas fa-eye"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Request Detail Modal -->
    <div v-if="showDetailModal" class="modal d-block" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Chi tiết yêu cầu</h5>
            <button type="button" class="btn-close" @click="showDetailModal = false"></button>
          </div>
          <div class="modal-body">
            <div v-if="detailRequest">
              <div class="mb-3">
                <h5 class="mb-1">{{ detailRequest.title }}</h5>
                <span
                  :class="getTypeClass(detailRequest.type)"
                  class="badge me-2">
                  {{ getTypeLabel(detailRequest.type) }}
                </span>
                <span
                  :class="getStatusClass(detailRequest.status)"
                  class="badge">
                  {{ getStatusLabel(detailRequest.status) }}
                </span>
              </div>

              <div class="mb-3">
                <strong>Mô tả:</strong>
                <p class="mb-0">{{ detailRequest.description || 'Không có mô tả' }}</p>
              </div>

              <div class="mb-3">
                <strong>Ngày yêu cầu:</strong>
                <p class="mb-0">{{ formatDate(detailRequest.created_at) }}</p>
              </div>

              <div v-if="detailRequest.status !== 'pending'" class="mb-3">
                <strong>Thời gian thực hiện:</strong>
                <p class="mb-0">
                  <span v-if="detailRequest.scheduled_time">
                    {{ formatDate(detailRequest.scheduled_time) }}
                  </span>
                  <span v-else class="text-muted">Chưa xác định</span>
                </p>
              </div>

              <div v-if="detailRequest.parent_note" class="mb-3">
                <strong>Ghi chú từ phụ huynh:</strong>
                <p class="mb-0">{{ detailRequest.parent_note }}</p>
              </div>

              <div v-if="detailRequest.status === 'approved' || detailRequest.status === 'completed'">
                <div class="d-grid">
                  <button
                    v-if="detailRequest.status === 'approved'"
                    class="btn btn-success"
                    @click="markRequestCompleted(detailRequest)"
                  >
                    <i class="fas fa-check-double me-1"></i>Đánh dấu đã hoàn thành
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDetailModal = false">Đóng</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  kid: {
    type: Object,
    required: true
  },
  selectedRequest: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['request-processed']);

const loading = ref(true);
const kidRequests = ref([]);
const requestTab = ref('pending');
const processing = ref(false);
const showDetailModal = ref(false);
const detailRequest = ref(null);

const defaultAvatar = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjBGMEYwIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iMzgiIHI9IjEyIiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0yNSA3NUM0MCA2NSA2MCA2NSA3NSA3NVY3NUgyNVoiIGZpbGw9IiNDQ0MiLz4KPC9zdmc+';

const pendingRequests = computed(() => {
  return kidRequests.value.filter(req => req.status === 'pending');
});

const processedRequests = computed(() => {
  return kidRequests.value.filter(req => req.status !== 'pending');
});

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getTypeLabel = (type) => {
  const types = {
    'toy': 'Đồ chơi',
    'food': 'Món ăn',
    'playground': 'Khu vui chơi',
    'activity': 'Hoạt động'
  };
  return types[type] || type;
};

const getTypeClass = (type) => {
  const classes = {
    'toy': 'bg-primary',
    'food': 'bg-success',
    'playground': 'bg-info',
    'activity': 'bg-warning'
  };
  return classes[type] || 'bg-secondary';
};

const getStatusLabel = (status) => {
  const statuses = {
    'pending': 'Đang chờ',
    'approved': 'Chấp nhận',
    'rejected': 'Từ chối',
    'completed': 'Hoàn thành'
  };
  return statuses[status] || status;
};

const getStatusClass = (status) => {
  const classes = {
    'pending': 'bg-warning',
    'approved': 'bg-success',
    'rejected': 'bg-danger',
    'completed': 'bg-info'
  };
  return classes[status] || 'bg-secondary';
};

const handleAvatarError = (event) => {
  event.target.src = defaultAvatar;
};

const fetchRequests = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/parent/kid/${props.kid.id}/requests`);
    kidRequests.value = response.data.requests;

    // Add temporary properties for form inputs
    kidRequests.value.forEach(request => {
      request.scheduledTime = request.scheduled_time ? new Date(request.scheduled_time).toISOString().slice(0, 16) : '';
      request.parentNote = request.parent_note || '';
    });

  } catch (error) {
    console.error('Error fetching kid requests:', error);
  } finally {
    loading.value = false;
  }
};

const processRequest = async (request, status) => {
  processing.value = true;

  try {
    const requestData = {
      status: status,
      scheduled_time: request.scheduledTime || null,
      parent_note: request.parentNote || null
    };

    await axios.put(`/api/parent/requests/${request.id}/process`, requestData);

    // Refresh requests
    await fetchRequests();

    // Notify parent component
    emit('request-processed');

    alert(`Yêu cầu đã được ${status === 'approved' ? 'chấp nhận' : 'từ chối'} thành công!`);
  } catch (error) {
    console.error('Error processing request:', error);
    alert('Có lỗi xảy ra khi xử lý yêu cầu. Vui lòng thử lại.');
  } finally {
    processing.value = false;
  }
};

const viewRequestDetail = (request) => {
  detailRequest.value = request;
  showDetailModal.value = true;
};

const markRequestCompleted = async (request) => {
  try {
    await axios.put(`/api/parent/requests/${request.id}/complete`);

    // Refresh requests
    await fetchRequests();

    // Notify parent component
    emit('request-processed');

    showDetailModal.value = false;
    alert('Yêu cầu đã được đánh dấu hoàn thành!');
  } catch (error) {
    console.error('Error completing request:', error);
    alert('Có lỗi xảy ra khi hoàn thành yêu cầu. Vui lòng thử lại.');
  }
};

watch(() => props.selectedRequest, (newValue) => {
  if (newValue) {
    requestTab.value = 'pending';

    // Find the matching request in the kidRequests list
    const matchingRequest = kidRequests.value.find(r => r.id === newValue.id);
    if (matchingRequest) {
      // Focus on this request by scrolling to it or highlighting it
      // This could be implemented with a ref on the element
    }
  }
}, { immediate: true });

onMounted(() => {
  fetchRequests();
});
</script>

<style scoped>
.card {
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-2px);
}

.table td {
  vertical-align: middle;
}
</style>
