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
