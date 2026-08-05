<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'خدمات راه دور کاربر';
$strings['plugin_comment'] = 'اضافه کردن لینک‌های شناسایی کاربر هدفمند iframe خاص سایت به نوار منو.';

$strings['salt'] = 'نمک';
$strings['salt_help'] = 'رشته کاراکتر مخفی، که برای تولید پارامتر URL <em>hash</em> استفاده می‌شود. هرچه طولانی‌تر، بهتر.
<br/>خدمات کاربر راه دور می‌توانند اصالت URL تولید شده را با عبارت PHP زیر بررسی کنند:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>که در آن
<br/><code>$salt</code> مقدار ورودی این فیلد است،
<br/><code>$userId</code> شماره کاربری است که توسط مقدار پارامتر URL <em>username</em> ارجاع داده شده و
<br/><code>$hash</code> حاوی مقدار پارامتر URL <em>hash</em> است.';
$strings['hide_link_from_navigation_menu'] = 'مخفی کردن لینک‌ها از منو';

// Please keep alphabetically sorted
$strings['CreateService'] = 'اضافه کردن سرویس به نوار منو';
$strings['DeleteServices'] = 'حذف سرویس‌ها از نوار منو';
$strings['ServicesToDelete'] = 'سرویس‌هایی که از نوار منو حذف شوند';
$strings['ServiceTitle'] = 'عنوان سرویس';
$strings['ServiceURL'] = 'آدرس وب‌سایت سرویس (URL)';
$strings['RedirectAccessURL'] = 'URL برای استفاده در Chamilo جهت هدایت کاربر به سرویس (URL)';
$strings['Actions'] = 'عملیات';
$strings['AddRemoteService'] = 'افزودن سرویس خارجی';
$strings['CurrentServices'] = 'سرویس‌های فعلی';
$strings['DeleteService'] = 'حذف سرویس';
$strings['InvalidSecurityToken'] = 'توکن امنیتی نامعتبر است.';
$strings['InvalidServiceTitle'] = 'لطفاً عنوان سرویس را وارد کنید.';
$strings['InvalidServiceUrl'] = 'لطفاً یک آدرس HTTP یا HTTPS معتبر وارد کنید.';
$strings['MissingSaltWarning'] = 'قبل از نمایش لینک‌های سرویس خارجی، یک salt پیکربندی کنید. این salt برای تولید URLهای امضا شده کاربران ضروری است.';
$strings['NoServicesConfigured'] = 'هنوز هیچ سرویس خارجی پیکربندی نشده است.';
$strings['OpenInIframe'] = 'باز کردن در iframe';
$strings['OpenRedirect'] = 'باز کردن آدرس تغییر مسیر';
$strings['RemoteServicesDescription'] = 'مدیریت سرویس‌های خارجی که URLهای امضا شده کاربران را از چامیلو دریافت می‌کنند. فقط کاربران احراز هویت شده می‌توانند این لینک‌ها را باز کنند.';
$strings['ServiceCreated'] = 'سرویس خارجی ایجاد شد.';
$strings['ServiceDeleted'] = 'سرویس خارجی حذف شد.';
$strings['ServiceManagement'] = 'مدیریت سرویس خارجی';
$strings['ServiceUnavailable'] = 'این سرویس خارجی در دسترس نیست. بررسی کنید که افزونه فعال باشد، salt پیکربندی شده باشد و آدرس معتبر باشد.';
