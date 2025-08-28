<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Daughter - Ứng dụng quản lý điểm thưởng</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f6c23e;
            --accent-color: #e74a3b;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .hero-section {
            background: linear-gradient(135deg, #7c69ef 0%, #448ef6 100%);
            color: white;
            padding: 5rem 0;
        }

        .feature-card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: #333;
        }

        .parent-color {
            color: #e6f2ff;
            background-color: #4e73df;
        }

        .kid-color {
            color: #ffe6f0;
            background-color: #e83e8c;
        }

        .footer {
            background-color: #343a40;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-child me-2"></i>
                My Daughter
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Tính năng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">Cách hoạt động</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary ms-lg-3 px-3" href="/login">Đăng nhập</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Quản lý điểm thưởng cho con của bạn</h1>
                    <p class="lead mb-4">Ứng dụng My Daughter giúp phụ huynh dễ dàng thiết lập hệ thống điểm thưởng, khuyến khích và theo dõi sự phát triển của con.</p>
                    <div class="d-flex gap-3">
                        <a href="/user-parent" class="btn btn-light btn-lg">
                            <i class="fas fa-user-tie me-2"></i>Dành cho phụ huynh
                        </a>
                        <a href="/login" class="btn btn-secondary btn-lg">
                            <i class="fas fa-child me-2"></i>Dành cho trẻ em
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0 text-center">
                    <img src="https://img.freepik.com/free-vector/hand-drawn-family-spending-time-together_23-2148508353.jpg" alt="Family illustration" class="img-fluid rounded-3 shadow" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Tính năng nổi bật</h2>
                <p class="lead text-muted">Công cụ hiệu quả để hỗ trợ việc nuôi dạy con cái</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <div class="feature-icon text-primary">
                                <i class="fas fa-star"></i>
                            </div>
                            <h4>Hệ thống điểm thưởng</h4>
                            <p class="text-muted">Thiết lập điểm thưởng cho các hành vi tốt, khuyến khích trẻ phát triển thói quen tích cực.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <div class="feature-icon text-warning">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h4>Theo dõi tiến triển</h4>
                            <p class="text-muted">Biểu đồ và báo cáo chi tiết giúp bạn theo dõi tiến triển của con theo thời gian thực.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <div class="feature-icon text-danger">
                                <i class="fas fa-gift"></i>
                            </div>
                            <h4>Phần thưởng hấp dẫn</h4>
                            <p class="text-muted">Thiết lập phần thưởng hấp dẫn để con bạn có động lực phấn đấu và đạt được mục tiêu.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-5 bg-light" id="how-it-works">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Cách hoạt động</h2>
                <p class="lead text-muted">Đơn giản và hiệu quả</p>
            </div>

            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="row g-0">
                            <div class="col-md-3 parent-color d-flex align-items-center justify-content-center">
                                <h1 class="display-3 fw-bold m-0">1</h1>
                            </div>
                            <div class="col-md-9">
                                <div class="card-body">
                                    <h5 class="card-title">Phụ huynh thiết lập hệ thống</h5>
                                    <p class="card-text">Tạo tài khoản, cài đặt quy tắc và điểm thưởng cho các hoạt động khác nhau.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="row g-0">
                            <div class="col-md-3 kid-color d-flex align-items-center justify-content-center">
                                <h1 class="display-3 fw-bold m-0">2</h1>
                            </div>
                            <div class="col-md-9">
                                <div class="card-body">
                                    <h5 class="card-title">Trẻ thực hiện nhiệm vụ</h5>
                                    <p class="card-text">Con bạn hoàn thành các nhiệm vụ và gửi yêu cầu xác nhận.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="row g-0">
                            <div class="col-md-3 parent-color d-flex align-items-center justify-content-center">
                                <h1 class="display-3 fw-bold m-0">3</h1>
                            </div>
                            <div class="col-md-9">
                                <div class="card-body">
                                    <h5 class="card-title">Phụ huynh xác nhận và trao điểm</h5>
                                    <p class="card-text">Bạn xác nhận hoạt động và trao điểm thưởng cho con.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="row g-0">
                            <div class="col-md-3 kid-color d-flex align-items-center justify-content-center">
                                <h1 class="display-3 fw-bold m-0">4</h1>
                            </div>
                            <div class="col-md-9">
                                <div class="card-body">
                                    <h5 class="card-title">Trẻ tích lũy điểm và nhận thưởng</h5>
                                    <p class="card-text">Con bạn tích lũy đủ điểm sẽ đổi được các phần thưởng đã thiết lập.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 text-center">
                    <img src="https://img.freepik.com/free-vector/happy-family-with-children-concept-illustration_114360-2073.jpg" alt="Family workflow" class="img-fluid rounded-3 shadow" style="max-height: 500px;">
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="fw-bold mb-4">Bắt đầu ngay hôm nay</h2>
            <p class="lead mb-4">Hãy cùng khám phá cách My Daughter có thể giúp gia đình bạn kết nối và phát triển</p>
            <a href="/user-parent" class="btn btn-light btn-lg me-3">
                <i class="fas fa-user-tie me-2"></i>Dành cho phụ huynh
            </a>
            <a href="/login" class="btn btn-secondary btn-lg">
                <i class="fas fa-child me-2"></i>Dành cho trẻ em
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>My Daughter</h5>
                    <p>Công cụ quản lý điểm thưởng cho gia đình hiện đại, giúp con trẻ phát triển tích cực.</p>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/" class="text-white text-decoration-none">Trang chủ</a></li>
                        <li class="mb-2"><a href="#features" class="text-white text-decoration-none">Tính năng</a></li>
                        <li class="mb-2"><a href="#how-it-works" class="text-white text-decoration-none">Cách hoạt động</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5>Tài khoản</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/login" class="text-white text-decoration-none">Đăng nhập</a></li>
                        <li class="mb-2"><a href="/user-parent" class="text-white text-decoration-none">Phụ huynh</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h5>Liên hệ</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> support@mydaughter.com</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i> +84 123 456 789</li>
                    </ul>
                    <div class="mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-white">
            <div class="text-center">
                <p class="mb-0">© 2025 My Daughter. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

