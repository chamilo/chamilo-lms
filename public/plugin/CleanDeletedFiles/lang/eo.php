<?php
$strings['plugin_title'] = 'Forigi forigitaj dosieroj';
$strings['plugin_comment'] = 'Definite forigi dosierojn markitajn kiel forigitaj. Aktivigu ĝin en la regiono menu_administrator, poste aliro ĝi el la ĉefa paĝo de administranto.';
$strings['FileList'] = 'Listo de dosieroj markitaj kiel forigitaj';
$strings['SizeTotalAllDir'] = 'Totala grando (ĉiuj dosierujoj)';
$strings['NoFilesDeleted'] = 'Ne estas dosieroj markitaj kiel forigitaj';
$strings['FilesDeletedMark'] = 'Dosieroj markitaj kiel forigitaj';
$strings['FileDirSize'] = 'Grando de dosierujaj dosieroj';
$strings['ConfirmDelete'] = 'Ĉu vi certas, ke vi volas forigi la dosieron?';
$strings['ErrorDeleteFile'] = 'Okazis eraro dum forigado de la dosiero';
$strings['ErrorEmptyPath'] = 'Estis problemo forigante la dosieron, la vojo ne povas esti malplena';
$strings['DeleteSelectedFiles'] = 'Forigi elektitajn dosierojn';
$strings['ConfirmDeleteFiles'] = 'Ĉu vi certas, ke vi volas forigi ĉiujn elektitajn dosierojn?';
$strings['DeletedSuccess'] = 'La forigado de la dosiero sukcesis';
$strings['path_dir'] = 'Dosierujon';
$strings['size'] = 'Grando';
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
$strings['ErrorNotCleanablePath'] = 'La dosiero ne estas forigebla orfa dosiero aŭ hereda forigita dosiero.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Forigeblaj fizikaj dosieroj';
$strings['NoCleanableFiles'] = 'Ne ekzistas forigeblaj fizikaj dosieroj en ĉi tiu radiko de konservado';
$strings['Chamilo2ResourceStorage'] = 'Risurcaj dosieroj';
$strings['Chamilo2ResourceStorageHelp'] = 'Dosieroj sub var/upload/resource estas listigitaj nur kiam ili ne estas referencitaj de resource_file. Forigu dokumentojn el la Dokumentoj ilo aŭ API, ne el ĉi tiu kromaĵo.';
$strings['Chamilo2AssetStorage'] = 'Asset-dosieroj';
$strings['Chamilo2AssetStorageHelp'] = 'Dosieroj sub var/upload/assets estas listigitaj nur kiam ili ne estas referencitaj de asset. Referencitaj assets estas ĉiam protektitaj.';
$strings['LegacyCourseFiles'] = 'Heritaj kursaj dosieroj';
$strings['LegacyUploadFiles'] = 'Heritaj alŝutitaj dosieroj';
$strings['LegacyPublicCourseFiles'] = 'Heritaj publikaj kursaj dosieroj';
$strings['LegacyPublicUploadFiles'] = 'Heritaj publikaj alŝutitaj dosieroj';
$strings['LegacyDeletedStorageHelp'] = 'Heritaj radikoj estas skanitaj nur por dosieroj kies baznomo enhavas la DELETED markilon.';
$strings['OrphanResourceFile'] = 'Orfa risurca dosiero';
$strings['OrphanAssetFile'] = 'Orfa asset-dosiero';
$strings['LegacyDeletedFile'] = 'Hereda DELETED dosiero';
$strings['OrphanResourceFiles'] = 'Orfaj risurcaj dosieroj';
$strings['OrphanAssetFiles'] = 'Orfaj asset-dosieroj';
$strings['LegacyDeletedFiles'] = 'Heritaj DELETED dosieroj';
$strings['Reason'] = 'Kialo';
$strings['ReasonOrphanResource'] = 'Fizika dosiero ekzistas en risurca konservado sed neniu resource_file vico montras al ĝi.';
$strings['ReasonOrphanAsset'] = 'Fizika dosiero ekzistas en asset-konservado sed neniu asset vico montras al ĝi.';
$strings['ReasonLegacyDeletedMarker'] = 'Hereda dosiero baznomo enhavas la DELETED markilon.';

$strings['StorageNoticeShort'] = 'Alŝutitaj dosieroj estas spuritaj tra resource_file kaj asset metadatumoj. Ĉi tiu kromaĵo nur listigas fizikajn dosierojn sub var/upload/resource kaj var/upload/assets kiuj ne plu estas referencitaj.';
$strings['SafeNoticeShort'] = 'Dosiero kun valida datumbaza referenco estas protektita. Dokumentoj kaj dosieroj devus ankoraŭ esti forigitaj per iliaj normalaj iloj.';
$strings['CheckedLocations'] = 'Kontrolitaj lokoj';
$strings['DetectionRule'] = 'Detekta regulo';
$strings['NoCleanableFilesFound'] = 'Neniuj forigeblaj fizikaj dosieroj trovitaj';
$strings['NoCleanableFilesFoundHelp'] = 'Ĉi tio estas la atendita rezulto kiam la konservado estas konsekvenca. La kontrolitaj lokoj estas montritaj sube por travidebleco.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Lanĉi limigitan skanon';
$strings['ScanNotRun'] = 'Skano ne komencita';
$strings['ScanNotRunHelp'] = 'La paĝo ne skanas la konservadon aŭtomate ĉar grandaj var/upload dosierujoj povas esti malrapidaj. Klaku Lanĉi limigitan skanon por inspekti lokajn orfajn dosierojn.';
$strings['ScanLimitedWarning'] = 'La skano estis ĉesigita frue por konservi la paĝon respondeca. Lanĉu ĝin denove poste aŭ inspekti la konservadon el la komandlinio se necese.';

$strings['PathFilter'] = 'Voja filtrilo';
$strings['PathFilterHelp'] = 'Nedeviga. Uzu ĉi tion por testi aŭ revizii specifan dosierujon, ekzemple clean-deleted-files-test. Ĝi kongruas nur relativajn vojojn.';
$strings['ActivePathFilter'] = 'Aktiva voja filtrilo: %s';
