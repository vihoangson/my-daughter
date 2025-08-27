<template>
  <div>
    <h2 class="mb-3">Reward & Punishment</h2>
    <div v-if="editing !== null">
      <RewardPunishmentForm :editing="editing" @cancel="cancel" @saved="saved" />
    </div>
    <div v-else>
      <RewardPunishmentList :refresh="refresh" @edit="edit" />
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import RewardPunishmentList from './RewardPunishmentList.vue';
import RewardPunishmentForm from './RewardPunishmentForm.vue';
const editing = ref(null);
const refresh = ref(false);
const edit = (item) => { editing.value = item; };
const saved = () => { editing.value = null; refresh.value = !refresh.value; };
const cancel = () => { editing.value = null; };
</script>

