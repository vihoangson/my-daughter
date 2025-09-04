<template>
  <div class="reward-items-manager mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h4 class="mb-0"><i class="fas fa-gift me-2 text-danger"></i>Phần thưởng</h4>
      <div class="d-flex gap-2 align-items-center">
        <button class="btn btn-sm btn-outline-secondary" @click="fetchItems" :disabled="loadingList">
          <span v-if="loadingList" class="spinner-border spinner-border-sm me-1" />Tải lại
        </button>
        <button v-if="!showForm" class="btn btn-sm btn-primary" @click="startCreate"><i class="fas fa-plus me-1"></i>Thêm</button>
        <button v-else class="btn btn-sm btn-secondary" @click="cancelForm">Đóng</button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
    <div v-if="success" class="alert alert-success py-2">{{ success }}</div>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3">{{ editingId ? 'Chỉnh sửa' : 'Thêm mới' }} phần thưởng</h6>
        <form @submit.prevent="submit">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Tên<span class="text-danger">*</span></label>
              <input class="form-control" v-model.trim="form.name" required maxlength="255" />
            </div>
            <div class="col-md-2">
              <label class="form-label">Giá (Acoin)<span class="text-danger">*</span></label>
              <input type="number" min="0" class="form-control" v-model.number="form.price_acoin" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Trạng thái</label>
              <select class="form-select" v-model="form.is_active">
                <option :value="true">Đang bật</option>
                <option :value="false">Tắt</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Hình ảnh</label>
              <input type="file" class="form-control" accept="image/*" @change="handleFile" />
              <small class="text-muted">Tối đa 10MB</small>
            </div>
            <div class="col-md-9">
              <label class="form-label">Ghi chú</label>
              <input class="form-control" v-model="form.note" />
            </div>
            <div class="col-md-3" v-if="preview || currentImage">
              <label class="form-label">Xem trước</label>
              <div class="d-flex gap-2 align-items-center">
                <img :src="preview || currentImage" class="rounded border" style="width:80px;height:80px;object-fit:cover" />
                <button v-if="currentImage && !removeImage" type="button" class="btn btn-sm btn-outline-danger" @click="removeImage=true">Gỡ ảnh</button>
                <span v-if="removeImage" class="badge text-bg-warning">Sẽ xoá</span>
              </div>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-success btn-sm" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1" />{{ editingId? 'Cập nhật' : 'Lưu' }}
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="cancelForm">Huỷ</button>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive" v-if="items.length">
      <table class="table table-sm table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:45px">#</th>
            <th style="width:70px">Ảnh</th>
            <th>Tên</th>
            <th style="width:120px">Giá (Acoin)</th>
            <th>Ghi chú</th>
            <th style="width:110px">Trạng thái</th>
            <th style="width:170px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="it in items" :key="it.id" :class="{'table-secondary': !it.is_active}">
            <td>{{ it.id }}</td>
            <td>
              <img v-if="it.image_url" :src="it.image_url" style="width:56px;height:56px;object-fit:cover;border-radius:6px" />
              <div v-else class="bg-light d-flex align-items-center justify-content-center text-muted small rounded" style="width:56px;height:56px">NO IMG</div>
            </td>
            <td class="fw-semibold">{{ it.name }}</td>
            <td class="text-end"><span class="badge bg-success-subtle text-success border">{{ it.price_acoin }}</span></td>
            <td class="small">{{ it.note || '-' }}</td>
            <td>
              <span class="badge" :class="it.is_active ? 'text-bg-success':'text-bg-secondary'">{{ it.is_active? 'Bật':'Tắt' }}</span>
            </td>
            <td>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-warning" @click="edit(it)">Sửa</button>
                <button class="btn btn-outline-secondary" @click="toggle(it)" :disabled="toggling[it.id]">
                  <span v-if="toggling[it.id]" class="spinner-border spinner-border-sm" />{{ it.is_active? 'Tắt':'Bật' }}
                </button>
                <button class="btn btn-danger" @click="remove(it)">Xoá</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else-if="!loadingList" class="text-muted fst-italic">Chưa có phần thưởng nào.</div>
    <div v-if="loadingList" class="py-3 text-center"><div class="spinner-border text-primary"></div></div>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import axios from 'axios';

