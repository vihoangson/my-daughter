# Nhật ký thay đổi MyDaughter

Tất cả những thay đổi đáng chú ý của dự án sẽ được ghi lại trong file này.

## [v2.0.0] - 2025-08-28

### Tính năng mới
- **Auto-upload Avatar**: Click chọn ảnh avatar sẽ tự động upload và cập nhật không cần bấm nút lưu
- **Local Storage**: Chuyển từ S3 sang local storage (`/storage/app/public`) cho việc lưu trữ avatar
- **Real-time Avatar Preview**: Hiển thị preview ảnh ngay khi chọn file
- **Enhanced File Validation**: Kiểm tra loại file và kích thước tốt hơn (JPEG, PNG, JPG, GIF - tối đa 2MB)
- **Improved UX**: Thông báo trạng thái loading và success khi upload avatar

### Cải tiến hệ thống
- **Avatar URL Generation**: Sử dụng `asset()` helper thay vì Storage disk URL cho tính nhất quán
- **Error Handling**: Cải thiện xử lý lỗi cho avatar upload và display
- **File Management**: Tên file unique với timestamp và user ID để tránh xung đột
- **Fallback Avatar**: SVG default avatar khi không có ảnh hoặc lỗi load
- **Debug Support**: Thêm logging và debug routes cho troubleshooting

### Sửa lỗi
- **Avatar Display**: Khắc phục vấn đề không hiển thị avatar sau upload
- **Infinite Loop Prevention**: Ngăn chặn vòng lặp vô tận khi avatar lỗi
- **File Cleanup**: Tự động xóa avatar cũ khi upload ảnh mới
- **Validation Consistency**: Đồng bộ validation giữa frontend và backend

### Thay đổi kỹ thuật
- **Storage Configuration**: Cập nhật filesystem config để sử dụng public disk
- **User Model**: Simplified avatar URL accessor với error handling tốt hơn
- **KidController**: Enhanced file upload handling với unique naming
- **Frontend Components**: Improved avatar change logic với auto-upload
- **Environment Configuration**: Updated APP_URL for local development

### Breaking Changes
- **Storage Migration**: Chuyển từ S3 sang local storage - cần chạy `php artisan storage:link`
- **URL Structure**: Avatar URLs thay đổi từ S3 URLs sang local asset URLs
- **File Organization**: Avatar files được lưu trong `/storage/app/public/avatars/`

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
