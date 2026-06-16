<?php
$strings['plugin_title'] = 'Vyčistit smazané soubory';
$strings['plugin_comment'] = 'Trvale smazat soubory označené jako smazané. Povolte to v oblasti menu_administrator a poté k němu přistupujte z hlavní administrační stránky.';
$strings['FileList'] = 'Seznam souborů označených jako smazané';
$strings['SizeTotalAllDir'] = 'Celková velikost (všechny adresáře)';
$strings['NoFilesDeleted'] = 'Nejsou žádné soubory označené jako smazané';
$strings['FilesDeletedMark'] = 'Soubory označené jako smazané';
$strings['FileDirSize'] = 'Velikost souborů adresáře';
$strings['ConfirmDelete'] = 'Jste si jisti, že chcete soubor smazat?';
$strings['ErrorDeleteFile'] = 'Došlo k chybě při mazání souboru';
$strings['ErrorEmptyPath'] = 'Při mazání souboru došlo k problému, cesta nemůže být prázdná';
$strings['DeleteSelectedFiles'] = 'Smazat vybrané soubory';
$strings['ConfirmDeleteFiles'] = 'Jste si jisti, že chcete smazat všechny vybrané soubory?';
$strings['DeletedSuccess'] = 'Mazání souboru bylo úspěšné';
$strings['path_dir'] = 'Adresář';
$strings['size'] = 'Velikost';
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
$strings['ErrorNotCleanablePath'] = 'Soubor není odstranitelný osiřelý soubor ani starý smazaný soubor.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Odstranitelné fyzické soubory';
$strings['NoCleanableFiles'] = 'V tomto kořenovém úložišti nejsou žádné odstranitelné fyzické soubory';
$strings['Chamilo2ResourceStorage'] = 'Soubory zdrojů';
$strings['Chamilo2ResourceStorageHelp'] = 'Soubory v var/upload/resource jsou zobrazeny pouze pokud na ně neodkazuje žádný záznam resource_file. Dokumenty odstraňujte pomocí nástroje Dokumenty nebo API, nikoli z tohoto pluginu.';
$strings['Chamilo2AssetStorage'] = 'Soubory aktiv';
$strings['Chamilo2AssetStorageHelp'] = 'Soubory v var/upload/assets jsou zobrazeny pouze pokud na ně neodkazuje žádný záznam asset. Odkazované assety jsou vždy chráněny.';
$strings['LegacyCourseFiles'] = 'Staré soubory kurzů';
$strings['LegacyUploadFiles'] = 'Staré nahrané soubory';
$strings['LegacyPublicCourseFiles'] = 'Staré veřejné soubory kurzů';
$strings['LegacyPublicUploadFiles'] = 'Staré veřejné nahrané soubory';
$strings['LegacyDeletedStorageHelp'] = 'Staré kořeny jsou prohledávány pouze u souborů, jejichž název obsahuje značku DELETED.';
$strings['OrphanResourceFile'] = 'Osiřelý soubor zdroje';
$strings['OrphanAssetFile'] = 'Osiřelý soubor assetu';
$strings['LegacyDeletedFile'] = 'Starý soubor DELETED';
$strings['OrphanResourceFiles'] = 'Osiřelé soubory zdrojů';
$strings['OrphanAssetFiles'] = 'Osiřelé soubory assetů';
$strings['LegacyDeletedFiles'] = 'Staré soubory DELETED';
$strings['Reason'] = 'Důvod';
$strings['ReasonOrphanResource'] = 'Fyzický soubor existuje v úložišti zdrojů, ale neukazuje na něj žádný řádek resource_file.';
$strings['ReasonOrphanAsset'] = 'Fyzický soubor existuje v úložišti assetů, ale neukazuje na něj žádný řádek asset.';
$strings['ReasonLegacyDeletedMarker'] = 'Název starého souboru obsahuje značku DELETED.';

$strings['StorageNoticeShort'] = 'Nahrané soubory jsou sledovány prostřednictvím metadat resource_file a asset. Tento plugin zobrazuje pouze fyzické soubory v var/upload/resource a var/upload/assets, na které se již neodkazuje.';
$strings['SafeNoticeShort'] = 'Soubor s platným odkazem v databázi je chráněn. Dokumenty a soubory by měly být stále mazány prostřednictvím jejich standardních nástrojů.';
$strings['CheckedLocations'] = 'Zkontrolovaná umístění';
$strings['DetectionRule'] = 'Pravidlo detekce';
$strings['NoCleanableFilesFound'] = 'Nebyly nalezeny žádné odstranitelné fyzické soubory';
$strings['NoCleanableFilesFoundHelp'] = 'Toto je očekávaný výsledek, pokud je úložiště konzistentní. Zkontrolovaná umístění jsou zobrazena níže pro transparentnost.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Spustit omezené skenování';
$strings['ScanNotRun'] = 'Skenování nebylo spuštěno';
$strings['ScanNotRunHelp'] = 'Stránka automaticky neskenuje úložiště, protože velké složky var/upload mohou být pomalé. Klikněte na Spustit omezené skenování pro kontrolu místních osiřelých souborů.';
$strings['ScanLimitedWarning'] = 'Skenování bylo předčasně zastaveno, aby stránka zůstala responzivní. Spusťte jej znovu později nebo zkontrolujte úložiště z příkazového řádku, pokud je to nutné.';

$strings['PathFilter'] = 'Filtr cesty';
$strings['PathFilterHelp'] = 'Volitelné. Použijte k testování nebo kontrole konkrétní složky, například clean-deleted-files-test. Odpovídá pouze relativním cestám.';
$strings['ActivePathFilter'] = 'Aktivní filtr cesty: %s';
