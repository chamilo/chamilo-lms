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
