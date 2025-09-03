<template>
  <div class="kid-details">
    <div class="row">
      <!-- Kid Information Card -->
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-child me-2"></i>Thông tin trẻ em</h5>
          </div>
          <div class="card-body">
            <div class="text-center mb-3">
              <img
                :src="kid.avatar_url || defaultAvatar"
                alt="Avatar"
                class="rounded-circle border"
                style="width: 100px; height: 100px; object-fit: cover;"
                @error="handleAvatarError"
              />
            </div>
            <div class="row g-2">
              <div class="col-12">
                <strong>Tên:</strong> {{ kid.name }}
              </div>
              <div class="col-12">
                <strong>Email:</strong> {{ kid.email }}
              </div>
              <div class="col-12">
                <strong>Tổng điểm:</strong> <span class="badge bg-success">{{ kid.total_points || 0 }}</span>
              </div>
              <div class="col-12">
                <strong>Yêu cầu đang chờ:</strong> <span class="badge bg-warning">{{ kid.pending_requests || 0 }}</span>
              </div>
              <div class="col-12">
                <strong>Ngày tạo:</strong> {{ formatDate(kid.created_at) }}
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button class="btn btn-outline-primary btn-sm w-100" @click="editKid">
              <i class="fas fa-edit me-1"></i>Chỉnh sửa thông tin
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs for Points and Requests -->
      <div class="col-md-8">
        <div class="card h-100">
          <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
              <li class="nav-item">
                <a class="nav-link" :class="{ active: localTab === 'points' }" href="#" @click.prevent="localTab = 'points'">
                  <i class="fas fa-star me-1"></i>Quản lý điểm
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" :class="{ active: localTab === 'requests' }" href="#" @click.prevent="localTab = 'requests'">
                  <i class="fas fa-paper-plane me-1"></i>Yêu cầu
                </a>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <!-- Points Management Tab -->
            <div v-if="localTab === 'points'">
              <div class="row">
                <div class="col-md-5">
                  <!-- Add Points Form -->
                  <AddPoints :kid-id="kid.id" @points-added="handlePointsAdded" />
                </div>
                <div class="col-md-7">
                  <!-- Points History -->
                  <div class="card">
                    <div class="card-header bg-info text-white">
                      <h5 class="mb-0"><i class="fas fa-history me-2"></i>Lịch sử điểm</h5>
                    </div>
                    <div class="card-body">
                      <div v-if="loading" class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                      </div>
                      <div v-else-if="!pointsHistory.length" class="text-center text-muted py-4">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <p>Chưa có lịch sử điểm nào</p>
                      </div>
                      <div v-else class="table-responsive">
                        <table class="table table-sm table-hover">
                          <thead>
                            <tr>
                              <th>Ngày</th>
                              <th>Loại</th>
                              <th>Điểm</th>
                              <th>Mô tả</th>
                              <th>Hình ảnh</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="point in pointsHistory" :key="point.id">
                              <td>{{ formatDate(point.created_at) }}</td>
                              <td>
                                <span v-if="point.type === 'reward'" class="badge bg-success">
                                  <i class="fas fa-plus me-1"></i>Thưởng
                                </span>
                                <span v-else class="badge bg-danger">
                                  <i class="fas fa-minus me-1"></i>Phạt
                                </span>
                              </td>
                              <td>
                                <span v-if="point.type === 'reward'" class="text-success fw-bold">
                                  +{{ point.points }}
                                </span>
                                <span v-else class="text-danger fw-bold">
                                  -{{ point.points }}
                                </span>
                              </td>
                              <td>{{ point.description || '-' }}</td>
                              <td>
                                <img
                                  v-if="point.evidence_url"
                                  :src="point.evidence_url"
                                  alt="Evidence"
                                  class="img-thumbnail cursor-pointer"
                                  style="max-width: 50px; max-height: 50px;"
                                  @click="showImageModal(point.evidence_url)"
                                />
                                <span v-else>-</span>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Requests Tab -->
            <div v-if="localTab === 'requests'">
              <div v-if="loading" class="text-center">
                <div class="spinner-border text-primary" role="status"></div>
              </div>
              <div v-else-if="!kidRequests.length" class="text-center text-muted py-4">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <p>Không có yêu cầu nào từ trẻ em này</p>
              </div>
              <div v-else>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Ngày yêu cầu</th>
                        <th>Tiêu đề</th>
                        <th>Loại</th>
                        <th>Mô tả</th>
                        <th>Hình</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="request in kidRequests" :key="request.id" :class="{ 'table-warning': request.status === 'pending' }">
                        <td>{{ formatDate(request.created_at) }}</td>
                        <td>{{ request.title }}</td>
                        <td>
                          <span :class="getTypeClass(request.type)" class="badge">
                            {{ getTypeLabel(request.type) }}
                          </span>
                        </td>
                        <td class="text-truncate" style="max-width:180px;" :title="request.description">{{ request.description }}</td>
                        <td>
                          <div v-if="request.image_url" class="request-thumb-wrapper">
                            <img
                              :src="request.image_url"
                              :alt="'Ảnh yêu cầu: ' + request.title"
                              class="request-thumb img-thumbnail"
                              @click="openRequestImage(request)"
                              @error="onRequestImageError($event)"
                            />
                          </div>
                          <span v-else class="text-muted small">-</span>
                        </td>
                        <td>
                          <span v-if="request.status === 'pending'" class="badge bg-warning text-dark">Đang chờ</span>
                          <span v-else-if="request.status === 'approved'" class="badge bg-success">Đã chấp nhận</span>
                          <span v-else-if="request.status === 'rejected'" class="badge bg-danger">Đã từ chối</span>
                          <span v-else-if="request.status === 'completed'" class="badge bg-info">Đã hoàn thành</span>
                          <span v-else class="badge bg-secondary">{{ request.status }}</span>
                        </td>
                        <td>
                          <div class="btn-group">
                            <button
                              v-if="request.status === 'pending'"
                              class="btn btn-sm btn-success"
                              @click="processRequest(request.id, 'approved')"
                            >
                              <i class="fas fa-check"></i>
                            </button>
                            <button
                              v-if="request.status === 'pending'"
                              class="btn btn-sm btn-danger"
                              @click="processRequest(request.id, 'rejected')"
                            >
                              <i class="fas fa-times"></i>
                            </button>
                            <button
                              v-if="request.status === 'approved'"
                              class="btn btn-sm btn-primary"
                              @click="completeRequest(request.id)"
                            >
                              <i class="fas fa-flag-checkered"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Kid Modal -->
    <div v-if="showEditModal" class="modal d-block" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Chỉnh sửa thông tin trẻ em</h5>
            <button type="button" class="btn-close" @click="showEditModal = false"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveKidChanges">
              <div class="mb-3">
                <label class="form-label">Tên *</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="editForm.name"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label">Email *</label>
                <input
                  type="email"
                  class="form-control"
                  v-model="editForm.email"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label">Mật khẩu mới (để trống nếu không thay đổi)</label>
                <input
                  type="password"
                  class="form-control"
                  v-model="editForm.password"
                  minlength="4"
                />
              </div>
              <div class="mb-3">
                <label class="form-label">Ảnh đại diện</label>
                <input
                  type="file"
                  class="form-control"
                  @change="handleAvatarChange"
                  accept="image/*"
                />
              </div>
              <div v-if="editForm.avatarPreview" class="mb-3 text-center">
                <img
                  :src="editForm.avatarPreview"
                  alt="Preview"
                  class="img-thumbnail rounded-circle"
                  style="width: 100px; height: 100px; object-fit: cover;"
                />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showEditModal = false">Hủy</button>
            <button type="button" class="btn btn-primary" @click="saveKidChanges" :disabled="updating">
              <span v-if="updating" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-save me-2"></i>
              {{ updating ? 'Đang lưu...' : 'Lưu thay đổi' }}
            </button>
          </div>
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

    <!-- Request Image Modal -->
    <RequestImageModal
      v-model="showRequestImageModal"
      :request="requestImageData"
      @closed="requestImageData = null"
    />

    <!-- Toast Notification -->
    <Toast
      :show="showToast"
      :message="toastMessage"
      :title="toastTitle"
      :type="toastType"
      @update:show="showToast = $event"
    />
  </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import AddPoints from './AddPoints.vue';
