import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import Login from './components/Login.vue';
import SimpleLogin from './components/SimpleLogin.vue';
import Dashboard from './components/Dashboard.vue';
import ParentDashboard from './components/ParentDashboard.vue';
import KidDashboard from './components/KidDashboard.vue';
import KidManager from './components/KidManager.vue';
import KidDetail from './components/parent/KidDetail.vue';
import Settings from './components/Settings.vue';

const routes = [
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
    path: '/',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/parent-dashboard',
    name: 'ParentDashboard',
    component: ParentDashboard,
    meta: { requiresAuth: true, requiresParent: true }
  },
  {
    path: '/user-kid',
    name: 'KidDashboard',
    component: KidDashboard,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/kid-manager',
    name: 'KidManager',
    component: KidManager,
    meta: { requiresAuth: true, requiresParent: true }
  },
  {
    path: '/kid/:id',
    name: 'KidDetail',
    component: KidDetail,
    meta: { requiresAuth: true, requiresParent: true }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: Settings,
    meta: { requiresAuth: true, requiresParent: true }
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
