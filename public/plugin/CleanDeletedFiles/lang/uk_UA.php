<?php
$strings['plugin_title'] = 'Очистити видалені файли';
$strings['plugin_comment'] = 'Безповоротно видалити файли, позначені як видалені. Увімкніть це в регіоні menu_administrator, а потім отримайте доступ з головної сторінки адміністратора.';
$strings['FileList'] = 'Список файлів, позначених як видалені';
$strings['SizeTotalAllDir'] = 'Загальний розмір (усі каталоги)';
$strings['NoFilesDeleted'] = 'Немає файлів, позначених як видалені';
$strings['FilesDeletedMark'] = 'Файли, позначені як видалені';
$strings['FileDirSize'] = 'Розмір файлів каталогу';
$strings['ConfirmDelete'] = 'Ви впевнені, що хочете видалити файл?';
$strings['ErrorDeleteFile'] = 'Виникла помилка під час видалення файлу';
$strings['ErrorEmptyPath'] = 'Виникла проблема з видаленням файлу, шлях не може бути порожнім';
$strings['DeleteSelectedFiles'] = 'Видалити вибрані файли';
$strings['ConfirmDeleteFiles'] = 'Ви впевнені, що хочете видалити всі вибрані файли?';
$strings['DeletedSuccess'] = 'Файл успішно видалено';
$strings['path_dir'] = 'Каталог';
$strings['size'] = 'Розмір';
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
$strings['ErrorNotCleanablePath'] = 'Файл не є чистим сиротинцем або застарілим видаленим файлом.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Файли, які можна очистити';
$strings['NoCleanableFiles'] = 'У цьому кореневому сховищі немає файлів, які можна очистити';
$strings['Chamilo2ResourceStorage'] = 'Файли ресурсів';
$strings['Chamilo2ResourceStorageHelp'] = 'Файли в var/upload/resource відображаються лише тоді, коли на них не посилається resource_file. Видаляйте документи через інструмент «Документи» або API, а не через цей плагін.';
$strings['Chamilo2AssetStorage'] = 'Файли активів';
$strings['Chamilo2AssetStorageHelp'] = 'Файли в var/upload/assets відображаються лише тоді, коли на них не посилається asset. Посилання на активи завжди захищені.';
$strings['LegacyCourseFiles'] = 'Застарілі файли курсів';
$strings['LegacyUploadFiles'] = 'Застарілі завантажені файли';
$strings['LegacyPublicCourseFiles'] = 'Застарілі публічні файли курсів';
$strings['LegacyPublicUploadFiles'] = 'Застарілі публічні завантажені файли';
$strings['LegacyDeletedStorageHelp'] = 'Застарілі кореневі каталоги скануються лише для файлів, чия базова назва містить маркер DELETED.';
$strings['OrphanResourceFile'] = 'Сирітський файл ресурсу';
$strings['OrphanAssetFile'] = 'Сирітський файл активу';
$strings['LegacyDeletedFile'] = 'Застарий файл DELETED';
$strings['OrphanResourceFiles'] = 'Сирітські файли ресурсів';
$strings['OrphanAssetFiles'] = 'Сирітські файли активів';
$strings['LegacyDeletedFiles'] = 'Застарілі файли DELETED';
$strings['Reason'] = 'Причина';
$strings['ReasonOrphanResource'] = 'Фізичний файл існує в сховищі ресурсів, але жоден рядок resource_file на нього не вказує.';
$strings['ReasonOrphanAsset'] = 'Фізичний файл існує в сховищі активів, але жоден рядок asset на нього не вказує.';
$strings['ReasonLegacyDeletedMarker'] = 'Базова назва застарілого файлу містить маркер DELETED.';

$strings['StorageNoticeShort'] = 'Завантажені файли відстежуються через метадані resource_file та asset. Цей плагін відображає лише фізичні файли в var/upload/resource та var/upload/assets, на які більше немає посилань.';
$strings['SafeNoticeShort'] = 'Файл із дійсним посиланням у базі даних захищений. Документи та файли слід видаляти через їхні звичайні інструменти.';
$strings['CheckedLocations'] = 'Перевірені розташування';
$strings['DetectionRule'] = 'Правило виявлення';
$strings['NoCleanableFilesFound'] = 'Не знайдено файлів, які можна очистити';
$strings['NoCleanableFilesFoundHelp'] = 'Це очікуваний результат, коли сховище узгоджене. Перевірені розташування наведено нижче для прозорості.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Запустити обмежене сканування';
$strings['ScanNotRun'] = 'Сканування не розпочато';
$strings['ScanNotRunHelp'] = 'Сторінка не сканує сховище автоматично, оскільки великі папки var/upload можуть працювати повільно. Натисніть «Запустити обмежене сканування», щоб перевірити локальні сирітські файли.';
$strings['ScanLimitedWarning'] = 'Сканування було зупинено достроково, щоб сторінка залишалася швидкою. Запустіть його пізніше або перевірте сховище через командний рядок, якщо потрібно.';

$strings['PathFilter'] = 'Фільтр шляху';
$strings['PathFilterHelp'] = 'Необов’язково. Використовуйте для тестування або перегляду певної папки, наприклад clean-deleted-files-test. Він працює лише з відносними шляхами.';
$strings['ActivePathFilter'] = 'Активний фільтр шляху: %s';
