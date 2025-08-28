<template>
  <div class="homepage">
    <div class="hero-section bg-primary text-white py-5 mb-4">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-7">
            <h1 class="display-4 fw-bold">Ứng dụng quản lý điểm thưởng</h1>
            <p class="lead">Hỗ trợ phụ huynh xây dựng thói quen tốt cho trẻ em thông qua hệ thống điểm thưởng</p>
            <div class="mt-4">
              <router-link to="/login" class="btn btn-light btn-lg me-2">
                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
              </router-link>
              <router-link to="/simple-login" class="btn btn-outline-light btn-lg">
                <i class="fas fa-child me-2"></i>Đăng nhập cho trẻ em
              </router-link>
            </div>
          </div>
          <div class="col-md-5 d-none d-md-block text-center">
            <div class="hero-image">
              <!-- Replace external image with Font Awesome icon -->
              <i class="fas fa-users fa-6x text-white mb-3 opacity-75"></i>
              <div class="text-center text-white">
                <p class="h5">Cùng xây dựng thói quen tốt cho gia đình</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container mb-5">
      <div class="row text-center mb-5">
        <div class="col-12">
          <h2 class="display-5 fw-bold">Tại sao chọn ứng dụng của chúng tôi?</h2>
          <p class="lead text-muted">Giải pháp hiệu quả giúp phụ huynh và trẻ em cùng xây dựng thói quen tốt</p>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                <i class="fas fa-star fa-2x"></i>
              </div>
              <h3 class="card-title h5 mb-3">Hệ thống điểm thưởng</h3>
              <p class="card-text text-muted">Tạo động lực cho trẻ thông qua hệ thống điểm thưởng rõ ràng và minh bạch.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                <i class="fas fa-child fa-2x"></i>
              </div>
              <h3 class="card-title h5 mb-3">Phù hợp với trẻ em</h3>
              <p class="card-text text-muted">Giao diện đơn giản, thân thiện và dễ sử dụng cho cả phụ huynh và trẻ em.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                <i class="fas fa-chart-line fa-2x"></i>
              </div>
              <h3 class="card-title h5 mb-3">Theo dõi tiến độ</h3>
              <p class="card-text text-muted">Theo dõi sự tiến bộ của trẻ qua thời gian và khuyến khích phát triển các thói quen tốt.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid bg-light py-5 mb-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-6 mb-4 mb-md-0">
            <!-- Replace external image with Font Awesome icon in a styled container -->
            <div class="text-center">
              <div class="bg-white p-4 rounded-circle d-inline-block shadow-sm">
                <i class="fas fa-home fa-5x text-primary"></i>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <h2 class="fw-bold mb-4">Xây dựng môi trường gia đình tích cực</h2>
            <p class="lead">Với ứng dụng quản lý điểm thưởng, phụ huynh có thể:</p>
            <ul class="list-unstyled">
              <li class="mb-3">
                <i class="fas fa-check-circle text-success me-2"></i>
                <span>Tạo hệ thống khen thưởng rõ ràng cho trẻ em</span>
              </li>
              <li class="mb-3">
                <i class="fas fa-check-circle text-success me-2"></i>
                <span>Theo dõi sự tiến bộ và phát triển của trẻ</span>
              </li>
              <li class="mb-3">
                <i class="fas fa-check-circle text-success me-2"></i>
                <span>Xây dựng thói quen tốt thông qua các nhiệm vụ và phần thưởng</span>
              </li>
              <li class="mb-3">
                <i class="fas fa-check-circle text-success me-2"></i>
                <span>Tạo động lực cho trẻ trong học tập và các hoạt động hàng ngày</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="container mb-5">
      <div class="row align-items-center">
        <div class="col-md-6 order-md-2 mb-4 mb-md-0">
          <!-- Replace external image with Font Awesome icon in a styled container -->
          <div class="text-center">
            <div class="bg-primary p-4 rounded-3 d-inline-block shadow">
              <i class="fas fa-tasks fa-5x text-white"></i>
            </div>
          </div>
        </div>
        <div class="col-md-6 order-md-1">
          <h2 class="fw-bold mb-4">Quy trình đơn giản và hiệu quả</h2>
          <div class="d-flex mb-4">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="min-width: 40px; height: 40px;">
              <span class="fw-bold">1</span>
            </div>
            <div>
              <h4 class="h5 mb-1">Thiết lập tài khoản</h4>
              <p class="text-muted mb-0">Tạo tài khoản phụ huynh và thêm các tài khoản trẻ em</p>
            </div>
          </div>
          <div class="d-flex mb-4">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="min-width: 40px; height: 40px;">
              <span class="fw-bold">2</span>
            </div>
            <div>
              <h4 class="h5 mb-1">Thiết lập mục tiêu</h4>
              <p class="text-muted mb-0">Xác định các hành vi và mục tiêu muốn khuyến khích</p>
            </div>
          </div>
          <div class="d-flex mb-4">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="min-width: 40px; height: 40px;">
              <span class="fw-bold">3</span>
            </div>
            <div>
              <h4 class="h5 mb-1">Thưởng và phạt điểm</h4>
              <p class="text-muted mb-0">Tạo hệ thống điểm thưởng cho các hành vi tích cực</p>
            </div>
          </div>
          <div class="d-flex">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="min-width: 40px; height: 40px;">
              <span class="fw-bold">4</span>
            </div>
            <div>
              <h4 class="h5 mb-1">Theo dõi tiến độ</h4>
              <p class="text-muted mb-0">Theo dõi sự tiến bộ và điều chỉnh mục tiêu khi cần thiết</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-primary text-white py-5">
      <div class="container">
        <div class="row text-center">
          <div class="col-12 mb-4">
            <h2 class="fw-bold">Sẵn sàng bắt đầu?</h2>
            <p class="lead">Tạo môi trường học tập tích cực cho trẻ ngay hôm nay</p>
          </div>
          <div class="col-12">
            <router-link to="/login" class="btn btn-light btn-lg me-2">
              <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
            </router-link>
            <router-link to="/simple-login" class="btn btn-outline-light btn-lg">
              <i class="fas fa-child me-2"></i>Đăng nhập cho trẻ em
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <footer class="bg-dark text-white py-4">
      <div class="container">
        <div class="row">
          <div class="col-md-6 mb-3 mb-md-0">
            <h5>Ứng dụng quản lý điểm thưởng</h5>
            <p class="mb-0 text-muted">© 2025 My Daughter. Bản quyền thuộc về My Daughter.</p>
          </div>
          <div class="col-md-6 text-md-end">
            <ul class="list-inline mb-0">
              <li class="list-inline-item">
                <a href="#" class="text-white">
                  <i class="fab fa-facebook fa-fw"></i>
                </a>
              </li>
              <li class="list-inline-item">
                <a href="#" class="text-white">
                  <i class="fab fa-twitter fa-fw"></i>
                </a>
              </li>
              <li class="list-inline-item">
                <a href="#" class="text-white">
                  <i class="fab fa-instagram fa-fw"></i>
                </a>
              </li>
              <li class="list-inline-item">
                <a href="#" class="text-white">
                  <i class="fab fa-youtube fa-fw"></i>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'Homepage'
}
</script>

<style scoped>
.hero-section {
  background-image: linear-gradient(135deg, #1976d2 0%, #64b5f6 100%);
}

.hero-image {
  padding: 2rem;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-10px);
  }
  100% {
    transform: translateY(0px);
  }
}

.card {
  transition: transform 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
}
</style>
