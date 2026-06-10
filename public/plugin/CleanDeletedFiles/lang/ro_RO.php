<?php
$strings['plugin_title'] = 'Curăță fișierele șterse';
$strings['plugin_comment'] = 'Șterge permanent fișierele marcate ca șterse. Activează-l în regiunea menu_administrator apoi accesează-l de pe pagina principală de administrare.';
$strings['FileList'] = 'Lista fișierelor marcate ca șterse';
$strings['SizeTotalAllDir'] = 'Dimensiune totală (toate directoarele)';
$strings['NoFilesDeleted'] = 'Nu există fișiere marcate ca șterse';
$strings['FilesDeletedMark'] = 'Fișiere marcate ca șterse';
$strings['FileDirSize'] = 'Dimensiunea fișierelor din director';
$strings['ConfirmDelete'] = 'Ești sigur că vrei să ștergi fișierul?';
$strings['ErrorDeleteFile'] = 'A apărut o eroare în timpul ștergerii fișierului';
$strings['ErrorEmptyPath'] = 'A apărut o problemă la ștergerea fișierului, calea nu poate fi goală';
$strings['DeleteSelectedFiles'] = 'Șterge fișierele selectate';
$strings['ConfirmDeleteFiles'] = 'Ești sigur că vrei să ștergi toate fișierele selectate?';
$strings['DeletedSuccess'] = 'Ștergerea fișierului a fost reușită';
$strings['path_dir'] = 'Director';
$strings['size'] = 'Dimensiune';
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
$strings['ErrorNotCleanablePath'] = 'Fișierul nu este un orfan curățabil sau un fișier șters vechi.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Fișiere fizice curățabile';
$strings['NoCleanableFiles'] = 'Nu există fișiere fizice curățabile în această rădăcină de stocare';
$strings['Chamilo2ResourceStorage'] = 'Fișiere resursă';
$strings['Chamilo2ResourceStorageHelp'] = 'Fișierele din var/upload/resource sunt listate doar când nu sunt referențiate de resource_file. Ștergeți documentele din instrumentul Documente sau API, nu din acest plugin.';
$strings['Chamilo2AssetStorage'] = 'Fișiere asset';
$strings['Chamilo2AssetStorageHelp'] = 'Fișierele din var/upload/assets sunt listate doar când nu sunt referențiate de asset. Asset-urile referențiate sunt întotdeauna protejate.';
$strings['LegacyCourseFiles'] = 'Fișiere vechi de curs';
$strings['LegacyUploadFiles'] = 'Fișiere vechi de încărcare';
$strings['LegacyPublicCourseFiles'] = 'Fișiere vechi publice de curs';
$strings['LegacyPublicUploadFiles'] = 'Fișiere vechi publice de încărcare';
$strings['LegacyDeletedStorageHelp'] = 'Rădăcinile vechi sunt scanate doar pentru fișiere al căror nume de bază conține marcajul DELETED.';
$strings['OrphanResourceFile'] = 'Fișier resursă orfan';
$strings['OrphanAssetFile'] = 'Fișier asset orfan';
$strings['LegacyDeletedFile'] = 'Fișier vechi DELETED';
$strings['OrphanResourceFiles'] = 'Fișiere resursă orfane';
$strings['OrphanAssetFiles'] = 'Fișiere asset orfane';
$strings['LegacyDeletedFiles'] = 'Fișiere vechi DELETED';
$strings['Reason'] = 'Motiv';
$strings['ReasonOrphanResource'] = 'Fișierul fizic există în stocarea de resurse, dar niciun rând resource_file nu îl indică.';
$strings['ReasonOrphanAsset'] = 'Fișierul fizic există în stocarea de asset-uri, dar niciun rând asset nu îl indică.';
$strings['ReasonLegacyDeletedMarker'] = 'Numele de bază al fișierului vechi conține marcajul DELETED.';

$strings['StorageNoticeShort'] = 'Fișierele încărcate sunt urmărite prin metadatele resource_file și asset. Acest plugin listează doar fișierele fizice din var/upload/resource și var/upload/assets care nu mai sunt referențiate.';
$strings['SafeNoticeShort'] = 'Un fișier cu o referință validă în baza de date este protejat. Documentele și fișierele ar trebui șterse în continuare prin instrumentele lor normale.';
$strings['CheckedLocations'] = 'Locații verificate';
$strings['DetectionRule'] = 'Regulă de detecție';
$strings['NoCleanableFilesFound'] = 'Nu s-au găsit fișiere fizice curățabile';
$strings['NoCleanableFilesFoundHelp'] = 'Acesta este rezultatul așteptat când stocarea este consistentă. Locațiile verificate sunt afișate mai jos pentru transparență.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Rulează scanare limitată';
$strings['ScanNotRun'] = 'Scanarea nu a început';
$strings['ScanNotRunHelp'] = 'Pagina nu scanează stocarea automat deoarece folderele var/upload mari pot fi lente. Faceți clic pe Rulează scanare limitată pentru a inspecta fișierele orfane locale.';
$strings['ScanLimitedWarning'] = 'Scanarea a fost oprită prematur pentru a menține pagina responsivă. Rulați-o din nou mai târziu sau inspectați stocarea din linia de comandă dacă este necesar.';

$strings['PathFilter'] = 'Filtru de cale';
$strings['PathFilterHelp'] = 'Opțional. Utilizați-l pentru a testa sau revizui un anumit dosar, de exemplu clean-deleted-files-test. Se potrivește doar cu căi relative.';
$strings['ActivePathFilter'] = 'Filtru de cale activ: %s';
