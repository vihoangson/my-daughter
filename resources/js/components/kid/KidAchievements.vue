<template>
  <div class="kid-achievements mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h5 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Huy hiệu thành tích</h5>
      <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary" :class="{active: viewMode==='list'}" @click="viewMode='list'">Danh sách</button>
          <button class="btn btn-outline-secondary" :class="{active: viewMode==='grid'}" @click="viewMode='grid'">Grid</button>
        </div>
        <button class="btn btn-sm btn-outline-secondary" @click="load" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-1" />Tải lại
        </button>
      </div>
    </div>
    <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
    <div v-if="!loading && achievementsWithImage.length===0" class="text-muted fst-italic">Chưa có thành tích có hình ảnh.</div>

    <!-- LIST VIEW -->
    <div v-if="viewMode==='list'">
      <div v-if="achievementsWithImage.length" class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:50px">#</th>
              <th style="width:70px">Ảnh</th>
              <th>Tên</th>
              <th style="min-width:160px">Ghi chú phụ huynh</th>
              <th style="width:110px">Trạng thái</th>
              <th style="width:90px">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(a,i) in achievementsWithImage" :key="'row-'+a.id" :class="{'table-secondary': !a.achieved}">
              <td>{{ i+1 }}</td>
              <td>
                <img :src="a.image_url" :alt="a.name" class="rounded" style="width:56px;height:56px;object-fit:cover" />
              </td>
              <td>
                <div class="fw-semibold">{{ a.name }}</div>
                <div class="small text-muted" v-if="a.category">{{ a.category }}</div>
              </td>
              <td class="small">
                <span v-if="a.note">{{ shortNote(a.note) }}</span>
                <span v-else class="text-muted fst-italic">(Không)</span>
              </td>
              <td>
                <span class="badge w-100" :class="a.achieved ? 'text-bg-success':'text-bg-secondary'">{{ a.achieved? 'Đã đạt':'Chưa đạt' }}</span>
              </td>
              <td>
                <button class="btn btn-primary btn-sm w-100" @click="openDetails(a)">Chi tiết</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- GRID VIEW -->
    <div v-else class="row g-3">
      <div v-for="a in achievementsWithImage" :key="a.id" class="col-6 col-sm-4 col-md-3 col-lg-2">
        <div class="achievement-tile card h-100 text-center p-2 position-relative" :class="{'not-achieved': !a.achieved}">
          <div class="ratio ratio-1x1 mb-2 rounded overflow-hidden border bg-light">
            <img v-if="a.image_url" :src="a.image_url" :alt="a.name" class="img-fluid w-100 h-100 object-cover" />
          </div>
          <div class="name small fw-semibold text-truncate" :title="a.name">{{ a.name }}</div>
            <div class="note text-muted small" v-if="a.note" :title="a.note">{{ shortNote(a.note) }}</div>
          <div class="status mt-1 mb-1">
            <span v-if="a.achieved" class="badge text-bg-success">Đã đạt</span>
            <span v-else class="badge text-bg-secondary">Chưa đạt</span>
          </div>
          <button class="btn btn-sm btn-outline-primary w-100 mt-auto" @click="openDetails(a)">Chi tiết</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h6 class="modal-title mb-0 d-flex align-items-center gap-2">
              <i class="fas fa-award text-warning"></i>
              {{ selected?.name }}
            </h6>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="selected" class="row g-3">
              <div class="col-md-5">
                <div class="ratio ratio-1x1 rounded overflow-hidden border bg-light mb-2">
                  <img v-if="selected.image_url" :src="selected.image_url" :alt="selected.name" class="w-100 h-100 object-cover" />
                  <div v-else class="d-flex justify-content-center align-items-center h-100 text-muted small">Không có ảnh</div>
                </div>
                <div class="mb-2 d-flex flex-wrap gap-1">
                  <span v-if="selected.category" class="badge text-bg-info">{{ selected.category }}</span>
                  <span v-else class="badge text-bg-secondary">Không phân loại</span>
                  <span class="badge" :class="selected.achieved? 'text-bg-success':'text-bg-secondary'">{{ selected.achieved? 'Đã đạt':'Chưa đạt' }}</span>
                </div>
              </div>
              <div class="col-md-7">
                <div class="mb-3">
                  <label class="form-label fw-semibold mb-1">Ghi chú của phụ huynh</label>
                  <div v-if="selected.note" class="p-2 small rounded border bg-body-tertiary">{{ selected.note }}</div>
                  <div v-else class="text-muted small fst-italic">Không có ghi chú.</div>
                </div>
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-semibold mb-0">Ghi chú của bé</label>
                    <div class="btn-group btn-group-sm">
                      <button v-if="!editingPersonal" class="btn btn-outline-primary" @click="startEditPersonal">Chỉnh sửa</button>
                      <div v-else class="d-flex gap-1">
                        <button class="btn btn-success" :disabled="savingPersonal" @click="savePersonalNote">
                          <span v-if="savingPersonal" class="spinner-border spinner-border-sm me-1" />Lưu
                        </button>
                        <button class="btn btn-outline-secondary" :disabled="savingPersonal" @click="cancelEditPersonal">Huỷ</button>
                      </div>
                    </div>
                  </div>
                  <div v-if="!editingPersonal" class="p-2 rounded border bg-white personal-note-box" :class="{'text-muted fst-italic': !personalNote}">
                    {{ personalNote || 'Chưa có ghi chú cá nhân.' }}
                  </div>
                  <div v-else>
                    <textarea v-model="personalNoteDraft" class="form-control" rows="4" :maxlength="5000" placeholder="Nhập ghi chú (tối đa 5000 ký tự)"></textarea>
                    <div class="form-text">Ghi chú được lưu an toàn trên hệ thống.</div>
                  </div>
                </div>
                <div v-if="saveError" class="alert alert-warning py-1 small mb-0">{{ saveError }}</div>
              </div>
            </div>
          </div>
          <div class="modal-footer py-2">
            <button class="btn btn-outline-secondary btn-sm" @click="closeModal">Đóng</button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showModal" class="modal-backdrop fade show" @click="closeModal"></div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const achievements = ref([]);
