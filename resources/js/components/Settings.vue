<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Cài đặt hệ thống</h2>
          <button class="btn btn-primary" @click="checkSystemStatus" :disabled="checking">
            <span v-if="checking" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ checking ? 'Đang kiểm tra...' : 'Kiểm tra trạng thái hệ thống' }}
          </button>
        </div>

        <!-- System Information -->
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="card-title mb-0">Thông tin người dùng</h5>
              </div>
              <div class="card-body">
                <div v-if="loading" class="text-center">
                  <div class="spinner-border" role="status"></div>
                </div>
                <div v-else-if="systemInfo">
                  <div class="row">
                    <div class="col-6">
                      <div class="text-center">
                        <h3 class="text-primary">{{ systemInfo.users.total }}</h3>
                        <p class="mb-0">Tổng số người dùng</p>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="text-center">
                        <h3 class="text-success">{{ systemInfo.relationships.total }}</h3>
                        <p class="mb-0">Mối quan hệ</p>
                      </div>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-6">
                      <div class="text-center">
                        <h4 class="text-info">{{ systemInfo.users.parents }}</h4>
                        <p class="mb-0">Phụ huynh</p>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="text-center">
                        <h4 class="text-warning">{{ systemInfo.users.children }}</h4>
                        <p class="mb-0">Trẻ em</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="card-title mb-0">Thống kê yêu cầu</h5>
              </div>
              <div class="card-body">
                <div v-if="loading" class="text-center">
                  <div class="spinner-border" role="status"></div>
                </div>
                <div v-else-if="systemInfo">
                  <div class="row">
                    <div class="col-6">
                      <div class="text-center">
                        <h3 class="text-primary">{{ systemInfo.requests.total }}</h3>
                        <p class="mb-0">Tổng yêu cầu</p>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="text-center">
                        <h3 class="text-warning">{{ systemInfo.requests.pending }}</h3>
                        <p class="mb-0">Chờ xử lý</p>
                      </div>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-6">
                      <div class="text-center">
                        <h4 class="text-success">{{ systemInfo.requests.accepted }}</h4>
                        <p class="mb-0">Đã chấp nhận</p>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="text-center">
                        <h4 class="text-danger">{{ systemInfo.requests.denied }}</h4>
                        <p class="mb-0">Đã từ chối</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Relationships Table -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Mối quan hệ giữa các người dùng</h5>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center">
              <div class="spinner-border" role="status"></div>
            </div>
            <div v-else-if="systemInfo && systemInfo.relationships.details.length > 0">
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Tên trẻ</th>
                      <th>Tên phụ huynh</th>
                      <th>Ngày tạo</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="relation in systemInfo.relationships.details" :key="`${relation.child_name}-${relation.parent_name}`">
                      <td>{{ relation.child_name }}</td>
                      <td>{{ relation.parent_name }}</td>
                      <td>{{ formatDate(relation.created_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-center text-muted">
              Không có mối quan hệ nào được tìm thấy
            </div>
          </div>
        </div>

        <!-- System Status -->
        <div class="card" v-if="systemStatus">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Trạng thái hệ thống</h5>
            <span class="badge" :class="systemStatus.overall_status === 'HEALTHY' ? 'bg-success' : 'bg-warning'">
              {{ systemStatus.overall_status === 'HEALTHY' ? 'Khỏe mạnh' : 'Có vấn đề' }}
            </span>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4" v-for="(check, key) in systemStatus.checks" :key="key">
                <div class="card mb-3">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                      <h6 class="card-title">{{ getCheckTitle(key) }}</h6>
                      <span class="badge" :class="getStatusClass(check.status)">
                        {{ getStatusText(check.status) }}
                      </span>
                    </div>
                    <p class="card-text small">{{ check.message }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="text-muted small">
              Kiểm tra lần cuối: {{ formatDate(systemStatus.timestamp) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const checking = ref(false);
const systemInfo = ref(null);
const systemStatus = ref(null);

const loadSystemInfo = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/parent/system-info');
    systemInfo.value = response.data;
  } catch (error) {
    console.error('Error loading system info:', error);
  } finally {
    loading.value = false;
  }
};

const checkSystemStatus = async () => {
  checking.value = true;
  try {
    const response = await axios.get('/api/parent/system-status');
    systemStatus.value = response.data;
  } catch (error) {
    console.error('Error checking system status:', error);
  } finally {
    checking.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString('vi-VN');
};

const getCheckTitle = (key) => {
  const titles = {
    'database': 'Kết nối cơ sở dữ liệu',
    'tables': 'Bảng dữ liệu',
    'data_integrity': 'Tính toàn vẹn dữ liệu'
  };
  return titles[key] || key;
};

const getStatusClass = (status) => {
  switch (status) {
    case 'OK': return 'bg-success';
    case 'WARNING': return 'bg-warning';
    case 'ERROR': return 'bg-danger';
    default: return 'bg-secondary';
  }
};

const getStatusText = (status) => {
  switch (status) {
    case 'OK': return 'Tốt';
    case 'WARNING': return 'Cảnh báo';
    case 'ERROR': return 'Lỗi';
    default: return 'Không xác định';
  }
};

onMounted(() => {
  loadSystemInfo();
});
</script>

<style scoped>
.card {
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

.table th {
  background-color: #f8f9fa;
  font-weight: 600;
}

.badge {
  font-size: 0.75rem;
}
</style>
