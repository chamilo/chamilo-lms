<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Dịch vụ từ xa của người dùng';
$strings['plugin_comment'] = 'Thêm các liên kết xác định người dùng nhắm đến iframe cụ thể của trang web vào thanh menu.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'Chuỗi ký tự bí mật, dùng để tạo tham số URL <em>hash</em>. Càng dài càng tốt.
<br/>Dịch vụ người dùng từ xa có thể kiểm tra tính xác thực của URL được tạo bằng biểu thức PHP sau:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Trong đó
<br/><code>$salt</code> là giá trị nhập vào này,
<br/><code>$userId</code> là số của người dùng được tham chiếu bởi giá trị tham số URL <em>username</em> và
<br/><code>$hash</code> chứa giá trị tham số URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'ẩn liên kết khỏi menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Thêm dịch vụ vào thanh menu';
$strings['DeleteServices'] = 'Xóa dịch vụ khỏi thanh menu';
$strings['ServicesToDelete'] = 'Dịch vụ cần xóa khỏi thanh menu';
$strings['ServiceTitle'] = 'Tiêu đề dịch vụ';
$strings['ServiceURL'] = 'Vị trí trang web dịch vụ (URL)';
$strings['RedirectAccessURL'] = 'URL dùng trong Chamilo để chuyển hướng người dùng đến dịch vụ (URL)';
$strings['Actions'] = 'Hành động';
$strings['AddRemoteService'] = 'Thêm dịch vụ từ xa';
$strings['CurrentServices'] = 'Dịch vụ hiện tại';
$strings['DeleteService'] = 'Xóa dịch vụ';
$strings['InvalidSecurityToken'] = 'Mã thông báo bảo mật không hợp lệ.';
$strings['InvalidServiceTitle'] = 'Vui lòng nhập tiêu đề dịch vụ.';
$strings['InvalidServiceUrl'] = 'Vui lòng nhập URL HTTP hoặc HTTPS hợp lệ.';
$strings['MissingSaltWarning'] = 'Hãy cấu hình salt trước khi hiển thị liên kết dịch vụ từ xa. Salt là bắt buộc để tạo URL người dùng đã ký.';
$strings['NoServicesConfigured'] = 'Chưa có dịch vụ từ xa nào được cấu hình.';
$strings['OpenInIframe'] = 'Mở trong iframe';
$strings['OpenRedirect'] = 'Mở URL chuyển hướng';
$strings['RemoteServicesDescription'] = 'Quản lý các dịch vụ bên ngoài nhận URL người dùng đã ký từ Chamilo. Chỉ người dùng đã xác thực mới có thể mở các liên kết này.';
$strings['ServiceCreated'] = 'Dịch vụ từ xa đã được tạo.';
$strings['ServiceDeleted'] = 'Dịch vụ từ xa đã được xóa.';
$strings['ServiceManagement'] = 'Quản lý dịch vụ từ xa';
$strings['ServiceUnavailable'] = 'Dịch vụ từ xa này không khả dụng. Hãy kiểm tra xem plugin đã được kích hoạt, salt đã được cấu hình và URL có hợp lệ hay không.';
