<template>
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);" @click.self="close">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ kid?.name }} - Cộng/Trừ điểm</h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>
        <form @submit.prevent="submit">
          <div class="modal-body">
            <div v-if="successMsg" class="alert alert-success py-2">{{ successMsg }}</div>
            <div v-if="errorMsg" class="alert alert-danger py-2">{{ errorMsg }}</div>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Loại</label>
                <div class="btn-group w-100" role="group">
                  <input type="radio" class="btn-check" name="type" id="typeReward" value="reward" v-model="form.type" />
                  <label class="btn btn-outline-success" for="typeReward">Cộng</label>
                  <input type="radio" class="btn-check" name="type" id="typePun" value="punishment" v-model="form.type" />
                  <label class="btn btn-outline-danger" for="typePun">Trừ</label>
                </div>
              </div>
              <div class="col-md-3">
                <label class="form-label">Số điểm</label>
                <input type="number" class="form-control" v-model.number="form.points" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Ghi chú</label>
                <input type="text" class="form-control" v-model="form.description" placeholder="Ghi chú..." />
              </div>
              <div class="col-12">
                <label class="form-label">Hình ảnh bằng chứng (tùy chọn)</label>
                <input ref="fileInput" type="file" accept="image/*" class="form-control" @change="onFile" />
                <small class="text-muted">Định dạng: jpg, png, gif, webp. Tối đa 4MB.</small>
                <div v-if="preview" class="mt-2">
                  <img :src="preview" alt="preview" class="img-thumbnail" style="max-height:150px" />
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="close">Đóng</button>
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2" />Lưu
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, watch } from 'vue';
import axios from 'axios';
const props = defineProps({ kid: Object });
const emit = defineEmits(['close','saved']);
const form = reactive({ type: 'reward', points: 1, description: '' });
const file = ref(null);
const preview = ref(null);
const loading = ref(false);
const successMsg = ref('');
const errorMsg = ref('');
const fileInput = ref(null);
watch(()=>props.kid, ()=>{ reset(); });
function reset(){ form.type='reward'; form.points=1; form.description=''; file.value=null; preview.value=null; successMsg.value=''; errorMsg.value=''; if(fileInput.value) fileInput.value.value=''; }
function onFile(e){ const f = e.target.files[0]; file.value = f || null; if(f){ const r = new FileReader(); r.onload = ev => preview.value = ev.target.result; r.readAsDataURL(f);} else preview.value=null; }
async function submit(){ if(!props.kid) return; loading.value=true; successMsg.value=''; errorMsg.value=''; try { const fd = new FormData(); fd.append('child_id', props.kid.id); fd.append('points', form.points); fd.append('type', form.type); if(form.description) fd.append('description', form.description); if(file.value) fd.append('evidence', file.value); const { data } = await axios.post('/api/reward-punishments', fd, { headers: { 'Content-Type': 'multipart/form-data' }}); successMsg.value='Lưu thành công'; emit('saved', data); setTimeout(()=>{ close(); }, 600); } catch(e){ errorMsg.value = e.response?.data?.message || 'Lỗi lưu điểm'; } finally { loading.value=false; }
}
function close(){ emit('close'); }
</script>
<style scoped>
.modal { overflow-y:auto; }
</style>

