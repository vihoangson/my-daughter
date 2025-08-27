<template>
  <div>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-3" v-if="isAuth">
      <div class="container-fluid">
        <a class="navbar-brand" href="#" @click.prevent="goDashboard">MyDaughter</a>
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
    <div class="container-fluid">
      <router-view />
    </div>
  </div>
</template>
<script setup>
import { useRouter } from 'vue-router';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const router = useRouter();
const isAuth = ref(!!localStorage.getItem('token'));
const currentUser = ref(window.currentUser || null);

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
