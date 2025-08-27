<template>
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu tới phụ huynh</h5>
    </div>
    <div class="card-body">
      <form @submit.prevent="submitRequest">
        <div class="mb-3">
          <label for="requestTitle" class="form-label">Tiêu đề yêu cầu</label>
          <input
            type="text"
            class="form-control"
            id="requestTitle"
            v-model="requestData.title"
            placeholder="Nhập tiêu đề yêu cầu"
            required
          >
        </div>

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

const submitRequest = async () => {
  if (!requestData.title || !requestData.type) {
    return;
  }

  isSubmitting.value = true;

  try {
    const response = await axios.post('/api/kid/requests', requestData);

    // Reset form
    resetForm();

    // Notify parent component that a new request was submitted
    emit('request-submitted', response.data);

    // Show success notification
    alert('Yêu cầu của bạn đã được gửi thành công!');
  } catch (error) {
    console.error('Error submitting request:', error);
    alert('Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại sau.');
  } finally {
    isSubmitting.value = false;
  }
};

const resetForm = () => {
  requestData.title = '';
  requestData.type = '';
  requestData.description = '';
};
</script>

