<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'მომხმარებლის შორეული სერვისები';
$strings['plugin_comment'] = 'საიტის სპეციფიკურ iframe-ზე მიმართულ მომხმარებელის იდენტიფიკაციის ბმულებს მენიუს ზოლში დამატებს.';

$strings['salt'] = 'მარილი';
$strings['salt_help'] = 'საიდუმლო სიმბოლოების სტრიქონი, რომელსაც იყენებენ <em>hash</em> URL პარამეტრის გენერაციისთვის. რაც უფრო გრძელი, მით უკეთესი.
<br/>შორეული მომხმარებლის სერვისები შეძლებენ გენერირებული URL-ის ავთენტურობის შემოწმებას შემდეგი PHP გამოთქმით :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>სადაც
<br/><code>$salt</code> არის ეს შეყვანის მნიშვნელობა,
<br/><code>$userId</code> არის მომხმარებლის ნომერი, რომელზეც მიუთითებს <em>username</em> URL პარამეტრის მნიშვნელობა და
<br/><code>$hash</code> შეიცავს <em>hash</em> URL პარამეტრის მნიშვნელობას.';
$strings['hide_link_from_navigation_menu'] = 'ბმულების მენიუდან დამალვა';

// Please keep alphabetically sorted
$strings['CreateService'] = 'სერვისის მენიუს ზოლში დამატება';
$strings['DeleteServices'] = 'სერვისების მენიუს ზოლიდან ამოღება';
$strings['ServicesToDelete'] = 'მენიუს ზოლიდან ამოღებული სერვისები';
$strings['ServiceTitle'] = 'სერვისის სათაური';
$strings['ServiceURL'] = 'სერვისის ვებ-საიტის მდებარეობა (URL)';
$strings['RedirectAccessURL'] = 'Chamilo-ში მომხმარებლის სერვისზე გადამისამართებისთვის გამოსაყენებელი URL (URL)';
$strings['Actions'] = 'მოქმედებები';
$strings['AddRemoteService'] = 'დისტანციური სერვისის დამატება';
$strings['CurrentServices'] = 'მიმდინარე სერვისები';
$strings['DeleteService'] = 'სერვისის წაშლა';
$strings['InvalidSecurityToken'] = 'უმართებლო უსაფრთხოების ტოკენი.';
$strings['InvalidServiceTitle'] = 'გთხოვთ შეიყვანოთ სერვისის სათაური.';
$strings['InvalidServiceUrl'] = 'გთხოვთ შეიყვანოთ სწორი HTTP ან HTTPS URL.';
$strings['MissingSaltWarning'] = 'კონფიგურირეთ სოლტი დისტანციური სერვისის ბმულების გამოქვეყნებამდე. სოლტი საჭიროა ხელმოწერილი მომხმარებლის URL-ების გენერირებისთვის.';
$strings['NoServicesConfigured'] = 'დისტანციური სერვისები ჯერ არ არის კონფიგურირებული.';
$strings['OpenInIframe'] = 'ჩატვირთვა iframe-ში';
$strings['OpenRedirect'] = 'გადამისამართების URL-ის გახსნა';
$strings['RemoteServicesDescription'] = 'მართეთ გარე სერვისები, რომლებიც იღებენ ხელმოწერილ მომხმარებლის URL-ებს Chamilo-დან. მხოლოდ ავტორიზებულ მომხმარებლებს შეუძლიათ გახსნან ეს ბმულები.';
$strings['ServiceCreated'] = 'დისტანციური სერვისი შეიქმნა.';
$strings['ServiceDeleted'] = 'დისტანციური სერვისი წაიშალა.';
$strings['ServiceManagement'] = 'დისტანციური სერვისების მართვა';
$strings['ServiceUnavailable'] = 'ეს დისტანციური სერვისი მიუწვდომელია. შეამოწმეთ რომ დამატება ჩართულია, სოლტი კონფიგურირებულია და URL სწორია.';
