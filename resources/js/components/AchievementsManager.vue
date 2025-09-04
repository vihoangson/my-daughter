<template>
  <div class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h4 class="mb-0">Thành tích (Achievements)</h4>
      <div class="d-flex align-items-center gap-2">
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :class="{active: viewMode==='list'}" @click="viewMode='list'">Danh sách</button>
          <button class="btn btn-sm btn-outline-secondary" :class="{active: viewMode==='matrix'}" @click="viewMode='matrix'">Grid View</button>
        </div>
        <div class="btn-group">
          <button class="btn btn-sm btn-primary" @click="startCreate" v-if="!showForm">+ Thêm</button>
          <button class="btn btn-sm btn-secondary" @click="cancelForm" v-else>Đóng</button>
        </div>
      </div>
    </div>

    <div v-if="formError" class="alert alert-danger py-2">{{ formError }}</div>
    <div v-if="formSuccess" class="alert alert-success py-2">{{ formSuccess }}</div>

    <div v-if="showForm" class="card card-body mb-3">
      <h6 class="fw-bold mb-3">{{ editingId ? 'Chỉnh sửa' : 'Tạo mới' }} Achievement</h6>
      <form @submit.prevent="submit">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Tên</label>
            <input class="form-control" v-model="form.name" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Phân loại</label>
            <select class="form-select" v-model="form.category">
              <option value="">-- Không --</option>
              <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Ghi chú</label>
            <input class="form-control" v-model="form.note" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Hình ảnh (tuỳ chọn)</label>
            <input type="file" class="form-control" @change="handleFile" accept="image/*" />
            <div class="form-text">Tối đa 10MB.</div>
          </div>
          <div class="col-md-6" v-if="preview || currentImage">
            <label class="form-label">Xem trước</label>
            <div class="d-flex align-items-center gap-2">
              <img :src="preview || currentImage" alt="preview" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #ccc" />
              <button v-if="currentImage && !removeImage" type="button" class="btn btn-sm btn-outline-danger" @click="removeImage = true">Gỡ ảnh</button>
              <span v-if="removeImage" class="badge text-bg-warning">Ảnh sẽ bị xoá</span>
            </div>
          </div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-success btn-sm" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" />{{ editingId ? 'Cập nhật' : 'Lưu' }}
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="cancelForm">Hủy</button>
        </div>
      </form>
    </div>

    <!-- LIST VIEW -->
    <div v-if="viewMode==='list'">
      <div class="table-responsive" v-if="achievements.length">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:40px">#</th>
              <th style="width:60px">Ảnh</th>
              <th style="width:130px">Phân loại</th>
              <th>Tên</th>
              <th style="min-width:150px">Ghi chú</th>
              <th>Trẻ đạt</th>
              <th style="min-width:240px">Trạng thái theo trẻ</th>
              <th style="width:140px">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in achievements" :key="a.id">
              <td>{{ a.id }}</td>
              <td>
                <img v-if="a.image_url" :src="a.image_url" style="width:50px;height:50px;object-fit:cover;border-radius:6px" />
                <div v-else class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;font-size:11px">NO IMG</div>
              </td>
              <td>
                <span v-if="a.category" class="badge text-bg-info">{{ a.category }}</span>
                <span v-else class="text-muted small">-</span>
              </td>
              <td class="fw-semibold">{{ a.name }}</td>
              <td>{{ a.note }}</td>
              <td>{{ countAchieved(a) }}/{{ kids.length }}</td>
              <td>
                <div class="d-flex flex-wrap gap-1">
                  <button v-for="k in kids" :key="k.id" type="button" class="btn btn-xs btn-outline-success position-relative" :class="{'active-achieved': isAchieved(a,k)}" @click="toggle(a,k)" :disabled="toggling[ a.id + '-' + k.id ]">
                    <span class="small">{{ shortName(k.name) }}</span>
                    <span v-if="toggling[ a.id + '-' + k.id ]" class="spinner-border spinner-border-sm position-absolute top-50 start-50 translate-middle" style="width:14px;height:14px"></span>
                  </button>
                </div>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-warning" @click="edit(a)">Sửa</button>
                  <button class="btn btn-danger" @click="remove(a)">Xóa</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-muted fst-italic">Chưa có thành tích nào.</div>
    </div>

    <!-- MATRIX VIEW -->
    <div v-else-if="viewMode==='matrix'">
      <div v-if="!achievements.length" class="text-muted fst-italic">Chưa có thành tích nào.</div>
      <div v-else class="matrix-wrapper border rounded">
        <div class="table-responsive" style="max-height:70vh;">
          <table class="table table-sm table-bordered align-middle matrix-table mb-0">
            <thead class="table-light sticky-top">
              <tr>
                <th style="min-width:260px">Thành tích</th>
                <th class="text-center" style="width:70px">Ph.loại</th>
                <th class="text-center" style="width:70px">Trẻ đạt</th>
                <th v-for="k in kids" :key="'head-'+k.id" class="kid-col text-center" :title="k.name">
                  <div class="kid-name">{{ shortName(k.name) }}</div>
                </th>
                <th style="width:120px" class="text-center">Hành động</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in achievements" :key="'row-'+a.id">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <img v-if="a.image_url" :src="a.image_url" style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid #ddd" />
                    <div class="flex-grow-1">
                      <div class="fw-semibold">{{ a.name }}</div>
                      <div class="small text-muted" v-if="a.note">{{ a.note }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-center">
                  <span v-if="a.category" class="badge text-bg-info">{{ a.category }}</span>
                  <span v-else class="text-muted small">-</span>
                </td>
                <td class="text-center small fw-semibold">{{ countAchieved(a) }}/{{ kids.length }}</td>
                <td v-for="k in kids" :key="'cell-'+a.id+'-'+k.id" class="text-center p-0">
                  <button type="button"
                          class="cell-btn w-100 h-100 position-relative"
                          :class="{'achieved': isAchieved(a,k)}"
                          @click="toggle(a,k)"
                          :disabled="toggling[a.id+'-'+k.id]">
                    <span v-if="toggling[a.id+'-'+k.id]" class="spinner-border spinner-border-sm position-absolute top-50 start-50 translate-middle" style="width:14px;height:14px"></span>
                    <span v-else class="icon">{{ isAchieved(a,k) ? '✓' : '' }}</span>
                  </button>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-warning" @click="edit(a)">Sửa</button>
                    <button class="btn btn-danger" @click="remove(a)">Xóa</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="small text-muted mt-2 px-2">Bấm vào ô để bật/tắt trạng thái đạt cho từng trẻ.</div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import axios from 'axios';

const achievements = ref([]);
const kids = ref([]);
const showForm = ref(false);
const editingId = ref(null);
const form = reactive({ name:'', category:'', note:'', image:null });
const currentImage = ref(null);
const preview = ref(null);
const removeImage = ref(false);
const formError = ref('');
const formSuccess = ref('');
const saving = ref(false);
const toggling = reactive({});
const categories = ['Thể chất','Tinh thần','Học tập','Xã hội','Sáng tạo'];
const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
const viewMode = ref('list');

const fetchAll = async () => {
  await Promise.all([fetchKids(), fetchAchievements()]);
};

const normalizeArray = (raw, primaryKey) => {
  if (Array.isArray(raw)) return raw;
  if (raw && primaryKey && Array.isArray(raw[primaryKey])) return raw[primaryKey];
  return [];
};

const fetchKids = async () => {
  try {
    const { data } = await axios.get('/api/parent/kids');
    kids.value = normalizeArray(data, 'kids');
  } catch (e) {
    console.error('fetchKids error', e);
    kids.value = [];
  }
};
const fetchAchievements = async () => {
  try {
    const { data } = await axios.get('/api/parent/achievements');
    achievements.value = normalizeArray(data, 'achievements');
  } catch (e) {
    console.error('fetchAchievements error', e);
    achievements.value = [];
  }
};

const startCreate = () => { resetForm(); showForm.value=true; };
const edit = (a) => {
  resetForm();
  editingId.value = a.id;
  form.name = a.name;
  form.category = a.category || '';
  form.note = a.note;
  currentImage.value = a.image_url;
  showForm.value = true;
  // Scroll to top to reveal form
  nextTick(() => {
    try {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (e) {
      window.scrollTo(0,0);
    }
  });
};
const cancelForm = () => { resetForm(); showForm.value=false; };

const resetForm = () => {
  editingId.value=null; form.name=''; form.category=''; form.note=''; form.image=null; preview.value=null; currentImage.value=null; removeImage.value=false; formError.value=''; formSuccess.value='';
};

const handleFile = (e) => {
  const file = e.target.files[0];
  if(!file) { form.image=null; preview.value=null; return; }
  if(file.size > MAX_IMAGE_SIZE){
    alert('File quá lớn (>' + (MAX_IMAGE_SIZE/1024/1024) + 'MB). Giới hạn 10MB');
    e.target.value='';
    return;
  }
  form.image = file;
  const reader = new FileReader();
  reader.onload = ev => preview.value = ev.target.result;
  reader.readAsDataURL(file);
};

const submit = async () => {
  formError.value=''; formSuccess.value=''; saving.value=true;
  try {
    const fd = new FormData();
    fd.append('name', form.name);
    if(form.category) fd.append('category', form.category);
    if(form.note) fd.append('note', form.note);
    if(form.image) fd.append('image', form.image);
    if(editingId.value){
      if(removeImage.value) fd.append('remove_image','1');
      await axios.post(`/api/parent/achievements/${editingId.value}?_method=PUT`, fd, { headers:{'Content-Type':'multipart/form-data'} });
      formSuccess.value='Đã cập nhật';
    } else {
      await axios.post('/api/parent/achievements', fd, { headers:{'Content-Type':'multipart/form-data'} });
      formSuccess.value='Đã tạo';
    }
    await fetchAchievements();
    cancelForm();
  } catch(e){
    formError.value = e.response?.data?.message || 'Lỗi';
  } finally { saving.value=false; }
};

const remove = async (a) => {
  if(!confirm('Xoá thành tích này?')) return;
  try { await axios.delete(`/api/parent/achievements/${a.id}`); achievements.value = achievements.value.filter(x=>x.id!==a.id); } catch(e){ alert('Không thể xoá'); }
};

const isAchieved = (a,k) => {
  return !!a.kids?.find(x=>x.id===k.id && x.achieved);
};

const countAchieved = (a) => {
  return kids.value.filter(k=>isAchieved(a,k)).length;
};

const toggle = async (a,k) => {
  const key = a.id + '-' + k.id;
  toggling[key]=true;
  try {
    const { data } = await axios.post(`/api/parent/achievements/${a.id}/toggle-kid/${k.id}`);
    // update local structure
    if(data.achieved){
      if(!a.kids) a.kids=[];
      const existing = a.kids.find(x=>x.id===k.id);
      if(existing) existing.achieved = true; else a.kids.push({id:k.id,name:k.name,achieved:true});
    } else {
      if(a.kids){
        const existing = a.kids.find(x=>x.id===k.id);
        if(existing) existing.achieved = false;
      }
    }
  } catch(e){
    alert('Không thể cập nhật');
  } finally { delete toggling[key]; }
};

const shortName = (n) => {
  if(!n) return '';
  return n.split(' ').map(p=>p[0]).join('').slice(0,3).toUpperCase();
};

onMounted(fetchAll);
</script>
<style scoped>
.btn-xs { padding:2px 6px; font-size:11px; }
.active-achieved { background:#198754 !important; color:#fff !important; }
.matrix-wrapper { background:#fff; }
.matrix-table th, .matrix-table td { white-space:nowrap; }
.matrix-table .kid-col { min-width:60px; }
.kid-name { font-size:11px; font-weight:600; }
.cell-btn { border:0; background:#f8f9fa; padding:0; min-width:40px; min-height:40px; cursor:pointer; }
.cell-btn:hover { background:#e2e6ea; }
.cell-btn.achieved { background:#198754; color:#fff; font-weight:600; }
.cell-btn.achieved:hover { background:#146c43; }
.cell-btn .icon { font-size:16px; line-height:1; }
</style>
