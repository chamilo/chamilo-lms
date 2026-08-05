<?php
$strings['plugin_title'] = 'Limpar ficheiros eliminados';
$strings['plugin_comment'] = 'Eliminar permanentemente os ficheiros marcados como eliminados. Ative-o na região menu_administrator e depois aceda a partir da página principal de administração.';
$strings['FileList'] = 'Lista de ficheiros marcados como eliminados';
$strings['SizeTotalAllDir'] = 'Tamanho total (todas as diretórias)';
$strings['NoFilesDeleted'] = 'Não existem ficheiros marcados como eliminados';
$strings['FilesDeletedMark'] = 'Ficheiros marcados como eliminados';
$strings['FileDirSize'] = 'Tamanho dos ficheiros da diretória';
$strings['ConfirmDelete'] = 'Tem a certeza de que pretende eliminar o ficheiro?';
$strings['ErrorDeleteFile'] = 'Ocorreu um erro ao eliminar o ficheiro';
$strings['ErrorEmptyPath'] = 'Houve um problema ao eliminar o ficheiro, o caminho não pode estar vazio';
$strings['DeleteSelectedFiles'] = 'Eliminar ficheiros selecionados';
$strings['ConfirmDeleteFiles'] = 'Tem a certeza de que pretende eliminar todos os ficheiros selecionados?';
$strings['DeletedSuccess'] = 'A eliminação do ficheiro foi bem-sucedida';
$strings['path_dir'] = 'Diretória';
$strings['size'] = 'Tamanho';
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
$strings['ErrorNotCleanablePath'] = 'O ficheiro não é um órfão limpo ou um ficheiro eliminado legado.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Ficheiros físicos limpos';
$strings['NoCleanableFiles'] = 'Não existem ficheiros físicos limpos nesta raiz de armazenamento';
$strings['Chamilo2ResourceStorage'] = 'Ficheiros de recursos';
$strings['Chamilo2ResourceStorageHelp'] = 'Os ficheiros em var/upload/resource são listados apenas quando não são referenciados por resource_file. Elimine documentos a partir da ferramenta Documentos ou da API, não a partir deste plugin.';
$strings['Chamilo2AssetStorage'] = 'Ficheiros de recursos';
$strings['Chamilo2AssetStorageHelp'] = 'Os ficheiros em var/upload/assets são listados apenas quando não são referenciados por asset. Os recursos referenciados estão sempre protegidos.';
$strings['LegacyCourseFiles'] = 'Ficheiros de curso legados';
$strings['LegacyUploadFiles'] = 'Ficheiros de carregamento legados';
$strings['LegacyPublicCourseFiles'] = 'Ficheiros de curso públicos legados';
$strings['LegacyPublicUploadFiles'] = 'Ficheiros de carregamento públicos legados';
$strings['LegacyDeletedStorageHelp'] = 'As raízes legadas são analisadas apenas para ficheiros cujo nome base contém o marcador DELETED.';
$strings['OrphanResourceFile'] = 'Ficheiro de recurso órfão';
$strings['OrphanAssetFile'] = 'Ficheiro de recurso órfão';
$strings['LegacyDeletedFile'] = 'Ficheiro DELETED legado';
$strings['OrphanResourceFiles'] = 'Ficheiros de recursos órfãos';
$strings['OrphanAssetFiles'] = 'Ficheiros de recursos órfãos';
$strings['LegacyDeletedFiles'] = 'Ficheiros DELETED legados';
$strings['Reason'] = 'Motivo';
$strings['ReasonOrphanResource'] = 'O ficheiro físico existe no armazenamento de recursos mas nenhuma linha resource_file aponta para ele.';
$strings['ReasonOrphanAsset'] = 'O ficheiro físico existe no armazenamento de recursos mas nenhuma linha asset aponta para ele.';
$strings['ReasonLegacyDeletedMarker'] = 'O nome base do ficheiro legado contém o marcador DELETED.';

$strings['StorageNoticeShort'] = 'Os ficheiros carregados são rastreados através dos metadados resource_file e asset. Este plugin lista apenas os ficheiros físicos em var/upload/resource e var/upload/assets que já não são referenciados.';
$strings['SafeNoticeShort'] = 'Um ficheiro com uma referência válida na base de dados está protegido. Os documentos e ficheiros devem continuar a ser eliminados através das suas ferramentas normais.';
$strings['CheckedLocations'] = 'Localizações verificadas';
$strings['DetectionRule'] = 'Regra de deteção';
$strings['NoCleanableFilesFound'] = 'Nenhum ficheiro físico limpo encontrado';
$strings['NoCleanableFilesFoundHelp'] = 'Este é o resultado esperado quando o armazenamento é consistente. As localizações verificadas são mostradas abaixo para transparência.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Executar análise limitada';
$strings['ScanNotRun'] = 'Análise não iniciada';
$strings['ScanNotRunHelp'] = 'A página não analisa o armazenamento automaticamente porque pastas var/upload grandes podem ser lentas. Clique em Executar análise limitada para inspecionar ficheiros órfãos locais.';
$strings['ScanLimitedWarning'] = 'A análise foi interrompida prematuramente para manter a página responsiva. Execute-a novamente mais tarde ou inspecione o armazenamento a partir da linha de comandos, se necessário.';

$strings['PathFilter'] = 'Filtro de caminho';
$strings['PathFilterHelp'] = 'Opcional. Utilize isto para testar ou rever uma pasta específica, por exemplo clean-deleted-files-test. Corresponde apenas a caminhos relativos.';
$strings['ActivePathFilter'] = 'Filtro de caminho ativo: %s';
