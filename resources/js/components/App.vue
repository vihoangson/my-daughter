<template>
  <div class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-3" v-if="isAuth">
      <div class="container-fluid">
        <a class="navbar-brand" href="#" @click.prevent="goDashboard">MyDaughter</a>

        <!-- Navigation Menu for Parents -->
        <div class="navbar-nav me-auto" v-if="currentUser && currentUser.type === 'parent'">
          <router-link to="/" class="nav-link">Dashboard</router-link>
          <router-link to="/settings" class="nav-link">Cài đặt</router-link>
        </div>

        <div class="d-flex align-items-center">
          <span v-if="currentUser" class="me-3">
            Xin chào, {{ currentUser.name }}
            <span class="badge bg-secondary ms-1">
              {{ currentUser.type === 'child' ? 'Trẻ em' : 'Phụ huynh' }}
            </span>
          </span>
          <button class="btn btn-outline-danger" @click="logout">Đăng xuất</button>
        </div>
      </div>
    </nav>

    <main class="flex-grow-1">
      <div class="container-fluid">
        <router-view />
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-auto" v-if="isAuth">
      <div class="container">
        <div class="row">
          <div class="col-md-6">
            <h5>MyDaughter v1.0</h5>
            <p class="mb-1">Hệ thống quản lý gia đình thông minh</p>
            <p class="small text-muted">Giúp kết nối và quản lý hoạt động giữa phụ huynh và con em</p>
          </div>
          <div class="col-md-3">
            <h6>Liên kết nhanh</h6>
            <ul class="list-unstyled">
              <li v-if="currentUser && currentUser.type === 'parent'">
                <router-link to="/" class="text-light text-decoration-none">Dashboard</router-link>
              </li>
              <li v-if="currentUser && currentUser.type === 'parent'">
                <router-link to="/settings" class="text-light text-decoration-none">Cài đặt</router-link>
              </li>
              <li v-if="currentUser && currentUser.type === 'child'">
                <router-link to="/user-kid" class="text-light text-decoration-none">Bảng điều khiển</router-link>
              </li>
            </ul>
          </div>
          <div class="col-md-3">
            <h6>Thông tin</h6>
            <ul class="list-unstyled">
              <li class="small">
                <span class="text-muted">Người dùng:</span> {{ currentUser?.name }}
              </li>
              <li class="small">
                <span class="text-muted">Loại tài khoản:</span>
                {{ currentUser?.type === 'child' ? 'Trẻ em' : 'Phụ huynh' }}
              </li>
              <li class="small">
                <span class="text-muted">Phiên bản:</span> 1.0
              </li>
            </ul>
          </div>
        </div>
        <hr class="my-3">
        <div class="row">
          <div class="col-12 text-center">
            <p class="mb-0 small text-muted">
              © {{ currentYear }} MyDaughter System. Được phát triển với ❤️ cho gia đình Việt Nam.
            </p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const router = useRouter();
const isAuth = ref(!!localStorage.getItem('token'));
const currentUser = ref(window.currentUser || null);
const currentYear = new Date().getFullYear();

const goDashboard = () => {
  if (isAuth.value) {
    if (currentUser.value?.type === 'child') {
      router.push('/user-kid');
    } else {
      router.push('/');
    }
  } else {
    router.push('/login');
  }
};

const logout = async () => {
  try {
    await axios.post('/api/auth/logout');
  } catch(e) {
    console.error('Logout error:', e);
  }
  localStorage.removeItem('token');
  delete axios.defaults.headers.common['Authorization'];
  window.currentUser = null;
  currentUser.value = null;
  isAuth.value = false;
  router.push('/login');
};

// Refresh auth state on route change
router.afterEach(() => {
  isAuth.value = !!localStorage.getItem('token');
  currentUser.value = window.currentUser || null;
});

// Check authentication and fetch user data on mount
onMounted(async () => {
  const token = localStorage.getItem('token');
  if (token && !window.currentUser) {
    try {
      const response = await axios.get('/api/auth/me');
      window.currentUser = response.data;
      currentUser.value = response.data;

      // Redirect to appropriate dashboard
      if (response.data.type === 'child') {
        router.push('/user-kid');
      } else {
        router.push('/');
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
      router.push('/login');
    }
  } else if (token && window.currentUser) {
    currentUser.value = window.currentUser;

    // Redirect to appropriate dashboard if on login page
    if (router.currentRoute.value.path === '/login') {
      if (window.currentUser.type === 'child') {
        router.push('/user-kid');
      } else {
        router.push('/');
      }
    }
  } else if (!token) {
    router.push('/login');
  }
});
</script>

<style scoped>
.min-vh-100 {
  min-height: 100vh;
}

.flex-grow-1 {
  flex-grow: 1;
}

footer a:hover {
  color: #adb5bd !important;
}

.text-decoration-none {
  text-decoration: none;
}
</style>
