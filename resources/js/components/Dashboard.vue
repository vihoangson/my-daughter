<template>
  <div>
    <!-- If child user, show KidDashboard (with tabs including Cổ phiếu) -->
    <KidDashboard v-if="isChild" />
    <template v-else>
      <h2 class="mb-3">Reward & Punishment</h2>
      <div v-if="editing !== null">
        <RewardPunishmentForm :editing="editing" @cancel="cancel" @saved="saved" />
      </div>
      <div v-else>
        <RewardPunishmentList :refresh="refresh" @edit="edit" />
      </div>
      <KidManager />
    </template>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue';
import RewardPunishmentList from './RewardPunishmentList.vue';
import RewardPunishmentForm from './RewardPunishmentForm.vue';
import KidManager from './KidManager.vue';
import KidDashboard from './kid/KidDashboard.vue'; // added

const editing = ref(null);
const refresh = ref(false);
const edit = (item) => { editing.value = item; };
const saved = () => { editing.value = null; refresh.value = !refresh.value; };
const cancel = () => { editing.value = null; };
const isChild = computed(()=> window.currentUser && window.currentUser.type === 'child');
</script>
