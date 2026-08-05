<?php
$strings['plugin_title'] = "Limpiar ficheros borrados";
$strings['plugin_comment'] = "Lista y elimina de forma segura archivos físicos huérfanos del almacenamiento local de recursos y assets de Chamilo 2. Active el plugin en la región menu_administrator y acceda desde la página principal de administración.";
$strings['FileList'] = "Limpiar ficheros borrados";
$strings['SizeTotalAllDir'] = "Tamaño total";
$strings['NoFilesDeleted'] = "No hay ficheros marcados como eliminados";
$strings['FilesDeletedMark'] = "Ficheros marcados como eliminados";
$strings['FileDirSize'] = "Tamaño de archivos";
$strings['ConfirmDelete'] = "¿Está seguro que desea borrar de forma permanente este archivo físico?";
$strings['ErrorDeleteFile'] = "Se ha producido un error al borrar el fichero";
$strings['ErrorEmptyPath'] = "Ha habido un problema al borrar el fichero, la ruta no puede ser vacía";
$strings['DeleteSelectedFiles'] = "Borrar archivos seleccionados";
$strings['ConfirmDeleteFiles'] = "¿Está seguro que desea borrar de forma permanente todos los archivos físicos seleccionados?";
$strings['DeletedSuccess'] = "El borrado de archivos ha sido correcto";
$strings['path_dir'] = "Directorio";
$strings['size'] = "Tamaño";
$strings['ScanSummary'] = "Resumen del análisis";
$strings['Chamilo2StorageNotice'] = "Los archivos subidos se rastrean mediante metadata en resource_file y asset. Este plugin no borra documentos, recursos, assets ni filas de base de datos; solo lista archivos físicos que ya no están referenciados.";
$strings['SafeDryRunNotice'] = "El análisis es intencionalmente conservador. Los archivos de recursos y assets solo se listan cuando existen en disco pero faltan en su tabla de metadata.";
$strings['RelativePath'] = "Ruta relativa";
$strings['StorageType'] = "Tipo de almacenamiento";
$strings['Status'] = "Estado";
$strings['CanBeDeleted'] = "Se puede borrar";
$strings['ProtectedReferenced'] = "Protegido / referenciado";
$strings['ReferencedFileWarning'] = "Si un archivo todavía está referenciado por resource_file o asset, no se lista aquí. Borra documentos/archivos mediante las interfaces normales de Chamilo 2 para que Symfony, Doctrine y Vich/Flysystem limpien metadata y almacenamiento de forma consistente.";
$strings['DeleteUnavailableReferenced'] = "Este archivo todavía está referenciado por metadata de Chamilo y no se puede borrar aquí.";
$strings['DeleteSingle'] = "Borrar este archivo físico";
$strings['ErrorInvalidToken'] = "El token de seguridad no es válido. Recarga la página e inténtalo de nuevo.";
$strings['ErrorInvalidPath'] = "La ruta del archivo no es válida o está fuera de los directorios de almacenamiento permitidos.";
$strings['ErrorMissingDeletedMarker'] = "El archivo no está marcado con el indicador DELETED.";
$strings['ErrorReferencedPath'] = "El archivo todavía está referenciado por metadata de Chamilo y no se puede borrar aquí.";
$strings['ErrorNotCleanablePath'] = "El archivo no es un huérfano limpiable ni un archivo legacy marcado como borrado.";
$strings['DeletedFilesCount'] = "Archivos borrados";
$strings['SkippedFilesCount'] = "Archivos omitidos";
$strings['NoSelection'] = "No se seleccionaron archivos.";
$strings['CleanableFiles'] = "Archivos físicos limpiables";
$strings['NoCleanableFiles'] = "No hay archivos físicos limpiables en esta raíz de almacenamiento";
$strings['Chamilo2ResourceStorage'] = "Archivos de recursos";
$strings['Chamilo2ResourceStorageHelp'] = "Los archivos en var/upload/resource solo se listan cuando no están referenciados por resource_file. Borra documentos desde la herramienta Documentos o la API, no desde este plugin.";
$strings['Chamilo2AssetStorage'] = "Archivos de assets";
$strings['Chamilo2AssetStorageHelp'] = "Los archivos en var/upload/assets solo se listan cuando no están referenciados por asset. Los assets referenciados siempre quedan protegidos.";
$strings['LegacyCourseFiles'] = "Archivos legacy de cursos";
$strings['LegacyUploadFiles'] = "Archivos legacy de upload";
$strings['LegacyPublicCourseFiles'] = "Archivos públicos legacy de cursos";
$strings['LegacyPublicUploadFiles'] = "Archivos públicos legacy de upload";
$strings['LegacyDeletedStorageHelp'] = "Las raíces legacy solo se escanean buscando archivos cuyo nombre contiene el marcador DELETED.";
$strings['OrphanResourceFile'] = "Archivo de recurso huérfano";
$strings['OrphanAssetFile'] = "Archivo de asset huérfano";
$strings['LegacyDeletedFile'] = "Archivo legacy DELETED";
$strings['OrphanResourceFiles'] = "Archivos de recursos huérfanos";
$strings['OrphanAssetFiles'] = "Archivos de assets huérfanos";
$strings['LegacyDeletedFiles'] = "Archivos legacy DELETED";
$strings['Reason'] = "Razón";
$strings['ReasonOrphanResource'] = "El archivo físico existe en el almacenamiento de recursos pero ninguna fila de resource_file apunta a él.";
$strings['ReasonOrphanAsset'] = "El archivo físico existe en el almacenamiento de assets pero ninguna fila de asset apunta a él.";
$strings['ReasonLegacyDeletedMarker'] = "El nombre del archivo legacy contiene el marcador DELETED.";

