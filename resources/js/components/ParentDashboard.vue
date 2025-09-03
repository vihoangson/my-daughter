<template>
  <div class="container-fluid parent-dashboard" style="background-color: #e3f2fd; min-height: 100vh; padding-bottom: 2rem;">
    <!-- Toast Component -->
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
              <small class="opacity-75">Phụ huynh</small>
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
            <a class="nav-link" :class="{ active: activeTab === 'kids' }" href="#" @click.prevent="activeTab = 'kids'">
              <i class="fas fa-child me-1"></i> Quản lý trẻ em
            </a>
          </li>
          <li class="nav-item" v-if="selectedKid">
            <a class="nav-link" :class="{ active: activeTab === 'kidDetail' }" href="#" @click.prevent="activeTab = 'kidDetail'">
              <i class="fas fa-user me-1"></i> {{ selectedKid.name }}
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" :class="{ active: activeTab === 'profile' }" href="#" @click.prevent="activeTab = 'profile'">
              <i class="fas fa-user-cog me-1"></i> Cài đặt hồ sơ
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" :class="{ active: activeTab === 'finance' }" href="#" @click.prevent="activeTab = 'finance'">
              <i class="fas fa-coins me-1"></i> Tài chính
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-if="activeTab === 'dashboard'" class="row">
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
                  <strong>Số trẻ em:</strong> {{ kids?.length || 0 }}
                </div>
                <div class="col-12">
                  <strong>Ngày tạo:</strong> {{ formatDate(profile.created_at) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-8 mb-4">
        <div class="card h-100">
          <div class="card-header bg-warning text-white">
            <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Yêu cầu mới nhất</h5>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center">
              <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else-if="!pendingRequests.length" class="text-center py-4">
              <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
              <p>Không có yêu cầu nào cần xử lý</p>
            </div>
            <div v-else>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Trẻ em</th>
                      <th>Tiêu đề</th>
                      <th>Loại</th>
                      <th>Ngày yêu cầu</th>
                      <th>Hình</th>
                      <th>Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="request in pendingRequests" :key="request.id">
                      <td>
                        <div class="d-flex align-items-center">
                          <img
                            :src="request.child?.avatar_url || defaultAvatar"
                            alt="Avatar"
                            class="rounded-circle me-2"
                            style="width: 30px; height: 30px; object-fit: cover;"
                            @error="handleAvatarError"
                          />
                          <span>{{ request.child?.name || 'Unknown' }}</span>
                        </div>
                      </td>
                      <td>{{ request.title }}</td>
                      <td>
                        <span :class="getTypeClass(request.type)" class="badge">
                          {{ getTypeLabel(request.type) }}
                        </span>
                      </td>
                      <td>{{ formatDate(request.created_at) }}</td>
                      <td>
                        <div v-if="request.image_url" class="thumb-wrapper">
                          <img
                            :src="request.image_url"
                            :alt="'Ảnh: ' + request.title"
                            class="request-thumb border rounded"
                            @click="openRequestImage(request)"
                            @error="onRequestImageError($event)"
                          />
                        </div>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td>
                        <button
                          class="btn btn-sm btn-primary me-1"
                          @click="handleViewKidDetails(request)"
                        >
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

    <!-- Kids Management Content -->
    <div v-if="activeTab === 'kids'" class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-child me-2"></i>Danh sách trẻ em</h5>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else-if="!kids.length" class="text-center py-4">
              <i class="fas fa-child fa-3x text-muted mb-3"></i>
              <p class="text-muted">Bạn chưa có trẻ em nào</p>
            </div>
            <div v-else class="row">
              <div v-for="kid in kids" :key="kid.id" class="col-md-4 mb-4">
                <div class="card h-100 border-primary">
                  <div class="card-body text-center">
                    <img
                      :src="kid.avatar_url || defaultAvatar"
                      alt="Avatar"
                      class="rounded-circle mb-3"
                      style="width: 80px; height: 80px; object-fit: cover;"
                      @error="handleAvatarError"
                    />
                    <h5 class="card-title">{{ kid.name }}</h5>
                    <p class="text-muted">{{ kid.email }}</p>
                    <div class="mb-3">
                      <span class="badge bg-success me-1">{{ kid.total_points || 0 }} điểm</span>
                      <span class="badge bg-warning">{{ kid.pending_requests || 0 }} yêu cầu mới</span>
                    </div>
                    <button class="btn btn-primary w-100" @click="viewKidDetails(kid)">
                      <i class="fas fa-eye me-1"></i>Xem chi tiết
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Kid Detail Content -->
    <div v-if="activeTab === 'kidDetail' && selectedKid" class="row">
      <KidDetail
        :kid="selectedKid"
        :selected-request="selectedRequest"
        @request-processed="handleRequestProcessed"
      />
    </div>

    <!-- Profile Settings Content -->
    <div v-if="activeTab === 'profile'" class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-user-cog me-2"></i>Cài đặt hồ sơ</h5>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else>
              <form @submit.prevent="updateProfile">
                <div class="mb-3">
                  <label class="form-label">Tên</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="profile.name"
                    required
                  />
                </div>
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input
                    type="email"
                    class="form-control"
                    v-model="profile.email"
                    required
                    readonly
                  />
                </div>
                <div class="mb-3">
                  <label class="form-label">Số điện thoại</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="profile.phone"
                    required
                  />
                </div>
                <div class="mb-3">
                  <label class="form-label">Địa chỉ</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="profile.address"
                    required
                  />
                </div>
                <div class="mb-3">
                  <label class="form-label">Ảnh đại diện</label>
                  <div class="d-flex align-items-center">
                    <img
                      :src="profile.avatar_url || defaultAvatar"
                      alt="Avatar"
                      class="rounded-circle me-3"
                      style="width: 60px; height: 60px; object-fit: cover;"
                      @error="handleAvatarError"
                    />
                    <input
                      type="file"
                      class="form-control-file"
                      @change="onAvatarChange"
                    />
                  </div>
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i>Lưu thay đổi
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Finance (Acoin) Content -->
    <div v-if="activeTab === 'finance'" class="row">
      <div class="col-12">
        <div class="card mb-3">
          <div class="card-body py-2 d-flex flex-wrap gap-3 align-items-center">
            <div class="me-4"><strong>Tổng số dư Acoin tất cả trẻ:</strong> <span class="badge bg-success ms-1">{{ totalAcoin }}</span></div>
            <div v-if="currentFundingKid"><strong>Đang xem:</strong> {{ currentFundingKid.name }} ({{ currentFundingKid.acoin_balance || 0 }} Acoin)</div>
          </div>
        </div>
        <div class="card">
          <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-coins me-2"></i>Quản lý Acoin cho trẻ</h5>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-light btn-sm" @click="refreshKids" :disabled="fundingLoading">
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': fundingLoading }"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-else>
              <div v-if="!kids.length" class="text-center text-muted py-5">
                <i class="fas fa-child fa-3x mb-3"></i>
                <p>Bạn chưa có trẻ em nào để nạp Acoin.</p>
              </div>
              <div v-else class="row g-4">
                <div class="col-lg-4">
                  <!-- funding form -->
                  <form @submit.prevent="fundAcoin" class="border rounded p-3 h-100">
                    <h6 class="mb-3">Nạp Acoin</h6>
                    <div class="mb-3">
                      <label class="form-label">Chọn trẻ</label>
                      <select v-model="fundingKidId" class="form-select" required @change="handleKidSelection">
                        <option value="" disabled>-- Chọn --</option>
                        <option v-for="k in kids" :key="k.id" :value="k.id">{{ k.name }}</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Số Acoin muốn nạp</label>
                      <input type="number" min="1" class="form-control" v-model.number="fundingAmount" required placeholder="VD: 100" />
                    </div>
                    <div class="mb-3 small text-muted" v-if="currentFundingKid">
                      Số dư hiện tại: <strong>{{ currentFundingKid.acoin_balance || 0 }}</strong> Acoin
                    </div>
                    <div class="d-flex gap-2 mb-3">
                      <button type="button" class="btn btn-outline-secondary btn-sm" v-for="q in quickAmounts" :key="q" @click="fundingAmount = q">+{{ q }}</button>
                    </div>
                    <button type="submit" class="btn btn-success w-100" :disabled="fundingLoading">
                      <span v-if="!fundingLoading"><i class="fas fa-plus-circle me-1"></i>Nạp</span>
                      <span v-else><i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...</span>
                    </button>
                  </form>
                </div>
                <div class="col-lg-8">
                  <h6 class="mb-3 d-flex align-items-center">Danh sách trẻ & số dư Acoin</h6>
                  <div class="table-responsive mb-3">
                    <table class="table table-striped align-middle">
                      <thead>
                        <tr>
                          <th>Trẻ</th>
                          <th class="text-center">Số dư Acoin</th>
                          <th class="text-center">Điểm thưởng</th>
                          <th class="text-center">Lịch sử</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="k in kids" :key="k.id" :class="{ 'table-active': k.id === transactionKidId }">
                          <td>
                            <div class="d-flex align-items-center">
                              <img :src="k.avatar_url || defaultAvatar" class="rounded-circle me-2" style="width:40px;height:40px;object-fit:cover;" @error="handleAvatarError" />
                              <div>
                                <div class="fw-semibold">{{ k.name }}</div>
                                <div class="small text-muted">{{ k.email }}</div>
                              </div>
                            </div>
                          </td>
                          <td class="text-center"><span class="badge bg-success">{{ k.acoin_balance || 0 }}</span></td>
                          <td class="text-center"><span class="badge bg-primary">{{ k.total_points || 0 }}</span></td>
                          <td class="text-center">
                            <button class="btn btn-sm" :class="k.id === transactionKidId ? 'btn-secondary' : 'btn-outline-secondary'" @click="toggleTransactions(k)">
                              <i class="fas fa-list"></i>
                            </button>
                          </td>
                          <td class="text-end">
                            <button class="btn btn-sm btn-outline-success me-1" @click="quickFund(k)" :disabled="fundingLoading" title="Nạp nhanh 100">
                              <i class="fas fa-plus"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div v-if="transactionKidId" class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <h6 class="mb-0"><i class="fas fa-history me-1"></i>Lịch sử giao dịch ({{ transactions.length }})</h6>
                      <div>
                        <button class="btn btn-sm btn-outline-primary me-2" @click="reloadTransactions" :disabled="transactionsLoading">
                          <i class="fas fa-sync-alt" :class="{ 'fa-spin': transactionsLoading }"></i>
                        </button>
                        <select class="form-select form-select-sm d-inline-block w-auto" v-model.number="transactionLimit" @change="reloadTransactions">
                          <option v-for="opt in [20,50,100,200]" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                      </div>
                    </div>
                    <div v-if="transactionsLoading" class="text-center py-3">
                      <div class="spinner-border spinner-border-sm text-primary"></div>
                    </div>
                    <div v-else-if="!transactions.length" class="text-muted small fst-italic">Chưa có giao dịch.</div>
                    <div v-else class="table-responsive" style="max-height:300px;overflow:auto;">
                      <table class="table table-sm align-middle mb-0">
                        <thead class="table-secondary position-sticky top-0">
                          <tr>
                            <th>Thời gian</th>
                            <th class="text-end">Số tiền</th>
                            <th>Loại</th>
                            <th>Mô tả</th>
                            <th class="text-end">Số dư sau</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="tx in transactions" :key="tx.id">
                            <td class="small">{{ formatDate(tx.created_at) }}</td>
                            <td class="text-end" :class="tx.amount > 0 ? 'text-success' : 'text-danger'">
                              {{ tx.amount > 0 ? '+' : ''}}{{ tx.amount }}
                            </td>
                            <td><span class="badge" :class="typeBadgeClass(tx.type)">{{ mapType(tx.type) }}</span></td>
                            <td class="small">{{ tx.description || '-' }}</td>
                            <td class="text-end small fw-semibold">{{ tx.balance_after }}</td>
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
      </div>
    </div>

    <!-- Request Image Modal -->
    <RequestImageModal
      v-model="showRequestImageModal"
      :request="currentRequestImage"
      @closed="currentRequestImage = null"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import KidDetail from './parent/KidDetail.vue';
import Toast from './common/Toast.vue';
import RequestImageModal from './common/RequestImageModal.vue';

const router = useRouter();
const loading = ref(true);
const profile = ref(null);
const kids = ref([]);
const requests = ref([]);
const activeTab = ref('dashboard');
const selectedKid = ref(null);
const selectedRequest = ref(null);
const showToast = ref(false);
const toastMessage = ref('');
const toastTitle = ref('Thông báo');
const toastType = ref('success');

const defaultAvatar = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjBGMEYwIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iMzgiIHI9IjEyIiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0yNSA3NUM0MCA2NSA2MCA2NSA3NSA3NVY3NUgyNVoiIGZpbGw9IiNDQ0MiLz4KPC9zdmc+';

const pendingRequests = computed(() => {
  return requests.value.filter(req => req.status === 'pending');
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

const handleAvatarError = (event) => {
  event.target.src = defaultAvatar;
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
    // Fetch data separately to better handle errors
    try {
      const profileResponse = await axios.get('/api/parent/profile');
      profile.value = profileResponse.data.profile;
    } catch (error) {
      console.error('Error fetching profile:', error);
    }

    try {
      const kidsResponse = await axios.get('/api/parent/kids');
      // Check if response has expected format and assign accordingly
      if (Array.isArray(kidsResponse.data)) {
        kids.value = kidsResponse.data;
      } else if (kidsResponse.data && Array.isArray(kidsResponse.data.kids)) {
        kids.value = kidsResponse.data.kids;
      } else {
        console.error('Unexpected kids data format:', kidsResponse.data);
        kids.value = [];
      }
      console.log('Kids data loaded:', kids.value);
    } catch (error) {
      console.error('Error fetching kids:', error);
      kids.value = [];
    }

    try {
      const requestsResponse = await axios.get('/api/parent/requests');
      if (requestsResponse.data && Array.isArray(requestsResponse.data.requests)) {
        requests.value = requestsResponse.data.requests;
      } else {
        console.error('Unexpected requests data format:', requestsResponse.data);
        requests.value = [];
      }
    } catch (error) {
      console.error('Error fetching requests:', error);
      requests.value = [];
    }

  } catch (error) {
    console.error('General error fetching data:', error);
    if (error.response?.status === 401 || error.response?.status === 403) {
      logout();
    }
  } finally {
    loading.value = false;
  }
};

const viewKidDetails = (kid) => {
  selectedKid.value = kid;
  activeTab.value = 'kidDetail';
  selectedRequest.value = null;
};

const selectRequest = (request) => {
  selectedRequest.value = request;
};

const handleRequestProcessed = () => {
  fetchData();
};

const handleViewKidDetails = (request) => {
  if (request.child) {
    viewKidDetails(request.child);
    selectRequest(request);
  }
};

const showToastMessage = (message, title = 'Thông báo', type = 'success') => {
  toastMessage.value = message;
  toastTitle.value = title;
  toastType.value = type;
  showToast.value = true;
};

const updateProfile = async () => {
  loading.value = true;
  try {
    const formData = new FormData();
    formData.append('name', profile.value.name);
    formData.append('phone', profile.value.phone);
    formData.append('address', profile.value.address);
    if (profile.value.avatar) {
      formData.append('avatar', profile.value.avatar);
    }

    await axios.post('/api/parent/profile', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    // Refetch profile data
    const profileResponse = await axios.get('/api/parent/profile');
    profile.value = profileResponse.data.profile;

    showToastMessage('Cập nhật hồ sơ thành công!', 'Thành công', 'success');
  } catch (error) {
    console.error('Error updating profile:', error);
    showToastMessage('Đã xảy ra lỗi khi cập nhật hồ sơ', 'Lỗi', 'danger');
  } finally {
    loading.value = false;
  }
};

const onAvatarChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      profile.value.avatar_url = e.target.result;
    };
    reader.readAsDataURL(file);
    profile.value.avatar = file;
  }
};

const fundingKidId = ref('');
const fundingAmount = ref(null);
const fundingLoading = ref(false);
const quickAmounts = [50,100,200,500];
const totalAcoin = computed(()=> kids.value.reduce((sum,k)=> sum + (parseInt(k.acoin_balance)||0), 0));
const transactions = ref([]);
const transactionsLoading = ref(false);
const transactionKidId = ref(null);
const transactionLimit = ref(50);

const currentFundingKid = computed(() => kids.value.find(k => k.id === fundingKidId.value));

const refreshKids = () => {
  fetchData();
};

const fundAcoin = async () => {
  if(!fundingKidId.value || !fundingAmount.value || fundingAmount.value < 1) return;
  fundingLoading.value = true;
  try {
    const kidId = fundingKidId.value;
    const amount = fundingAmount.value;
    const res = await axios.post(`/api/parent/kids/${kidId}/acoin-fund`, { amount });
    // Update local kid balance
    const kid = kids.value.find(k => k.id === kidId);
    if(kid) kid.acoin_balance = res.data.balance_after;
    showToastMessage('Nạp Acoin thành công', 'Thành công', 'success');
    fundingAmount.value = null;
  } catch (e) {
    console.error('Fund error', e);
    showToastMessage(e.response?.data?.message || 'Lỗi nạp Acoin', 'Lỗi', 'danger');
  } finally {
    fundingLoading.value = false;
  }
};

const quickFund = (kid) => {
  fundingKidId.value = kid.id;
  fundingAmount.value = 100; // default quick amount
  fundAcoin();
};

const typeBadgeClass = (type) => {
  switch(type){
    case 'fund': return 'bg-success';
    case 'spend': return 'bg-danger';
    case 'trade_buy': return 'bg-primary';
    case 'trade_sell': return 'bg-warning text-dark';
    default: return 'bg-secondary';
  }
};
const mapType = (type) => ({fund:'Nạp',spend:'Chi',trade_buy:'Mua CP',trade_sell:'Bán CP'}[type] || type);

const loadTransactions = async (kidId) => {
  if(!kidId) return;
  transactionsLoading.value = true;
  try {
    const res = await axios.get(`/api/parent/kids/${kidId}/acoin-transactions`, { params: { limit: transactionLimit.value }});
    transactions.value = res.data.transactions || [];
  } catch(e){
    console.error('Load transactions error', e);
    showToastMessage('Không tải được lịch sử', 'Lỗi', 'danger');
    transactions.value = [];
  } finally {
    transactionsLoading.value = false;
  }
};

const toggleTransactions = (kid) => {
  if(transactionKidId.value === kid.id){
    transactionKidId.value = null;
    transactions.value = [];
    return;
  }
  transactionKidId.value = kid.id;
  fundingKidId.value = kid.id; // sync selection
  loadTransactions(kid.id);
};

const reloadTransactions = () => {
  if(transactionKidId.value) loadTransactions(transactionKidId.value);
};

const handleKidSelection = () => {
  transactionKidId.value = fundingKidId.value;
  loadTransactions(transactionKidId.value);
};

// Request image modal state
const showRequestImageModal = ref(false);
const currentRequestImage = ref(null);

const openRequestImage = (request) => {
  if(!request?.image_url) return;
  currentRequestImage.value = request;
  showRequestImageModal.value = true;
};
const onRequestImageError = (e) => {
  e.target.style.opacity = 0.4;
  e.target.title = 'Không tải được ảnh';
};

onMounted(() => {
  fetchData();
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

.parent-dashboard {
  background-color: #e6f2ff; /* Light blue background */
  min-height: 100vh;
  padding-bottom: 2rem;
}

.table-active { --bs-table-accent-bg: #e8f7ff; }
.request-thumb { width:48px; height:48px; object-fit:cover; cursor:pointer; transition:filter .15s, transform .15s; }
.request-thumb:hover { filter:brightness(0.9); transform:scale(1.05); }
.thumb-wrapper { width:50px; }
</style>
