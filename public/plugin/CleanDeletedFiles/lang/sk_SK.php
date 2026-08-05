<?php
$strings['plugin_title'] = 'Vyčistiť zmazané súbory';
$strings['plugin_comment'] = 'Trvalo zmazať súbory označené ako zmazané. Povoliť v oblasti menu_administrator a potom pristupovať z hlavnej administračnej stránky.';
$strings['FileList'] = 'Zoznam súborov označených ako zmazané';
$strings['SizeTotalAllDir'] = 'Celková veľkosť (všetky adresáre)';
$strings['NoFilesDeleted'] = 'Žiadne súbory označené ako zmazané';
$strings['FilesDeletedMark'] = 'Súbory označené ako zmazané';
$strings['FileDirSize'] = 'Veľkosť súborov adresára';
$strings['ConfirmDelete'] = 'Naozaj chcete zmazať súbor?';
$strings['ErrorDeleteFile'] = 'Pri mazaní súboru došlo k chybe';
$strings['ErrorEmptyPath'] = 'Pri mazaní súboru nastal problém, cesta nemôže byť prázdna';
$strings['DeleteSelectedFiles'] = 'Zmazať vybrané súbory';
$strings['ConfirmDeleteFiles'] = 'Naozaj chcete zmazať všetky vybrané súbory?';
$strings['DeletedSuccess'] = 'Mazanie súboru bolo úspešné';
$strings['path_dir'] = 'Adresár';
$strings['size'] = 'Veľkosť';
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
$strings['ErrorNotCleanablePath'] = 'Súbor nie je vyčistiteľný osirelý súbor ani starý vymazaný súbor.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Vyčistiteľné fyzické súbory';
$strings['NoCleanableFiles'] = 'V tomto úložnom koreni nie sú žiadne vyčistiteľné fyzické súbory';
$strings['Chamilo2ResourceStorage'] = 'Súbory zdrojov';
$strings['Chamilo2ResourceStorageHelp'] = 'Súbory v var/upload/resource sú zobrazené iba v prípade, že na ne neodkazuje resource_file. Dokumenty odstraňujte cez nástroj Dokumenty alebo API, nie cez tento plugin.';
$strings['Chamilo2AssetStorage'] = 'Súbory aktív';
$strings['Chamilo2AssetStorageHelp'] = 'Súbory v var/upload/assets sú zobrazené iba v prípade, že na ne neodkazuje asset. Odkazované aktíva sú vždy chránené.';
$strings['LegacyCourseFiles'] = 'Staré súbory kurzov';
$strings['LegacyUploadFiles'] = 'Staré nahrané súbory';
$strings['LegacyPublicCourseFiles'] = 'Staré verejné súbory kurzov';
$strings['LegacyPublicUploadFiles'] = 'Staré verejné nahrané súbory';
$strings['LegacyDeletedStorageHelp'] = 'Staré korene sa prehľadávajú iba pre súbory, ktorých základný názov obsahuje značku DELETED.';
$strings['OrphanResourceFile'] = 'Osirelý súbor zdroja';
$strings['OrphanAssetFile'] = 'Osirelý súbor aktíva';
$strings['LegacyDeletedFile'] = 'Starý súbor DELETED';
$strings['OrphanResourceFiles'] = 'Osirelé súbory zdrojov';
$strings['OrphanAssetFiles'] = 'Osirelé súbory aktív';
$strings['LegacyDeletedFiles'] = 'Staré súbory DELETED';
$strings['Reason'] = 'Dôvod';
$strings['ReasonOrphanResource'] = 'Fyzický súbor existuje v úložisku zdrojov, ale neukazuje naň žiadny riadok resource_file.';
$strings['ReasonOrphanAsset'] = 'Fyzický súbor existuje v úložisku aktív, ale neukazuje naň žiadny riadok asset.';
$strings['ReasonLegacyDeletedMarker'] = 'Základný názov starého súboru obsahuje značku DELETED.';

$strings['StorageNoticeShort'] = 'Nahrané súbory sú sledované cez metaúdaje resource_file a asset. Tento plugin zobrazuje iba fyzické súbory v var/upload/resource a var/upload/assets, na ktoré sa už neodkazuje.';
$strings['SafeNoticeShort'] = 'Súbor s platným odkazom v databáze je chránený. Dokumenty a súbory by mali byť stále odstraňované cez ich bežné nástroje.';
$strings['CheckedLocations'] = 'Skontrolované umiestnenia';
$strings['DetectionRule'] = 'Pravidlo detekcie';
$strings['NoCleanableFilesFound'] = 'Nenašli sa žiadne vyčistiteľné fyzické súbory';
$strings['NoCleanableFilesFoundHelp'] = 'Toto je očakávaný výsledok, keď je úložisko konzistentné. Skontrolované umiestnenia sú zobrazené nižšie pre transparentnosť.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Spustiť obmedzené skenovanie';
$strings['ScanNotRun'] = 'Skenovanie nebolo spustené';
$strings['ScanNotRunHelp'] = 'Stránka automaticky neskenuje úložisko, pretože veľké priečinky var/upload môžu byť pomalé. Kliknutím na Spustiť obmedzené skenovanie skontrolujete lokálne osirelé súbory.';
$strings['ScanLimitedWarning'] = 'Skenovanie bolo predčasne zastavené, aby stránka zostala responzívna. Spustite ho znova neskôr alebo skontrolujte úložisko z príkazového riadka, ak je to potrebné.';

$strings['PathFilter'] = 'Filter cesty';
$strings['PathFilterHelp'] = 'Voliteľné. Použite na testovanie alebo kontrolu konkrétneho priečinka, napríklad clean-deleted-files-test. Zodpovedá iba relatívnym cestám.';
$strings['ActivePathFilter'] = 'Aktívny filter cesty: %s';
