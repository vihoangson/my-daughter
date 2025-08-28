<template>
  <div class="position-fixed top-0 end-0 p-3" style="z-index: 11">
    <div
      id="liveToast"
      class="toast"
      :class="typeClass"
      role="alert"
      aria-live="assertive"
      aria-atomic="true">
      <div class="toast-header">
        <strong class="me-auto">{{ title }}</strong>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="toast"
          aria-label="Close"
          @click="hide"></button>
      </div>
      <div class="toast-body">
        {{ message }}
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watchEffect } from 'vue';
import { Toast } from 'bootstrap';

export default {
  name: 'ToastMessage',
  props: {
    message: {
      type: String,
      required: true
    },
    title: {
      type: String,
      default: 'Thông báo'
    },
    type: {
      type: String,
      default: 'success',
      validator: (value) => ['success', 'danger', 'warning', 'info'].includes(value)
    },
    show: {
      type: Boolean,
      default: false
    },
    duration: {
      type: Number,
      default: 3000
    }
  },
  setup(props, { emit }) {
    const toastInstance = ref(null);

    const hide = () => {
      if (toastInstance.value) {
        toastInstance.value.hide();
      }
      emit('update:show', false);
    };

    const typeClass = computed(() => {
      return {
        'bg-success text-white': props.type === 'success',
        'bg-danger text-white': props.type === 'danger',
        'bg-warning': props.type === 'warning',
        'bg-info': props.type === 'info'
      };
    });

    onMounted(() => {
      const toastEl = document.getElementById('liveToast');
      toastInstance.value = new Toast(toastEl, {
        delay: props.duration
      });

      toastEl.addEventListener('hidden.bs.toast', () => {
        emit('update:show', false);
      });
    });

    watchEffect(() => {
      if (props.show && toastInstance.value) {
        toastInstance.value.show();
      }
    });

    return {
      hide,
      typeClass
    };
  }
}
</script>

<style scoped>
.toast {
  min-width: 250px;
}
</style>
