import { createApp, ref } from 'vue';
import 'bootstrap/dist/css/bootstrap.min.css';
import RewardPunishmentList from './components/RewardPunishmentList.vue';
import RewardPunishmentForm from './components/RewardPunishmentForm.vue';

const App = {
  components: { RewardPunishmentList, RewardPunishmentForm },
  setup() {
    const editing = ref(null);
    const refresh = ref(false);
    const handleEdit = (item) => { editing.value = item; };
    const handleSaved = () => { editing.value = null; refresh.value = !refresh.value; };
    const handleCancel = () => { editing.value = null; };
    return { editing, refresh, handleEdit, handleSaved, handleCancel };
  },
  template: `
    <div class="container mt-4">
      <RewardPunishmentForm v-if="editing !== null" :editing="editing" @cancel="handleCancel" @saved="handleSaved" />
      <RewardPunishmentList v-else :refresh="refresh" @edit="handleEdit" />
    </div>
  `
};

createApp(App).mount('#app');
