<template>
  <div>
    <h2>{{ editing ? 'Edit' : 'Add' }} Reward/Punishment</h2>
    <form @submit.prevent="handleSubmit">
      <div class="mb-3">
        <label class="form-label">Child</label>
        <select name="child_id" class="form-select" v-model="form.child_id" required>
          <option value="">Select Child</option>
          <option v-for="child in children" :key="child.id" :value="child.id">{{ child.name }}</option>
        </select>
        <div v-if="errors.child_id" class="text-danger">{{ errors.child_id[0] }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" v-model="form.type" required>
          <option value="reward">Reward</option>
          <option value="punishment">Punishment</option>
        </select>
        <div v-if="errors.type" class="text-danger">{{ errors.type[0] }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Points</label>
        <input type="number" name="points" class="form-control" v-model="form.points" required />
        <div v-if="errors.points" class="text-danger">{{ errors.points[0] }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control" v-model="form.description" />
        <div v-if="errors.description" class="text-danger">{{ errors.description[0] }}</div>
      </div>
      <button type="submit" class="btn btn-success me-2">Save</button>
      <button type="button" class="btn btn-secondary" @click="$emit('cancel')">Cancel</button>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({ editing: Object });
const emit = defineEmits(['cancel', 'saved']);
const children = ref([]);
const form = ref({ child_id: '', type: 'reward', points: '', description: '' });
const errors = ref({});

const fetchChildren = async () => {
  const res = await axios.get('/api/children');
  children.value = res.data;
};

watch(() => props.editing, (val) => {
  if (val) form.value = { ...val };
  else form.value = { child_id: '', type: 'reward', points: '', description: '' };
  errors.value = {};
});

onMounted(fetchChildren);

const handleSubmit = async () => {
  errors.value = {};
  try {
    if (props.editing) {
      await axios.put(`/api/reward-punishments/${props.editing.id}`, form.value);
    } else {
      await axios.post('/api/reward-punishments', form.value);
    }
    emit('saved');
  } catch (err) {
    errors.value = err.response?.data?.errors || {};
  }
};
</script>

