<template>
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu tới phụ huynh</h5>
    </div>
    <div class="card-body">
      <form @submit.prevent="submitRequest" enctype="multipart/form-data">
        <!-- Title -->
        <div class="mb-3">
          <label for="requestTitle" class="form-label">Tiêu đề yêu cầu</label>
          <input
            type="text"
            class="form-control"
            id="requestTitle"
            v-model="requestData.title"
            placeholder="Nhập tiêu đề yêu cầu"
            required
          />
        </div>
        <!-- Type -->
        <div class="mb-3">
          <label for="requestType" class="form-label">Loại yêu cầu</label>
          <select
            class="form-select"
            id="requestType"
            v-model="requestData.type"
            required
          >
            <option value="" disabled>Chọn loại yêu cầu</option>
            <option value="toy">Đồ chơi</option>
            <option value="food">Món ăn</option>
            <option value="playground">Đi khu vui chơi</option>
            <option value="activity">Hoạt động mong muốn</option>
          </select>
        </div>
        <!-- Description -->
        <div class="mb-3">
          <label for="requestDescription" class="form-label">Mô tả chi tiết</label>
            <textarea
              class="form-control"
              id="requestDescription"
              v-model="requestData.description"
              rows="3"
              placeholder="Mô tả yêu cầu của bạn chi tiết hơn..."
            ></textarea>
        </div>
        <!-- Image Upload -->
        <div class="mb-3">
          <label class="form-label">Hình minh họa (tùy chọn)</label>
          <input
            class="form-control"
            type="file"
            accept="image/*"
            @change="handleFileChange"
          />
          <small class="text-muted d-block mt-1">Chấp nhận: jpg, jpeg, png, gif, webp. Tối đa 2MB.</small>
          <div v-if="imagePreview" class="mt-3 position-relative" style="max-width: 240px;">
            <img :src="imagePreview" alt="Preview" class="img-thumbnail" />
            <button
              type="button"
              class="btn btn-sm btn-danger position-absolute top-0 end-0"
              @click="removeImage"
              title="Xóa hình"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div v-if="imageError" class="text-danger mt-2 small">
            {{ imageError }}
          </div>
        </div>
        <!-- Buttons -->
        <div class="d-flex justify-content-end">
          <button
            type="button"
            class="btn btn-outline-secondary me-2"
            @click="resetForm"
          >
            <i class="fas fa-times me-1"></i>Hủy
          </button>
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="isSubmitting"
          >
            <i class="fas fa-paper-plane me-1"></i>
            <span v-if="isSubmitting">
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              Đang gửi...
            </span>
            <span v-else>Gửi yêu cầu</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const emit = defineEmits(['request-submitted']);

const isSubmitting = ref(false);
const requestData = reactive({
  title: '',
  type: '',
  description: ''
});

const imageFile = ref(null);
const imagePreview = ref(null);
const imageError = ref('');

const MAX_SIZE = 2 * 1024 * 1024; // 2MB
const ALLOWED_TYPES = ['image/jpeg','image/png','image/jpg','image/gif','image/webp'];

const handleFileChange = (e) => {
  imageError.value = '';
  const file = e.target.files[0];
  if (!file) {
    imageFile.value = null;
    imagePreview.value = null;
    return;
  }
  if (!ALLOWED_TYPES.includes(file.type)) {
    imageError.value = 'Định dạng không hợp lệ.';
    return;
  }
  if (file.size > MAX_SIZE) {
    imageError.value = 'Kích thước vượt quá 2MB.';
    return;
  }
  imageFile.value = file;
  const reader = new FileReader();
  reader.onload = (evt) => { imagePreview.value = evt.target.result; };
  reader.readAsDataURL(file);
};

const removeImage = () => {
  imageFile.value = null;
  imagePreview.value = null;
  imageError.value = '';
};

const submitRequest = async () => {
  if (!requestData.title || !requestData.type) return;
  if (imageError.value) return; // prevent submission if image invalid

  isSubmitting.value = true;

  try {
    const formData = new FormData();
    formData.append('title', requestData.title);
    formData.append('type', requestData.type);
    if (requestData.description) formData.append('description', requestData.description);
    if (imageFile.value) formData.append('image', imageFile.value);

    const response = await axios.post('/api/kid/requests', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    resetForm();
    emit('request-submitted', response.data);
    alert('Yêu cầu của bạn đã được gửi thành công!');
  } catch (error) {
    if (error.response && error.response.status === 422) {
      imageError.value = error.response.data?.errors?.image?.[0] || 'Dữ liệu không hợp lệ.';
    } else {
      console.error('Error submitting request:', error);
      alert('Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại sau.');
    }
  } finally {
    isSubmitting.value = false;
  }
};

const resetForm = () => {
  requestData.title = '';
  requestData.type = '';
  requestData.description = '';
  removeImage();
};
</script>

<!-- No style block added; relies on existing global styles -->
