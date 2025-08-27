<template>
  <div class="row justify-content-center">
    <div class="col-md-5">
      <h3 class="mb-3">Login</h3>
      <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
      <form @submit.prevent="submit">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" v-model="form.email" required />
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
            <input type="password" class="form-control" v-model="form.password" required />
        </div>
        <button class="btn btn-primary w-100" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2" />
          Login
        </button>
      </form>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
const router = useRouter();
const form = reactive({ email: 'parent@example.com', password: 'password' });
const loading = ref(false);
const error = ref('');
const submit = async () => {
  error.value='';
  loading.value=true;
  try {
    const { data } = await axios.post('/api/auth/login', form);
    localStorage.setItem('token', data.token);
    axios.defaults.headers.common['Authorization'] = 'Bearer '+data.token;
    window.currentUser = data.user;

    // Redirect based on user type
    const redirectPath = data.redirect_path || '/';
    router.push(redirectPath);
  } catch(e){
    error.value = e.response?.data?.message || 'Login failed';
  } finally { loading.value=false; }
};
</script>
