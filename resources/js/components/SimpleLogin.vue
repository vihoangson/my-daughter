<template>
  <div class="simple-login-container">
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-gradient">
      <div class="row w-100">
        <div class="col-12 col-md-6 col-lg-4 mx-auto">
          <div class="card shadow-lg border-0 rounded-4">
            <div class="card-body p-5">
              <div class="text-center mb-4">
                <h2 class="fw-bold text-primary mb-3">Đăng Nhập</h2>
                <p class="text-muted">Chọn loại tài khoản của bạn</p>
              </div>

              <!-- User Type Selection -->
              <div v-if="!selectedType" class="user-type-selection">
                <div class="row g-3">
                  <div class="col-6">
                    <button
                      @click="selectUserType('parent')"
                      class="btn btn-outline-primary btn-lg w-100 h-100 py-4 rounded-3 user-type-btn"
                    >
                      <i class="fas fa-user-tie fa-3x mb-3"></i>
                      <div class="fw-bold">PARENTS</div>
                    </button>
                  </div>
                  <div class="col-6">
                    <button
                      @click="selectUserType('child')"
                      class="btn btn-outline-success btn-lg w-100 h-100 py-4 rounded-3 user-type-btn"
                    >
                      <i class="fas fa-child fa-3x mb-3"></i>
                      <div class="fw-bold">KID</div>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Numeric Keypad -->
              <div v-if="selectedType" class="keypad-section">
                <div class="text-center mb-4">
                  <button
                    @click="goBack"
                    class="btn btn-link text-decoration-none p-0 mb-3"
                  >
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                  </button>
                  <h4 class="fw-bold">
                    <i :class="selectedType === 'parent' ? 'fas fa-user-tie' : 'fas fa-child'" class="me-2"></i>
                    {{ selectedType === 'parent' ? 'Phụ Huynh' : 'Trẻ Em' }}
                  </h4>
                  <p class="text-muted">Nhập mật khẩu số</p>
                </div>

                <!-- Password Display -->
                <div class="password-display mb-4">
                  <div class="password-dots d-flex justify-content-center gap-2 mb-3">
                    <div
                      v-for="i in 4"
                      :key="i"
                      class="password-dot"
                      :class="{ 'filled': password.length >= i }"
                    ></div>
                  </div>
                  <div class="text-center">
                    <small class="text-muted">{{ password.length }}/4 số</small>
                  </div>
                </div>

                <!-- Numeric Keypad -->
                <div class="numeric-keypad">
                  <div class="row g-2">
                    <div class="col-4" v-for="number in [1,2,3,4,5,6,7,8,9]" :key="number">
                      <button
                        @click="addNumber(number)"
                        class="btn btn-outline-secondary btn-lg w-100 keypad-btn"
                        :disabled="password.length >= 4"
                      >
                        {{ number }}
                      </button>
                    </div>
                    <div class="col-4">
                      <button
                        @click="clearPassword"
                        class="btn btn-outline-danger btn-lg w-100 keypad-btn"
                        :disabled="password.length === 0"
                      >
                        <i class="fas fa-backspace"></i>
                      </button>
                    </div>
                    <div class="col-4">
                      <button
                        @click="addNumber(0)"
                        class="btn btn-outline-secondary btn-lg w-100 keypad-btn"
                        :disabled="password.length >= 4"
                      >
                        0
                      </button>
                    </div>
                    <div class="col-4">
                      <button
                        @click="deleteLastNumber"
                        class="btn btn-outline-warning btn-lg w-100 keypad-btn"
                        :disabled="password.length === 0"
                      >
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Login Button -->
                <div class="mt-4">
                  <button
                    @click="login"
                    class="btn btn-primary btn-lg w-100 rounded-3"
                    :disabled="password.length !== 4 || isLoading"
                  >
                    <span v-if="isLoading">
                      <i class="fas fa-spinner fa-spin me-2"></i>Đang đăng nhập...
                    </span>
                    <span v-else>
                      <i class="fas fa-sign-in-alt me-2"></i>Đăng Nhập
                    </span>
                  </button>
                </div>

                <!-- Error Message -->
                <div v-if="errorMessage" class="alert alert-danger mt-3">
                  <i class="fas fa-exclamation-circle me-2"></i>
                  {{ errorMessage }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'SimpleLogin',
  data() {
    return {
      selectedType: null,
      password: '',
      isLoading: false,
      errorMessage: '',
      remainingLockoutSeconds: 0,
      lockoutCountdownInterval: null
    }
  },
  methods: {
    selectUserType(type) {
      this.selectedType = type
      this.password = ''
      this.errorMessage = ''
    },
    goBack() {
      this.selectedType = null
      this.password = ''
      this.errorMessage = ''
    },
    addNumber(number) {
      if (this.password.length < 4) {
        this.password += number.toString()
        this.errorMessage = ''

        // Auto-submit when 4 digits are entered
        if (this.password.length === 4) {
          this.login()
        }
      }
    },
    deleteLastNumber() {
      if (this.password.length > 0) {
        this.password = this.password.slice(0, -1)
        this.errorMessage = ''
      }
    },
    clearPassword() {
      this.password = ''
      this.errorMessage = ''
    },
    async login() {
      if (this.password.length !== 4) {
        this.errorMessage = 'Vui lòng nhập đủ 4 số'
        return
      }

      this.isLoading = true
      this.errorMessage = ''
//todo: hiển thị lỗi nếu có đối với người dùng nhập sai mật khẩu nhiều lần
      try {
        const response = await axios.post('/api/auth/simple-login', {
          password: this.password,
          type: this.selectedType
        })

        if (response.data.token) {
          localStorage.setItem('token', response.data.token)
          axios.defaults.headers.common['Authorization'] = 'Bearer ' + response.data.token
          window.currentUser = response.data.user

          // Redirect based on user type
          if (this.selectedType === 'parent') {
            this.$router.push('/')
          } else {
            this.$router.push('/user-kid')
          }
        }
      } catch (error) {
        if (error.response) {
          if (error.response.status === 401) {
            this.errorMessage = 'Mật khẩu không đúng hoặc không tìm thấy tài khoản'
          } else if (error.response.status === 429) {
            // Handle rate limiting (too many login attempts)
            const data = error.response.data
            this.errorMessage = data.message || 'Bạn đã nhập sai mật khẩu quá nhiều lần. Vui lòng thử lại sau.'

            // If we have remaining seconds, we can show a countdown timer
            if (data.remaining_seconds) {
              this.startLockoutCountdown(data.remaining_seconds)
            }
          } else {
            this.errorMessage = 'Có lỗi xảy ra. Vui lòng thử lại'
          }
        } else {
          this.errorMessage = 'Có lỗi xảy ra. Vui lòng thử lại'
        }
        this.password = ''
      } finally {
        this.isLoading = false
      }
    },
    startLockoutCountdown(seconds) {
      // Store the remaining seconds
      this.remainingLockoutSeconds = seconds

      // Clear any existing countdown interval
      if (this.lockoutCountdownInterval) {
        clearInterval(this.lockoutCountdownInterval)
      }

      // Set up the countdown interval
      this.lockoutCountdownInterval = setInterval(() => {
        // Decrease the remaining seconds
        this.remainingLockoutSeconds--

        // Update the error message with remaining time
        this.errorMessage = `Bạn đã nhập sai mật khẩu quá nhiều lần. Vui lòng thử lại sau ${this.remainingLockoutSeconds} giây.`

        // If the countdown is finished, clear the interval and reset
        if (this.remainingLockoutSeconds <= 0) {
          clearInterval(this.lockoutCountdownInterval)
          this.lockoutCountdownInterval = null
          this.errorMessage = 'Bạn có thể thử lại bây giờ.'
        }
      }, 1000)
    }
  }
}
</script>

<style scoped>
.simple-login-container {
  min-height: 100vh;
}

.bg-gradient {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.user-type-btn {
  transition: all 0.3s ease;
  border: 2px solid;
}

.user-type-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.password-dots {
  max-width: 300px;
  margin: 0 auto;
}

.password-dot {
  width: 20px;
  height: 20px;
  border: 2px solid #dee2e6;
  border-radius: 50%;
  background-color: transparent;
  transition: all 0.3s ease;
}

.password-dot.filled {
  background-color: #007bff;
  border-color: #007bff;
}

.keypad-btn {
  height: 60px;
  font-size: 1.2rem;
  font-weight: bold;
  transition: all 0.2s ease;
}

.keypad-btn:hover:not(:disabled) {
  transform: scale(1.05);
}

.keypad-btn:active:not(:disabled) {
  transform: scale(0.95);
}

.card {
  backdrop-filter: blur(10px);
  background-color: rgba(255, 255, 255, 0.95);
}

@media (max-width: 768px) {
  .card-body {
    padding: 2rem !important;
  }

  .keypad-btn {
    height: 50px;
    font-size: 1rem;
  }

  .user-type-btn {
    padding: 2rem 1rem !important;
  }
}
</style>
