<?php
$strings['plugin_title'] = 'Išvalyti ištrintas bylas';
$strings['plugin_comment'] = 'Nuolatiniam ištrinti pažymėtas kaip ištrintas bylas. Įjunkite jį menu_administrator srityje, tada pasiekite iš pagrindinio administratoriaus puslapio.';
$strings['FileList'] = 'Pažymėtų kaip ištrintų bylų sąrašas';
$strings['SizeTotalAllDir'] = 'Bendras dydis (visos direktorijos)';
$strings['NoFilesDeleted'] = 'Nėra bylų, pažymėtų kaip ištrintos';
$strings['FilesDeletedMark'] = 'Pažymėtos kaip ištrintos bylos';
$strings['FileDirSize'] = 'Direktorijos bylų dydis';
$strings['ConfirmDelete'] = 'Ar tikrai norite ištrinti bylą?';
$strings['ErrorDeleteFile'] = 'Įvyko klaida trinant bylą';
$strings['ErrorEmptyPath'] = 'Įvyko problema trinant bylą, kelias negali būti tuščias';
$strings['DeleteSelectedFiles'] = 'Ištrinti pažymėtas bylas';
$strings['ConfirmDeleteFiles'] = 'Ar tikrai norite ištrinti visas pažymėtas bylas?';
$strings['DeletedSuccess'] = 'Bylos ištrynimas pavyko';
$strings['path_dir'] = 'Direktorija';
$strings['size'] = 'Dydis';
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
$strings['ErrorNotCleanablePath'] = 'Šis failas nėra išvalomas našlaitis arba senas ištrintas failas.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Išvalomi fiziniai failai';
$strings['NoCleanableFiles'] = 'Šioje saugyklos šaknyje nėra išvalomų fizinių failų';
$strings['Chamilo2ResourceStorage'] = 'Ištekliaus failai';
$strings['Chamilo2ResourceStorageHelp'] = 'Failai, esantys var/upload/resource, rodomi tik tada, kai jų nenurodo resource_file. Dokumentus ištrinkite naudodami Dokumentų įrankį arba API, o ne šį įskiepį.';
$strings['Chamilo2AssetStorage'] = 'Išteklių failai';
$strings['Chamilo2AssetStorageHelp'] = 'Failai, esantys var/upload/assets, rodomi tik tada, kai jų nenurodo asset. Nurodyti ištekliai visada yra apsaugoti.';
$strings['LegacyCourseFiles'] = 'Seni kurso failai';
$strings['LegacyUploadFiles'] = 'Seni įkėlimo failai';
$strings['LegacyPublicCourseFiles'] = 'Seni viešieji kurso failai';
$strings['LegacyPublicUploadFiles'] = 'Seni viešieji įkėlimo failai';
$strings['LegacyDeletedStorageHelp'] = 'Senos šaknys skenuojamos tik failų, kurių bazinis vardas turi DELETED žymę.';
$strings['OrphanResourceFile'] = 'Našlaitis ištekliaus failas';
$strings['OrphanAssetFile'] = 'Našlaitis išteklių failas';
$strings['LegacyDeletedFile'] = 'Senas DELETED failas';
$strings['OrphanResourceFiles'] = 'Našlaičiai ištekliaus failai';
$strings['OrphanAssetFiles'] = 'Našlaičiai išteklių failai';
$strings['LegacyDeletedFiles'] = 'Seni DELETED failai';
$strings['Reason'] = 'Priežastis';
$strings['ReasonOrphanResource'] = 'Fizinis failas yra ištekliaus saugykloje, bet joks resource_file įrašas į jį nerodo.';
$strings['ReasonOrphanAsset'] = 'Fizinis failas yra išteklių saugykloje, bet joks asset įrašas į jį nerodo.';
$strings['ReasonLegacyDeletedMarker'] = 'Seno failo bazinis vardas turi DELETED žymę.';

$strings['StorageNoticeShort'] = 'Įkelti failai sekami per resource_file ir asset metaduomenis. Šis įskiepis rodo tik fizinius failus, esančius var/upload/resource ir var/upload/assets, kurie nebėra nurodyti.';
$strings['SafeNoticeShort'] = 'Failas su galiojančia duomenų bazės nuoroda yra apsaugotas. Dokumentus ir failus vis tiek reikia ištrinti naudojant įprastus įrankius.';
$strings['CheckedLocations'] = 'Patikrintos vietos';
$strings['DetectionRule'] = 'Aptikimo taisyklė';
$strings['NoCleanableFilesFound'] = 'Nerasta išvalomų fizinių failų';
$strings['NoCleanableFilesFoundHelp'] = 'Tai yra tikėtinas rezultatas, kai saugykla yra nuosekli. Patikrintos vietos parodytos žemiau skaidrumo sumetimais.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Vykdyti ribotą skenavimą';
$strings['ScanNotRun'] = 'Skenavimas nepradėtas';
$strings['ScanNotRunHelp'] = 'Puslapis automatiškai neskenuoja saugyklos, nes dideli var/upload aplankai gali veikti lėtai. Spustelėkite „Vykdyti ribotą skenavimą“, kad patikrintumėte vietinius našlaičių failus.';
$strings['ScanLimitedWarning'] = 'Skenavimas buvo sustabdytas anksčiau, kad puslapis išliktų greitas. Paleiskite jį dar kartą vėliau arba patikrinkite saugyklą iš komandinės eilutės, jei reikia.';

$strings['PathFilter'] = 'Kelias filtras';
$strings['PathFilterHelp'] = 'Pasirinktinai. Naudokite tai konkretaus aplanko testavimui arba peržiūrai, pavyzdžiui clean-deleted-files-test. Atitinka tik santykinius kelius.';
$strings['ActivePathFilter'] = 'Aktyvus kelio filtras: %s';
