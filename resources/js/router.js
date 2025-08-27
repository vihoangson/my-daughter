import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import Login from './components/Login.vue';
import Dashboard from './components/Dashboard.vue';
import KidDashboard from './components/KidDashboard.vue';

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Dashboard,
    meta: { requiresAuth: true, requiresParent: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/user-kid',
    name: 'KidDashboard',
    component: KidDashboard,
    meta: { requiresAuth: true, requiresKid: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation guard
router.beforeEach(async (to, from, next) => {
  const token = localStorage.getItem('token');

  if (to.meta.requiresAuth && !token) {
    next('/login');
    return;
  }

  if (token && !window.currentUser) {
    try {
      const response = await axios.get('/api/auth/me');
      window.currentUser = response.data;
    } catch (error) {
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
      next('/login');
      return;
    }
  }

  // Check user type requirements
  if (to.meta.requiresParent && window.currentUser?.type !== 'parent') {
    if (window.currentUser?.type === 'child') {
      next('/user-kid');
    } else {
      next('/login');
    }
    return;
  }

  if (to.meta.requiresKid && window.currentUser?.type !== 'child') {
    if (window.currentUser?.type === 'parent') {
      next('/');
    } else {
      next('/login');
    }
    return;
  }

  next();
});

export default router;
