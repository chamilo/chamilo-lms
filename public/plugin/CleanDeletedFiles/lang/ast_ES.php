<?php
$strings['plugin_title'] = 'Llimpiar archivos desaniciaos';
$strings['plugin_comment'] = "Desaniciar permanentemente los archivos marcaos como desaniciaos. Activalu na rexón menú_administrador y accedé a él dende la páxina principal d'administración.";
$strings['FileList'] = "Llista d'archivos marcaos como desaniciaos";
$strings['SizeTotalAllDir'] = 'Tamañu total (toes les directorios)';
$strings['NoFilesDeleted'] = 'Nun hai archivos marcaos como desaniciaos';
$strings['FilesDeletedMark'] = 'Archivos marcaos como desaniciaos';
$strings['FileDirSize'] = "Tamañu d'archivos del direutoriu";
$strings['ConfirmDelete'] = "¿Tas seguru de que quies desaniciar l'archivu?";
$strings['ErrorDeleteFile'] = "He producido un fallu al desaniciar l'archivu";
$strings['ErrorEmptyPath'] = "He habéu un problema al desaniciar l'archivu, la ruta nun pue tar balera";
$strings['DeleteSelectedFiles'] = 'Desaniciar archivos seleicionaos';
$strings['ConfirmDeleteFiles'] = '¿Tas seguru de que quies desaniciar tolos archivos seleicionaos?';
$strings['DeletedSuccess'] = 'La desaniciación del archivu realizóse con èxitu';
$strings['path_dir'] = 'Direutoriu';
$strings['size'] = 'Tamañu';
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
$strings['ErrorNotCleanablePath'] = 'El ficheru nun ye un ficheru orfánu llimpiable nin un ficheru heredáu desaniciáu.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Ficheros físicos llimpiables';
$strings['NoCleanableFiles'] = "Nun hai ficheros físicos llimpiables nesti raigañu d'almacenamientu";
$strings['Chamilo2ResourceStorage'] = 'Ficheros de recursos';
$strings['Chamilo2ResourceStorageHelp'] = 'Los ficheros en var/upload/resource llistense namái cuando nun son referenciaos por resource_file. Desanicie documentos dende la ferramienta Documentos o la API, non dende esti plugin.';
$strings['Chamilo2AssetStorage'] = "Ficheros d'activos";
$strings['Chamilo2AssetStorageHelp'] = 'Los ficheros en var/upload/assets llistense namái cuando nun son referenciaos por asset. Los activos referenciaos tán siempres protexíos.';
$strings['LegacyCourseFiles'] = 'Ficheros heredáu de cursos';
$strings['LegacyUploadFiles'] = 'Ficheros heredáu de xubíes';
$strings['LegacyPublicCourseFiles'] = 'Ficheros heredáu públicos de cursos';
$strings['LegacyPublicUploadFiles'] = 'Ficheros heredáu públicos de xubíes';
$strings['LegacyDeletedStorageHelp'] = "Los raigañu heredáu escanéense namái pa ficheros que'l so nome contenga'l marcador DELETED.";
$strings['OrphanResourceFile'] = 'Ficheru de recursu orfánu';
$strings['OrphanAssetFile'] = "Ficheru d'activu orfánu";
$strings['LegacyDeletedFile'] = 'Ficheru heredáu DELETED';
$strings['OrphanResourceFiles'] = 'Ficheros de recursu orfanos';
$strings['OrphanAssetFiles'] = "Ficheros d'activu orfanos";
$strings['LegacyDeletedFiles'] = 'Ficheros heredáu DELETED';
$strings['Reason'] = 'Razón';
$strings['ReasonOrphanResource'] = 'El ficheru físicu esiste nel almacenamientu de recursos pero nenguna filera de resource_file apúntalu.';
$strings['ReasonOrphanAsset'] = "El ficheru físicu esiste nel almacenamientu d'activos pero nenguna filera d'activos apúntalu.";
$strings['ReasonLegacyDeletedMarker'] = 'El nome del ficheru heredáu contién el marcador DELETED.';

$strings['StorageNoticeShort'] = 'Los ficheros xubíos sigúense a traviés de los metadatos de resource_file y asset. Esti plugin namái llista los ficheros físicos en var/upload/resource y var/upload/assets que yá nun son referenciaos.';
$strings['SafeNoticeShort'] = 'Un ficheru con una referencia válida na base de datos ta protexíu. Los documentos y ficheros deberíen desaniciase siempres a traviés de les sos ferramientes normales.';
$strings['CheckedLocations'] = 'Allugamientos revisaos';
$strings['DetectionRule'] = 'Regla de deteición';
$strings['NoCleanableFilesFound'] = "Nun s'atoparon ficheros físicos llimpiables";
$strings['NoCleanableFilesFoundHelp'] = "Esti ye'l resultáu esperáu cuando l'almacenamientu ye consistente. Los allugamientos revisaos amuésense abaxo pa mayor tresparencia.";

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Executar escanéu acutáu';
$strings['ScanNotRun'] = 'Escanéu non aniciáu';
$strings['ScanNotRunHelp'] = "La páxina nun escanea l'almacenamientu automáticamente porque carpetes var/upload grandes pueden ser lentes. Calca n'Executar escanéu acutáu pa inspeicionar ficheros orfanos llocales.";
$strings['ScanLimitedWarning'] = "L'escanéu paróse enantes pa caltener la páxina responsiva. Executalu de nuevu más tarde o inspeiciona l'almacenamientu dende la llinia de comandos si ye necesario.";

$strings['PathFilter'] = 'Filtru de camín';
$strings['PathFilterHelp'] = 'Opcional. Úsalu pa probar o revisar una carpeta específica, por exemplu clean-deleted-files-test. Namái concasa con caminos relativos.';
$strings['ActivePathFilter'] = 'Filtru de camín activu: %s';
