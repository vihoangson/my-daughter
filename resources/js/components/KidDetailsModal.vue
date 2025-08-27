<template>
  <div class="modal d-block" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Chi tiết trẻ em: {{ kid.name }}</h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="text-center">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <div v-else-if="kidDetails">
            <!-- Personal Information -->
            <div class="card mb-4">
              <div class="card-header">
                <h6 class="mb-0">Thông tin cá nhân</h6>
              </div>
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-md-3 text-center">
                    <img
                      :src="kidDetails.kid.avatar_url || '/images/default-avatar.png'"
                      alt="Avatar"
                      class="rounded-circle border"
                      style="width: 100px; height: 100px; object-fit: cover;"
                      @error="handleAvatarError"
                    />
                  </div>
                  <div class="col-md-9">
                    <div class="row">
                      <div class="col-md-6">
                        <strong>ID:</strong> {{ kidDetails.kid.id }}
                      </div>
                      <div class="col-md-6">
                        <strong>Tên:</strong> {{ kidDetails.kid.name }}
                      </div>
                      <div class="col-md-6 mt-2">
                        <strong>Email:</strong> {{ kidDetails.kid.email }}
                      </div>
                      <div class="col-md-6 mt-2">
                        <strong>Ngày tạo:</strong> {{ formatDate(kidDetails.kid.created_at) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Points Summary -->
            <div class="card mb-4">
              <div class="card-header">
                <h6 class="mb-0">Tổng quan điểm</h6>
              </div>
              <div class="card-body">
                <div class="row text-center">
                  <div class="col-md-4">
                    <div class="border rounded p-3">
                      <h4 class="text-primary mb-1">{{ kidDetails.total_points }}</h4>
                      <small class="text-muted">Tổng điểm</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="border rounded p-3">
                      <h4 class="text-info mb-1">{{ kidDetails.total_records }}</h4>
                      <small class="text-muted">Số lần ghi điểm</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="border rounded p-3">
                      <h4 class="text-success mb-1">{{ rewardCount }}</h4>
                      <small class="text-muted">Lần được thưởng</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Points History -->
            <div class="card">
              <div class="card-header">
                <h6 class="mb-0">Lịch sử điểm ({{ kidDetails.point_history.length }} bản ghi)</h6>
              </div>
              <div class="card-body">
                <div v-if="kidDetails.point_history.length === 0" class="text-muted text-center py-3">
                  Chưa có lịch sử điểm nào
                </div>
                <div v-else class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Ngày</th>
                        <th>Loại</th>
                        <th>Điểm</th>
                        <th>Ghi chú</th>
                        <th>Hình ảnh</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="record in kidDetails.point_history" :key="record.id">
                        <td>{{ formatDate(record.created_at) }}</td>
                        <td>
                          <span v-if="record.type === 'reward'" class="badge bg-success">Thưởng</span>
                          <span v-else class="badge bg-danger">Phạt</span>
                        </td>
                        <td>
                          <span v-if="record.type === 'reward'" class="text-success">+{{ record.points }}</span>
                          <span v-else class="text-danger">-{{ record.points }}</span>
                        </td>
                        <td>{{ record.description || '-' }}</td>
                        <td>
                          <div v-if="record.evidence_url">
                            <img
                              :src="record.evidence_url"
                              alt="Evidence"
                              class="img-thumbnail"
                              style="max-width: 60px; max-height: 60px; cursor: pointer;"
                              @click="showImageModal(record.evidence_url)"
                            />
                          </div>
                          <span v-else class="text-muted">-</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="error" class="alert alert-danger">
            {{ error }}
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">Đóng</button>
        </div>
      </div>
    </div>

    <!-- Image Modal -->
    <div v-if="showImage" class="modal d-block" style="background-color: rgba(0,0,0,0.8); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Hình ảnh bằng chứng</h5>
            <button type="button" class="btn-close" @click="showImage = null"></button>
          </div>
          <div class="modal-body text-center">
            <img :src="showImage" alt="Evidence" class="img-fluid" style="max-height: 500px;" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  kid: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close']);

const loading = ref(true);
const error = ref('');
const kidDetails = ref(null);
const showImage = ref(null);

const rewardCount = computed(() => {
  if (!kidDetails.value?.point_history) return 0;
  return kidDetails.value.point_history.filter(record => record.type === 'reward').length;
});

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const showImageModal = (url) => {
  showImage.value = url;
};

const handleAvatarError = (event) => {
  event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjBGMEYwIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iMzgiIHI9IjEyIiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0yNSA3NUM0MCA2NSA2MCA2NSA3NSA3NVY3NUgyNVoiIGZpbGw9IiNDQ0MiLz4KPC9zdmc+';
};

const fetchKidDetails = async () => {
  loading.value = true;
  error.value = '';

  try {
    const { data } = await axios.get(`/api/parent/kids/${props.kid.id}/details`);
    kidDetails.value = data;
  } catch (e) {
    error.value = e.response?.data?.message || 'Không thể tải thông tin chi tiết';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchKidDetails();
});
</script>
