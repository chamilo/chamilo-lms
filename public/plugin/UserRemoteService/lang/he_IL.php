<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'שירותי משתמש מרוחקים';
$strings['plugin_comment'] = 'מוסיף קישורי iframe ספציפיים לאתר לזיהוי משתמש לסרגל התפריט.';

$strings['salt'] = 'מלח';
$strings['salt_help'] = 'מחרוזת תווים סודית, המשמשת ליצירת פרמטר ה-<em>hash</em> ב-URL. הכי ארוכה, הכי טובה.
<br/>שירותי משתמש מרוחקים יכולים לבדוק את תקפות ה-URL שנוצר באמצעות הביטוי PHP הבא:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>כאשר
<br/><code>$salt</code> הוא ערך הקלט הזה,
<br/><code>$userId</code> הוא מספר המשתמש שמפנה אליו ערך פרמטר ה-<em>username</em> ב-URL ו
<br/><code>$hash</code> מכיל את ערך פרמטר ה-<em>hash</em> ב-URL.';
$strings['hide_link_from_navigation_menu'] = 'הסתר קישורים מהתפריט';

// Please keep alphabetically sorted
$strings['CreateService'] = 'הוסף שירות לסרגל התפריט';
$strings['DeleteServices'] = 'הסר שירותים מסרגל התפריט';
$strings['ServicesToDelete'] = 'שירותים להסרה מסרגל התפריט';
$strings['ServiceTitle'] = 'כותרת השירות';
$strings['ServiceURL'] = 'מיקום אתר השירות באינטרנט (URL)';
$strings['RedirectAccessURL'] = 'URL לשימוש ב-Chamilo להפניה של המשתמש לשירות (URL)';
$strings['Actions'] = 'פעולות';
$strings['AddRemoteService'] = 'הוסף שירות מרוחק';
$strings['CurrentServices'] = 'שירותים נוכחיים';
$strings['DeleteService'] = 'מחק שירות';
$strings['InvalidSecurityToken'] = 'אסימון אבטחה לא תקין.';
$strings['InvalidServiceTitle'] = 'נא להזין כותרת שירות.';
$strings['InvalidServiceUrl'] = 'נא להזין כתובת URL תקינה מסוג HTTP או HTTPS.';
$strings['MissingSaltWarning'] = 'יש להגדיר salt לפני חשיפת קישורים לשירותים מרוחקים. ה-salt נדרש ליצירת כתובות URL חתומות למשתמשים.';
$strings['NoServicesConfigured'] = 'טרם הוגדרו שירותים מרוחקים.';
$strings['OpenInIframe'] = 'פתח בתוך iframe';
$strings['OpenRedirect'] = 'פתח כתובת URL להפניה';
$strings['RemoteServicesDescription'] = 'נהל שירותים חיצוניים שמקבלים כתובות URL חתומות למשתמשים מ-Chamilo. רק משתמשים מאומתים יכולים לפתוח קישורים אלה.';
$strings['ServiceCreated'] = 'השירות המרוחק נוצר.';
$strings['ServiceDeleted'] = 'השירות המרוחק נמחק.';
$strings['ServiceManagement'] = 'ניהול שירותים מרוחקים';
$strings['ServiceUnavailable'] = 'שירות מרוחק זה אינו זמין. בדוק שהתוסף מופעל, שה-salt מוגדר וכתובת ה-URL תקינה.';
