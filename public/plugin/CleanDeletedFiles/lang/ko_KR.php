<?php
$strings['plugin_title'] = '삭제된 파일 정리';
$strings['plugin_comment'] = '삭제로 표시된 파일을 영구 삭제합니다. menu_administrator 영역에서 활성화한 후 메인 관리 페이지에서 접근하세요.';
$strings['FileList'] = '삭제로 표시된 파일 목록';
$strings['SizeTotalAllDir'] = '총 크기 (모든 디렉토리)';
$strings['NoFilesDeleted'] = '삭제로 표시된 파일이 없습니다';
$strings['FilesDeletedMark'] = '삭제로 표시된 파일';
$strings['FileDirSize'] = '디렉토리 파일 크기';
$strings['ConfirmDelete'] = '파일을 삭제하시겠습니까?';
$strings['ErrorDeleteFile'] = '파일 삭제 중 오류가 발생했습니다';
$strings['ErrorEmptyPath'] = '파일 삭제 중 문제가 발생했습니다. 경로가 비어 있을 수 없습니다';
$strings['DeleteSelectedFiles'] = '선택된 파일 삭제';
$strings['ConfirmDeleteFiles'] = '선택된 모든 파일을 삭제하시겠습니까?';
$strings['DeletedSuccess'] = '파일 삭제가 성공했습니다';
$strings['path_dir'] = '디렉토리';
$strings['size'] = '크기';
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
$strings['ErrorNotCleanablePath'] = '이 파일은 정리 가능한 고아 파일이나 레거시 삭제 파일이 아닙니다.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = '정리 가능한 물리적 파일';
$strings['NoCleanableFiles'] = '이 저장소 루트에 정리 가능한 물리적 파일이 없습니다';
$strings['Chamilo2ResourceStorage'] = '리소스 파일';
$strings['Chamilo2ResourceStorageHelp'] = 'var/upload/resource 아래의 파일은 resource_file에서 참조되지 않을 때만 표시됩니다. 문서는 이 플러그인이 아닌 문서 도구나 API를 통해 삭제하십시오.';
$strings['Chamilo2AssetStorage'] = '에셋 파일';
$strings['Chamilo2AssetStorageHelp'] = 'var/upload/assets 아래의 파일은 asset에서 참조되지 않을 때만 표시됩니다. 참조된 에셋은 항상 보호됩니다.';
$strings['LegacyCourseFiles'] = '레거시 강의 파일';
$strings['LegacyUploadFiles'] = '레거시 업로드 파일';
$strings['LegacyPublicCourseFiles'] = '레거시 공개 강의 파일';
$strings['LegacyPublicUploadFiles'] = '레거시 공개 업로드 파일';
$strings['LegacyDeletedStorageHelp'] = '레거시 루트는 파일명에 DELETED 표시가 포함된 파일만 검사합니다.';
$strings['OrphanResourceFile'] = '고아 리소스 파일';
$strings['OrphanAssetFile'] = '고아 에셋 파일';
$strings['LegacyDeletedFile'] = '레거시 DELETED 파일';
$strings['OrphanResourceFiles'] = '고아 리소스 파일';
$strings['OrphanAssetFiles'] = '고아 에셋 파일';
$strings['LegacyDeletedFiles'] = '레거시 DELETED 파일';
$strings['Reason'] = '이유';
$strings['ReasonOrphanResource'] = '리소스 저장소에 물리적 파일이 존재하지만 resource_file 레코드가 이를 가리키지 않습니다.';
$strings['ReasonOrphanAsset'] = '에셋 저장소에 물리적 파일이 존재하지만 asset 레코드가 이를 가리키지 않습니다.';
$strings['ReasonLegacyDeletedMarker'] = '레거시 파일명에 DELETED 표시가 포함되어 있습니다.';

$strings['StorageNoticeShort'] = '업로드된 파일은 resource_file과 asset 메타데이터를 통해 추적됩니다. 이 플러그인은 var/upload/resource와 var/upload/assets 아래에서 더 이상 참조되지 않는 물리적 파일만 나열합니다.';
$strings['SafeNoticeShort'] = '유효한 데이터베이스 참조가 있는 파일은 보호됩니다. 문서와 파일은 해당 일반 도구를 통해 삭제해야 합니다.';
$strings['CheckedLocations'] = '검사한 위치';
$strings['DetectionRule'] = '탐지 규칙';
$strings['NoCleanableFilesFound'] = '정리 가능한 물리적 파일을 찾을 수 없음';
$strings['NoCleanableFilesFoundHelp'] = '저장소가 일관된 상태일 때 예상되는 결과입니다. 투명성을 위해 검사한 위치를 아래에 표시합니다.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = '제한된 검사 실행';
$strings['ScanNotRun'] = '검사가 시작되지 않음';
$strings['ScanNotRunHelp'] = "대용량 var/upload 폴더는 속도가 느릴 수 있으므로 이 페이지는 저장소를 자동으로 검사하지 않습니다. 로컬 고아 파일을 검사하려면 '제한된 검사 실행'을 클릭하십시오.";
$strings['ScanLimitedWarning'] = '페이지 응답성을 유지하기 위해 검사가 조기에 중단되었습니다. 나중에 다시 실행하거나 필요 시 명령줄에서 저장소를 검사하십시오.';

$strings['PathFilter'] = '경로 필터';
$strings['PathFilterHelp'] = '선택 사항입니다. 특정 폴더(예: clean-deleted-files-test)를 테스트하거나 검토하는 데 사용합니다. 상대 경로만 일치합니다.';
$strings['ActivePathFilter'] = '활성 경로 필터: %s';