const items = ref([]);
const loadingList = ref(false);
const error = ref('');
const success = ref('');
const showForm = ref(false);
const editingId = ref(null);
const form = reactive({ name:'', price_acoin:null, note:'', is_active:true, image:null });
const currentImage = ref(null);
const preview = ref(null);
const removeImage = ref(false);
const saving = ref(false);
const toggling = reactive({});
const MAX_IMAGE_SIZE = 10 * 1024 * 1024;

const fetchItems = async () => {
  loadingList.value = true; error.value='';
  try { const { data } = await axios.get('/api/parent/rewards'); items.value = Array.isArray(data)? data : []; }
  catch(e){ error.value = e.response?.data?.message || 'Không tải được danh sách'; }
  finally { loadingList.value = false; }
};

const resetForm = () => {
  editingId.value=null; form.name=''; form.price_acoin=null; form.note=''; form.is_active=true; form.image=null; currentImage.value=null; preview.value=null; removeImage.value=false; error.value=''; success.value='';
};

const startCreate = () => { resetForm(); showForm.value=true; };
const cancelForm = () => { resetForm(); showForm.value=false; };

const edit = (it) => {
  resetForm();
  editingId.value = it.id;
  form.name = it.name;
  form.price_acoin = it.price_acoin;
  form.note = it.note;
  form.is_active = it.is_active;
  currentImage.value = it.image_url;
  showForm.value = true;
  nextTick(()=>{ try { window.scrollTo({ top:0, behavior:'smooth'}); } catch { window.scrollTo(0,0);} });
};

const handleFile = (e) => {
  const file = e.target.files[0];
  if(!file){ form.image=null; preview.value=null; return; }
  if(file.size > MAX_IMAGE_SIZE){ alert('File quá lớn >10MB'); e.target.value=''; return; }
  form.image = file;
  const reader = new FileReader();
  reader.onload = ev => preview.value = ev.target.result;
  reader.readAsDataURL(file);
};

const submit = async () => {
  error.value=''; success.value=''; saving.value=true;
  try {
    if(!form.name || form.price_acoin===null || form.price_acoin<0){ error.value='Thiếu dữ liệu hợp lệ'; return; }
    const fd = new FormData();
    fd.append('name', form.name);
    fd.append('price_acoin', form.price_acoin);
    if(form.note) fd.append('note', form.note);
    fd.append('is_active', form.is_active ? '1':'0');
    if(form.image) fd.append('image', form.image);
    if(editingId.value){ if(removeImage.value) fd.append('remove_image','1'); await axios.post(`/api/parent/rewards/${editingId.value}?_method=PUT`, fd, { headers:{'Content-Type':'multipart/form-data'} }); success.value='Đã cập nhật'; }
    else { await axios.post('/api/parent/rewards', fd, { headers:{'Content-Type':'multipart/form-data'} }); success.value='Đã tạo'; }
    await fetchItems(); cancelForm();
  } catch(e){ error.value = e.response?.data?.message || 'Lỗi lưu'; }
  finally { saving.value=false; }
};

const remove = async (it) => {
  if(!confirm('Xoá phần thưởng này?')) return;
  try { await axios.delete(`/api/parent/rewards/${it.id}`); items.value = items.value.filter(x=>x.id!==it.id); }
  catch(e){ alert('Không thể xoá'); }
};

const toggle = async (it) => {
  toggling[it.id]=true;
  try { const { data } = await axios.post(`/api/parent/rewards/${it.id}/toggle`); it.is_active = data.is_active; }
  catch(e){ alert('Không thể cập nhật'); }
  finally { delete toggling[it.id]; }
};

onMounted(fetchItems);
</script>
<style scoped>
.table td, .table th { vertical-align: middle; }
</style>

