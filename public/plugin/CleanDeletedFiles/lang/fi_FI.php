<?php
$strings['plugin_title'] = 'Puhdista poistetut tiedostot';
$strings['plugin_comment'] = 'Poista pysyvästi poistetuiksi merkittyjä tiedostoja. Ota se käyttöön menu_administrator-alueella ja käy se pääsivulta hallintasivulla.';
$strings['FileList'] = 'Poistetuiksi merkittyjen tiedostojen luettelo';
$strings['SizeTotalAllDir'] = 'Kokonaiskoko (kaikki hakemistot)';
$strings['NoFilesDeleted'] = 'Ei ole poistetuiksi merkittyjä tiedostoja';
$strings['FilesDeletedMark'] = 'Poistetuiksi merkittyjä tiedostoja';
$strings['FileDirSize'] = 'Hakemiston tiedostojen koko';
$strings['ConfirmDelete'] = 'Haluatko varmasti poistaa tiedoston?';
$strings['ErrorDeleteFile'] = 'Tiedoston poistossa tapahtui virhe';
$strings['ErrorEmptyPath'] = 'Tiedoston poistossa oli ongelma, polku ei voi olla tyhjä';
$strings['DeleteSelectedFiles'] = 'Poista valitut tiedostot';
$strings['ConfirmDeleteFiles'] = 'Haluatko varmasti poistaa kaikki valitut tiedostot?';
$strings['DeletedSuccess'] = 'Tiedoston poisto onnistui';
$strings['path_dir'] = 'Hakemisto';
$strings['size'] = 'Koko';
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
$strings['ErrorNotCleanablePath'] = 'Tiedosto ei ole puhdistettava orpo tai vanha poistettu tiedosto.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Puhdistettavat fyysiset tiedostot';
$strings['NoCleanableFiles'] = 'Tässä tallennustilan juuressa ei ole puhdistettavia fyysisiä tiedostoja';
$strings['Chamilo2ResourceStorage'] = 'Resurssitiedostot';
$strings['Chamilo2ResourceStorageHelp'] = 'Var/upload/resource-kansion alla olevat tiedostot listataan vain, kun niihin ei viitata resource_file-taulussa. Poista asiakirjoja Asiakirjat-työkalusta tai API:sta, älä tästä liitännäisestä.';
$strings['Chamilo2AssetStorage'] = 'Asset-tiedostot';
$strings['Chamilo2AssetStorageHelp'] = 'Var/upload/assets-kansion alla olevat tiedostot listataan vain, kun niihin ei viitata asset-taulussa. Viitatut assetit ovat aina suojattuja.';
$strings['LegacyCourseFiles'] = 'Vanhat kurssitiedostot';
$strings['LegacyUploadFiles'] = 'Vanhat lataustiedostot';
$strings['LegacyPublicCourseFiles'] = 'Vanhat julkiset kurssitiedostot';
$strings['LegacyPublicUploadFiles'] = 'Vanhat julkiset lataustiedostot';
$strings['LegacyDeletedStorageHelp'] = 'Vanhat juuret skannataan vain tiedostoille, joiden perusnimi sisältää DELETED-merkinnän.';
$strings['OrphanResourceFile'] = 'Orpo resurssitiedosto';
$strings['OrphanAssetFile'] = 'Orpo asset-tiedosto';
$strings['LegacyDeletedFile'] = 'Vanha DELETED-tiedosto';
$strings['OrphanResourceFiles'] = 'Orvot resurssitiedostot';
$strings['OrphanAssetFiles'] = 'Orvot asset-tiedostot';
$strings['LegacyDeletedFiles'] = 'Vanhat DELETED-tiedostot';
$strings['Reason'] = 'Syy';
$strings['ReasonOrphanResource'] = 'Fyysinen tiedosto on resurssitallennustilassa, mutta mikään resource_file-rivi ei viittaa siihen.';
$strings['ReasonOrphanAsset'] = 'Fyysinen tiedosto on asset-tallennustilassa, mutta mikään asset-rivi ei viittaa siihen.';
$strings['ReasonLegacyDeletedMarker'] = 'Vanha tiedoston perusnimi sisältää DELETED-merkinnän.';

$strings['StorageNoticeShort'] = 'Ladatut tiedostot seurataan resource_file- ja asset-metatietojen kautta. Tämä liitännäinen listaa vain fyysiset tiedostot var/upload/resource- ja var/upload/assets-kansioiden alta, joihin ei enää viitata.';
$strings['SafeNoticeShort'] = 'Tiedosto, jolla on kelvollinen tietokantaviite, on suojattu. Asiakirjat ja tiedostot tulee edelleen poistaa niiden normaaleilla työkaluilla.';
$strings['CheckedLocations'] = 'Tarkistetut sijainnit';
$strings['DetectionRule'] = 'Havaitsemissääntö';
$strings['NoCleanableFilesFound'] = 'Puhdistettavia fyysisiä tiedostoja ei löytynyt';
$strings['NoCleanableFilesFoundHelp'] = 'Tämä on odotettu tulos, kun tallennustila on johdonmukainen. Tarkistetut sijainnit näytetään alla läpinäkyvyyden vuoksi.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Suorita rajoitettu skannaus';
$strings['ScanNotRun'] = 'Skannausta ei ole aloitettu';
$strings['ScanNotRunHelp'] = 'Sivu ei skannaa tallennustilaa automaattisesti, koska suuret var/upload-kansiot voivat olla hitaita. Napsauta Suorita rajoitettu skannaus tarkistaaksesi paikalliset orpot tiedostot.';
$strings['ScanLimitedWarning'] = 'Skannaus keskeytettiin aikaisin pitämään sivu reagoivana. Suorita se uudelleen myöhemmin tai tarkista tallennustila komentoriviltä tarvittaessa.';

$strings['PathFilter'] = 'Polkusuodatin';
$strings['PathFilterHelp'] = 'Valinnainen. Käytä tätä tietyn kansion testaamiseen tai tarkasteluun, esimerkiksi clean-deleted-files-test. Se vastaa vain suhteellisia polkuja.';
$strings['ActivePathFilter'] = 'Aktiivinen polkusuodatin: %s';
