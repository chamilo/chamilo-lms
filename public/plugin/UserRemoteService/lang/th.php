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
$strings['Actions'] = 'การกระทำ';
$strings['AddRemoteService'] = 'เพิ่มบริการระยะไกล';
$strings['CurrentServices'] = 'บริการปัจจุบัน';
$strings['DeleteService'] = 'ลบบริการ';
$strings['InvalidSecurityToken'] = 'โทเค็นความปลอดภัยไม่ถูกต้อง';
$strings['InvalidServiceTitle'] = 'กรุณากรอกชื่อบริการ';
$strings['InvalidServiceUrl'] = 'กรุณากรอก URL ที่ถูกต้องแบบ HTTP หรือ HTTPS';
$strings['MissingSaltWarning'] = 'กรุณาตั้งค่า salt ก่อนเปิดเผยลิงก์บริการระยะไกล Salt จำเป็นสำหรับการสร้าง URL ผู้ใช้ที่มีลายเซ็น';
$strings['NoServicesConfigured'] = 'ยังไม่มีบริการระยะไกลที่ถูกกำหนดค่า';
$strings['OpenInIframe'] = 'เปิดใน iframe';
$strings['OpenRedirect'] = 'เปิด URL การเปลี่ยนเส้นทาง';
$strings['RemoteServicesDescription'] = 'จัดการบริการภายนอกที่รับ URL ผู้ใช้ที่มีลายเซ็นจาก Chamilo เฉพาะผู้ใช้ที่ผ่านการตรวจสอบสิทธิ์เท่านั้นที่สามารถเปิดลิงก์เหล่านี้ได้';
$strings['ServiceCreated'] = 'สร้างบริการระยะไกลแล้ว';
$strings['ServiceDeleted'] = 'ลบบริการระยะไกลแล้ว';
$strings['ServiceManagement'] = 'การจัดการบริการระยะไกล';
$strings['ServiceUnavailable'] = 'บริการระยะไกลนี้ไม่พร้อมใช้งาน โปรดตรวจสอบว่าปลั๊กอินถูกเปิดใช้งาน, salt ถูกกำหนดค่า และ URL ถูกต้อง';
