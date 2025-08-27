<template>
  <div class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h4 class="mb-0">Trẻ em được quản lý</h4>
      <button class="btn btn-sm btn-primary" @click="startCreate" v-if="!showForm">+ Thêm trẻ em</button>
      <button class="btn btn-sm btn-secondary" @click="cancelForm" v-else>Đóng</button>
    </div>

    <div v-if="showForm" class="card card-body mb-3">
      <h5 class="mb-3" v-if="!editingId">Tạo trẻ em</h5>
      <h5 class="mb-3" v-else>Chỉnh sửa trẻ em #{{ editingId }}</h5>
      <div v-if="successMsg" class="alert alert-success py-2">{{ successMsg }}</div>
      <div v-if="errorMsg" class="alert alert-danger py-2">{{ errorMsg }}</div>
      <form @submit.prevent="submit">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Tên</label>
            <input class="form-control" v-model="form.name" required />
            <small class="text-danger" v-if="errors.name">{{ errors.name[0] }}</small>
          </div>
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" v-model="form.email" required :disabled="editingId" />
            <small class="text-danger" v-if="errors.email">{{ errors.email[0] }}</small>
          </div>
          <div class="col-md-4">
            <label class="form-label" v-if="!editingId">Mật khẩu (bỏ trống = password)</label>
            <label class="form-label" v-else>Mật khẩu mới (để trống = giữ nguyên)</label>
            <input type="text" class="form-control" v-model="form.password" />
            <small class="text-danger" v-if="errors.password">{{ errors.password[0] }}</small>
          </div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-success" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" />{{ editingId ? 'Cập nhật' : 'Lưu' }}
          </button>
          <button type="button" class="btn btn-outline-secondary" @click="cancelForm">Hủy</button>
        </div>
      </form>
    </div>

    <table class="table table-sm table-bordered" v-if="kids.length">
      <thead>
        <tr>
          <th>#</th>
          <th>Tên</th>
          <th>Email</th>
          <th>Phụ huynh liên kết</th>
          <th style="width:190px">Hành động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="k in kids" :key="k.id">
          <td>{{ k.id }}</td>
          <td>{{ k.name }}</td>
          <td>{{ k.email }}</td>
          <td>{{ k.parents_count }}</td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-success" @click="openDetails(k)">Chi tiết</button>
              <button class="btn btn-info" @click="openPoint(k)">Điểm</button>
              <button class="btn btn-warning" @click="openEdit(k)">Sửa</button>
              <button class="btn btn-danger" @click="remove(k)">Xóa</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
    <div v-else class="text-muted fst-italic">Chưa có trẻ em nào.</div>
    <KidPointModal v-if="pointKid" :kid="pointKid" @close="pointKid=null" @saved="handlePointSaved" />
    <KidDetailsModal v-if="detailsKid" :kid="detailsKid" @close="detailsKid=null" />
    <KidEditModal v-if="editKid" :kid="editKid" @close="editKid=null" @updated="handleKidUpdated" />
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import KidPointModal from './KidPointModal.vue';
import KidDetailsModal from './KidDetailsModal.vue';
import KidEditModal from './KidEditModal.vue';

const kids = ref([]);
const showForm = ref(false);
const loading = ref(false);
const successMsg = ref('');
const errorMsg = ref('');
const errors = reactive({});
const editingId = ref(null);
const form = reactive({ name: '', email: '', password: '' });
const pointKid = ref(null);
const detailsKid = ref(null);
const editKid = ref(null);

const clearErrors = ()=>{ Object.keys(errors).forEach(k=>delete errors[k]); };

const resetForm = () => {
  form.name=''; form.email=''; form.password=''; editingId.value=null; successMsg.value=''; errorMsg.value=''; clearErrors();
};

const startCreate = () => { resetForm(); showForm.value = true; };
const startEdit = (k) => {
  resetForm();
  editingId.value = k.id;
  form.name = k.name;
  form.email = k.email; // email locked
  showForm.value = true;
};
const cancelForm = () => { resetForm(); showForm.value = false; };

const fetchKids = async () => {
  try {
    const { data } = await axios.get('/api/parent/kids');
    kids.value = data;
  } catch(e) { /* ignore */ }
};

const submit = async () => {
  loading.value = true; successMsg.value=''; errorMsg.value=''; clearErrors();
  try {
    if(editingId.value){
      const payload = { name: form.name, email: form.email }; // email kept for validation uniqueness rule context
      if(form.password) payload.password = form.password;
      await axios.put(`/api/parent/kids/${editingId.value}`, payload);
      successMsg.value = 'Cập nhật thành công';
    } else {
      const { data } = await axios.post('/api/parent/kids', form);
      successMsg.value = `Tạo thành công. Mật khẩu mặc định: ${data.default_password}`;
    }
    await fetchKids();
    resetForm();
    showForm.value = false;
  } catch(e){
    if(e.response?.status === 422){
      const valErr = e.response.data.errors || {}; for(const k in valErr) errors[k]=valErr[k];
      errorMsg.value = 'Vui lòng kiểm tra lại các trường.';
    } else if(e.response?.data?.message) errorMsg.value = e.response.data.message; else errorMsg.value='Lỗi không xác định';
  } finally { loading.value = false; }
};

const remove = async (k) => {
  if(!confirm(`Xóa trẻ #${k.id}?`)) return;
  try {
    await axios.delete(`/api/parent/kids/${k.id}`);
    kids.value = kids.value.filter(x=>x.id!==k.id);
  } catch(e){
    alert(e.response?.data?.message || 'Không xóa được');
  }
};

const openPoint = (k) => { pointKid.value = k; };
const handlePointSaved = () => { pointKid.value = null; };
const openDetails = (k) => { detailsKid.value = k; };
const openEdit = (k) => { editKid.value = k; };
const handleKidUpdated = async () => {
  editKid.value = null;
  await fetchKids(); // Refresh the list after update
};

onMounted(fetchKids);
</script>
