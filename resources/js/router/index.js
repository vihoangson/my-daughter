import { createRouter, createWebHistory } from 'vue-router';
import Login from '../components/Login.vue';
import Dashboard from '../components/Dashboard.vue';
import KidDashboard from '../components/kid/KidDashboard.vue'; // added
import KidRequestClassifier from '../components/kid/KidRequestClassifier.vue'; // new

const routes = [
  { path: '/login', name: 'login', component: Login, meta: { guest: true } },
  { path: '/', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true } },
  { path: '/user-kid', name: 'kid-dashboard', component: KidDashboard, meta: { requiresAuth: true } }, // added
  { path: '/classify-requests', name: 'classify-requests', component: KidRequestClassifier, meta: { requiresAuth: true } }, // new
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  if (to.meta.requiresAuth && !token) return next({ name: 'login' });
  if (to.meta.guest && token) return next({ name: 'dashboard' });
  next();
});

export default router;
