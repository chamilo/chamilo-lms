<?php
$strings['plugin_title'] = 'Dọn dẹp các tệp đã xóa';
$strings['plugin_comment'] = 'Xóa vĩnh viễn các tệp được đánh dấu là đã xóa. Bật nó trong vùng menu_administrator sau đó truy cập từ trang quản trị chính.';
$strings['FileList'] = 'Danh sách các tệp được đánh dấu là đã xóa';
$strings['SizeTotalAllDir'] = 'Tổng kích thước (tất cả thư mục)';
$strings['NoFilesDeleted'] = 'Không có tệp nào được đánh dấu là đã xóa';
$strings['FilesDeletedMark'] = 'Các tệp được đánh dấu là đã xóa';
$strings['FileDirSize'] = 'Kích thước tệp thư mục';
$strings['ConfirmDelete'] = 'Bạn có chắc chắn muốn xóa tệp này không?';
$strings['ErrorDeleteFile'] = 'Đã xảy ra lỗi khi xóa tệp';
$strings['ErrorEmptyPath'] = 'Có vấn đề khi xóa tệp, đường dẫn không thể rỗng';
$strings['DeleteSelectedFiles'] = 'Xóa các tệp đã chọn';
$strings['ConfirmDeleteFiles'] = 'Bạn có chắc chắn muốn xóa tất cả các tệp đã chọn không?';
$strings['DeletedSuccess'] = 'Việc xóa tệp đã thành công';
$strings['path_dir'] = 'Thư mục';
$strings['size'] = 'Kích thước';
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
$strings['ErrorNotCleanablePath'] = 'Tệp không phải là tệp mồ côi hoặc tệp đã xóa cũ có thể dọn dẹp.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Tệp vật lý có thể dọn dẹp';
$strings['NoCleanableFiles'] = 'Không có tệp vật lý nào có thể dọn dẹp trong thư mục gốc lưu trữ này';
$strings['Chamilo2ResourceStorage'] = 'Tệp tài nguyên';
$strings['Chamilo2ResourceStorageHelp'] = 'Các tệp trong var/upload/resource chỉ được liệt kê khi chúng không được tham chiếu bởi resource_file. Xóa tài liệu từ công cụ Tài liệu hoặc API, không phải từ plugin này.';
$strings['Chamilo2AssetStorage'] = 'Tệp tài sản';
$strings['Chamilo2AssetStorageHelp'] = 'Các tệp trong var/upload/assets chỉ được liệt kê khi chúng không được tham chiếu bởi asset. Tài sản được tham chiếu luôn được bảo vệ.';
$strings['LegacyCourseFiles'] = 'Tệp khóa học cũ';
$strings['LegacyUploadFiles'] = 'Tệp tải lên cũ';
$strings['LegacyPublicCourseFiles'] = 'Tệp khóa học công khai cũ';
$strings['LegacyPublicUploadFiles'] = 'Tệp tải lên công khai cũ';
$strings['LegacyDeletedStorageHelp'] = 'Các thư mục gốc cũ chỉ được quét đối với các tệp có tên chứa dấu DELETED.';
$strings['OrphanResourceFile'] = 'Tệp tài nguyên mồ côi';
$strings['OrphanAssetFile'] = 'Tệp tài sản mồ côi';
$strings['LegacyDeletedFile'] = 'Tệp DELETED cũ';
$strings['OrphanResourceFiles'] = 'Tệp tài nguyên mồ côi';
$strings['OrphanAssetFiles'] = 'Tệp tài sản mồ côi';
$strings['LegacyDeletedFiles'] = 'Tệp DELETED cũ';
$strings['Reason'] = 'Lý do';
$strings['ReasonOrphanResource'] = 'Tệp vật lý tồn tại trong kho lưu trữ tài nguyên nhưng không có hàng resource_file nào trỏ đến nó.';
$strings['ReasonOrphanAsset'] = 'Tệp vật lý tồn tại trong kho lưu trữ tài sản nhưng không có hàng asset nào trỏ đến nó.';
$strings['ReasonLegacyDeletedMarker'] = 'Tên tệp cũ chứa dấu DELETED.';

$strings['StorageNoticeShort'] = 'Các tệp đã tải lên được theo dõi qua metadata resource_file và asset. Plugin này chỉ liệt kê các tệp vật lý trong var/upload/resource và var/upload/assets không còn được tham chiếu.';
$strings['SafeNoticeShort'] = 'Một tệp có tham chiếu cơ sở dữ liệu hợp lệ sẽ được bảo vệ. Tài liệu và tệp vẫn nên được xóa thông qua các công cụ thông thường.';
$strings['CheckedLocations'] = 'Vị trí đã kiểm tra';
$strings['DetectionRule'] = 'Quy tắc phát hiện';
$strings['NoCleanableFilesFound'] = 'Không tìm thấy tệp vật lý nào có thể dọn dẹp';
$strings['NoCleanableFilesFoundHelp'] = 'Đây là kết quả mong đợi khi kho lưu trữ nhất quán. Các vị trí đã kiểm tra được hiển thị bên dưới để minh bạch.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Chạy quét giới hạn';
$strings['ScanNotRun'] = 'Quét chưa bắt đầu';
$strings['ScanNotRunHelp'] = 'Trang không tự động quét kho lưu trữ vì các thư mục var/upload lớn có thể chậm. Nhấp vào Chạy quét giới hạn để kiểm tra các tệp mồ côi cục bộ.';
$strings['ScanLimitedWarning'] = 'Quá trình quét đã bị dừng sớm để giữ cho trang phản hồi. Chạy lại sau hoặc kiểm tra kho lưu trữ từ dòng lệnh nếu cần.';

$strings['PathFilter'] = 'Bộ lọc đường dẫn';
$strings['PathFilterHelp'] = 'Tùy chọn. Sử dụng để kiểm tra hoặc xem xét một thư mục cụ thể, ví dụ clean-deleted-files-test. Nó chỉ khớp với đường dẫn tương đối.';
$strings['ActivePathFilter'] = 'Bộ lọc đường dẫn đang hoạt động: %s';
