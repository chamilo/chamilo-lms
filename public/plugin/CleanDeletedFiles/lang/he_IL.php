<?php
$strings['plugin_title'] = 'נקה קבצים שנמחקו';
$strings['plugin_comment'] = 'מחק לצמיתות קבצים שסומנו כנמחקים. הפעל זאת באזור תפריט_מנהל_מערכת ואז גש אליו מדף הניהול הראשי.';
$strings['FileList'] = 'רשימת קבצים שסומנו כנמחקים';
$strings['SizeTotalAllDir'] = 'גודל כולל (כל התיקיות)';
$strings['NoFilesDeleted'] = 'אין קבצים שסומנו כנמחקים';
$strings['FilesDeletedMark'] = 'קבצים שסומנו כנמחקים';
$strings['FileDirSize'] = 'גודל קבצי תיקייה';
$strings['ConfirmDelete'] = 'האם אתה בטוח שברצונך למחוק את הקובץ?';
$strings['ErrorDeleteFile'] = 'אירעה שגיאה במהלך מחיקת הקובץ';
$strings['ErrorEmptyPath'] = 'הייתה בעיה במחיקת הקובץ, הנתיב אינו יכול להיות ריק';
$strings['DeleteSelectedFiles'] = 'מחק קבצים נבחרים';
$strings['ConfirmDeleteFiles'] = 'האם אתה בטוח שברצונך למחוק את כל הקבצים הנבחרים?';
$strings['DeletedSuccess'] = 'מחיקת הקובץ בוצעה בהצלחה';
$strings['path_dir'] = 'תיקייה';
$strings['size'] = 'גודל';
$strings['ScanSummary'] = 'Scan summary';
$strings['Chamilo2StorageNotice'] = 'Chamilo 2 stores files through resources and assets. This plugin only lists files explicitly marked with DELETED.';
$strings['SafeDryRunNotice'] = 'This screen is a safe review step: referenced files are protected and cannot be deleted from here.';
$strings['RelativePath'] = 'Relative path';
$strings['StorageType'] = 'Storage type';
$strings['Status'] = 'Status';
$strings['CanBeDeleted'] = 'Can be deleted';
$strings['ProtectedReferenced'] = 'Protected / referenced';
$strings['ReferencedFileWarning'] = 'Files still referenced by resource_file or asset are protected. Do not delete them manually.';
$strings['DeleteUnavailableReferenced'] = 'This file is still referenced by Chamilo metadata and cannot be deleted here.';
$strings['DeleteSingle'] = 'Delete this file';
$strings['ErrorInvalidToken'] = 'The security token is invalid. Reload the page and try again.';
$strings['ErrorInvalidPath'] = 'The file path is invalid or outside the allowed storage directories.';
$strings['ErrorMissingDeletedMarker'] = 'The file is not marked with the DELETED marker.';
$strings['ErrorReferencedPath'] = 'The file is still referenced by Chamilo metadata and cannot be deleted here.';
$strings['ErrorNotCleanablePath'] = 'הקובץ אינו קובץ יתום ניתן לניקוי או קובץ שנמחק בירושה.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'קבצים פיזיים ניתנים לניקוי';
$strings['NoCleanableFiles'] = 'אין קבצים פיזיים ניתנים לניקוי בשורש האחסון הזה';
$strings['Chamilo2ResourceStorage'] = 'קבצי משאבים';
$strings['Chamilo2ResourceStorageHelp'] = 'קבצים תחת var/upload/resource מוצגים רק כאשר הם אינם מופנים על ידי resource_file. מחק מסמכים בכלי המסמכים או דרך ה-API, לא דרך התוסף הזה.';
$strings['Chamilo2AssetStorage'] = 'קבצי נכסים';
$strings['Chamilo2AssetStorageHelp'] = 'קבצים תחת var/upload/assets מוצגים רק כאשר הם אינם מופנים על ידי asset. נכסים מופנים תמיד מוגנים.';
$strings['LegacyCourseFiles'] = 'קבצי קורס בירושה';
$strings['LegacyUploadFiles'] = 'קבצי העלאה בירושה';
$strings['LegacyPublicCourseFiles'] = 'קבצי קורס ציבוריים בירושה';
$strings['LegacyPublicUploadFiles'] = 'קבצי העלאה ציבוריים בירושה';
$strings['LegacyDeletedStorageHelp'] = 'שורשים בירושה נסרקים רק עבור קבצים ששם הבסיס שלהם מכיל את הסמן DELETED.';
$strings['OrphanResourceFile'] = 'קובץ משאב יתום';
$strings['OrphanAssetFile'] = 'קובץ נכס יתום';
$strings['LegacyDeletedFile'] = 'קובץ DELETED בירושה';
$strings['OrphanResourceFiles'] = 'קבצי משאב יתומים';
$strings['OrphanAssetFiles'] = 'קבצי נכס יתומים';
$strings['LegacyDeletedFiles'] = 'קבצי DELETED בירושה';
$strings['Reason'] = 'סיבה';
$strings['ReasonOrphanResource'] = 'קובץ פיזי קיים באחסון המשאבים אך אין שורת resource_file המפנה אליו.';
$strings['ReasonOrphanAsset'] = 'קובץ פיזי קיים באחסון הנכסים אך אין שורת asset המפנה אליו.';
$strings['ReasonLegacyDeletedMarker'] = 'שם הבסיס של קובץ בירושה מכיל את הסמן DELETED.';

$strings['StorageNoticeShort'] = 'קבצים שהועלו מנוטרים דרך resource_file ומטא-נתונים של asset. תוסף זה מציג רק קבצים פיזיים תחת var/upload/resource ו-var/upload/assets שאינם מופנים יותר.';
$strings['SafeNoticeShort'] = 'קובץ עם הפניה תקינה למסד הנתונים מוגן. מסמכים וקבצים עדיין צריכים להימחק דרך הכלים הרגילים שלהם.';
$strings['CheckedLocations'] = 'מיקומים שנבדקו';
$strings['DetectionRule'] = 'כלל זיהוי';
$strings['NoCleanableFilesFound'] = 'לא נמצאו קבצים פיזיים ניתנים לניקוי';
$strings['NoCleanableFilesFoundHelp'] = 'זהו התוצאה הצפויה כאשר האחסון עקבי. המיקומים שנבדקו מוצגים להלן לשקיפות.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'הפעל סריקה מוגבלת';
$strings['ScanNotRun'] = 'הסריקה לא התחילה';
$strings['ScanNotRunHelp'] = 'הדף אינו סורק את האחסון באופן אוטומטי מכיוון שתיקיות var/upload גדולות עלולות להיות איטיות. לחץ על "הפעל סריקה מוגבלת" כדי לבדוק קבצי יתומים מקומיים.';
$strings['ScanLimitedWarning'] = 'הסריקה נעצרה מוקדם כדי לשמור על תגובתיות הדף. הפעל אותה שוב מאוחר יותר או בדוק את האחסון משורת הפקודה אם נדרש.';

$strings['PathFilter'] = 'מסנן נתיב';
$strings['PathFilterHelp'] = 'אופציונלי. השתמש בזה כדי לבדוק או לסקור תיקייה ספציפית, למשל clean-deleted-files-test. הוא מתאים רק לנתיבים יחסיים.';
$strings['ActivePathFilter'] = 'מסנן נתיב פעיל: %s';
