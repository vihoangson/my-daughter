<template>
  <div class="container-fluid">
    <!-- Loading state -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Đang tải...</span>
      </div>
    </div>

    <!-- Kid Details -->
    <div v-else-if="kidData" class="row">
      <!-- Kid Info Card -->
      <div class="col-md-4 mb-4">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
              <i class="fas fa-user-circle me-2"></i>
              Thông tin trẻ em
            </h5>
          </div>
          <div class="card-body">
            <div class="text-center mb-3">
              <div class="avatar-circle mx-auto mb-3">
                <i class="fas fa-child fa-3x text-primary"></i>
              </div>
              <h4 class="mb-1">{{ kidData.kid.name }}</h4>
              <p class="text-muted">{{ kidData.kid.email }}</p>
            </div>

            <div class="row text-center">
              <div class="col-4">
                <h3 :class="kidData.total_points >= 0 ? 'text-success' : 'text-danger'">
                  {{ kidData.total_points }}
                </h3>
                <small class="text-muted">Tổng điểm</small>
              </div>
              <div class="col-4">
                <h3 class="text-info">{{ kidData.total_records }}</h3>
                <small class="text-muted">Lần ghi nhận</small>
              </div>
              <div class="col-4">
                <h3 class="text-warning">{{ pendingRequests }}</h3>
                <small class="text-muted">Yêu cầu chờ</small>
              </div>
            </div>

            <hr>

            <div class="d-grid gap-2">
              <button class="btn btn-outline-primary" @click="showAddPointModal = true">
                <i class="fas fa-plus me-2"></i>Thêm điểm
              </button>
              <button class="btn btn-outline-info" @click="viewRequests">
                <i class="fas fa-list me-2"></i>Xem yêu cầu
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Point History -->
      <div class="col-md-8 mb-4">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
              <i class="fas fa-history me-2"></i>
              Lịch sử điểm
            </h5>
            <div class="btn-group btn-group-sm">
              <button
                class="btn"
                :class="filterType === 'all' ? 'btn-primary' : 'btn-outline-primary'"
                @click="filterType = 'all'"
              >
                Tất cả
              </button>
              <button
                class="btn"
                :class="filterType === 'reward' ? 'btn-success' : 'btn-outline-success'"
                @click="filterType = 'reward'"
              >
                Thưởng
              </button>
              <button
                class="btn"
                :class="filterType === 'punishment' ? 'btn-danger' : 'btn-outline-danger'"
                @click="filterType = 'punishment'"
              >
                Phạt
              </button>
            </div>
          </div>
          <div class="card-body">
            <div v-if="filteredHistory.length === 0" class="text-center py-4 text-muted">
              <i class="fas fa-clipboard-list fa-3x mb-3 opacity-50"></i>
              <p>Chưa có lịch sử điểm nào</p>
            </div>
            <div v-else>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Ngày</th>
                      <th>Loại</th>
                      <th>Lý do</th>
                      <th class="text-end">Điểm</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="record in paginatedHistory" :key="record.id">
                      <td>
                        <small>{{ formatDate(record.created_at) }}</small>
                      </td>
                      <td>
                        <span
                          class="badge"
                          :class="record.type === 'reward' ? 'bg-success' : 'bg-danger'"
                        >
                          {{ record.type === 'reward' ? 'Thưởng' : 'Phạt' }}
                        </span>
                      </td>
                      <td>{{ record.reason || 'Không có lý do' }}</td>
                      <td class="text-end">
                        <span
                          :class="record.type === 'reward' ? 'text-success' : 'text-danger'"
                          class="fw-bold"
                        >
                          {{ record.type === 'reward' ? '+' : '-' }}{{ record.points }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <nav v-if="totalPages > 1" class="mt-3">
                <ul class="pagination pagination-sm justify-content-center">
                  <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <a class="page-link" href="#" @click.prevent="currentPage = 1">Đầu</a>
                  </li>
                  <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <a class="page-link" href="#" @click.prevent="currentPage--">Trước</a>
                  </li>
                  <li
                    class="page-item"
                    v-for="page in visiblePages"
                    :key="page"
                    :class="{ active: currentPage === page }"
                  >
                    <a class="page-link" href="#" @click.prevent="currentPage = page">{{ page }}</a>
                  </li>
                  <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                    <a class="page-link" href="#" @click.prevent="currentPage++">Sau</a>
                  </li>
                  <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                    <a class="page-link" href="#" @click.prevent="currentPage = totalPages">Cuối</a>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error state -->
    <div v-else class="text-center py-5">
      <div class="text-danger">
        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
        <h5>Không thể tải thông tin trẻ em</h5>
        <p>{{ errorMessage || 'Có lỗi xảy ra khi tải dữ liệu.' }}</p>
        <button class="btn btn-primary" @click="loadKidDetails">Thử lại</button>
      </div>
    </div>

    <!-- Add Point Modal -->
    <div class="modal fade" :class="{ show: showAddPointModal }" :style="{ display: showAddPointModal ? 'block' : 'none' }" v-if="showAddPointModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Thêm điểm cho {{ kidData?.kid.name }}</h5>
            <button type="button" class="btn-close" @click="closeAddPointModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addPoint">
              <div class="mb-3">
                <label class="form-label">Loại *</label>
                <div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="type" id="reward" value="reward" v-model="pointForm.type">
                    <label class="form-check-label text-success" for="reward">
                      <i class="fas fa-plus-circle me-1"></i>Thưởng
                    </label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="type" id="punishment" value="punishment" v-model="pointForm.type">
                    <label class="form-check-label text-danger" for="punishment">
                      <i class="fas fa-minus-circle me-1"></i>Phạt
                    </label>
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="points" class="form-label">Số điểm *</label>
                <input
                  type="number"
                  class="form-control"
                  id="points"
                  v-model="pointForm.points"
                  min="1"
                  required
                >
              </div>
              <div class="mb-3">
                <label for="reason" class="form-label">Lý do</label>
                <textarea
                  class="form-control"
                  id="reason"
                  v-model="pointForm.reason"
                  rows="3"
                  placeholder="Nhập lý do thưởng/phạt..."
                ></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeAddPointModal">Hủy</button>
            <button type="button" class="btn btn-primary" @click="addPoint" :disabled="adding">
              <span v-if="adding" class="spinner-border spinner-border-sm me-2"></span>
              Thêm điểm
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade" :class="{ show: showAddPointModal }" v-if="showAddPointModal"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const adding = ref(false);
const kidData = ref(null);
const errorMessage = ref('');
const showAddPointModal = ref(false);
const filterType = ref('all');
const currentPage = ref(1);
const itemsPerPage = 10;
const pendingRequests = ref(0);

const pointForm = ref({
  type: 'reward',
  points: 1,
  reason: ''
});

const kidId = computed(() => route.params.id);

const filteredHistory = computed(() => {
  if (!kidData.value?.point_history) return [];

  if (filterType.value === 'all') {
    return kidData.value.point_history;
  }

  return kidData.value.point_history.filter(record => record.type === filterType.value);
});

const totalPages = computed(() => {
  return Math.ceil(filteredHistory.value.length / itemsPerPage);
});

const paginatedHistory = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredHistory.value.slice(start, end);
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const delta = 2;

  let pages = [];
  for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) {
    pages.push(i);
  }

  return pages;
});

