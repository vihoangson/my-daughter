<template>
  <div
    class="toast-container position-fixed top-0 end-0 p-3"
    style="z-index: 1100;"
  >
    <div
      v-if="show"
      class="toast show"
      :class="toastClass"
      role="alert"
      aria-live="assertive"
      aria-atomic="true"
    >
      <div class="toast-header" :class="headerClass">
        <strong class="me-auto">{{ title }}</strong>
        <button
          type="button"
          class="btn-close"
          @click="closeToast"
          aria-label="Close"
        ></button>
      </div>
      <div class="toast-body">
        {{ message }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Toast',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    message: {
      type: String,
      default: ''
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
    duration: {
      type: Number,
      default: 3000
    }
  },
  emits: ['update:show'],
  computed: {
    toastClass() {
      return {
        'border-success': this.type === 'success',
        'border-danger': this.type === 'danger',
        'border-warning': this.type === 'warning',
        'border-info': this.type === 'info'
      };
    },
    headerClass() {
      return {
        'text-bg-success': this.type === 'success',
        'text-bg-danger': this.type === 'danger',
        'text-bg-warning': this.type === 'warning',
        'text-bg-info': this.type === 'info'
      };
    }
  },
  watch: {
    show(newVal) {
      if (newVal && this.duration > 0) {
        setTimeout(() => {
          this.closeToast();
        }, this.duration);
      }
    }
  },
  methods: {
    closeToast() {
      this.$emit('update:show', false);
    }
  }
};
</script>
