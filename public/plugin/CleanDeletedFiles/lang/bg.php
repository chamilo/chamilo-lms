<?php
$strings['plugin_title'] = 'Изчистване на изтритите файлове';
$strings['plugin_comment'] = 'Постоянно изтриване на файловете, маркирани като изтрити. Активирайте я в областта menu_administrator, след което я достъпете от главната страница на администратора.';
$strings['FileList'] = 'Списък на файловете, маркирани като изтрити';
$strings['SizeTotalAllDir'] = 'Общ размер (всички директории)';
$strings['NoFilesDeleted'] = 'Няма файлове, маркирани като изтрити';
$strings['FilesDeletedMark'] = 'Файлове, маркирани като изтрити';
$strings['FileDirSize'] = 'Размер на файловете в директорията';
$strings['ConfirmDelete'] = 'Сигурни ли сте, че искате да изтриете файла?';
$strings['ErrorDeleteFile'] = 'Грешка при изтриването на файла';
$strings['ErrorEmptyPath'] = 'Проблем при изтриването на файла, пътят не може да бъде празен';
$strings['DeleteSelectedFiles'] = 'Изтрий избраните файлове';
$strings['ConfirmDeleteFiles'] = 'Сигурни ли сте, че искате да изтриете всички избрани файлове?';
$strings['DeletedSuccess'] = 'Изтриването на файла беше успешно';
$strings['path_dir'] = 'Директория';
$strings['size'] = 'Размер';
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
$strings['ErrorNotCleanablePath'] = 'Файлът не е изчистим осиротял или остарял изтрит файл.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Изчистими физически файлове';
$strings['NoCleanableFiles'] = 'Няма изчистими физически файлове в това хранилище';
$strings['Chamilo2ResourceStorage'] = 'Файлове на ресурси';
$strings['Chamilo2ResourceStorageHelp'] = 'Файловете в var/upload/resource се показват само когато не са посочени от resource_file. Изтривайте документи от инструмента Документи или чрез API, а не от този плъгин.';
$strings['Chamilo2AssetStorage'] = 'Файлове на активи';
$strings['Chamilo2AssetStorageHelp'] = 'Файловете в var/upload/assets се показват само когато не са посочени от asset. Посочените активи са винаги защитени.';
$strings['LegacyCourseFiles'] = 'Остарели файлове на курсове';
$strings['LegacyUploadFiles'] = 'Остарели качени файлове';
$strings['LegacyPublicCourseFiles'] = 'Остарели публични файлове на курсове';
$strings['LegacyPublicUploadFiles'] = 'Остарели публични качени файлове';
$strings['LegacyDeletedStorageHelp'] = 'Остарелите коренни директории се сканират само за файлове, чието име съдържа маркера DELETED.';
$strings['OrphanResourceFile'] = 'Осиротял файл на ресурс';
$strings['OrphanAssetFile'] = 'Осиротял файл на актив';
$strings['LegacyDeletedFile'] = 'Остарял DELETED файл';
$strings['OrphanResourceFiles'] = 'Осиротели файлове на ресурси';
$strings['OrphanAssetFiles'] = 'Осиротели файлове на активи';
$strings['LegacyDeletedFiles'] = 'Остарели DELETED файлове';
$strings['Reason'] = 'Причина';
$strings['ReasonOrphanResource'] = 'Физическият файл съществува в хранилището за ресурси, но няма ред resource_file, който да сочи към него.';
$strings['ReasonOrphanAsset'] = 'Физическият файл съществува в хранилището за активи, но няма ред asset, който да сочи към него.';
$strings['ReasonLegacyDeletedMarker'] = 'Името на остарелия файл съдържа маркера DELETED.';

$strings['StorageNoticeShort'] = 'Качени файлове се проследяват чрез resource_file и asset метаданни. Този плъгин показва само физическите файлове под var/upload/resource и var/upload/assets, които вече не се използват.';
$strings['SafeNoticeShort'] = 'Файл с валидна препратка в базата данни е защитен. Документите и файловете трябва да се изтриват чрез съответните им инструменти.';
$strings['CheckedLocations'] = 'Проверени местоположения';
$strings['DetectionRule'] = 'Правило за откриване';
$strings['NoCleanableFilesFound'] = 'Не са намерени изчистими физически файлове';
$strings['NoCleanableFilesFoundHelp'] = 'Това е очакваният резултат, когато хранилището е последователно. Проверените местоположения са показани по-долу за прозрачност.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Стартиране на ограничено сканиране';
$strings['ScanNotRun'] = 'Сканирането не е стартирано';
$strings['ScanNotRunHelp'] = 'Страницата не сканира хранилището автоматично, тъй като големите var/upload папки могат да бъдат бавни. Кликнете върху „Стартиране на ограничено сканиране“, за да проверите локалните осиротели файлове.';
$strings['ScanLimitedWarning'] = 'Сканирането беше спряно преждевременно, за да се запази отзивчивостта на страницата. Стартирайте го отново по-късно или проверете хранилището от командния ред, ако е необходимо.';

$strings['PathFilter'] = 'Филтър за пътя';
$strings['PathFilterHelp'] = 'По избор. Използвайте го, за да тествате или прегледате конкретна папка, например clean-deleted-files-test. Съвпада само с относителни пътища.';
$strings['ActivePathFilter'] = 'Активен филтър за пътя: %s';