$strings['StorageNoticeShort'] = "Los archivos subidos se rastrean mediante metadata en resource_file y asset. Este plugin solo lista archivos físicos bajo var/upload/resource y var/upload/assets que ya no están referenciados.";
$strings['SafeNoticeShort'] = "Un archivo con referencia válida en base de datos queda protegido. Los documentos y archivos deben seguir borrándose desde sus herramientas normales.";
$strings['CheckedLocations'] = "Ubicaciones revisadas";
$strings['DetectionRule'] = "Regla de detección";
$strings['NoCleanableFilesFound'] = "No se encontraron archivos físicos limpiables";
$strings['NoCleanableFilesFoundHelp'] = "Este es el resultado esperado cuando el almacenamiento está consistente. Las ubicaciones revisadas se muestran abajo para transparencia.";

$strings['ResourceFiles'] = "Archivos de recursos";

$strings['ResourceStorageHelp'] = "Los archivos en var/upload/resource solo se listan cuando no están referenciados por resource_file. Borra documentos desde la herramienta Documentos o la API, no desde este plugin.";

$strings['AssetFiles'] = "Archivos de assets";

$strings['AssetStorageHelp'] = "Los archivos en var/upload/assets solo se listan cuando no están referenciados por asset. Los assets referenciados siempre quedan protegidos.";

$strings['RunLimitedScan'] = "Ejecutar análisis limitado";
$strings['ScanNotRun'] = "Análisis no iniciado";
$strings['ScanNotRunHelp'] = "La página no analiza el almacenamiento automáticamente porque las carpetas grandes de var/upload pueden ser lentas. Haz clic en Ejecutar análisis limitado para revisar archivos huérfanos locales.";
$strings['ScanLimitedWarning'] = "El análisis se detuvo antes de terminar para mantener la página responsive. Ejecútalo de nuevo más tarde o revisa el almacenamiento desde línea de comandos si es necesario.";

$strings['PathFilter'] = "Filtro de ruta";
$strings['PathFilterHelp'] = "Opcional. Úsalo para probar o revisar una carpeta específica, por ejemplo clean-deleted-files-test. Solo compara rutas relativas.";
$strings['ActivePathFilter'] = "Filtro de ruta activo: %s";
