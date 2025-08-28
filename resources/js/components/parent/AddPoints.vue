<template>
  <div class="add-points-form">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Thêm điểm</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitPoints">
          <div class="mb-3">
            <label class="form-label">Loại điểm *</label>
            <div class="d-flex">
              <div class="form-check form-check-inline">
                <input
                  class="form-check-input"
                  type="radio"
                  id="typeReward"
                  value="reward"
                  v-model="pointForm.type"
                  name="pointType"
                  required
                />
                <label class="form-check-label" for="typeReward">
                  <span class="text-success"><i class="fas fa-plus me-1"></i>Thưởng</span>
                </label>
              </div>
              <div class="form-check form-check-inline">
                <input
                  class="form-check-input"
                  type="radio"
                  id="typePunishment"
                  value="punishment"
                  v-model="pointForm.type"
                  name="pointType"
                  required
                />
                <label class="form-check-label" for="typePunishment">
                  <span class="text-danger"><i class="fas fa-minus me-1"></i>Phạt</span>
                </label>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Số điểm *</label>
            <input
              type="number"
              class="form-control"
              v-model.number="pointForm.points"
              min="1"
              max="100"
              required
              :class="{ 'is-invalid': errors.points }"
            />
            <div v-if="errors.points" class="invalid-feedback">{{ errors.points[0] }}</div>
            <div class="form-text">
              <span v-if="pointForm.type === 'reward'" class="text-success">
                <i class="fas fa-plus-circle me-1"></i>Cộng {{ pointForm.points || 0 }} điểm
              </span>
              <span v-else class="text-danger">
                <i class="fas fa-minus-circle me-1"></i>Trừ {{ pointForm.points || 0 }} điểm
              </span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Mô tả *</label>
            <textarea
              class="form-control"
              v-model="pointForm.description"
              rows="2"
              placeholder="Ví dụ: Dọn dẹp phòng ngủ, hoàn thành bài tập về nhà..."
              required
              :class="{ 'is-invalid': errors.description }"
            ></textarea>
            <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] }}</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Hình ảnh bằng chứng (không bắt buộc)</label>
            <input
              type="file"
              class="form-control"
              @change="handleFileChange"
              accept="image/*"
              :class="{ 'is-invalid': errors.evidence }"
            />
            <div v-if="errors.evidence" class="invalid-feedback">{{ errors.evidence[0] }}</div>
            <div class="form-text">Hình ảnh không quá 2MB, định dạng: JPG, PNG, GIF</div>
          </div>

          <div v-if="imagePreview" class="mb-3 text-center">
            <img :src="imagePreview" alt="Preview" class="img-thumbnail" style="max-height: 200px" />
            <button type="button" class="btn btn-sm btn-danger d-block mx-auto mt-2" @click="clearImage">
              <i class="fas fa-times me-1"></i>Xóa ảnh
            </button>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-save me-2"></i>
              {{ submitting ? 'Đang lưu...' : 'Lưu điểm' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue';
import axios from 'axios';

const props = defineProps({
  kidId: {
    type: [Number, String],
    required: true
  }
});

const emit = defineEmits(['point-added']);

const pointForm = ref({
  type: 'reward',
  points: 5,
  description: '',
  evidence: null
});

const submitting = ref(false);
const errors = ref({});
const imagePreview = ref(null);

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (!file) {
    clearImage();
    return;
  }

  // Validate file type and size
  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
  if (!validTypes.includes(file.type)) {
    alert('Vui lòng chọn file ảnh (JPEG, PNG, JPG, GIF)');
    clearImage();
    return;
  }

  if (file.size > 2048 * 1024) { // 2MB
    alert('File ảnh không được vượt quá 2MB');
    clearImage();
    return;
  }

  // Set file to form data
  pointForm.value.evidence = file;

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const clearImage = () => {
  pointForm.value.evidence = null;
  imagePreview.value = null;
  // Clear file input
  const fileInput = document.querySelector('input[type="file"]');
  if (fileInput) {
    fileInput.value = '';
  }
};

const submitPoints = async () => {
  submitting.value = true;
  errors.value = {};

  try {
    const formData = new FormData();
    formData.append('type', pointForm.value.type);
    formData.append('points', pointForm.value.points);
    formData.append('description', pointForm.value.description);

    if (pointForm.value.evidence) {
      formData.append('evidence', pointForm.value.evidence);
    }

    const response = await axios.post(`/api/parent/kids/${props.kidId}/points`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    // Reset form
    pointForm.value.description = '';
    pointForm.value.evidence = null;
    imagePreview.value = null;

    // Clear file input
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
      fileInput.value = '';
    }

    // Emit event to parent component
    emit('point-added', response.data);

    // Show success message
    alert('Đã thêm điểm thành công!');
  } catch (error) {
    console.error('Error adding points:', error);

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else if (error.response?.data?.message) {
      alert(`Lỗi: ${error.response.data.message}`);
    } else {
      alert('Đã xảy ra lỗi khi thêm điểm. Vui lòng thử lại sau.');
    }
  } finally {
    submitting.value = false;
  }
};
</script>
