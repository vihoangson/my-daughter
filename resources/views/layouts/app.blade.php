<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    @vite(['resources/js/app.js'])
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">MyDaughter</a>
        </div>
    </nav>
    <div class="container mt-4 flex-grow-1">
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>MyDaughter</h5>
                    <p class="small">Hệ thống quản lý điểm thưởng và hoạt động cho trẻ em</p>
                    <p class="small">Version 1.0.0</p>
                </div>
                <div class="col-md-4">
                    <h5>Liên hệ</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> support@mydaughter.com</li>
                        <li><i class="fas fa-phone me-2"></i> +84 123 456 789</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> TP. Hồ Chí Minh, Việt Nam</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Theo dõi chúng tôi</h5>
                    <div class="d-flex gap-3 fs-4">
                        <a href="#" class="text-white"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-2">
            <div class="text-center">
                <p class="small mb-0">© {{ date('Y') }} MyDaughter. Đã đăng ký bản quyền.</p>
            </div>
        </div>
    </footer>
</body>
</html>
