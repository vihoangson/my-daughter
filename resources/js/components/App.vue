<template>
  <div>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
      <div class="container-fluid">
        <a class="navbar-brand" href="#" @click.prevent="goDashboard">MyDaughter</a>
        <button v-if="isAuth" class="btn btn-outline-danger" @click="logout">Logout</button>
      </div>
    </nav>
    <router-view />
  </div>
</template>
<script setup>
import { useRouter } from 'vue-router';
import { ref, onMounted } from 'vue';
import axios from 'axios';
const router = useRouter();
const isAuth = ref(!!localStorage.getItem('token'));
const goDashboard = () => { if(isAuth.value) router.push({name:'dashboard'}); else router.push({name:'login'}); };
const logout = async () => {
  try { await axios.post('/api/auth/logout'); } catch(e) {}
  localStorage.removeItem('token');
  delete axios.defaults.headers.common['Authorization'];
  isAuth.value = false;
  router.push({name:'login'});
};
// refresh auth state on route change
router.afterEach(()=>{ isAuth.value = !!localStorage.getItem('token'); });
onMounted(()=>{ if(isAuth.value) router.push({name:'dashboard'}); });
</script>

