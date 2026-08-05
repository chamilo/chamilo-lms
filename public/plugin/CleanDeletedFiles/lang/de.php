<?php
$strings['plugin_title'] = 'Gelöschte Dateien bereinigen';
$strings['plugin_comment'] = 'Als gelöscht markierte Dateien endgültig löschen. Aktivieren Sie es im Bereich menu_administrator und greifen Sie darauf von der Haupt-Admin-Seite zu.';
$strings['FileList'] = 'Liste der als gelöscht markierten Dateien';
$strings['SizeTotalAllDir'] = 'Gesamtgröße (alle Verzeichnisse)';
$strings['NoFilesDeleted'] = 'Es gibt keine als gelöscht markierten Dateien';
$strings['FilesDeletedMark'] = 'Als gelöscht markierte Dateien';
$strings['FileDirSize'] = 'Verzeichnisdateigröße';
$strings['ConfirmDelete'] = 'Sind Sie sicher, dass Sie die Datei löschen möchten?';
$strings['ErrorDeleteFile'] = 'Ein Fehler ist beim Löschen der Datei aufgetreten';
$strings['ErrorEmptyPath'] = 'Beim Löschen der Datei ist ein Problem aufgetreten, der Pfad darf nicht leer sein';
$strings['DeleteSelectedFiles'] = 'Ausgewählte Dateien löschen';
$strings['ConfirmDeleteFiles'] = 'Sind Sie sicher, dass Sie alle ausgewählten Dateien löschen möchten?';
$strings['DeletedSuccess'] = 'Das Löschen der Datei war erfolgreich';
$strings['path_dir'] = 'Verzeichnis';
$strings['size'] = 'Größe';
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
$strings['ErrorNotCleanablePath'] = 'Die Datei ist keine bereinigungsfähige verwaiste oder veraltete gelöschte Datei.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Bereinigungsfähige physische Dateien';
$strings['NoCleanableFiles'] = 'Es gibt keine bereinigungsfähigen physischen Dateien in diesem Speicher-Root';
$strings['Chamilo2ResourceStorage'] = 'Ressourcendateien';
$strings['Chamilo2ResourceStorageHelp'] = 'Dateien unter var/upload/resource werden nur aufgelistet, wenn sie nicht von resource_file referenziert werden. Löschen Sie Dokumente über das Dokumenten-Tool oder die API, nicht über dieses Plugin.';
$strings['Chamilo2AssetStorage'] = 'Asset-Dateien';
$strings['Chamilo2AssetStorageHelp'] = 'Dateien unter var/upload/assets werden nur aufgelistet, wenn sie nicht von asset referenziert werden. Referenzierte Assets sind immer geschützt.';
$strings['LegacyCourseFiles'] = 'Veraltete Kursdateien';
$strings['LegacyUploadFiles'] = 'Veraltete Upload-Dateien';
$strings['LegacyPublicCourseFiles'] = 'Veraltete öffentliche Kursdateien';
$strings['LegacyPublicUploadFiles'] = 'Veraltete öffentliche Upload-Dateien';
$strings['LegacyDeletedStorageHelp'] = 'Veraltete Roots werden nur nach Dateien gescannt, deren Dateiname den DELETED-Marker enthält.';
$strings['OrphanResourceFile'] = 'Verwaiste Ressourcendatei';
$strings['OrphanAssetFile'] = 'Verwaiste Asset-Datei';
$strings['LegacyDeletedFile'] = 'Veraltete DELETED-Datei';
$strings['OrphanResourceFiles'] = 'Verwaiste Ressourcendateien';
$strings['OrphanAssetFiles'] = 'Verwaiste Asset-Dateien';
$strings['LegacyDeletedFiles'] = 'Veraltete DELETED-Dateien';
$strings['Reason'] = 'Grund';
$strings['ReasonOrphanResource'] = 'Physische Datei existiert im Ressourcenspeicher, aber keine resource_file-Zeile verweist darauf.';
$strings['ReasonOrphanAsset'] = 'Physische Datei existiert im Assetspeicher, aber keine asset-Zeile verweist darauf.';
$strings['ReasonLegacyDeletedMarker'] = 'Dateiname der veralteten Datei enthält den DELETED-Marker.';

$strings['StorageNoticeShort'] = 'Hochgeladene Dateien werden über resource_file- und asset-Metadaten verfolgt. Dieses Plugin listet nur physische Dateien unter var/upload/resource und var/upload/assets auf, die nicht mehr referenziert werden.';
$strings['SafeNoticeShort'] = 'Eine Datei mit gültigem Datenbankverweis ist geschützt. Dokumente und Dateien sollten weiterhin über ihre normalen Tools gelöscht werden.';
$strings['CheckedLocations'] = 'Geprüfte Speicherorte';
$strings['DetectionRule'] = 'Erkennungsregel';
$strings['NoCleanableFilesFound'] = 'Keine bereinigungsfähigen physischen Dateien gefunden';
$strings['NoCleanableFilesFoundHelp'] = 'Dies ist das erwartete Ergebnis, wenn der Speicher konsistent ist. Die geprüften Speicherorte werden unten zur Transparenz angezeigt.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Begrenzten Scan ausführen';
$strings['ScanNotRun'] = 'Scan nicht gestartet';
$strings['ScanNotRunHelp'] = 'Die Seite scannt den Speicher nicht automatisch, da große var/upload-Ordner langsam sein können. Klicken Sie auf „Begrenzten Scan ausführen“, um lokale verwaiste Dateien zu prüfen.';
$strings['ScanLimitedWarning'] = 'Der Scan wurde vorzeitig gestoppt, um die Seite reaktionsfähig zu halten. Führen Sie ihn später erneut aus oder prüfen Sie den Speicher über die Kommandozeile, falls erforderlich.';

$strings['PathFilter'] = 'Pfadfilter';
$strings['PathFilterHelp'] = 'Optional. Verwenden Sie dies, um einen bestimmten Ordner zu testen oder zu überprüfen, z. B. clean-deleted-files-test. Es wird nur auf relative Pfade angewendet.';
$strings['ActivePathFilter'] = 'Aktiver Pfadfilter: %s';
