<template>
  <div class="container-fluid">
    <!-- Header -->
    <div class="row bg-primary text-white py-3 mb-4">
      <div class="col">
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <img
              :src="profile?.avatar_url || defaultAvatar"
              alt="Avatar"
              class="rounded-circle me-3"
              style="width: 60px; height: 60px; object-fit: cover;"
              @error="handleAvatarError"
            />
            <div>
              <h4 class="mb-0">Xin chào, {{ profile?.name }}!</h4>
              <small class="opacity-75">Trẻ em</small>
            </div>
          </div>
          <button class="btn btn-outline-light" @click="logout">
            <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
          </button>
        </div>
      </div>
    </div>

    <!-- Main tabs -->
    <div class="row mb-4">
      <div class="col-12">
        <ul class="nav nav-tabs">
          <li class="nav-item">
            <a class="nav-link" :class="{ active: activeTab === 'dashboard' }" href="#" @click.prevent="activeTab = 'dashboard'">
              <i class="fas fa-home me-1"></i> Trang chủ
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" :class="{ active: activeTab === 'requests' }" href="#" @click.prevent="activeTab = 'requests'">
              <i class="fas fa-paper-plane me-1"></i> Yêu cầu
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Dashboard Tab Content -->
    <div v-if="activeTab === 'dashboard'">
      <div class="row">
        <!-- Personal Information Card -->
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <div class="card-header bg-info text-white">
              <h5 class="mb-0"><i class="fas fa-user me-2"></i>Thông tin cá nhân</h5>
            </div>
            <div class="card-body">
              <div v-if="loading" class="text-center">
                <div class="spinner-border text-primary" role="status"></div>
              </div>
              <div v-else-if="profile">
                <div class="text-center mb-3">
                  <img
                    :src="profile.avatar_url || defaultAvatar"
                    alt="Avatar"
                    class="rounded-circle border"
                    style="width: 100px; height: 100px; object-fit: cover;"
                    @error="handleAvatarError"
                  />
                </div>
                <div class="row g-2">
                  <div class="col-12">
                    <strong>Tên:</strong> {{ profile.name }}
                  </div>
                  <div class="col-12">
                    <strong>Email:</strong> {{ profile.email }}
                  </div>
                  <div class="col-12">
                    <strong>Số phụ huynh:</strong> {{ profileData?.parents_count || 0 }}
                  </div>
                  <div class="col-12">
                    <strong>Ngày tạo:</strong> {{ formatDate(profile.created_at) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Points Summary Cards -->
        <div class="col-md-8 mb-4">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="card bg-success text-white">
                <div class="card-body text-center">
                  <i class="fas fa-star fa-2x mb-2"></i>
                  <h3 class="mb-1">{{ dashboardData?.total_points || 0 }}</h3>
                  <p class="mb-0">Tổng điểm</p>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card bg-info text-white">
                <div class="card-body text-center">
                  <i class="fas fa-chart-line fa-2x mb-2"></i>
                  <h3 class="mb-1">{{ dashboardData?.statistics?.total_records || 0 }}</h3>
                  <p class="mb-0">Tổng hoạt động</p>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card bg-primary text-white">
                <div class="card-body text-center">
                  <i class="fas fa-trophy fa-2x mb-2"></i>
                  <h3 class="mb-1">{{ dashboardData?.statistics?.reward_count || 0 }}</h3>
                  <p class="mb-0">Lần được thưởng</p>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card bg-warning text-white">
                <div class="card-body text-center">
                  <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                  <h3 class="mb-1">{{ dashboardData?.statistics?.punishment_count || 0 }}</h3>
                  <p class="mb-0">Lần bị phạt</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-history me-2"></i>Hoạt động gần đây</h5>
            </div>
            <div class="card-body">
              <div v-if="loading" class="text-center">
                <div class="spinner-border text-primary" role="status"></div>
              </div>
              <div v-else-if="!dashboardData?.recent_activities?.length" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Chưa có hoạt động nào</p>
              </div>
              <div v-else class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Ngày</th>
                      <th>Loại</th>
                      <th>Điểm</th>
                      <th>Ghi chú</th>
                      <th>Hình ảnh</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="activity in dashboardData.recent_activities" :key="activity.id">
                      <td>{{ formatDate(activity.created_at) }}</td>
                      <td>
                        <span v-if="activity.type === 'reward'" class="badge bg-success">
                          <i class="fas fa-plus me-1"></i>Thưởng
                        </span>
                        <span v-else class="badge bg-danger">
                          <i class="fas fa-minus me-1"></i>Phạt
                        </span>
                      </td>
                      <td>
                        <span v-if="activity.type === 'reward'" class="text-success fw-bold">
                          +{{ activity.points }}
                        </span>
                        <span v-else class="text-danger fw-bold">
                          -{{ activity.points }}
                        </span>
                      </td>
                      <td>{{ activity.description || '-' }}</td>
                      <td>
                        <div v-if="activity.evidence_url">
                          <img
                            :src="activity.evidence_url"
                            alt="Evidence"
                            class="img-thumbnail cursor-pointer"
                            style="max-width: 50px; max-height: 50px;"
                            @click="showImageModal(activity.evidence_url)"
                          />
                        </div>
                        <span v-else class="text-muted">-</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- View All Button -->
              <div v-if="dashboardData?.point_history?.length > 10" class="text-center mt-3">
                <button class="btn btn-outline-primary" @click="showAllHistory = !showAllHistory">
                  {{ showAllHistory ? 'Ẩn bớt' : 'Xem tất cả lịch sử' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Full History Modal -->
      <div v-if="showAllHistory" class="modal d-block" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Toàn bộ lịch sử điểm</h5>
              <button type="button" class="btn-close" @click="showAllHistory = false"></button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Ngày</th>
                      <th>Loại</th>
                      <th>Điểm</th>
                      <th>Ghi chú</th>
                      <th>Hình ảnh</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="record in dashboardData?.point_history" :key="record.id">
                      <td>{{ formatDate(record.created_at) }}</td>
                      <td>
                        <span v-if="record.type === 'reward'" class="badge bg-success">Thưởng</span>
                        <span v-else class="badge bg-danger">Phạt</span>
                      </td>
                      <td>
                        <span v-if="record.type === 'reward'" class="text-success">+{{ record.points }}</span>
                        <span v-else class="text-danger">-{{ record.points }}</span>
                      </td>
                      <td>{{ record.description || '-' }}</td>
                      <td>
                        <div v-if="record.evidence_url">
                          <img
                            :src="record.evidence_url"
                            alt="Evidence"
                            class="img-thumbnail cursor-pointer"
                            style="max-width: 50px; max-height: 50px;"
                            @click="showImageModal(record.evidence_url)"
                          />
                        </div>
                        <span v-else class="text-muted">-</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showAllHistory = false">Đóng</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Requests Tab Content -->
    <div v-if="activeTab === 'requests'">
      <div class="row">
        <div class="col-md-5">
          <RequestForm @request-submitted="handleRequestSubmitted" />
        </div>
        <div class="col-md-7">
          <RequestHistory :refresh-trigger="refreshTrigger" />
        </div>
      </div>
    </div>

    <!-- Image Modal -->
    <div v-if="showImage" class="modal d-block" style="background-color: rgba(0,0,0,0.8); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Hình ảnh bằng chứng</h5>
            <button type="button" class="btn-close" @click="showImage = null"></button>
          </div>
          <div class="modal-body text-center">
            <img :src="showImage" alt="Evidence" class="img-fluid" style="max-height: 500px;" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import RequestForm from './kid/RequestForm.vue';
import RequestHistory from './kid/RequestHistory.vue';

const router = useRouter();
const loading = ref(true);
const profile = ref(null);
const profileData = ref(null);
const dashboardData = ref(null);
const showAllHistory = ref(false);
const showImage = ref(null);
const activeTab = ref('dashboard');
const refreshTrigger = ref(false);

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

const showImageModal = (url) => {
  showImage.value = url;
};

const logout = async () => {
  try {
    await axios.post('/api/auth/logout');
    localStorage.removeItem('token');
    delete axios.defaults.headers.common['Authorization'];
    window.currentUser = null;
    router.push('/login');
  } catch (error) {
    console.error('Logout error:', error);
    // Force logout anyway
    localStorage.removeItem('token');
    delete axios.defaults.headers.common['Authorization'];
    window.currentUser = null;
    router.push('/login');
  }
};

const fetchData = async () => {
  loading.value = true;
  try {
    // Fetch both profile and dashboard data
    const [profileResponse, dashboardResponse] = await Promise.all([
      axios.get('/api/kid/profile'),
      axios.get('/api/kid/dashboard')
    ]);

    profileData.value = profileResponse.data;
    profile.value = profileData.value.profile;
    dashboardData.value = dashboardResponse.data;
  } catch (error) {
    console.error('Error fetching data:', error);
    if (error.response?.status === 401 || error.response?.status === 403) {
      logout();
    }
  } finally {
    loading.value = false;
  }
};

const handleRequestSubmitted = () => {
  refreshTrigger.value = !refreshTrigger.value;
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

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
