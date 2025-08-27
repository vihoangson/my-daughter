<template>
  <div>
    <h2>Reward & Punishment List</h2>
    <button class="btn btn-primary mb-2" @click="$emit('edit', null)">Add New</button>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Child</th>
          <th>Type</th>
          <th>Points</th>
          <th>Description</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in data" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.child?.name }}</td>
          <td>{{ item.type }}</td>
          <td>{{ item.points }}</td>
          <td>{{ item.description }}</td>
          <td>
            <button class="btn btn-sm btn-warning me-2" @click="$emit('edit', item)">Edit</button>
            <button class="btn btn-sm btn-danger" @click="handleDelete(item.id)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({ refresh: Boolean });
const emit = defineEmits(['edit']);
const data = ref([]);
const loading = ref(true);

const fetchData = async () => {
  loading.value = true;
  const res = await axios.get('/api/reward-punishments');
  data.value = res.data.data || [];
  loading.value = false;
};

const handleDelete = async (id) => {
  if (!window.confirm('Delete this record?')) return;
  await axios.delete(`/api/reward-punishments/${id}`);
  fetchData();
};

onMounted(fetchData);
watch(() => props.refresh, fetchData);
</script>

