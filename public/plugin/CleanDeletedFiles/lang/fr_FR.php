<?php
$strings['plugin_title'] = 'Nettoyer les fichiers supprimés';
$strings['plugin_comment'] = "Élimine de manière définitive les fichiers marqués comme éliminés. Activer le plugin dans la région 'menu_administrator' puis y accéder depuis la page d'administration.";
$strings['FileList'] = 'Liste des fichiers marqués comme éliminés';
$strings['SizeTotalAllDir'] = 'Taille totale (tous les répertoires)';
$strings['NoFilesDeleted'] = "Il n'y a pas de fichiers marqués comme supprimés";
$strings['FilesDeletedMark'] = 'Fichiers marqués comme supprimés';
$strings['FileDirSize'] = 'Taille des fichiers du répertoire';
$strings['ConfirmDelete'] = 'Êtes-vous certain de vouloir supprimer le fichier?';
$strings['ErrorDeleteFile'] = 'Une erreur a empêché la suppression du fichier';
$strings['ErrorEmptyPath'] = 'Il y a eu un problème au moment de supprimer le fichier. Le chemin ne peut être vide.';
$strings['DeleteSelectedFiles'] = 'Supprimer les fichiers sélectionnés';
$strings['ConfirmDeleteFiles'] = 'Êtes-vous certain de vouloir supprimer tous les fichiers sélectionnés?';
$strings['DeletedSuccess'] = "La suppression des fichiers s'est bien déroulée.";
$strings['path_dir'] = 'Répertoire';
$strings['size'] = 'Taille';
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
$strings['ErrorNotCleanablePath'] = "Le fichier n'est pas un orphelin nettoyable ou un fichier supprimé hérité.";
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Fichiers physiques nettoyables';
$strings['NoCleanableFiles'] = "Il n'y a aucun fichier physique nettoyable dans cette racine de stockage";
$strings['Chamilo2ResourceStorage'] = 'Fichiers de ressources';
$strings['Chamilo2ResourceStorageHelp'] = "Les fichiers sous var/upload/resource sont listés uniquement lorsqu'ils ne sont pas référencés par resource_file. Supprimez les documents depuis l'outil Documents ou l'API, et non depuis ce plugin.";
$strings['Chamilo2AssetStorage'] = "Fichiers d'assets";
$strings['Chamilo2AssetStorageHelp'] = "Les fichiers sous var/upload/assets sont listés uniquement lorsqu'ils ne sont pas référencés par asset. Les assets référencés sont toujours protégés.";
$strings['LegacyCourseFiles'] = 'Fichiers de cours hérités';
$strings['LegacyUploadFiles'] = "Fichiers d'envoi hérités";
$strings['LegacyPublicCourseFiles'] = 'Fichiers de cours publics hérités';
$strings['LegacyPublicUploadFiles'] = "Fichiers d'envoi publics hérités";
$strings['LegacyDeletedStorageHelp'] = 'Les racines héritées sont analysées uniquement pour les fichiers dont le nom de base contient le marqueur DELETED.';
$strings['OrphanResourceFile'] = 'Fichier de ressource orphelin';
$strings['OrphanAssetFile'] = "Fichier d'asset orphelin";
$strings['LegacyDeletedFile'] = 'Fichier DELETED hérité';
$strings['OrphanResourceFiles'] = 'Fichiers de ressources orphelins';
$strings['OrphanAssetFiles'] = "Fichiers d'assets orphelins";
$strings['LegacyDeletedFiles'] = 'Fichiers DELETED hérités';
$strings['Reason'] = 'Raison';
$strings['ReasonOrphanResource'] = 'Le fichier physique existe dans le stockage des ressources mais aucune ligne resource_file ne le référence.';
$strings['ReasonOrphanAsset'] = 'Le fichier physique existe dans le stockage des assets mais aucune ligne asset ne le référence.';
$strings['ReasonLegacyDeletedMarker'] = 'Le nom de base du fichier hérité contient le marqueur DELETED.';

$strings['StorageNoticeShort'] = 'Les fichiers envoyés sont suivis via les métadonnées resource_file et asset. Ce plugin liste uniquement les fichiers physiques sous var/upload/resource et var/upload/assets qui ne sont plus référencés.';
$strings['SafeNoticeShort'] = 'Un fichier avec une référence valide en base de données est protégé. Les documents et fichiers doivent toujours être supprimés via leurs outils habituels.';
$strings['CheckedLocations'] = 'Emplacements vérifiés';
$strings['DetectionRule'] = 'Règle de détection';
$strings['NoCleanableFilesFound'] = 'Aucun fichier physique nettoyable trouvé';
$strings['NoCleanableFilesFoundHelp'] = 'Ceci est le résultat attendu lorsque le stockage est cohérent. Les emplacements vérifiés sont affichés ci-dessous pour transparence.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Exécuter une analyse limitée';
$strings['ScanNotRun'] = 'Analyse non démarrée';
$strings['ScanNotRunHelp'] = "La page n'analyse pas le stockage automatiquement car les grands dossiers var/upload peuvent être lents. Cliquez sur Exécuter une analyse limitée pour inspecter les fichiers orphelins locaux.";
$strings['ScanLimitedWarning'] = "L'analyse a été arrêtée prématurément pour conserver la réactivité de la page. Relancez-la plus tard ou inspectez le stockage depuis la ligne de commande si nécessaire.";

$strings['PathFilter'] = 'Filtre de chemin';
$strings['PathFilterHelp'] = 'Optionnel. Utilisez-le pour tester ou examiner un dossier spécifique, par exemple clean-deleted-files-test. Il correspond uniquement aux chemins relatifs.';
$strings['ActivePathFilter'] = 'Filtre de chemin actif : %s';
