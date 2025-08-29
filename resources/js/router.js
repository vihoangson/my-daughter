import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import Login from './components/Login.vue';
import SimpleLogin from './components/SimpleLogin.vue';
import ParentDashboard from './components/ParentDashboard.vue';
import KidDashboard from './components/KidDashboard.vue';
import Homepage from './components/Homepage.vue';
import GameSelection from './components/GameSelection.vue';

const routes = [
  {
    path: '/',
    name: 'Homepage',
    component: Homepage
  },
  {
    path: '/user-parent',
    name: 'ParentDashboard',
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
  },
  {
    path: '/user-kid/game',
    name: 'GameSelection',
    component: GameSelection,
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

  // Redirect based on user type
  if (window.currentUser) {
    // For root path, redirect based on user type
    if (to.path === '/') {
      if (window.currentUser.type === 'parent') {
        next('/user-parent');
        return;
      } else if (window.currentUser.type === 'child') {
        next('/user-kid');
        return;
      }
    }

    // Redirect kid users to the kid dashboard if they try to access other pages
    if (window.currentUser.type === 'child' &&
        to.path !== '/user-kid' &&
        !to.path.startsWith('/user-kid/')) {
      next('/user-kid');
      return;
    }
  }

  // Check if route requires kid user
  if (to.meta.requiresKid && (!window.currentUser || window.currentUser.type !== 'child')) {
    next('/login');
    return;
  }

  // Check if route requires parent user
  if (to.meta.requiresParent && (!window.currentUser || window.currentUser.type !== 'parent')) {
    next('/login');
    return;
  }

  next();
});

export default router;