import Toast from '../common/Toast.vue';
import RequestImageModal from '../common/RequestImageModal.vue';

export default {
  components: {
    AddPoints,
    Toast,
    RequestImageModal
  },
  props: {
    kid: {
      type: Object,
      required: true
    },
    selectedRequest: {
      type: Object,
      default: null
    }
  },
  emits: ['request-processed'],
  setup(props, { emit }) {
    const loading = ref(false);
    const updating = ref(false);
    const pointsHistory = ref([]);
    const kidRequests = ref([]);
    const localTab = ref('points');
    const showEditModal = ref(false);
    const showImage = ref(null);
    const showRequestImageModal = ref(false);
    const requestImageData = ref(null);

    // Toast state
    const showToast = ref(false);
    const toastMessage = ref('');
    const toastTitle = ref('Thông báo');
    const toastType = ref('success');

    const editForm = ref({
      name: '',
      email: '',
      password: '',
      avatar: null,
      avatarPreview: null
    });

    const defaultAvatar = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjBGMEYwIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iMzgiIHI9IjEyIiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0yNSA3NUM0MCA2NSA2MCA2NSA3NSA3NVY3NUgyNVoiIGZpbGw9IiNDQ0MiLz4KPC9zdmc+';

    // Helper functions
    const formatDate = (dateString) => {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {
        hour: '2-digit',
        minute: '2-digit'
      });
    };

    const getTypeLabel = (type) => {
      const types = {
        'toy': 'Đồ chơi',
        'food': 'Món ăn',
        'playground': 'Khu vui chơi',
        'activity': 'Hoạt động'
      };
      return types[type] || type;
    };

    const getTypeClass = (type) => {
      const classes = {
        'toy': 'bg-primary',
        'food': 'bg-success',
        'playground': 'bg-info',
        'activity': 'bg-warning'
      };
      return classes[type] || 'bg-secondary';
    };

    const handleAvatarError = (event) => {
      event.target.src = defaultAvatar;
    };

    // Fetch data functions
    const fetchPointsHistory = async () => {
      try {
        const response = await axios.get(`/api/parent/kid/${props.kid.id}/points-history`);
        pointsHistory.value = response.data.points_history || [];
      } catch (error) {
        console.error('Error fetching points history:', error);
        showToastMessage('Không thể tải lịch sử điểm', 'Lỗi', 'danger');
      }
    };

    const fetchKidRequests = async () => {
      try {
        const response = await axios.get(`/api/parent/kid/${props.kid.id}/requests`);
        kidRequests.value = response.data.requests || [];
      } catch (error) {
        console.error('Error fetching kid requests:', error);
        showToastMessage('Không thể tải yêu cầu của trẻ em', 'Lỗi', 'danger');
      }
    };

    const fetchData = async () => {
      loading.value = true;
      await Promise.all([
        fetchPointsHistory(),
        fetchKidRequests()
      ]);
      loading.value = false;
    };

    // Action methods
    const editKid = () => {
      editForm.value = {
        name: props.kid.name,
        email: props.kid.email,
        password: '',
        avatar: null,
        avatarPreview: null
      };
      showEditModal.value = true;
    };

    const handleAvatarChange = (event) => {
      const file = event.target.files[0];
      if (!file) return;

      // Validate file type and size
      const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
      if (!validTypes.includes(file.type)) {
        showToastMessage('Vui lòng chọn file ảnh (JPEG, PNG, JPG, GIF)', 'Lỗi', 'danger');
        return;
      }

      if (file.size > 2048 * 1024) { // 2MB
        showToastMessage('File ảnh không được vượt quá 2MB', 'Lỗi', 'danger');
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        editForm.value.avatarPreview = e.target.result;
      };
      reader.readAsDataURL(file);
      editForm.value.avatar = file;
    };

    const saveKidChanges = async () => {
      updating.value = true;
      try {
        const formData = new FormData();
        formData.append('name', editForm.value.name);
        formData.append('email', editForm.value.email);

        if (editForm.value.password) {
          formData.append('password', editForm.value.password);
        }

        if (editForm.value.avatar) {
          formData.append('avatar', editForm.value.avatar);
        }

        await axios.post(`/api/parent/kids/${props.kid.id}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'X-HTTP-Method-Override': 'PUT'
          }
        });

        showToastMessage('Cập nhật thông tin thành công!', 'Thành công', 'success');
        showEditModal.value = false;
        emit('request-processed'); // Trigger parent component to refresh data
      } catch (error) {
        console.error('Error updating kid:', error);
        showToastMessage('Đã xảy ra lỗi khi cập nhật thông tin', 'Lỗi', 'danger');
      } finally {
        updating.value = false;
      }
    };

    const processRequest = async (requestId, status) => {
      try {
        await axios.put(`/api/parent/requests/${requestId}/process`, { status });
        showToastMessage(
          status === 'approved' ? 'Đã chấp nhận yêu cầu' : 'Đã từ chối yêu cầu',
          'Thành công',
          status === 'approved' ? 'success' : 'warning'
        );
        fetchKidRequests();
        emit('request-processed');
      } catch (error) {
        console.error('Error processing request:', error);
        showToastMessage('Đã xảy ra lỗi khi xử lý yêu cầu', 'Lỗi', 'danger');
      }
    };

    const completeRequest = async (requestId) => {
      try {
        await axios.put(`/api/parent/requests/${requestId}/complete`);
        showToastMessage('Đã hoàn thành yêu cầu', 'Thành công', 'success');
        fetchKidRequests();
        emit('request-processed');
      } catch (error) {
        console.error('Error completing request:', error);
        showToastMessage('Đã xảy ra lỗi khi hoàn thành yêu cầu', 'Lỗi', 'danger');
      }
    };

    const showImageModal = (url) => {
      showImage.value = url;
    };

    const openRequestImage = (request) => {
      if(!request?.image_url) return;
      requestImageData.value = request;
      showRequestImageModal.value = true;
    };
    const closeRequestImage = () => {
      showRequestImageModal.value = false;
      requestImageData.value = null;
    };
    const onRequestImageError = (e) => {
      e.target.style.opacity = 0.4;
      e.target.title = 'Không tải được ảnh';
    };

    const handlePointsAdded = () => {
      fetchPointsHistory();
      emit('request-processed'); // Update parent component data
      showToastMessage('Đã thêm điểm thành công', 'Thành công', 'success');
    };

    const showToastMessage = (message, title = 'Thông báo', type = 'success') => {
      toastMessage.value = message;
      toastTitle.value = title;
      toastType.value = type;
      showToast.value = true;
    };

    // Watch for changes in selected request
    watch(() => props.selectedRequest, (newVal) => {
      if (newVal) {
        localTab.value = 'requests';
      }
    });

    // Lifecycle methods
    onMounted(() => {
      fetchData();
    });

    return {
      loading,
      updating,
      pointsHistory,
      kidRequests,
      localTab,
      showEditModal,
      showImage,
      showRequestImageModal,
      requestImageData,
      editForm,
      defaultAvatar,
      formatDate,
      getTypeLabel,
      getTypeClass,
      handleAvatarError,
      editKid,
      handleAvatarChange,
      saveKidChanges,
      processRequest,
      completeRequest,
      showImageModal,
      openRequestImage,
      closeRequestImage,
      onRequestImageError,
      handlePointsAdded,
      showToast,
      toastMessage,
      toastTitle,
      toastType,
      showToastMessage
    };
  }
};
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.modal {
  display: block;
}
.request-thumb-wrapper { width:54px; }
.request-thumb { width:50px; height:50px; object-fit:cover; cursor:pointer; transition:filter .15s, transform .15s; }
.request-thumb:hover { filter:brightness(0.9); transform:scale(1.05); }
</style>
