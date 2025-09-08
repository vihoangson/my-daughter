import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import Login from './components/Login.vue';
import SimpleLogin from './components/SimpleLogin.vue';
import Homepage from './components/Homepage.vue';
import GameSelection from './components/GameSelection.vue';
import PuzzleChallenge from './components/PuzzleChallenge.vue';
import MathAdventure from './components/MathAdventure.vue';
import MemoryMatch from './components/MemoryMatch.vue';
import MusicMaker from './components/MusicMaker.vue';
import ScienceLab from './components/ScienceLab.vue';
import AnimalQuiz from './components/AnimalQuiz.vue';
import KidRequestClassifier from './components/kid/KidRequestClassifier.vue'; // added
import KidRequests from './components/kid/KidRequests.vue';
import KidPoints from './components/kid/KidPoints.vue';
import KidStocks from './components/kid/KidStocks.vue';
import KidAchievements from './components/kid/KidAchievements.vue';
import KidProfile from './components/kid/KidProfile.vue';
import ParentDashboardV2 from './components/ParentDashboardV2.vue';
import KidDashboardV2 from "@/components/KidDashboardV2.vue";

// Persist auth header across F5
const existingToken = localStorage.getItem('token');
if (existingToken) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${existingToken}`;
}

const routes = [
  {
    path: '/',
    name: 'Homepage',
    component: Homepage
  },
  // New clearer parent route
  {
    path: '/parent',
    name: 'ParentDashboard',
    component: ParentDashboardV2,
    meta: { requiresAuth: true, requiresParent: true }
  },
  // Legacy path kept for backward compatibility
  {
    path: '/user-parent',
    redirect: '/parent'
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
    component: KidDashboardV2,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/game',
    name: 'GameSelection',
    component: GameSelection,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/game/puzzle-challenge',
    name: 'PuzzleChallenge',
    component: PuzzleChallenge,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/game/math-adventure',
    name: 'MathAdventure',
    component: MathAdventure,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/game/memory-match',
    name: 'MemoryMatch',
    component: MemoryMatch,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/game/music-maker',
    name: 'MusicMaker',
    component: MusicMaker,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/game/science-lab',
    name: 'ScienceLab',
    component: ScienceLab,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/game/animal-quiz',
    name: 'AnimalQuiz',
    component: AnimalQuiz,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/classify-requests',
    name: 'KidRequestClassifier',
    component: KidRequestClassifier,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/requests',
    name: 'KidRequests',
    component: KidRequests,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/points',
    name: 'KidPoints',
    component: KidPoints,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/stocks',
    name: 'KidStocks',
    component: KidStocks,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/achievements',
    name: 'KidAchievements',
    component: KidAchievements,
    meta: { requiresAuth: true, requiresKid: true }
  },
  {
    path: '/user-kid/profile',
    name: 'KidProfile',
    component: KidProfile,
    meta: { requiresAuth: true, requiresKid: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

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

  // Resume last protected route when hitting root or login while already authenticated
  if (window.currentUser && (to.path === '/' || to.path === '/login')) {
    const last = localStorage.getItem('lastRoute');
    if (last && last !== '/login' && last !== to.fullPath) {
      next(last);
      return;
    }
  }

  // Redirect based on user type if landing at root without lastRoute
  if (window.currentUser && to.path === '/') {
    if (window.currentUser.type === 'parent') {
      next('/parent');
      return;
    } else if (window.currentUser.type === 'child') {
      next('/user-kid');
      return;
    }
  }

  if (window.currentUser) {
    if (window.currentUser.type === 'child' &&
        to.path !== '/user-kid' &&
        !to.path.startsWith('/user-kid/') &&
        to.path !== '/classify-requests') {
      next('/user-kid');
      return;
    }
  }

  if (to.meta.requiresKid && (!window.currentUser || window.currentUser.type !== 'child')) {
    next('/login');
    return;
  }

  if (to.meta.requiresParent && (!window.currentUser || window.currentUser.type !== 'parent')) {
    next('/login');
    return;
  }

  next();
});

router.afterEach((to) => {
  if (window.currentUser && to.meta.requiresAuth) {
    localStorage.setItem('lastRoute', to.fullPath);
  }
});

export default router;
