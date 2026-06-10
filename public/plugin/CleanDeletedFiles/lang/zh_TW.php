<?php
$strings['plugin_title'] = '清除已刪除檔案';
$strings['plugin_comment'] = '永久刪除標記為已刪除的檔案。在管理員選單區域啟用後，從主管理頁面存取。';
$strings['FileList'] = '標記為已刪除的檔案清單';
$strings['SizeTotalAllDir'] = '總大小（所有目錄）';
$strings['NoFilesDeleted'] = '沒有標記為已刪除的檔案';
$strings['FilesDeletedMark'] = '標記為已刪除的檔案';
$strings['FileDirSize'] = '目錄檔案大小';
$strings['ConfirmDelete'] = '您確定要刪除此檔案嗎？';
$strings['ErrorDeleteFile'] = '刪除檔案時發生錯誤';
$strings['ErrorEmptyPath'] = '刪除檔案時發生問題，路徑不能為空';
$strings['DeleteSelectedFiles'] = '刪除選取的檔案';
$strings['ConfirmDeleteFiles'] = '您確定要刪除所有選取的檔案嗎？';
$strings['DeletedSuccess'] = '檔案刪除成功';
$strings['path_dir'] = '目錄';
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
$strings['ErrorNotCleanablePath'] = '該檔案不是可清理的孤立檔案或舊版已刪除檔案。';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = '可清理的實體檔案';
$strings['NoCleanableFiles'] = '此儲存根目錄中沒有可清理的實體檔案';
$strings['Chamilo2ResourceStorage'] = '資源檔案';
$strings['Chamilo2ResourceStorageHelp'] = 'var/upload/resource 下的檔案僅在未被 resource_file 參照時才會列出。請透過文件工具或 API 刪除文件，而非透過此外掛。';
$strings['Chamilo2AssetStorage'] = '資產檔案';
$strings['Chamilo2AssetStorageHelp'] = 'var/upload/assets 下的檔案僅在未被 asset 參照時才會列出。被參照的資產一律受到保護。';
$strings['LegacyCourseFiles'] = '舊版課程檔案';
$strings['LegacyUploadFiles'] = '舊版上傳檔案';
$strings['LegacyPublicCourseFiles'] = '舊版公開課程檔案';
$strings['LegacyPublicUploadFiles'] = '舊版公開上傳檔案';
$strings['LegacyDeletedStorageHelp'] = '舊版根目錄僅掃描 basename 包含 DELETED 標記的檔案。';
$strings['OrphanResourceFile'] = '孤立資源檔案';
$strings['OrphanAssetFile'] = '孤立資產檔案';
$strings['LegacyDeletedFile'] = '舊版 DELETED 檔案';
$strings['OrphanResourceFiles'] = '孤立資源檔案';
$strings['OrphanAssetFiles'] = '孤立資產檔案';
$strings['LegacyDeletedFiles'] = '舊版 DELETED 檔案';
$strings['Reason'] = '原因';
$strings['ReasonOrphanResource'] = '實體檔案存在於資源儲存中，但沒有 resource_file 資料列指向它。';
$strings['ReasonOrphanAsset'] = '實體檔案存在於資產儲存中，但沒有 asset 資料列指向它。';
$strings['ReasonLegacyDeletedMarker'] = '舊版檔案的 basename 包含 DELETED 標記。';

$strings['StorageNoticeShort'] = '上傳檔案透過 resource_file 和 asset 中繼資料進行追蹤。此外掛僅列出 var/upload/resource 和 var/upload/assets 下不再被參照的實體檔案。';
$strings['SafeNoticeShort'] = '具有有效資料庫參照的檔案會受到保護。文件和檔案仍應透過其正常工具進行刪除。';
$strings['CheckedLocations'] = '已檢查的位置';
$strings['DetectionRule'] = '偵測規則';
$strings['NoCleanableFilesFound'] = '未找到可清理的實體檔案';
$strings['NoCleanableFilesFoundHelp'] = '當儲存一致時，這是預期的結果。下方顯示已檢查的位置以供參考。';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = '執行有限掃描';
$strings['ScanNotRun'] = '掃描尚未開始';
$strings['ScanNotRunHelp'] = '此頁面不會自動掃描儲存空間，因為大型 var/upload 資料夾可能會很慢。請點擊「執行有限掃描」來檢查本機孤立檔案。';
$strings['ScanLimitedWarning'] = '掃描已提前停止以保持頁面回應性。請稍後再次執行，或從命令列檢查儲存空間。';

$strings['PathFilter'] = '路徑篩選器';
$strings['PathFilterHelp'] = '選填。用於測試或檢視特定資料夾，例如 clean-deleted-files-test。它僅比對相對路徑。';
$strings['ActivePathFilter'] = '作用中的路徑篩選器：%s';
