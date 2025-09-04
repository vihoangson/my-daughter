<template>
  <div class="kid-dashboard container py-3">
    <h2 class="mb-3">Khu Vui Chơi Của Bé</h2>
    <ul class="nav nav-tabs mb-3" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link" :class="{active: activeTab==='request'}" @click="activeTab='request'">Gửi yêu cầu</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" :class="{active: activeTab==='history'}" @click="activeTab='history'">Lịch sử yêu cầu</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" :class="{active: activeTab==='stocks'}" @click="activeTab='stocks'">Cổ phiếu (Acoin)</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" :class="{active: activeTab==='achievements'}" @click="activeTab='achievements'">Thành tích</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" :class="{active: activeTab==='rewards'}" @click="activeTab='rewards'">Phần thưởng</button>
      </li>
    </ul>

    <div v-show="activeTab==='request'">
      <RequestForm @request-submitted="refreshKey++" />
    </div>
    <div v-show="activeTab==='history'">
      <RequestHistory :refresh-trigger="refreshKey" />
    </div>
    <div v-show="activeTab==='stocks'">
      <StockTrading />
    </div>
    <div v-show="activeTab==='achievements'">
      <KidAchievements />
    </div>
    <div v-show="activeTab==='rewards'">
      <KidRewards />
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import RequestForm from './RequestForm.vue';
import RequestHistory from './RequestHistory.vue';
import StockTrading from './StockTrading.vue';
import KidAchievements from './KidAchievements.vue';
import KidRewards from './KidRewards.vue';

const activeTab = ref('request');
const refreshKey = ref(0);
</script>
