<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'خدمات المستخدم عن بعد';
$strings['plugin_comment'] = 'يضيف روابط محددة للموقع تستهدف إطار iframe لتحديد هوية المستخدم إلى شريط القوائم.';

$strings['salt'] = 'الملح';
$strings['salt_help'] = 'سلسلة حرفية سرية، تُستخدم لتوليد معلمة URL <em>hash</em>. كلما طالت، كان أفضل.
<br/>يمكن لخدمات المستخدم عن بعد التحقق من صحة الـURL المُولد باستخدام التعبير PHP التالي:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>حيث
<br/><code>$salt</code> هي قيمة الإدخال هذه،
<br/><code>$userId</code> هو رقم المستخدم المشار إليه بقيمة معلمة URL <em>username</em> و
<br/><code>$hash</code> تحتوي على قيمة معلمة URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'إخفاء الروابط من القائمة';

// Please keep alphabetically sorted
$strings['CreateService'] = 'إضافة الخدمة إلى شريط القوائم';
$strings['DeleteServices'] = 'إزالة الخدمات من شريط القوائم';
$strings['ServicesToDelete'] = 'الخدمات المراد إزالتها من شريط القوائم';
$strings['ServiceTitle'] = 'عنوان الخدمة';
$strings['ServiceURL'] = 'موقع موقع الخدمة على الويب (URL)';
$strings['RedirectAccessURL'] = 'الـURL المستخدم في Chamilo لتوجيه المستخدم إلى الخدمة (URL)';
$strings['Actions'] = 'الإجراءات';
$strings['AddRemoteService'] = 'إضافة خدمة عن بعد';
$strings['CurrentServices'] = 'الخدمات الحالية';
$strings['DeleteService'] = 'حذف الخدمة';
$strings['InvalidSecurityToken'] = 'رمز الأمان غير صالح.';
$strings['InvalidServiceTitle'] = 'يرجى إدخال عنوان الخدمة.';
$strings['InvalidServiceUrl'] = 'يرجى إدخال عنوان URL صالح لـ HTTP أو HTTPS.';
$strings['MissingSaltWarning'] = 'يُرجى تهيئة الملح (salt) قبل تعريض روابط الخدمات عن بعد. يلزم الملح لإنشاء عناوين URL موقعة للمستخدمين.';
$strings['NoServicesConfigured'] = 'لم يتم تكوين أي خدمات عن بعد بعد.';
$strings['OpenInIframe'] = 'فتح في إطار iframe';
$strings['OpenRedirect'] = 'فتح عنوان URL لإعادة التوجيه';
$strings['RemoteServicesDescription'] = 'إدارة الخدمات الخارجية التي تتلقى عناوين URL موقعة للمستخدمين من Chamilo. يمكن للمستخدمين المصادق عليهم فقط فتح هذه الروابط.';
$strings['ServiceCreated'] = 'تم إنشاء الخدمة عن بعد.';
$strings['ServiceDeleted'] = 'تم حذف الخدمة عن بعد.';
$strings['ServiceManagement'] = 'إدارة الخدمات عن بعد';
$strings['ServiceUnavailable'] = 'هذه الخدمة عن بعد غير متاحة. تأكد من تمكين الإضافة وتهيئة الملح (salt) وصحة عنوان URL.';
