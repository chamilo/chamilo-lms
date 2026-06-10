<?php
$strings['plugin_title'] = 'Neteja els fitxers esborrats';
$strings['plugin_comment'] = "Esborra permanentment els fitxers marcats com a esborrats. Activa'l a la regió menu_administrator i accedeix-hi des de la pàgina principal d'administració.";
$strings['FileList'] = 'Llista de fitxers marcats com a esborrats';
$strings['SizeTotalAllDir'] = 'Mida total (tots els directoris)';
$strings['NoFilesDeleted'] = 'No hi ha fitxers marcats com a esborrats';
$strings['FilesDeletedMark'] = 'Fitxers marcats com a esborrats';
$strings['FileDirSize'] = 'Mida dels fitxers del directori';
$strings['ConfirmDelete'] = 'Esteu segur que voleu esborrar el fitxer?';
$strings['ErrorDeleteFile'] = "S'ha produït un error en esborrar el fitxer";
$strings['ErrorEmptyPath'] = 'Hi ha hagut un problema en esborrar el fitxer, el camí no pot estar buit';
$strings['DeleteSelectedFiles'] = 'Esborra els fitxers seleccionats';
$strings['ConfirmDeleteFiles'] = 'Esteu segur que voleu esborrar tots els fitxers seleccionats?';
$strings['DeletedSuccess'] = "L'esborrat del fitxer s'ha realitzat correctament";
$strings['path_dir'] = 'Directori';
$strings['size'] = 'Mida';
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
$strings['ErrorNotCleanablePath'] = 'El fitxer no és un orfe netejable ni un fitxer suprimit heretat.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Fitxers físics netejables';
$strings['NoCleanableFiles'] = "No hi ha fitxers físics netejables en aquesta arrel d'emmagatzematge";
$strings['Chamilo2ResourceStorage'] = 'Fitxers de recursos';
$strings['Chamilo2ResourceStorageHelp'] = "Els fitxers a var/upload/resource es llisten només quan no estan referenciats per resource_file. Suprimiu documents des de l'eina Documents o l'API, no des d'aquest connector.";
$strings['Chamilo2AssetStorage'] = "Fitxers d'actius";
$strings['Chamilo2AssetStorageHelp'] = 'Els fitxers a var/upload/assets es llisten només quan no estan referenciats per asset. Els actius referenciats sempre estan protegits.';
$strings['LegacyCourseFiles'] = 'Fitxers de curs heretats';
$strings['LegacyUploadFiles'] = 'Fitxers de càrrega heretats';
$strings['LegacyPublicCourseFiles'] = 'Fitxers de curs públics heretats';
$strings['LegacyPublicUploadFiles'] = 'Fitxers de càrrega públics heretats';
$strings['LegacyDeletedStorageHelp'] = "Les arrels heretades s'analitzen només per als fitxers el nom base dels quals conté el marcador DELETED.";
$strings['OrphanResourceFile'] = 'Fitxer de recurs orfe';
$strings['OrphanAssetFile'] = "Fitxer d'actiu orfe";
$strings['LegacyDeletedFile'] = 'Fitxer DELETED heretat';
$strings['OrphanResourceFiles'] = 'Fitxers de recurs orfes';
$strings['OrphanAssetFiles'] = "Fitxers d'actiu orfes";
$strings['LegacyDeletedFiles'] = 'Fitxers DELETED heretats';
$strings['Reason'] = 'Motiu';
$strings['ReasonOrphanResource'] = "El fitxer físic existeix a l'emmagatzematge de recursos però cap fila resource_file l'apunta.";
$strings['ReasonOrphanAsset'] = "El fitxer físic existeix a l'emmagatzematge d'actius però cap fila asset l'apunta.";
$strings['ReasonLegacyDeletedMarker'] = 'El nom base del fitxer heretat conté el marcador DELETED.';

$strings['StorageNoticeShort'] = 'Els fitxers carregats es registren mitjançant les metadades resource_file i asset. Aquest connector només llista els fitxers físics a var/upload/resource i var/upload/assets que ja no estan referenciats.';
$strings['SafeNoticeShort'] = "Un fitxer amb una referència vàlida a la base de dades està protegit. Els documents i fitxers s'han de suprimir encara a través de les seves eines normals.";
$strings['CheckedLocations'] = 'Ubicacions comprovades';
$strings['DetectionRule'] = 'Regla de detecció';
$strings['NoCleanableFilesFound'] = "No s'han trobat fitxers físics netejables";
$strings['NoCleanableFilesFoundHelp'] = "Aquest és el resultat esperat quan l'emmagatzematge és consistent. Les ubicacions comprovades es mostren a continuació per transparència.";

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Executa una exploració limitada';
$strings['ScanNotRun'] = 'Exploració no iniciada';
$strings['ScanNotRunHelp'] = "La pàgina no explora l'emmagatzematge automàticament perquè carpetes var/upload grans poden ser lentes. Feu clic a Executa una exploració limitada per inspeccionar fitxers orfes locals.";
$strings['ScanLimitedWarning'] = "L'exploració s'ha aturat abans d'hora per mantenir la pàgina responsiva. Torneu-la a executar més tard o inspeccioneu l'emmagatzematge des de la línia de comandes si cal.";

$strings['PathFilter'] = 'Filtre de camí';
$strings['PathFilterHelp'] = 'Opcional. Utilitzeu-lo per provar o revisar una carpeta específica, per exemple clean-deleted-files-test. Només coincideix amb camins relatius.';
$strings['ActivePathFilter'] = 'Filtre de camí actiu: %s';
