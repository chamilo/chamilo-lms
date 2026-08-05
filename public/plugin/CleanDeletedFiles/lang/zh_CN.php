<?php
$strings['plugin_title'] = '清理已删除文件';
$strings['plugin_comment'] = '永久删除标记为已删除的文件。在管理员菜单区域启用它，然后从主管理员页面访问。';
$strings['FileList'] = '标记为已删除的文件列表';
$strings['SizeTotalAllDir'] = '总大小（所有目录）';
$strings['NoFilesDeleted'] = '没有标记为已删除的文件';
$strings['FilesDeletedMark'] = '标记为已删除的文件';
$strings['FileDirSize'] = '目录文件大小';
$strings['ConfirmDelete'] = '您确定要删除该文件吗？';
$strings['ErrorDeleteFile'] = '删除文件时发生错误';
$strings['ErrorEmptyPath'] = '删除文件时出现问题，路径不能为空';
$strings['DeleteSelectedFiles'] = '删除选定的文件';
$strings['ConfirmDeleteFiles'] = '您确定要删除所有选定的文件吗？';
$strings['DeletedSuccess'] = '文件删除成功';
$strings['path_dir'] = '目录';
$strings['size'] = '大小';
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
$strings['ErrorNotCleanablePath'] = '该文件不是可清理的孤立文件或旧版已删除文件。';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = '可清理的物理文件';
$strings['NoCleanableFiles'] = '此存储根目录中没有可清理的物理文件';
$strings['Chamilo2ResourceStorage'] = '资源文件';
$strings['Chamilo2ResourceStorageHelp'] = 'var/upload/resource 下的文件仅在未被 resource_file 引用时才会列出。请通过文档工具或 API 删除文档，不要通过此插件删除。';
$strings['Chamilo2AssetStorage'] = '资源文件';
$strings['Chamilo2AssetStorageHelp'] = 'var/upload/assets 下的文件仅在未被 asset 引用时才会列出。被引用的资源文件始终受到保护。';
$strings['LegacyCourseFiles'] = '旧版课程文件';
$strings['LegacyUploadFiles'] = '旧版上传文件';
$strings['LegacyPublicCourseFiles'] = '旧版公开课程文件';
$strings['LegacyPublicUploadFiles'] = '旧版公开上传文件';
$strings['LegacyDeletedStorageHelp'] = '旧版根目录仅扫描文件名包含 DELETED 标记的文件。';
$strings['OrphanResourceFile'] = '孤立资源文件';
$strings['OrphanAssetFile'] = '孤立资源文件';
$strings['LegacyDeletedFile'] = '旧版 DELETED 文件';
$strings['OrphanResourceFiles'] = '孤立资源文件';
$strings['OrphanAssetFiles'] = '孤立资源文件';
$strings['LegacyDeletedFiles'] = '旧版 DELETED 文件';
$strings['Reason'] = '原因';
$strings['ReasonOrphanResource'] = '物理文件存在于资源存储中，但没有 resource_file 记录指向它。';
$strings['ReasonOrphanAsset'] = '物理文件存在于资源存储中，但没有 asset 记录指向它。';
$strings['ReasonLegacyDeletedMarker'] = '旧版文件名包含 DELETED 标记。';

$strings['StorageNoticeShort'] = '上传的文件通过 resource_file 和 asset 元数据进行跟踪。此插件仅列出 var/upload/resource 和 var/upload/assets 下不再被引用的物理文件。';
$strings['SafeNoticeShort'] = '具有有效数据库引用的文件受到保护。文档和文件仍应通过其常规工具删除。';
$strings['CheckedLocations'] = '已检查的位置';
$strings['DetectionRule'] = '检测规则';
$strings['NoCleanableFilesFound'] = '未找到可清理的物理文件';
$strings['NoCleanableFilesFoundHelp'] = '当存储一致时，这是预期结果。下方显示已检查的位置以确保透明。';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = '运行有限扫描';
$strings['ScanNotRun'] = '扫描未开始';
$strings['ScanNotRunHelp'] = '此页面不会自动扫描存储，因为较大的 var/upload 文件夹可能会很慢。请点击“运行有限扫描”来检查本地孤立文件。';
$strings['ScanLimitedWarning'] = '扫描已提前停止以保持页面响应。如有需要，请稍后再次运行或从命令行检查存储。';

$strings['PathFilter'] = '路径筛选器';
$strings['PathFilterHelp'] = '可选。用于测试或查看特定文件夹，例如 clean-deleted-files-test。它仅匹配相对路径。';
$strings['ActivePathFilter'] = '活动路径筛选器：%s';
