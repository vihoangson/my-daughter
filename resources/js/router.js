import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import Login from './components/Login.vue';
import SimpleLogin from './components/SimpleLogin.vue';
import ParentDashboard from './components/ParentDashboard.vue';
import KidDashboard from './components/KidDashboard.vue';

const routes = [
  {
    path: '/',
    name: 'Home',
    component: ParentDashboard,
    meta: { requiresAuth: true, requiresParent: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/simple-login',
    name: 'SimpleLogin',
    component: SimpleLogin
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

  // Redirect kid users to the kid dashboard
  if (window.currentUser && window.currentUser.type === 'child') {
    if (to.path !== '/user-kid') {
      next('/user-kid');
      return;
    }
  }

  // Check if route requires kid user
  if (to.meta.requiresKid && (!window.currentUser || window.currentUser.type !== 'child')) {
    next('/');
    return;
  }

  // Check if route requires parent user
  if (to.meta.requiresParent && (!window.currentUser || window.currentUser.type !== 'parent')) {
    next('/user-kid');
    return;
  }

  next();
});

export default router;
