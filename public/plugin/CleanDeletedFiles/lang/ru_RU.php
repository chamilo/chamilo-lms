<?php
$strings['plugin_title'] = 'Очистить удалённые файлы';
$strings['plugin_comment'] = 'Безвозвратно удалить файлы, помеченные как удалённые. Включите в разделе меню_администратор, затем перейдите к нему со страницы главного администратора.';
$strings['FileList'] = 'Список файлов, помеченных как удалённые';
$strings['SizeTotalAllDir'] = 'Общий размер (все каталоги)';
$strings['NoFilesDeleted'] = 'Нет файлов, помеченных как удалённые';
$strings['FilesDeletedMark'] = 'Файлы, помеченные как удалённые';
$strings['FileDirSize'] = 'Размер файлов каталога';
$strings['ConfirmDelete'] = 'Вы уверены, что хотите удалить файл?';
$strings['ErrorDeleteFile'] = 'Произошла ошибка при удалении файла';
$strings['ErrorEmptyPath'] = 'Произошла проблема при удалении файла, путь не может быть пустым';
$strings['DeleteSelectedFiles'] = 'Удалить выбранные файлы';
$strings['ConfirmDeleteFiles'] = 'Вы уверены, что хотите удалить все выбранные файлы?';
$strings['DeletedSuccess'] = 'Удаление файла прошло успешно';
$strings['path_dir'] = 'Каталог';
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
$strings['ErrorNotCleanablePath'] = 'Файл не является удаляемым сиротским или устаревшим удалённым файлом.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Удаляемые физические файлы';
$strings['NoCleanableFiles'] = 'В этом корневом каталоге хранилища нет удаляемых физических файлов';
$strings['Chamilo2ResourceStorage'] = 'Файлы ресурсов';
$strings['Chamilo2ResourceStorageHelp'] = 'Файлы в var/upload/resource отображаются только если на них нет ссылки в resource_file. Удаляйте документы через инструмент «Документы» или API, а не через этот плагин.';
$strings['Chamilo2AssetStorage'] = 'Файлы ассетов';
$strings['Chamilo2AssetStorageHelp'] = 'Файлы в var/upload/assets отображаются только если на них нет ссылки в таблице asset. Ссылки на ассеты всегда защищены.';
$strings['LegacyCourseFiles'] = 'Устаревшие файлы курсов';
$strings['LegacyUploadFiles'] = 'Устаревшие загруженные файлы';
$strings['LegacyPublicCourseFiles'] = 'Устаревшие общедоступные файлы курсов';
$strings['LegacyPublicUploadFiles'] = 'Устаревшие общедоступные загруженные файлы';
$strings['LegacyDeletedStorageHelp'] = 'Устаревшие корневые каталоги сканируются только на наличие файлов, базовое имя которых содержит маркер DELETED.';
$strings['OrphanResourceFile'] = 'Сиротский файл ресурса';
$strings['OrphanAssetFile'] = 'Сиротский файл ассета';
$strings['LegacyDeletedFile'] = 'Устаревший файл DELETED';
$strings['OrphanResourceFiles'] = 'Сиротские файлы ресурсов';
$strings['OrphanAssetFiles'] = 'Сиротские файлы ассетов';
$strings['LegacyDeletedFiles'] = 'Устаревшие файлы DELETED';
$strings['Reason'] = 'Причина';
$strings['ReasonOrphanResource'] = 'Физический файл существует в хранилище ресурсов, но на него не указывает ни одна запись resource_file.';
$strings['ReasonOrphanAsset'] = 'Физический файл существует в хранилище ассетов, но на него не указывает ни одна запись asset.';
$strings['ReasonLegacyDeletedMarker'] = 'Базовое имя устаревшего файла содержит маркер DELETED.';

$strings['StorageNoticeShort'] = 'Загруженные файлы отслеживаются через метаданные resource_file и asset. Этот плагин отображает только физические файлы в var/upload/resource и var/upload/assets, на которые больше нет ссылок.';
$strings['SafeNoticeShort'] = 'Файл с действительной ссылкой в базе данных защищён. Документы и файлы следует удалять через соответствующие инструменты.';
$strings['CheckedLocations'] = 'Проверенные расположения';
$strings['DetectionRule'] = 'Правило обнаружения';
$strings['NoCleanableFilesFound'] = 'Удаляемых физических файлов не найдено';
$strings['NoCleanableFilesFoundHelp'] = 'Это ожидаемый результат при согласованности хранилища. Проверенные расположения показаны ниже для прозрачности.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Запустить ограниченное сканирование';
$strings['ScanNotRun'] = 'Сканирование не запущено';
$strings['ScanNotRunHelp'] = 'Страница не сканирует хранилище автоматически, так как большие папки var/upload могут работать медленно. Нажмите «Запустить ограниченное сканирование» для проверки локальных сиротских файлов.';
$strings['ScanLimitedWarning'] = 'Сканирование было остановлено раньше, чтобы сохранить отзывчивость страницы. Запустите его позже или проверьте хранилище из командной строки при необходимости.';

$strings['PathFilter'] = 'Фильтр пути';
$strings['PathFilterHelp'] = 'Необязательно. Используйте для тестирования или просмотра конкретной папки, например clean-deleted-files-test. Сопоставляет только относительные пути.';
$strings['ActivePathFilter'] = 'Активный фильтр пути: %s';
