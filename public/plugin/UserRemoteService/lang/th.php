<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'บริการผู้ใช้ระยะไกล';
$strings['plugin_comment'] = 'เพิ่มลิงก์ระบุตัวตนผู้ใช้ที่กำหนดเป้าหมาย iframe เฉพาะไซต์ลงในแถบเมนู';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'สตริงตัวอักษรลับ ใช้ในการสร้างพารามิเตอร์ URL <em>hash</em> ยิ่งยาวยิ่งดี
<br/>บริการผู้ใช้ระยะไกลสามารถตรวจสอบความถูกต้องของ URL ที่สร้างขึ้นด้วยนิพจน์ PHP ต่อไปนี้:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>โดย
<br/><code>$salt</code> คือค่าที่ป้อนนี้
<br/><code>$userId</code> คือหมายเลขของผู้ใช้ที่อ้างอิงจากค่าพารามิเตอร์ URL <em>username</em> และ
<br/><code>$hash</code> ประกอบด้วยค่าพารามิเตอร์ URL <em>hash</em>';
$strings['hide_link_from_navigation_menu'] = 'ซ่อนลิงก์จากเมนู';

// Please keep alphabetically sorted
$strings['CreateService'] = 'เพิ่มบริการลงในแถบเมนู';
$strings['DeleteServices'] = 'ลบบริการออกจากแถบเมนู';
$strings['ServicesToDelete'] = 'บริการที่จะลบออกจากแถบเมนู';
$strings['ServiceTitle'] = 'ชื่อบริการ';
$strings['ServiceURL'] = 'ที่ตั้งเว็บไซต์บริการ (URL)';
$strings['RedirectAccessURL'] = 'URL ที่ใช้ใน Chamilo เพื่อเปลี่ยนเส้นทางผู้ใช้ไปยังบริการ (URL)';
