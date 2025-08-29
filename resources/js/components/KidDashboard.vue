<template>
  <div class="container-fluid kid-dashboard" style="background-color: #ffebee; min-height: 100vh; padding-bottom: 2rem;">
    <!-- Toast Notification -->
    <Toast
      :show="showToast"
      :message="toastMessage"
      :title="toastTitle"
      :type="toastType"
      @update:show="showToast = $event"
    />

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
          <li class="nav-item">
            <a class="nav-link" :class="{ active: activeTab === 'profile' }" href="#" @click.prevent="activeTab = 'profile'">
              <i class="fas fa-user-cog me-1"></i> Cài đặt hồ sơ
            </a>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" to="/user-kid/game">
              <i class="fas fa-gamepad me-1"></i> Trò chơi 1
            </router-link>
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

    <!-- Profile Settings Tab Content -->
    <div v-if="activeTab === 'profile'">
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header bg-warning text-white">
              <h5 class="mb-0"><i class="fas fa-user-cog me-2"></i>Cài đặt hồ sơ</h5>
            </div>
            <div class="card-body">
              <div v-if="profileLoading || loading" class="text-center">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Đang tải thông tin...</p>
              </div>
              <div v-else-if="!profile" class="text-center text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <p>Không thể tải thông tin hồ sơ</p>
                <button class="btn btn-primary" @click="fetchData">Thử lại</button>
              </div>
              <div v-else>
                <!-- Avatar Section -->
                <div class="text-center mb-4">
                  <div class="position-relative d-inline-block">
                    <img
                      :src="avatarPreview || profile.avatar_url || defaultAvatar"
                      alt="Avatar"
                      class="rounded-circle border"
                      style="width: 120px; height: 120px; object-fit: cover;"
                      @error="handleAvatarError"
                    />
                    <button
                      type="button"
                      class="btn btn-primary btn-sm position-absolute"
                      style="bottom: 0; right: 0; border-radius: 50%; width: 35px; height: 35px;"
                      @click="$refs.avatarInput.click()"
                    >
                      <i class="fas fa-camera"></i>
                    </button>
                    <input
                      ref="avatarInput"
                      type="file"
                      class="d-none"
                      accept="image/*"
                      @change="handleAvatarChange"
                    />
                  </div>
                  <p class="small text-muted mt-2">Nhấp vào nút camera để thay đổi ảnh đại diện (sẽ tự động lưu)</p>
                  <div v-if="updating" class="mt-2">
                    <small class="text-info">
                      <i class="fas fa-spinner fa-spin me-1"></i>
                      Đang tải lên ảnh đại diện...
                    </small>
                  </div>
                  <div v-else-if="avatarPreview" class="mt-2">
                    <small class="text-success">
                      <i class="fas fa-check-circle me-1"></i>
                      Ảnh đại diện đã được cập nhật thành công!
                    </small>
                  </div>
                </div>

                <form @submit.prevent="updateProfile">
                  <div class="mb-3">
                    <label class="form-label">Tên hiển thị *</label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="profileForm.name"
                      required
                      :class="{ 'is-invalid': errors.name }"
                    />
                    <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Email (chỉ đọc)</label>
                    <input
                      type="email"
                      class="form-control"
                      :value="profile.email"
                      readonly
                      disabled
                    />
                    <div class="form-text">Email không thể thay đổi</div>
                  </div>

                  <div class="d-grid">
                    <button type="submit" class="btn btn-primary" :disabled="updating">
                      <span v-if="updating" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="fas fa-save me-2"></i>
                      {{ updating ? 'Đang lưu...' : 'Lưu thay đổi' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-danger text-white">
              <h5 class="mb-0"><i class="fas fa-key me-2"></i>Đổi mật khẩu</h5>
            </div>
            <div class="card-body">
              <form @submit.prevent="changePassword">
                <div class="mb-3">
                  <label class="form-label">Mật khẩu hiện tại *</label>
                  <input
                    type="password"
                    class="form-control"
                    v-model="passwordForm.current_password"
                    required
                    :class="{ 'is-invalid': errors.current_password }"
                  />
                  <div v-if="errors.current_password" class="invalid-feedback">{{ errors.current_password[0] }}</div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Mật khẩu mới *</label>
                  <input
                    type="password"
                    class="form-control"
                    v-model="passwordForm.new_password"
                    required
                    minlength="4"
                    :class="{ 'is-invalid': errors.new_password }"
                  />
                  <div v-if="errors.new_password" class="invalid-feedback">{{ errors.new_password[0] }}</div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Xác nhận mật khẩu mới *</label>
                  <input
                    type="password"
                    class="form-control"
                    v-model="passwordForm.new_password_confirmation"
                    required
                    minlength="4"
                    :class="{ 'is-invalid': !passwordsMatch && passwordForm.new_password_confirmation }"
                  />
                  <div v-if="!passwordsMatch && passwordForm.new_password_confirmation" class="invalid-feedback">
                    Mật khẩu xác nhận không khớp
                  </div>
                </div>

                <div class="d-grid">
                  <button
                    type="submit"
                    class="btn btn-danger"
                    :disabled="changingPassword || !passwordsMatch"
                  >
                    <span v-if="changingPassword" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-key me-2"></i>
                    {{ changingPassword ? 'Đang đổi...' : 'Đổi mật khẩu' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import RequestForm from './kid/RequestForm.vue';
import RequestHistory from './kid/RequestHistory.vue';
import Toast from './common/Toast.vue'; // Fix import path to Toast component

const router = useRouter();
const loading = ref(true);
const profile = ref(null);
const profileData = ref(null);
const dashboardData = ref(null);
const showAllHistory = ref(false);
const showImage = ref(null);
const activeTab = ref('dashboard');
const refreshTrigger = ref(false);
const profileLoading = ref(false);
const updating = ref(false);
const changingPassword = ref(false);
const errors = ref({});

// Toast state
const showToast = ref(false);
const toastMessage = ref('');
const toastTitle = ref('');
const toastType = ref('success');

// Profile form data
const profileForm = ref({
  name: '',
  avatar: null
});

// Add preview URL for avatar
const avatarPreview = ref(null);

// Password form data
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
});

// Computed for password validation
const passwordsMatch = computed(() => {
  return passwordForm.value.new_password === passwordForm.value.new_password_confirmation;
});

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

    // Debug avatar URL
    console.log('Profile data:', profile.value);
    console.log('Avatar URL:', profile.value.avatar_url);
    console.log('Avatar path:', profile.value.avatar);

    // Initialize profile form with current data
    profileForm.value.name = profile.value.name;
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

const updateProfile = async () => {
  updating.value = true;
  errors.value = {};

  try {
    let response;

    if (profileForm.value.avatar) {
      // Use POST with FormData when uploading file
      const formData = new FormData();
      formData.append('name', profileForm.value.name);
      formData.append('avatar', profileForm.value.avatar);

      response = await axios.post('/api/kid/profile/update', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
    } else {
      // Use PUT with JSON when only updating name
      response = await axios.put('/api/kid/profile', {
        name: profileForm.value.name
      }, {
        headers: {
          'Content-Type': 'application/json'
        }
      });
    }

    // Update profile data completely
    profile.value = response.data.profile;
    profile.value.avatar_url = response.data.avatar_url;

    // Update profileData as well
    profileData.value.profile = profile.value;
    profileData.value.avatar_url = response.data.avatar_url;

    // Update window.currentUser for header and other components
    if (window.currentUser) {
      window.currentUser.name = profile.value.name;
      window.currentUser.avatar = profile.value.avatar;
      window.currentUser.avatar_url = response.data.avatar_url;
    }

    // Show success toast
    toastTitle.value = 'Thành công';
    toastMessage.value = 'Cập nhật hồ sơ thành công!';
    toastType.value = 'success';
    showToast.value = true;

    // Reset form and preview
    profileForm.value.avatar = null;
    avatarPreview.value = null;

    // Clear file input
    const avatarInput = document.querySelector('input[type="file"]');
    if (avatarInput) {
      avatarInput.value = '';
    }

    // Force re-render by updating key or refreshing data
    await fetchData();

  } catch (error) {
    console.error('Error updating profile:', error);
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else if (error.response?.data?.message) {
      // Show error toast
      toastTitle.value = 'Lỗi';
      toastMessage.value = error.response.data.message;
      toastType.value = 'danger';
      showToast.value = true;
    } else {
      // Show error toast
      toastTitle.value = 'Lỗi';
      toastMessage.value = 'Đã xảy ra lỗi. Vui lòng thử lại sau.';
      toastType.value = 'danger';
      showToast.value = true;
    }
  } finally {
    updating.value = false;
  }
};

const changePassword = async () => {
  changingPassword.value = true;
  errors.value = {};

  try {
    await axios.put('/api/kid/change-password', {
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
      new_password_confirmation: passwordForm.value.new_password_confirmation
    });

    // Show success toast
    toastTitle.value = 'Thành công';
    toastMessage.value = 'Đổi mật khẩu thành công!';
    toastType.value = 'success';
    showToast.value = true;

    // Reset password form
    passwordForm.value.current_password = '';
    passwordForm.value.new_password = '';
    passwordForm.value.new_password_confirmation = '';

  } catch (error) {
    console.error('Error changing password:', error);
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else if (error.response?.data?.message) {
      // Show error toast
      toastTitle.value = 'Lỗi';
      toastMessage.value = error.response.data.message;
      toastType.value = 'danger';
      showToast.value = true;
    } else {
      // Show error toast
      toastTitle.value = 'Lỗi';
      toastMessage.value = 'Đã xảy ra lỗi. Vui lòng thử lại sau.';
      toastType.value = 'danger';
      showToast.value = true;
    }
  } finally {
    changingPassword.value = false;
  }
};

const handleAvatarChange = async (event) => {
  const file = event.target.files[0];
  if (!file) {
    // Clear preview if no file selected
    avatarPreview.value = null;
    return;
  }

  // Validate file type and size
  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
  if (!validTypes.includes(file.type)) {
    // Show error toast instead of alert
    toastTitle.value = 'Lỗi';
    toastMessage.value = 'Vui lòng chọn file ảnh (JPEG, PNG, JPG, GIF)';
    toastType.value = 'danger';
    showToast.value = true;

    event.target.value = '';
    avatarPreview.value = null;
    return;
  }

  if (file.size > 2048 * 1024) { // 2MB
    // Show error toast instead of alert
    toastTitle.value = 'Lỗi';
    toastMessage.value = 'File ảnh không được vượt quá 2MB';
    toastType.value = 'danger';
    showToast.value = true;

    event.target.value = '';
    avatarPreview.value = null;
    return;
  }

  // Create preview immediately
  const reader = new FileReader();
  reader.onload = (e) => {
    avatarPreview.value = e.target.result;
  };
  reader.onerror = () => {
    // Show error toast instead of alert
    toastTitle.value = 'Lỗi';
    toastMessage.value = 'Không thể đọc file ảnh. Vui lòng thử lại.';
    toastType.value = 'danger';
    showToast.value = true;

    event.target.value = '';
    avatarPreview.value = null;
    return;
  };
  reader.readAsDataURL(file);

  // Auto-upload the avatar immediately
  await uploadAvatarImmediately(file);
};

// New function to handle immediate avatar upload
const uploadAvatarImmediately = async (file) => {
  updating.value = true;
  errors.value = {};

  try {
    // Create FormData for avatar upload
    const formData = new FormData();
    formData.append('name', profileForm.value.name); // Keep current name
    formData.append('avatar', file);

    const response = await axios.post('/api/kid/profile/update', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    // Update profile data completely
    profile.value = response.data.profile;
    profile.value.avatar_url = response.data.avatar_url;

    // Update profileData as well
    profileData.value.profile = profile.value;
    profileData.value.avatar_url = response.data.avatar_url;

    // Update window.currentUser for header and other components
    if (window.currentUser) {
      window.currentUser.name = profile.value.name;
      window.currentUser.avatar = profile.value.avatar;
      window.currentUser.avatar_url = response.data.avatar_url;
    }

    // Show success toast
    toastTitle.value = 'Thành công';
    toastMessage.value = 'Cập nhật ảnh đại diện thành công!';
    toastType.value = 'success';
    showToast.value = true;

    // Clear preview after successful upload
    avatarPreview.value = null;

    // Clear file input
    const avatarInput = document.querySelector('input[type="file"]');
    if (avatarInput) {
      avatarInput.value = '';
    }

    // Force re-fetch data to update profile info
    await fetchData();

  } catch (error) {
    console.error('Error uploading avatar:', error);

    // Show error toast
    toastTitle.value = 'Lỗi';
    toastMessage.value = 'Đã xảy ra lỗi khi tải ảnh đại diện lên. Vui lòng thử lại.';
    toastType.value = 'danger';
    showToast.value = true;
  } finally {
    updating.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
/* Add any component-specific styles here */
.kid-dashboard {
  background-color: #ffe6f0; /* Light pink background */
  min-height: 100vh;
  padding-bottom: 2rem;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
