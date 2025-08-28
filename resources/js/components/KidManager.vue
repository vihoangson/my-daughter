<template>
  <div class="mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4>Trẻ em được quản lý</h4>
      <button class="btn btn-primary btn-sm" @click="showAddModal = true">
        <i class="fas fa-plus"></i> Thêm trẻ em
      </button>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Đang tải...</span>
      </div>
    </div>

    <!-- Kids list -->
    <div v-else-if="kids.length > 0" class="row">
      <div v-for="kid in kids" :key="kid.id" class="col-md-6 col-lg-4 mb-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="card-title mb-1">{{ kid.name }}</h6>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="#" @click.prevent="viewDetails(kid)">Xem chi tiết</a></li>
                  <li><a class="dropdown-item" href="#" @click.prevent="editKid(kid)">Chỉnh sửa</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteKid(kid)">Xóa</a></li>
                </ul>
              </div>
            </div>

            <p class="card-text small text-muted mb-2">{{ kid.email }}</p>

            <div class="row text-center">
              <div class="col-6">
                <div class="border-end">
                  <h5 class="mb-0" :class="kid.total_points >= 0 ? 'text-success' : 'text-danger'">
                    {{ kid.total_points || 0 }}
                  </h5>
                  <small class="text-muted">Điểm</small>
                </div>
              </div>
              <div class="col-6">
                <h5 class="mb-0 text-warning">{{ kid.pending_requests || 0 }}</h5>
                <small class="text-muted">Yêu cầu chờ</small>
              </div>
            </div>

            <div class="mt-2">
              <button class="btn btn-outline-primary btn-sm w-100" @click="viewDetails(kid)">
                Xem chi tiết
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-5">
      <div class="text-muted">
        <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
        <h5>Chưa có trẻ em nào được quản lý</h5>
        <p>Thêm trẻ em để bắt đầu quản lý hoạt động của các em.</p>
        <button class="btn btn-primary" @click="showAddModal = true">
          <i class="fas fa-plus"></i> Thêm trẻ em đầu tiên
        </button>
      </div>
    </div>

    <!-- Add Kid Modal -->
    <div class="modal fade" :class="{ show: showAddModal }" :style="{ display: showAddModal ? 'block' : 'none' }" v-if="showAddModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingKid ? 'Chỉnh sửa trẻ em' : 'Thêm trẻ em mới' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveKid">
              <div class="mb-3">
                <label for="kidName" class="form-label">Tên trẻ em *</label>
                <input
                  type="text"
                  class="form-control"
                  id="kidName"
                  v-model="kidForm.name"
                  required
                  :class="{ 'is-invalid': errors.name }"
                >
                <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
              </div>
              <div class="mb-3">
                <label for="kidEmail" class="form-label">Email *</label>
                <input
                  type="email"
                  class="form-control"
                  id="kidEmail"
                  v-model="kidForm.email"
                  required
                  :class="{ 'is-invalid': errors.email }"
                >
                <div v-if="errors.email" class="invalid-feedback">{{ errors.email[0] }}</div>
              </div>
              <div class="mb-3" v-if="!editingKid">
                <label for="kidPassword" class="form-label">Mật khẩu</label>
                <input
                  type="password"
                  class="form-control"
                  id="kidPassword"
                  v-model="kidForm.password"
                  placeholder="Để trống sẽ dùng mật khẩu mặc định: password"
                >
                <div class="form-text">Nếu để trống, mật khẩu mặc định sẽ là "password"</div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">Hủy</button>
            <button type="button" class="btn btn-primary" @click="saveKid" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              {{ editingKid ? 'Cập nhật' : 'Thêm' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade" :class="{ show: showAddModal }" v-if="showAddModal"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const loading = ref(false);
const saving = ref(false);
const kids = ref([]);
const showAddModal = ref(false);
const editingKid = ref(null);
const errors = ref({});

const kidForm = ref({
  name: '',
  email: '',
  password: ''
});

const loadKids = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/parent/kids');
    kids.value = response.data.kids || [];
  } catch (error) {
    console.error('Error loading kids:', error);
    if (error.response?.status === 403) {
      alert('Bạn không có quyền truy cập chức năng này.');
    }
  } finally {
    loading.value = false;
  }
};

const viewDetails = (kid) => {
  router.push(`/kid/${kid.id}`);
};

const editKid = (kid) => {
  editingKid.value = kid;
  kidForm.value = {
    name: kid.name,
    email: kid.email,
    password: ''
  };
  showAddModal.value = true;
};

const saveKid = async () => {
  saving.value = true;
  errors.value = {};

  try {
    if (editingKid.value) {
      // Update existing kid
      await axios.put(`/api/parent/kids/${editingKid.value.id}`, kidForm.value);
    } else {
      // Create new kid
      await axios.post('/api/parent/kids', kidForm.value);
    }

    closeModal();
    loadKids();
  } catch (error) {
    console.error('Error saving kid:', error);
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      alert('Có lỗi xảy ra khi lưu thông tin.');
    }
  } finally {
    saving.value = false;
  }
};

const deleteKid = async (kid) => {
  if (!confirm(`Bạn có chắc muốn xóa trẻ em "${kid.name}"?`)) {
    return;
  }

  try {
    await axios.delete(`/api/parent/kids/${kid.id}`);
    loadKids();
  } catch (error) {
    console.error('Error deleting kid:', error);
    alert('Có lỗi xảy ra khi xóa trẻ em.');
  }
};

const closeModal = () => {
  showAddModal.value = false;
  editingKid.value = null;
  kidForm.value = {
    name: '',
    email: '',
    password: ''
  };
  errors.value = {};
};

onMounted(() => {
  loadKids();
});
</script>

<style scoped>
.card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.modal {
  background-color: rgba(0,0,0,0.5);
}

.border-end {
  border-right: 1px solid #dee2e6;
}

.opacity-50 {
  opacity: 0.5;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>
