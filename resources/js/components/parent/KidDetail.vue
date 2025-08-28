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

    <!-- Tabs for different sections -->
    <div class="row mb-4">
      <div class="col-12">
        <ul class="nav nav-tabs">
          <li class="nav-item">
            <a class="nav-link" :class="{ active: mainTab === 'requests' }" href="#" @click.prevent="mainTab = 'requests'">
              <i class="fas fa-bell me-1"></i>Yêu cầu
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" :class="{ active: mainTab === 'points' }" href="#" @click.prevent="mainTab = 'points'">
              <i class="fas fa-star me-1"></i>Quản lý điểm
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Requests Management -->
    <div v-if="mainTab === 'requests'" class="row">
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

    <!-- Points Management -->
    <div v-if="mainTab === 'points'" class="row">
      <div class="col-md-4 mb-4">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Thêm điểm</h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitPoints">
              <div class="mb-3">
                <label for="pointsType" class="form-label">Loại</label>
                <select id="pointsType" class="form-select" v-model="pointsForm.type" required>
                  <option value="reward">Thưởng</option>
                  <option value="punishment">Phạt</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="pointsAmount" class="form-label">Số điểm</label>
                <input
                  type="number"
                  class="form-control"
                  id="pointsAmount"
                  v-model="pointsForm.points"
                  min="1"
                  max="100"
                  required
                >
                <div class="form-text">
                  {{ pointsForm.type === 'reward' ? 'Thưởng' : 'Trừ' }} điểm cho trẻ
                </div>
              </div>

              <div class="mb-3">
                <label for="pointsDescription" class="form-label">Mô tả</label>
                <textarea
                  class="form-control"
                  id="pointsDescription"
                  v-model="pointsForm.description"
                  rows="3"
                  required
                ></textarea>
                <div class="form-text">Lý do {{ pointsForm.type === 'reward' ? 'thưởng' : 'phạt' }}</div>
              </div>

              <div class="mb-3">
                <label for="pointsEvidence" class="form-label">Hình ảnh bằng chứng (nếu có)</label>
                <input
                  type="file"
                  class="form-control"
                  id="pointsEvidence"
                  @change="handleEvidenceChange"
                  accept="image/*"
                >
                <div class="form-text">Hỗ trợ các định dạng JPG, PNG (tối đa 2MB)</div>
              </div>

              <div v-if="evidencePreview" class="mb-3 text-center">
                <img
                  :src="evidencePreview"
                  alt="Preview"
                  class="img-thumbnail"
                  style="max-height: 150px"
                >
                <button
                  type="button"
                  class="btn btn-sm btn-danger d-block mx-auto mt-2"
                  @click="clearEvidence"
                >
                  <i class="fas fa-times me-1"></i>Xóa hình ảnh
                </button>
              </div>

              <div class="d-grid gap-2">
                <button
                  type="submit"
                  class="btn"
                  :class="pointsForm.type === 'reward' ? 'btn-success' : 'btn-danger'"
                  :disabled="submittingPoints"
                >
                  <span v-if="submittingPoints" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else :class="pointsForm.type === 'reward' ? 'fas fa-plus me-1' : 'fas fa-minus me-1'"></i>
                  {{ pointsForm.type === 'reward' ? 'Thưởng điểm' : 'Trừ điểm' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Lịch sử điểm</h5>
          </div>
          <div class="card-body">
            <div v-if="loadingPoints" class="text-center py-4">
              <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else-if="!pointsHistory.length" class="text-center py-4">
              <i class="fas fa-history fa-3x text-muted mb-3"></i>
              <p class="text-muted">Chưa có lịch sử điểm nào</p>
            </div>
            <div v-else>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Ngày</th>
                      <th>Loại</th>
                      <th>Điểm</th>
                      <th>Mô tả</th>
                      <th>Hình ảnh</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="record in pointsHistory" :key="record.id">
                      <td>{{ formatDate(record.created_at) }}</td>
                      <td>
                        <span
                          class="badge"
                          :class="record.type === 'reward' ? 'bg-success' : 'bg-danger'"
                        >
                          {{ record.type === 'reward' ? 'Thưởng' : 'Phạt' }}
                        </span>
                      </td>
                      <td>
                        <span
                          :class="record.type === 'reward' ? 'text-success' : 'text-danger'"
                          class="fw-bold"
                        >
                          {{ record.type === 'reward' ? '+' : '-' }}{{ record.points }}
                        </span>
                      </td>
                      <td>{{ record.description }}</td>
                      <td>
                        <img
                          v-if="record.evidence_url"
                          :src="record.evidence_url"
                          alt="Evidence"
                          class="img-thumbnail cursor-pointer"
                          style="max-width: 50px; max-height: 50px;"
                          @click="showImageModal(record.evidence_url)"
                        >
                        <span v-else class="text-muted">-</span>
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

    <!-- Image Modal -->
    <div v-if="modalImage" class="modal d-block" style="background-color: rgba(0,0,0,0.8); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Hình ảnh bằng chứng</h5>
            <button type="button" class="btn-close" @click="modalImage = null"></button>
          </div>
          <div class="modal-body text-center">
            <img :src="modalImage" alt="Evidence" class="img-fluid" style="max-height: 500px;" />
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
const mainTab = ref('requests');
const loadingPoints = ref(true);
const pointsHistory = ref([]);
const pointsForm = ref({
  type: 'reward',
  points: null,
  description: '',
  evidence: null
});
const submittingPoints = ref(false);
const evidencePreview = ref(null);
const modalImage = ref(null);

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

const fetchPointsHistory = async () => {
  loadingPoints.value = true;
  try {
    const response = await axios.get(`/api/parent/kid/${props.kid.id}/points-history`);
    pointsHistory.value = response.data.history;
  } catch (error) {
    console.error('Error fetching points history:', error);
  } finally {
    loadingPoints.value = false;
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

const submitPoints = async () => {
  submittingPoints.value = true;

  const formData = new FormData();
  formData.append('type', pointsForm.value.type);
  formData.append('points', pointsForm.value.points);
  formData.append('description', pointsForm.value.description);
  if (pointsForm.value.evidence) {
    formData.append('evidence', pointsForm.value.evidence);
  }

  try {
    await axios.post(`/api/parent/kid/${props.kid.id}/points`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    // Refresh points history
    await fetchPointsHistory();

    alert('Cập nhật điểm thành công!');

    // Reset form
    pointsForm.value.points = null;
    pointsForm.value.description = '';
    pointsForm.value.evidence = null;
    evidencePreview.value = null;
  } catch (error) {
    console.error('Error submitting points:', error);
    alert('Có lỗi xảy ra khi cập nhật điểm. Vui lòng thử lại.');
  } finally {
    submittingPoints.value = false;
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

const handleEvidenceChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
      alert('Kích thước tệp tin phải nhỏ hơn 2MB.');
      event.target.value = null;
      return;
    }

    // Validate file type (JPG, PNG)
    const validTypes = ['image/jpeg', 'image/png'];
    if (!validTypes.includes(file.type)) {
      alert('Chỉ chấp nhận tệp tin hình ảnh (JPG, PNG).');
      event.target.value = null;
      return;
    }

    pointsForm.value.evidence = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      evidencePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const clearEvidence = () => {
  pointsForm.value.evidence = null;
  evidencePreview.value = null;
};

const showImageModal = (imageUrl) => {
  modalImage.value = imageUrl;
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
  fetchPointsHistory();
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
