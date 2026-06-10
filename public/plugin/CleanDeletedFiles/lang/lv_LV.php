<?php
$strings['plugin_title'] = 'Notīrīt dzēstos failus';
$strings['plugin_comment'] = 'Pastāvīgi dzēst failus, kas atzīmēti kā dzēsti. Iespējot to administratora izvēlnē, tad piekļūt no galvenās administratora lapas.';
$strings['FileList'] = 'Failu saraksts, kas atzīmēti kā dzēsti';
$strings['SizeTotalAllDir'] = 'Kopējais izmērs (visas mapes)';
$strings['NoFilesDeleted'] = 'Nav failu, kas atzīmēti kā dzēsti';
$strings['FilesDeletedMark'] = 'Faili, kas atzīmēti kā dzēsti';
$strings['FileDirSize'] = 'Mapes failu izmērs';
$strings['ConfirmDelete'] = 'Vai tiešām vēlaties dzēst failu?';
$strings['ErrorDeleteFile'] = 'Radās kļūda, dzēšot failu';
$strings['ErrorEmptyPath'] = 'Radās problēma, dzēšot failu, ceļš nevar būt tukšs';
$strings['DeleteSelectedFiles'] = 'Dzēst izvēlētos failus';
$strings['ConfirmDeleteFiles'] = 'Vai tiešām vēlaties dzēst visus izvēlētos failus?';
$strings['DeletedSuccess'] = 'Faila dzēšana veiksmīga';
$strings['path_dir'] = 'Mape';
$strings['size'] = 'Izmērs';
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
$strings['ErrorNotCleanablePath'] = 'Fails nav tīrāms bārenis vai novecojis dzēsts fails.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Tīrāmi fiziskie faili';
$strings['NoCleanableFiles'] = 'Šajā krātuves saknē nav tīrāmu fizisko failu';
$strings['Chamilo2ResourceStorage'] = 'Resursu faili';
$strings['Chamilo2ResourceStorageHelp'] = 'Faili mapē var/upload/resource tiek uzskaitīti tikai tad, ja uz tiem neatsaucas resource_file. Dzēsiet dokumentus no Dokumentu rīka vai API, nevis no šī spraudņa.';
$strings['Chamilo2AssetStorage'] = 'Aktīvu faili';
$strings['Chamilo2AssetStorageHelp'] = 'Faili mapē var/upload/assets tiek uzskaitīti tikai tad, ja uz tiem neatsaucas asset. Atsauktie aktīvi vienmēr ir aizsargāti.';
$strings['LegacyCourseFiles'] = 'Novecojuši kursa faili';
$strings['LegacyUploadFiles'] = 'Novecojuši augšupielādes faili';
$strings['LegacyPublicCourseFiles'] = 'Novecojuši publiskie kursa faili';
$strings['LegacyPublicUploadFiles'] = 'Novecojuši publiskie augšupielādes faili';
$strings['LegacyDeletedStorageHelp'] = 'Novecojušās saknes tiek skenētas tikai failiem, kuru pamatnosaukumā ir DELETED marķieris.';
$strings['OrphanResourceFile'] = 'Bāreņa resursu fails';
$strings['OrphanAssetFile'] = 'Bāreņa aktīva fails';
$strings['LegacyDeletedFile'] = 'Novecojis DELETED fails';
$strings['OrphanResourceFiles'] = 'Bāreņu resursu faili';
$strings['OrphanAssetFiles'] = 'Bāreņu aktīvu faili';
$strings['LegacyDeletedFiles'] = 'Novecojuši DELETED faili';
$strings['Reason'] = 'Iemesls';
$strings['ReasonOrphanResource'] = 'Fiziskais fails pastāv resursu krātuvē, bet neviens resource_file ieraksts uz to neatsaucas.';
$strings['ReasonOrphanAsset'] = 'Fiziskais fails pastāv aktīvu krātuvē, bet neviens asset ieraksts uz to neatsaucas.';
$strings['ReasonLegacyDeletedMarker'] = 'Novecojuša faila pamatnosaukumā ir DELETED marķieris.';

$strings['StorageNoticeShort'] = 'Augšupielādētie faili tiek izsekoti, izmantojot resource_file un asset metadatus. Šis spraudnis uzskaita tikai fiziskos failus mapēs var/upload/resource un var/upload/assets, uz kuriem vairs neatsaucas.';
$strings['SafeNoticeShort'] = 'Fails ar derīgu datubāzes atsauci ir aizsargāts. Dokumenti un faili joprojām jādzēš, izmantojot to parastos rīkus.';
$strings['CheckedLocations'] = 'Pārbaudītās atrašanās vietas';
$strings['DetectionRule'] = 'Noteikšanas noteikums';
$strings['NoCleanableFilesFound'] = 'Nav atrasti tīrāmi fiziskie faili';
$strings['NoCleanableFilesFoundHelp'] = 'Tas ir paredzētais rezultāts, kad krātuve ir konsekventa. Pārbaudītās atrašanās vietas ir parādītas zemāk pārredzamības nolūkos.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Palaist ierobežotu skenēšanu';
$strings['ScanNotRun'] = 'Skenēšana nav sākta';
$strings['ScanNotRunHelp'] = 'Lapa automātiski neskenē krātuvi, jo lielas var/upload mapes var būt lēnas. Noklikšķiniet uz Palaist ierobežotu skenēšanu, lai pārbaudītu lokālos bāreņu failus.';
$strings['ScanLimitedWarning'] = 'Skenēšana tika apturēta priekšlaicīgi, lai saglabātu lapas atsaucību. Palaidiet to vēlreiz vēlāk vai pārbaudiet krātuvi no komandrindas, ja nepieciešams.';

$strings['PathFilter'] = 'Ceļa filtrs';
$strings['PathFilterHelp'] = 'Neobligāti. Izmantojiet to, lai pārbaudītu vai pārskatītu konkrētu mapi, piemēram, clean-deleted-files-test. Tas atbilst tikai relatīvajiem ceļiem.';
$strings['ActivePathFilter'] = 'Aktīvs ceļa filtrs: %s';
