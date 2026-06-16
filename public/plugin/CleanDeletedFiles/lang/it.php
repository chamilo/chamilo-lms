<?php
$strings['plugin_title'] = 'Pulisci file eliminati';
$strings['plugin_comment'] = 'Elimina permanentemente i file contrassegnati come eliminati. Abilitalo nella sezione menu_amministratore quindi accedivi dalla pagina admin principale.';
$strings['FileList'] = 'Elenco dei file contrassegnati come eliminati';
$strings['SizeTotalAllDir'] = 'Dimensione totale (tutte le directory)';
$strings['NoFilesDeleted'] = 'Non ci sono file contrassegnati come eliminati';
$strings['FilesDeletedMark'] = 'File contrassegnati come eliminati';
$strings['FileDirSize'] = 'Dimensione file directory';
$strings['ConfirmDelete'] = 'Sei sicuro di voler eliminare il file?';
$strings['ErrorDeleteFile'] = "Si è verificato un errore durante l'eliminazione del file";
$strings['ErrorEmptyPath'] = "Si è verificato un problema durante l'eliminazione del file, il percorso non può essere vuoto";
$strings['DeleteSelectedFiles'] = 'Elimina file selezionati';
$strings['ConfirmDeleteFiles'] = 'Sei sicuro di voler eliminare tutti i file selezionati?';
$strings['DeletedSuccess'] = "L'eliminazione del file è avvenuta con successo";
$strings['path_dir'] = 'Directory';
$strings['size'] = 'Dimensione';
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
$strings['ErrorNotCleanablePath'] = 'Il file non è un orfano pulibile o un file eliminato legacy.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'File fisici pulibili';
$strings['NoCleanableFiles'] = 'Non ci sono file fisici pulibili in questa radice di archiviazione';
$strings['Chamilo2ResourceStorage'] = 'File di risorse';
$strings['Chamilo2ResourceStorageHelp'] = "I file in var/upload/resource vengono elencati solo se non sono referenziati da resource_file. Elimina i documenti dallo strumento Documenti o dall'API, non da questo plugin.";
$strings['Chamilo2AssetStorage'] = 'File di asset';
$strings['Chamilo2AssetStorageHelp'] = 'I file in var/upload/assets vengono elencati solo se non sono referenziati da asset. Gli asset referenziati sono sempre protetti.';
$strings['LegacyCourseFiles'] = 'File legacy dei corsi';
$strings['LegacyUploadFiles'] = 'File di upload legacy';
$strings['LegacyPublicCourseFiles'] = 'File pubblici legacy dei corsi';
$strings['LegacyPublicUploadFiles'] = 'File di upload pubblici legacy';
$strings['LegacyDeletedStorageHelp'] = 'Le radici legacy vengono analizzate solo per i file il cui nome base contiene il marcatore DELETED.';
$strings['OrphanResourceFile'] = 'File di risorsa orfano';
$strings['OrphanAssetFile'] = 'File di asset orfano';
$strings['LegacyDeletedFile'] = 'File DELETED legacy';
$strings['OrphanResourceFiles'] = 'File di risorse orfani';
$strings['OrphanAssetFiles'] = 'File di asset orfani';
$strings['LegacyDeletedFiles'] = 'File DELETED legacy';
$strings['Reason'] = 'Motivo';
$strings['ReasonOrphanResource'] = "Il file fisico esiste nell'archivio delle risorse ma nessuna riga resource_file lo punta.";
$strings['ReasonOrphanAsset'] = "Il file fisico esiste nell'archivio degli asset ma nessuna riga asset lo punta.";
$strings['ReasonLegacyDeletedMarker'] = 'Il nome base del file legacy contiene il marcatore DELETED.';

$strings['StorageNoticeShort'] = 'I file caricati sono tracciati tramite i metadati di resource_file e asset. Questo plugin elenca solo i file fisici in var/upload/resource e var/upload/assets che non sono più referenziati.';
$strings['SafeNoticeShort'] = 'Un file con un riferimento valido al database è protetto. I documenti e i file devono essere eliminati tramite i loro strumenti normali.';
$strings['CheckedLocations'] = 'Posizioni controllate';
$strings['DetectionRule'] = 'Regola di rilevamento';
$strings['NoCleanableFilesFound'] = 'Nessun file fisico pulibile trovato';
$strings['NoCleanableFilesFoundHelp'] = "Questo è il risultato previsto quando l'archiviazione è coerente. Le posizioni controllate sono mostrate di seguito per trasparenza.";

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Esegui scansione limitata';
$strings['ScanNotRun'] = 'Scansione non avviata';
$strings['ScanNotRunHelp'] = "La pagina non esegue automaticamente la scansione dell'archivio perché le cartelle var/upload di grandi dimensioni possono essere lente. Clicca su Esegui scansione limitata per ispezionare i file orfani locali.";
$strings['ScanLimitedWarning'] = "La scansione è stata interrotta in anticipo per mantenere la pagina reattiva. Esegui di nuovo più tardi o ispeziona l'archivio dalla riga di comando se necessario.";

$strings['PathFilter'] = 'Filtro percorso';
$strings['PathFilterHelp'] = 'Opzionale. Usalo per testare o esaminare una cartella specifica, ad esempio clean-deleted-files-test. Corrisponde solo ai percorsi relativi.';
$strings['ActivePathFilter'] = 'Filtro percorso attivo: %s';