const loadKidDetails = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await axios.get(`/api/parent/kids/${kidId.value}/details`);
    kidData.value = response.data;

    // Load pending requests count
    await loadPendingRequests();
  } catch (error) {
    console.error('Error loading kid details:', error);
    if (error.response?.status === 403) {
      errorMessage.value = 'Bạn không có quyền xem thông tin này.';
    } else if (error.response?.status === 404) {
      errorMessage.value = 'Không tìm thấy thông tin trẻ em.';
    } else {
      errorMessage.value = 'Có lỗi xảy ra khi tải thông tin.';
    }
  } finally {
    loading.value = false;
  }
};

const loadPendingRequests = async () => {
  try {
    const response = await axios.get(`/api/parent/kid/${kidId.value}/requests`);
    const requests = response.data.requests || [];
    pendingRequests.value = requests.filter(req => req.status === 'pending').length;
  } catch (error) {
    console.error('Error loading pending requests:', error);
  }
};

const addPoint = async () => {
  if (!pointForm.value.type || !pointForm.value.points) {
    alert('Vui lòng điền đầy đủ thông tin.');
    return;
  }

  adding.value = true;

  try {
    await axios.post('/api/reward-punishments', {
      child_id: kidId.value,
      type: pointForm.value.type,
      points: pointForm.value.points,
      reason: pointForm.value.reason
    });

    closeAddPointModal();
    loadKidDetails(); // Reload data
  } catch (error) {
    console.error('Error adding point:', error);
    alert('Có lỗi xảy ra khi thêm điểm.');
  } finally {
    adding.value = false;
  }
};

const viewRequests = () => {
  // Navigate to requests page or show requests modal
  router.push(`/kid/${kidId.value}/requests`);
};

const closeAddPointModal = () => {
  showAddPointModal.value = false;
  pointForm.value = {
    type: 'reward',
    points: 1,
    reason: ''
  };
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString('vi-VN');
};

// Watch for route changes
watch(() => route.params.id, () => {
  if (route.params.id) {
    loadKidDetails();
  }
});

// Reset pagination when filter changes
watch(filterType, () => {
  currentPage.value = 1;
});

onMounted(() => {
  loadKidDetails();
});
</script>

<style scoped>
.avatar-circle {
  width: 80px;
  height: 80px;
  background-color: #f8f9fa;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card {
  border: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
  border-bottom: 1px solid rgba(0,0,0,0.125);
}

.table-hover tbody tr:hover {
  background-color: rgba(0,0,0,0.025);
}

.modal {
  background-color: rgba(0,0,0,0.5);
}

.opacity-50 {
  opacity: 0.5;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.pagination-sm .page-link {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}
</style>
