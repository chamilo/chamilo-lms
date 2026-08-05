<?php
$strings['plugin_title'] = 'Törölt fájlok tisztítása';
$strings['plugin_comment'] = 'Törölje véglegesen a törölteként megjelölt fájlokat. Engedélyezze a menu_administrator régióban, majd érje el a fő adminisztrációs oldalról.';
$strings['FileList'] = 'Törölteként megjelölt fájlok listája';
$strings['SizeTotalAllDir'] = 'Teljes méret (minden könyvtár)';
$strings['NoFilesDeleted'] = 'Nincsenek törölteként megjelölt fájlok';
$strings['FilesDeletedMark'] = 'Törölteként megjelölt fájlok';
$strings['FileDirSize'] = 'Könyvtár fájlok mérete';
$strings['ConfirmDelete'] = 'Biztosan törölni szeretné a fájlt?';
$strings['ErrorDeleteFile'] = 'Hiba történt a fájl törlése közben';
$strings['ErrorEmptyPath'] = 'Probléma történt a fájl törlésekor, az elérési út nem lehet üres';
$strings['DeleteSelectedFiles'] = 'Kijelölt fájlok törlése';
$strings['ConfirmDeleteFiles'] = 'Biztosan törölni szeretné az összes kijelölt fájlt?';
$strings['DeletedSuccess'] = 'A fájl törlése sikeres volt';
$strings['path_dir'] = 'Könyvtár';
$strings['size'] = 'Méret';
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
$strings['ErrorNotCleanablePath'] = 'A fájl nem törölhető árva vagy régi törölt fájl.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Törölhető fizikai fájlok';
$strings['NoCleanableFiles'] = 'Nincsenek törölhető fizikai fájlok ebben a tárolási gyökérben';
$strings['Chamilo2ResourceStorage'] = 'Erőforrás fájlok';
$strings['Chamilo2ResourceStorageHelp'] = 'A var/upload/resource alatt lévő fájlok csak akkor jelennek meg, ha nincs rájuk hivatkozás a resource_file táblában. A dokumentumokat a Dokumentumok eszközből vagy API-ból törölje, ne ebből a bővítményből.';
$strings['Chamilo2AssetStorage'] = 'Eszköz fájlok';
$strings['Chamilo2AssetStorageHelp'] = 'A var/upload/assets alatt lévő fájlok csak akkor jelennek meg, ha nincs rájuk hivatkozás az asset táblában. A hivatkozott eszközfájlok mindig védettek.';
$strings['LegacyCourseFiles'] = 'Régi kurzusfájlok';
$strings['LegacyUploadFiles'] = 'Régi feltöltött fájlok';
$strings['LegacyPublicCourseFiles'] = 'Régi nyilvános kurzusfájlok';
$strings['LegacyPublicUploadFiles'] = 'Régi nyilvános feltöltött fájlok';
$strings['LegacyDeletedStorageHelp'] = 'A régi gyökerek csak azokra a fájlokra vizsgálódnak, amelyek alapneve tartalmazza a DELETED jelölőt.';
$strings['OrphanResourceFile'] = 'Árva erőforrás fájl';
$strings['OrphanAssetFile'] = 'Árva eszköz fájl';
$strings['LegacyDeletedFile'] = 'Régi DELETED fájl';
$strings['OrphanResourceFiles'] = 'Árva erőforrás fájlok';
$strings['OrphanAssetFiles'] = 'Árva eszköz fájlok';
$strings['LegacyDeletedFiles'] = 'Régi DELETED fájlok';
$strings['Reason'] = 'Ok';
$strings['ReasonOrphanResource'] = 'A fizikai fájl létezik az erőforrás-tárolóban, de nincs rá mutató resource_file rekord.';
$strings['ReasonOrphanAsset'] = 'A fizikai fájl létezik az eszköz-tárolóban, de nincs rá mutató asset rekord.';
$strings['ReasonLegacyDeletedMarker'] = 'A régi fájl alapneve tartalmazza a DELETED jelölőt.';

$strings['StorageNoticeShort'] = 'A feltöltött fájlokat a resource_file és asset metaadatok követik. Ez a bővítmény csak a var/upload/resource és var/upload/assets alatt lévő, már nem hivatkozott fizikai fájlokat listázza.';
$strings['SafeNoticeShort'] = 'Az érvényes adatbázis-hivatkozással rendelkező fájl védett. A dokumentumokat és fájlokat továbbra is a szokásos eszközeiken keresztül kell törölni.';
$strings['CheckedLocations'] = 'Ellenőrzött helyek';
$strings['DetectionRule'] = 'Észlelési szabály';
$strings['NoCleanableFilesFound'] = 'Nem található törölhető fizikai fájl';
$strings['NoCleanableFilesFoundHelp'] = 'Ez a várható eredmény, ha a tároló konzisztens. Az ellenőrzött helyek az átláthatóság érdekében lent láthatók.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Korlátozott vizsgálat futtatása';
$strings['ScanNotRun'] = 'A vizsgálat nem indult el';
$strings['ScanNotRunHelp'] = 'Az oldal nem vizsgálja automatikusan a tárolót, mert a nagy var/upload mappák lassúak lehetnek. Kattintson a Korlátozott vizsgálat futtatása gombra a helyi árva fájlok ellenőrzéséhez.';
$strings['ScanLimitedWarning'] = 'A vizsgálat idő előtt leállt az oldal válaszidejének megőrzése érdekében. Futtassa újra később, vagy ellenőrizze a tárolót parancssorból, ha szükséges.';

$strings['PathFilter'] = 'Útvonal szűrő';
$strings['PathFilterHelp'] = 'Opcionális. Használja ezt egy adott mappa teszteléséhez vagy áttekintéséhez, például clean-deleted-files-test. Csak relatív útvonalakra illeszkedik.';
$strings['ActivePathFilter'] = 'Aktív útvonal szűrő: %s';
