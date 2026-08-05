<?php
$strings['plugin_title'] = 'تنظيف الملفات المحذوفة';
$strings['plugin_comment'] = 'حذف الملفات المُشار إليها كمحذوفة نهائيًا. فعّلها في منطقة menu_administrator ثم الوصول إليها من الصفحة الرئيسية للإدارة.';
$strings['FileList'] = 'قائمة الملفات المُشار إليها كمحذوفة';
$strings['SizeTotalAllDir'] = 'الحجم الإجمالي (جميع المجلدات)';
$strings['NoFilesDeleted'] = 'لا توجد ملفات مُشار إليها كمحذوفة';
$strings['FilesDeletedMark'] = 'الملفات المُشار إليها كمحذوفة';
$strings['FileDirSize'] = 'حجم ملفات المجلد';
$strings['ConfirmDelete'] = 'هل أنت متأكد من رغبتك في حذف الملف؟';
$strings['ErrorDeleteFile'] = 'حدث خطأ أثناء حذف الملف';
$strings['ErrorEmptyPath'] = 'حدث مشكلة أثناء حذف الملف، لا يمكن أن يكون المسار فارغًا';
$strings['DeleteSelectedFiles'] = 'حذف الملفات المحددة';
$strings['ConfirmDeleteFiles'] = 'هل أنت متأكد من رغبتك في حذف جميع الملفات المحددة؟';
$strings['DeletedSuccess'] = 'تم حذف الملف بنجاح';
$strings['path_dir'] = 'المجلد';
$strings['size'] = 'الحجم';
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
$strings['ErrorNotCleanablePath'] = 'الملف ليس ملفًا يتيمًا قابلاً للتنظيف أو ملفًا محذوفًا قديمًا.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'الملفات الفيزيائية القابلة للتنظيف';
$strings['NoCleanableFiles'] = 'لا توجد ملفات فيزيائية قابلة للتنظيف في جذر التخزين هذا';
$strings['Chamilo2ResourceStorage'] = 'ملفات الموارد';
$strings['Chamilo2ResourceStorageHelp'] = 'تُسرد الملفات الموجودة تحت var/upload/resource فقط عندما لا تكون مشار إليها بواسطة resource_file. احذف المستندات من أداة المستندات أو من API، وليس من هذه الإضافة.';
$strings['Chamilo2AssetStorage'] = 'ملفات الأصول';
$strings['Chamilo2AssetStorageHelp'] = 'تُسرد الملفات الموجودة تحت var/upload/assets فقط عندما لا تكون مشار إليها بواسطة asset. الأصول المشار إليها محمية دائمًا.';
$strings['LegacyCourseFiles'] = 'ملفات المقررات القديمة';
$strings['LegacyUploadFiles'] = 'ملفات الرفع القديمة';
$strings['LegacyPublicCourseFiles'] = 'ملفات المقررات العامة القديمة';
$strings['LegacyPublicUploadFiles'] = 'ملفات الرفع العامة القديمة';
$strings['LegacyDeletedStorageHelp'] = 'يتم فحص الجذور القديمة فقط للملفات التي يحتوي اسمها الأساسي على علامة DELETED.';
$strings['OrphanResourceFile'] = 'ملف مورد يتيم';
$strings['OrphanAssetFile'] = 'ملف أصل يتيم';
$strings['LegacyDeletedFile'] = 'ملف DELETED قديم';
$strings['OrphanResourceFiles'] = 'ملفات موارد يتيمة';
$strings['OrphanAssetFiles'] = 'ملفات أصول يتيمة';
$strings['LegacyDeletedFiles'] = 'ملفات DELETED قديمة';
$strings['Reason'] = 'السبب';
$strings['ReasonOrphanResource'] = 'الملف الفيزيائي موجود في تخزين الموارد ولكن لا يوجد صف resource_file يشير إليه.';
$strings['ReasonOrphanAsset'] = 'الملف الفيزيائي موجود في تخزين الأصول ولكن لا يوجد صف asset يشير إليه.';
$strings['ReasonLegacyDeletedMarker'] = 'يحتوي اسم الملف الأساسي للملف القديم على علامة DELETED.';

$strings['StorageNoticeShort'] = 'يتم تتبع الملفات المرفوعة من خلال بيانات resource_file و asset. تسرد هذه الإضافة فقط الملفات الفيزيائية الموجودة تحت var/upload/resource و var/upload/assets التي لم تعد مشارًا إليها.';
$strings['SafeNoticeShort'] = 'الملف الذي يحتوي على مرجع قاعدة بيانات صالح محمي. يجب حذف المستندات والملفات من خلال أدواتها العادية.';
$strings['CheckedLocations'] = 'المواقع التي تم فحصها';
$strings['DetectionRule'] = 'قاعدة الكشف';
$strings['NoCleanableFilesFound'] = 'لم يتم العثور على ملفات فيزيائية قابلة للتنظيف';
$strings['NoCleanableFilesFoundHelp'] = 'هذه هي النتيجة المتوقعة عندما يكون التخزين متسقًا. يتم عرض المواقع التي تم فحصها أدناه للشفافية.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'تشغيل فحص محدود';
$strings['ScanNotRun'] = 'لم يبدأ الفحص';
$strings['ScanNotRunHelp'] = 'لا تقوم الصفحة بفحص التخزين تلقائيًا لأن مجلدات var/upload الكبيرة قد تكون بطيئة. انقر على تشغيل فحص محدود لفحص الملفات اليتيمة المحلية.';
$strings['ScanLimitedWarning'] = 'تم إيقاف الفحص مبكرًا للحفاظ على استجابة الصفحة. شغله مرة أخرى لاحقًا أو افحص التخزين من سطر الأوامر إذا لزم الأمر.';

$strings['PathFilter'] = 'تصفية المسار';
$strings['PathFilterHelp'] = 'اختياري. استخدم هذا لاختبار أو مراجعة مجلد معين، على سبيل المثال clean-deleted-files-test. يطابق المسارات النسبية فقط.';
$strings['ActivePathFilter'] = 'تصفية المسار النشطة: %s';
