<template>
  <div class="modal d-block" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Chỉnh sửa thông tin: {{ kid.name }}</h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        <form @submit.prevent="submit">
          <div class="modal-body">
            <div v-if="successMsg" class="alert alert-success py-2">{{ successMsg }}</div>
            <div v-if="errorMsg" class="alert alert-danger py-2">{{ errorMsg }}</div>

            <!-- Avatar Section -->
            <div class="text-center mb-4">
              <div class="position-relative d-inline-block">
                <img
                  :src="avatarPreview || kid.avatar_url || '/images/default-avatar.png'"
                  alt="Avatar"
                  class="rounded-circle border"
                  style="width: 120px; height: 120px; object-fit: cover;"
                  @error="handleImageError"
                />
                <button
                  type="button"
                  class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle"
                  style="width: 35px; height: 35px;"
                  @click="$refs.avatarInput.click()"
                >
                  <i class="fas fa-camera"></i>
                </button>
              </div>
              <input
                ref="avatarInput"
                type="file"
                class="d-none"
                accept="image/*"
                @change="handleAvatarChange"
              />
              <div class="mt-2">
                <small class="text-muted">Nhấn vào nút camera để thay đổi avatar</small>
              </div>
            </div>

            <!-- Form Fields -->
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Tên <span class="text-danger">*</span></label>
                <input
                  class="form-control"
                  v-model="form.name"
                  required
                  :class="{ 'is-invalid': errors.name }"
                />
                <div class="invalid-feedback" v-if="errors.name">{{ errors.name[0] }}</div>
              </div>

              <div class="col-12">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input
                  type="email"
                  class="form-control"
                  v-model="form.email"
                  required
                  :class="{ 'is-invalid': errors.email }"
                />
                <div class="invalid-feedback" v-if="errors.email">{{ errors.email[0] }}</div>
              </div>

              <div class="col-12">
                <label class="form-label">Mật khẩu mới</label>
                <input
                  type="password"
                  class="form-control"
                  v-model="form.password"
                  placeholder="Để trống nếu không muốn thay đổi"
                  :class="{ 'is-invalid': errors.password }"
                />
                <div class="invalid-feedback" v-if="errors.password">{{ errors.password[0] }}</div>
                <small class="text-muted">Để trống nếu không muốn thay đổi mật khẩu</small>
              </div>

              <div class="col-12">
                <label class="form-label">Xác nhận mật khẩu mới</label>
                <input
                  type="password"
                  class="form-control"
                  v-model="form.password_confirmation"
                  placeholder="Nhập lại mật khẩu mới"
                  :class="{ 'is-invalid': passwordMismatch }"
                />
                <div class="invalid-feedback" v-if="passwordMismatch">Mật khẩu xác nhận không khớp</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="$emit('close')">Hủy</button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="loading || passwordMismatch"
            >
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              Cập nhật
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  kid: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'updated']);

const loading = ref(false);
const successMsg = ref('');
const errorMsg = ref('');
const errors = reactive({});
const avatarPreview = ref(null);
const avatarFile = ref(null);

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const passwordMismatch = computed(() => {
  if (!form.password && !form.password_confirmation) return false;
  return form.password !== form.password_confirmation;
});

const clearErrors = () => {
  Object.keys(errors).forEach(k => delete errors[k]);
};

const handleImageError = (event) => {
  event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjBGMEYwIi8+CjxjaXJjbGUgY3g9IjYwIiBjeT0iNDUiIHI9IjE1IiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0zMCA5MEM0MCA4MCA4MCA4MCA5MCA5MFY5MEgzMFoiIGZpbGw9IiNDQ0MiLz4KPC9zdmc+';
};

const handleAvatarChange = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Validate file type
  if (!file.type.startsWith('image/')) {
    errorMsg.value = 'Vui lòng chọn file hình ảnh';
    return;
  }

  // Validate file size (2MB)
  if (file.size > 2 * 1024 * 1024) {
    errorMsg.value = 'Kích thước file không được vượt quá 2MB';
    return;
  }

  avatarFile.value = file;

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    avatarPreview.value = e.target.result;
  };
  reader.readAsDataURL(file);

  errorMsg.value = '';
};

const submit = async () => {
  if (passwordMismatch.value) return;

  loading.value = true;
  successMsg.value = '';
  errorMsg.value = '';
  clearErrors();

  try {
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('email', form.email);

    if (form.password) {
      formData.append('password', form.password);
    }

    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value);
    }

    const { data } = await axios.post(`/api/parent/kids/${props.kid.id}?_method=PUT`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    successMsg.value = 'Cập nhật thông tin thành công!';
    emit('updated', data.kid);

    setTimeout(() => {
      emit('close');
    }, 1500);

  } catch (e) {
    if (e.response?.status === 422) {
      const validationErrors = e.response.data.errors || {};
      for (const key in validationErrors) {
        errors[key] = validationErrors[key];
      }
      errorMsg.value = 'Vui lòng kiểm tra lại thông tin';
    } else {
      errorMsg.value = e.response?.data?.message || 'Có lỗi xảy ra';
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  // Initialize form with kid data
  form.name = props.kid.name;
  form.email = props.kid.email;
});
</script>

<style scoped>
.btn-close:focus {
  box-shadow: none;
}
</style>