const loading = ref(false);
const error = ref('');
const viewMode = ref('list');
const showModal = ref(false);
const selected = ref(null);
const personalNote = ref('');
const personalNoteDraft = ref('');
const editingPersonal = ref(false);
const savingPersonal = ref(false);
const saveError = ref('');

const achievementsWithImage = computed(()=> achievements.value.filter(a=> !!a.image_url));

const load = async () => {
  loading.value = true; error.value='';
  try { const { data } = await axios.get('/api/kid/achievements'); achievements.value = data; }
  catch(e) { error.value = e.response?.data?.message || 'Không tải được danh sách'; }
  finally { loading.value=false; }
};

const openDetails = (a) => {
  selected.value = a; personalNote.value = a.kid_note || ''; personalNoteDraft.value = personalNote.value; editingPersonal.value = false; saveError.value=''; showModal.value = true; document.body.classList.add('modal-open');
};
const closeModal = () => { showModal.value=false; selected.value=null; setTimeout(()=>document.body.classList.remove('modal-open'),150); };
const startEditPersonal = () => { editingPersonal.value=true; personalNoteDraft.value = personalNote.value; saveError.value=''; };
const cancelEditPersonal = () => { editingPersonal.value=false; personalNoteDraft.value = personalNote.value; saveError.value=''; };
const savePersonalNote = async () => {
  if(!selected.value) return; saveError.value=''; if(personalNoteDraft.value.length>5000){ saveError.value='Vượt quá 5000 ký tự.'; return; }
  savingPersonal.value=true; try { await axios.put(`/api/kid/achievements/${selected.value.id}/kid-note`, { kid_note: personalNoteDraft.value }); personalNote.value = personalNoteDraft.value.trim(); const idx=achievements.value.findIndex(x=>x.id===selected.value.id); if(idx!==-1) achievements.value[idx].kid_note = personalNote.value; if(selected.value) selected.value.kid_note = personalNote.value; editingPersonal.value=false; }
  catch(e){ saveError.value = e.response?.data?.message || 'Không lưu được ghi chú'; }
  finally { savingPersonal.value=false; }
};
const shortNote = (n) => n && n.length>60 ? n.slice(0,57)+'...' : (n||'');

onMounted(load);
</script>
<style scoped>
.table td, .table th { vertical-align:middle; }
.achievement-tile { transition:.25s; border:1px solid #e3e6ef; box-shadow:0 1px 2px rgba(0,0,0,.05); }
.achievement-tile.not-achieved { filter:grayscale(1) brightness(.75); opacity:.7; }
.achievement-tile.not-achieved:hover { filter:grayscale(.85) brightness(.85); }
.object-cover { object-fit:cover; }
.ratio { position:relative; width:100%; }
.ratio:before { content:""; display:block; padding-top:100%; }
.ratio>* { position:absolute; inset:0; }
.modal-backdrop { background:rgba(0,0,0,.35); }
.modal-open { overflow:hidden; }
.personal-note-box { min-height:90px; white-space:pre-line; }
</style>
