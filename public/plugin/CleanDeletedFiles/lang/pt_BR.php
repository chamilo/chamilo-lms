<?php
$strings['plugin_title'] = 'Limpar arquivos excluídos';
$strings['plugin_comment'] = 'Excluir permanentemente arquivos marcados como excluídos. Ative na região menu_administrator e acesse pela página principal de administração.';
$strings['FileList'] = 'Lista de arquivos marcados como excluídos';
$strings['SizeTotalAllDir'] = 'Tamanho total (todos os diretórios)';
$strings['NoFilesDeleted'] = 'Não há arquivos marcados como excluídos';
$strings['FilesDeletedMark'] = 'Arquivos marcados como excluídos';
$strings['FileDirSize'] = 'Tamanho dos arquivos do diretório';
$strings['ConfirmDelete'] = 'Tem certeza de que deseja excluir o arquivo?';
$strings['ErrorDeleteFile'] = 'Ocorreu um erro ao excluir o arquivo';
$strings['ErrorEmptyPath'] = 'Houve um problema ao excluir o arquivo, o caminho não pode estar vazio';
$strings['DeleteSelectedFiles'] = 'Excluir arquivos selecionados';
$strings['ConfirmDeleteFiles'] = 'Tem certeza de que deseja excluir todos os arquivos selecionados?';
$strings['DeletedSuccess'] = 'A exclusão do arquivo foi bem-sucedida';
$strings['path_dir'] = 'Diretório';
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
$strings['ErrorNotCleanablePath'] = 'O arquivo não é um órfão limpável ou um arquivo legado excluído.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Arquivos físicos limpáveis';
$strings['NoCleanableFiles'] = 'Não há arquivos físicos limpáveis nesta raiz de armazenamento';
$strings['Chamilo2ResourceStorage'] = 'Arquivos de recursos';
$strings['Chamilo2ResourceStorageHelp'] = 'Os arquivos em var/upload/resource são listados apenas quando não são referenciados por resource_file. Exclua documentos pela ferramenta Documentos ou pela API, não por este plugin.';
$strings['Chamilo2AssetStorage'] = 'Arquivos de assets';
$strings['Chamilo2AssetStorageHelp'] = 'Os arquivos em var/upload/assets são listados apenas quando não são referenciados por asset. Assets referenciados estão sempre protegidos.';
$strings['LegacyCourseFiles'] = 'Arquivos legados de cursos';
$strings['LegacyUploadFiles'] = 'Arquivos legados de upload';
$strings['LegacyPublicCourseFiles'] = 'Arquivos legados públicos de cursos';
$strings['LegacyPublicUploadFiles'] = 'Arquivos legados públicos de upload';
$strings['LegacyDeletedStorageHelp'] = 'As raízes legadas são verificadas apenas para arquivos cujo nome base contém o marcador DELETED.';
$strings['OrphanResourceFile'] = 'Arquivo órfão de recurso';
$strings['OrphanAssetFile'] = 'Arquivo órfão de asset';
$strings['LegacyDeletedFile'] = 'Arquivo legado DELETED';
$strings['OrphanResourceFiles'] = 'Arquivos órfãos de recursos';
$strings['OrphanAssetFiles'] = 'Arquivos órfãos de assets';
$strings['LegacyDeletedFiles'] = 'Arquivos legados DELETED';
$strings['Reason'] = 'Motivo';
$strings['ReasonOrphanResource'] = 'O arquivo físico existe no armazenamento de recursos, mas nenhuma linha em resource_file aponta para ele.';
$strings['ReasonOrphanAsset'] = 'O arquivo físico existe no armazenamento de assets, mas nenhuma linha em asset aponta para ele.';
$strings['ReasonLegacyDeletedMarker'] = 'O nome base do arquivo legado contém o marcador DELETED.';

$strings['StorageNoticeShort'] = 'Os arquivos enviados são rastreados por meio dos metadados de resource_file e asset. Este plugin lista apenas os arquivos físicos em var/upload/resource e var/upload/assets que não são mais referenciados.';
$strings['SafeNoticeShort'] = 'Um arquivo com uma referência válida no banco de dados está protegido. Documentos e arquivos devem ser excluídos por meio de suas ferramentas normais.';
$strings['CheckedLocations'] = 'Locais verificados';
$strings['DetectionRule'] = 'Regra de detecção';
$strings['NoCleanableFilesFound'] = 'Nenhum arquivo físico limpável encontrado';
$strings['NoCleanableFilesFoundHelp'] = 'Este é o resultado esperado quando o armazenamento está consistente. Os locais verificados são mostrados abaixo para transparência.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Executar verificação limitada';
$strings['ScanNotRun'] = 'Verificação não iniciada';
$strings['ScanNotRunHelp'] = 'A página não verifica o armazenamento automaticamente porque pastas var/upload grandes podem ser lentas. Clique em Executar verificação limitada para inspecionar arquivos órfãos locais.';
$strings['ScanLimitedWarning'] = 'A verificação foi interrompida prematuramente para manter a página responsiva. Execute-a novamente mais tarde ou inspecione o armazenamento pela linha de comando, se necessário.';

$strings['PathFilter'] = 'Filtro de caminho';
$strings['PathFilterHelp'] = 'Opcional. Use isto para testar ou revisar uma pasta específica, por exemplo clean-deleted-files-test. Corresponde apenas a caminhos relativos.';
$strings['ActivePathFilter'] = 'Filtro de caminho ativo: %s';
