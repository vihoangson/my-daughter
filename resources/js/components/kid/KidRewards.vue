<template>
  <div class="kid-rewards mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h5 class="mb-0"><i class="fas fa-gift me-2 text-danger"></i>Phần thưởng</h5>
      <div class="d-flex gap-2">
        <div class="badge bg-success" v-if="profile">Acoin: {{ profile.acoin_balance }}</div>
        <button class="btn btn-sm btn-outline-secondary" @click="loadAll" :disabled="loading || redeeming">
          <span v-if="loading" class="spinner-border spinner-border-sm me-1" />Tải lại
        </button>
      </div>
    </div>
    <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
    <div v-if="success" class="alert alert-success py-2">{{ success }}</div>
    <div v-if="!loading && !items.length" class="text-muted fst-italic">Chưa có phần thưởng khả dụng.</div>
    <div class="row g-3">
      <div v-for="it in items" :key="it.id" class="col-6 col-sm-4 col-md-3 col-lg-2">
        <div class="card h-100 reward-tile" @click="openDetails(it)">
          <div class="ratio ratio-1x1 bg-light rounded-top overflow-hidden">
            <img v-if="it.image_url" :src="it.image_url" :alt="it.name" class="w-100 h-100 object-cover" />
            <div v-else class="d-flex flex-column justify-content-center align-items-center h-100 text-muted small">NO IMG</div>
          </div>
          <div class="card-body p-2 d-flex flex-column">
            <div class="fw-semibold small text-truncate" :title="it.name">{{ it.name }}</div>
            <div class="small text-primary">{{ it.price_acoin }} Acoin</div>
            <div class="mt-auto small text-muted" v-if="it.redeemed_count">Đã đổi: {{ it.redeemed_count }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h6 class="modal-title mb-0 d-flex align-items-center gap-2">
              <i class="fas fa-gift text-danger"></i>{{ selected?.name }}
            </h6>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="selected" class="row g-3">
              <div class="col-5">
                <div class="ratio ratio-1x1 rounded overflow-hidden bg-light border">
                  <img v-if="selected.image_url" :src="selected.image_url" :alt="selected.name" class="w-100 h-100 object-cover" />
                  <div v-else class="d-flex justify-content-center align-items-center text-muted small">NO IMG</div>
                </div>
              </div>
              <div class="col-7">
                <p class="mb-2 small"><strong>Tên:</strong> {{ selected.name }}</p>
                <p class="mb-2 small"><strong>Giá:</strong> <span class="text-primary fw-bold">{{ selected.price_acoin }} Acoin</span></p>
                <p class="mb-2 small"><strong>Ghi chú:</strong> {{ selected.note || 'Không có' }}</p>
                <p class="mb-2 small" v-if="selected.redeemed_count"><strong>Đã đổi:</strong> {{ selected.redeemed_count }}</p>
                <div class="alert alert-warning py-2 small mb-2" v-if="profile && profile.acoin_balance < selected.price_acoin">
                  Không đủ Acoin. Cần {{ selected.price_acoin }}, hiện có {{ profile.acoin_balance }}.
                </div>
                <button class="btn btn-success w-100" :disabled="redeeming || (profile && profile.acoin_balance < selected.price_acoin)" @click="redeem">
                  <span v-if="redeeming" class="spinner-border spinner-border-sm me-2" />Đổi thưởng
                </button>
              </div>
            </div>
            <hr />
            <h6 class="fw-bold small mb-2">Lịch sử đổi gần đây</h6>
            <div v-if="redemptionsLoading" class="text-center py-2"><div class="spinner-border spinner-border-sm"></div></div>
            <div v-else-if="!recentRedemptions.length" class="small text-muted fst-italic">Chưa có lịch sử.</div>
            <ul v-else class="list-unstyled small mb-0" style="max-height:140px;overflow:auto;">
              <li v-for="r in recentRedemptions" :key="r.id" class="d-flex justify-content-between border-bottom py-1">
                <span>{{ r.reward_item?.name || '---' }}</span>
                <span class="text-danger">-{{ r.price_acoin }}</span>
              </li>
            </ul>
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
import { ref, onMounted } from 'vue';
import axios from 'axios';

const items = ref([]);
const loading = ref(false);
const error = ref('');
const success = ref('');
const profile = ref(null);

const showModal = ref(false);
const selected = ref(null);
const redeeming = ref(false);
const redemptionsLoading = ref(false);
const recentRedemptions = ref([]);

const loadProfile = async () => {
  try { const { data } = await axios.get('/api/kid/profile'); profile.value = data.profile || data; } catch { /* ignore */ }
};

const fetchItems = async () => {
  loading.value=true; error.value='';
  try { const { data } = await axios.get('/api/kid/rewards'); items.value = Array.isArray(data)? data: []; }
  catch(e){ error.value = e.response?.data?.message || 'Không tải được danh sách'; }
  finally { loading.value=false; }
};

const loadRedemptions = async () => {
  redemptionsLoading.value = true; recentRedemptions.value=[];
  try { const { data } = await axios.get('/api/kid/rewards/redemptions'); recentRedemptions.value = Array.isArray(data)? data: data; }
  catch { /* ignore */ }
  finally { redemptionsLoading.value=false; }
};

const openDetails = (it) => {
  selected.value = it; showModal.value=true; loadRedemptions();
};
const closeModal = () => { showModal.value=false; selected.value=null; };

const redeem = async () => {
  if(!selected.value) return; redeeming.value=true; success.value=''; error.value='';
  try {
    const { data } = await axios.post(`/api/kid/rewards/${selected.value.id}/redeem`);
    success.value = data.message || 'Đổi thành công';
    if(profile.value) profile.value.acoin_balance = data.balance_after;
    // increment local redeemed count
    const found = items.value.find(x=>x.id===selected.value.id);
    if(found){ found.redeemed_count = (found.redeemed_count||0)+1; selected.value.redeemed_count = found.redeemed_count; }
    await loadRedemptions();
  } catch(e){ error.value = e.response?.data?.message || 'Đổi thất bại'; }
  finally { redeeming.value=false; }
};

const loadAll = async () => { await Promise.all([loadProfile(), fetchItems()]); };

onMounted(loadAll);
</script>
<style scoped>
.reward-tile { cursor:pointer; transition:.2s; }
.reward-tile:hover { box-shadow:0 4px 12px rgba(0,0,0,.12); transform:translateY(-3px); }
.object-cover { object-fit:cover; }
.modal-backdrop { background:rgba(0,0,0,.35); }
</style>

