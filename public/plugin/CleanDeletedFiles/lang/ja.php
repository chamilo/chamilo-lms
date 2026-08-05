<?php
$strings['plugin_title'] = '削除済みファイルをクリーンアップ';
$strings['plugin_comment'] = '削除マークされたファイルを永久削除します。メニュー_管理者領域で有効化し、管理者メインページからアクセスしてください。';
$strings['FileList'] = '削除マークされたファイルのリスト';
$strings['SizeTotalAllDir'] = '合計サイズ（全ディレクトリ）';
$strings['NoFilesDeleted'] = '削除マークされたファイルはありません';
$strings['FilesDeletedMark'] = '削除マークされたファイル';
$strings['FileDirSize'] = 'ディレクトリファイルサイズ';
$strings['ConfirmDelete'] = 'ファイルを削除してもよろしいですか？';
$strings['ErrorDeleteFile'] = 'ファイル削除中にエラーが発生しました';
$strings['ErrorEmptyPath'] = 'ファイル削除中に問題が発生しました。パスは空にできません';
$strings['DeleteSelectedFiles'] = '選択したファイルを削除';
$strings['ConfirmDeleteFiles'] = '選択したすべてのファイルを削除してもよろしいですか？';
$strings['DeletedSuccess'] = 'ファイルの削除が成功しました';
$strings['path_dir'] = 'ディレクトリ';
$strings['size'] = 'サイズ';
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
$strings['ErrorNotCleanablePath'] = 'このファイルはクリーンアップ可能な孤立ファイルまたは従来の削除ファイルではありません。';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'クリーンアップ可能な物理ファイル';
$strings['NoCleanableFiles'] = 'このストレージルートにクリーンアップ可能な物理ファイルはありません';
$strings['Chamilo2ResourceStorage'] = 'リソースファイル';
$strings['Chamilo2ResourceStorageHelp'] = 'var/upload/resource 以下のファイルは、resource_file から参照されていない場合にのみ表示されます。ドキュメントは Documents ツールまたは API から削除してください。このプラグインからは削除しないでください。';
$strings['Chamilo2AssetStorage'] = 'アセットファイル';
$strings['Chamilo2AssetStorageHelp'] = 'var/upload/assets 以下のファイルは、asset から参照されていない場合にのみ表示されます。参照されているアセットは常に保護されます。';
$strings['LegacyCourseFiles'] = '従来のコースファイル';
$strings['LegacyUploadFiles'] = '従来のアップロードファイル';
$strings['LegacyPublicCourseFiles'] = '従来の公開コースファイル';
$strings['LegacyPublicUploadFiles'] = '従来の公開アップロードファイル';
$strings['LegacyDeletedStorageHelp'] = '従来のルートは、ベース名に DELETED マーカーを含むファイルのみをスキャンします。';
$strings['OrphanResourceFile'] = '孤立リソースファイル';
$strings['OrphanAssetFile'] = '孤立アセットファイル';
$strings['LegacyDeletedFile'] = '従来の DELETED ファイル';
$strings['OrphanResourceFiles'] = '孤立リソースファイル';
$strings['OrphanAssetFiles'] = '孤立アセットファイル';
$strings['LegacyDeletedFiles'] = '従来の DELETED ファイル';
$strings['Reason'] = '理由';
$strings['ReasonOrphanResource'] = 'リソースストレージに物理ファイルが存在しますが、resource_file の行がそれを指していません。';
$strings['ReasonOrphanAsset'] = 'アセットストレージに物理ファイルが存在しますが、asset の行がそれを指していません。';
$strings['ReasonLegacyDeletedMarker'] = '従来のファイルのベース名に DELETED マーカーが含まれています。';

$strings['StorageNoticeShort'] = 'アップロードされたファイルは resource_file と asset のメタデータで追跡されます。このプラグインは、var/upload/resource および var/upload/assets 以下の物理ファイルのうち、参照されなくなったもののみを表示します。';
$strings['SafeNoticeShort'] = '有効なデータベース参照を持つファイルは保護されます。ドキュメントとファイルは、通常のツールから削除する必要があります。';
$strings['CheckedLocations'] = 'チェックした場所';
$strings['DetectionRule'] = '検出ルール';
$strings['NoCleanableFilesFound'] = 'クリーンアップ可能な物理ファイルが見つかりません';
$strings['NoCleanableFilesFoundHelp'] = 'これはストレージが一貫している場合の想定される結果です。透明性のためにチェックした場所を以下に表示します。';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = '限定スキャンを実行';
$strings['ScanNotRun'] = 'スキャンは開始されていません';
$strings['ScanNotRunHelp'] = 'このページはストレージを自動的にスキャンしません。var/upload フォルダが大きいと処理が遅くなる可能性があるためです。「限定スキャンを実行」をクリックして、ローカルの孤立ファイルを検査してください。';
$strings['ScanLimitedWarning'] = 'ページの応答性を維持するため、スキャンは途中で停止されました。後で再度実行するか、必要に応じてコマンドラインからストレージを検査してください。';

$strings['PathFilter'] = 'パスフィルター';
$strings['PathFilterHelp'] = 'オプション。特定のフォルダ（例: clean-deleted-files-test）をテストまたは確認する場合に使用します。相対パスのみ一致します。';
$strings['ActivePathFilter'] = '有効なパスフィルター: %s';
