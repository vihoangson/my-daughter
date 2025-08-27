# Nhật ký thay đổi MyDaughter

Tất cả những thay đổi đáng chú ý của dự án sẽ được ghi lại trong file này.

## [v1.0.0] - 2025-08-27

### Tính năng
- Hệ thống quản lý điểm thưởng và phạt cho trẻ em
- Đăng nhập cho phụ huynh và trẻ em với các quyền hạn khác nhau
- Trang dashboard riêng biệt cho trẻ em và phụ huynh
- Trẻ em có thể gửi yêu cầu tới phụ huynh (đồ chơi, món ăn, đi khu vui chơi, hoạt động mong muốn)
- Phụ huynh có thể xem và phản hồi (chấp nhận/từ chối) các yêu cầu từ trẻ em
- Phụ huynh có thể thêm, xóa, sửa thông tin trẻ em
- Phụ huynh có thể quản lý điểm thưởng/phạt
- Hỗ trợ upload avatar và lưu trữ hình ảnh
- Tích hợp S3 storage cho việc lưu trữ hình ảnh

### Cải tiến kỹ thuật
- Sử dụng Laravel 9.x framework
- Xác thực người dùng với Laravel Sanctum
- Giao diện người dùng thân thiện và responsive với Bootstrap
- Tích hợp Vue.js cho các component tương tác người dùng
- API RESTful cho tương tác giữa frontend và backend
