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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import KidDetail from './parent/KidDetail.vue';

const router = useRouter();
const loading = ref(true);
const profile = ref(null);
const kids = ref([]);
const requests = ref([]);
const activeTab = ref('dashboard');
const selectedKid = ref(null);
const selectedRequest = ref(null);

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
