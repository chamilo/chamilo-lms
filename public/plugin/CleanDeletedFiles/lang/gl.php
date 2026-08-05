<?php
$strings['plugin_title'] = 'Limpar ficheiros eliminados';
$strings['plugin_comment'] = 'Eliminar permanentemente os ficheiros marcados como eliminados. Actíveo na rexión menu_administrator e acceda desde a páxina principal de administración.';
$strings['FileList'] = 'Lista de ficheiros marcados como eliminados';
$strings['SizeTotalAllDir'] = 'Tamaño total (todas as directorios)';
$strings['NoFilesDeleted'] = 'Non hai ficheiros marcados como eliminados';
$strings['FilesDeletedMark'] = 'Ficheiros marcados como eliminados';
$strings['FileDirSize'] = 'Tamaño dos ficheiros do directorio';
$strings['ConfirmDelete'] = 'Está seguro de que quere eliminar o ficheiro?';
$strings['ErrorDeleteFile'] = 'Produciuse un erro ao eliminar o ficheiro';
$strings['ErrorEmptyPath'] = 'Houbo un problema ao eliminar o ficheiro, a ruta non pode estar baleira';
$strings['DeleteSelectedFiles'] = 'Eliminar ficheiros seleccionados';
$strings['ConfirmDeleteFiles'] = 'Está seguro de que quere eliminar todos os ficheiros seleccionados?';
$strings['DeletedSuccess'] = 'A eliminación do ficheiro foi exitosa';
$strings['path_dir'] = 'Directorio';
$strings['size'] = 'Tamaño';
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
$strings['ErrorNotCleanablePath'] = 'O ficheiro non é un orfo limpábel ou un ficheiro eliminado por herdanza.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Ficheiros físicos limpábeis';
$strings['NoCleanableFiles'] = 'Non hai ficheiros físicos limpábeis neste directorio raíz de almacenamento';
$strings['Chamilo2ResourceStorage'] = 'Ficheiros de recursos';
$strings['Chamilo2ResourceStorageHelp'] = 'Os ficheiros en var/upload/resource só se listan cando non están referenciados por resource_file. Elimine os documentos desde a ferramenta Documentos ou a API, non desde este complemento.';
$strings['Chamilo2AssetStorage'] = 'Ficheiros de activos';
$strings['Chamilo2AssetStorageHelp'] = 'Os ficheiros en var/upload/assets só se listan cando non están referenciados por asset. Os activos referenciados están sempre protexidos.';
$strings['LegacyCourseFiles'] = 'Ficheiros de cursos herdados';
$strings['LegacyUploadFiles'] = 'Ficheiros de carga herdados';
$strings['LegacyPublicCourseFiles'] = 'Ficheiros públicos de cursos herdados';
$strings['LegacyPublicUploadFiles'] = 'Ficheiros públicos de carga herdados';
$strings['LegacyDeletedStorageHelp'] = 'Os directorios raíz herdados só se analizan para ficheiros cuxo nome base contén o marcador DELETED.';
$strings['OrphanResourceFile'] = 'Ficheiro de recurso orfo';
$strings['OrphanAssetFile'] = 'Ficheiro de activo orfo';
$strings['LegacyDeletedFile'] = 'Ficheiro DELETED herdado';
$strings['OrphanResourceFiles'] = 'Ficheiros de recursos orfos';
$strings['OrphanAssetFiles'] = 'Ficheiros de activos orfos';
$strings['LegacyDeletedFiles'] = 'Ficheiros DELETED herdados';
$strings['Reason'] = 'Motivo';
$strings['ReasonOrphanResource'] = 'O ficheiro físico existe no almacenamento de recursos pero ningunha fila de resource_file apunta a el.';
$strings['ReasonOrphanAsset'] = 'O ficheiro físico existe no almacenamento de activos pero ningunha fila de asset apunta a el.';
$strings['ReasonLegacyDeletedMarker'] = 'O nome base do ficheiro herdado contén o marcador DELETED.';

$strings['StorageNoticeShort'] = 'Os ficheiros cargados son rastrexados a través dos metadatos de resource_file e asset. Este complemento só lista os ficheiros físicos en var/upload/resource e var/upload/assets que xa non están referenciados.';
$strings['SafeNoticeShort'] = 'Un ficheiro cunha referencia válida na base de datos está protexido. Os documentos e ficheiros deben eliminarse a través das súas ferramentas habituais.';
$strings['CheckedLocations'] = 'Localizacións comprobadas';
$strings['DetectionRule'] = 'Regra de detección';
$strings['NoCleanableFilesFound'] = 'Non se atoparon ficheiros físicos limpábeis';
$strings['NoCleanableFilesFoundHelp'] = 'Este é o resultado esperado cando o almacenamento é consistente. As localizacións comprobadas móstranse a continuación para maior transparencia.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Executar análise limitada';
$strings['ScanNotRun'] = 'Análise non iniciada';
$strings['ScanNotRunHelp'] = 'A páxina non analiza o almacenamento automaticamente porque os cartafoles var/upload grandes poden ser lentos. Faga clic en Executar análise limitada para inspeccionar ficheiros orfos locais.';
$strings['ScanLimitedWarning'] = 'A análise detívose prematuramente para manter a páxina responsiva. Execútea de novo máis tarde ou inspeccione o almacenamento desde a liña de comandos se é necesario.';

$strings['PathFilter'] = 'Filtro de ruta';
$strings['PathFilterHelp'] = 'Opcional. Úseo para probar ou revisar un cartafol específico, por exemplo clean-deleted-files-test. Só coincide con rutas relativas.';
$strings['ActivePathFilter'] = 'Filtro de ruta activo: %s';
