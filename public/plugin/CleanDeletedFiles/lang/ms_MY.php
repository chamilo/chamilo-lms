<?php
$strings['plugin_title'] = 'Bersihkan fail yang dihapus';
$strings['plugin_comment'] = 'Hapus secara kekal fail yang ditandakan sebagai dihapus. Aktifkan di rantau menu_administrator kemudian akses dari laman utama pentadbir.';
$strings['FileList'] = 'Senarai fail yang ditandakan sebagai dihapus';
$strings['SizeTotalAllDir'] = 'Jumlah saiz (semua direktori)';
$strings['NoFilesDeleted'] = 'Tiada fail yang ditandakan sebagai dihapus';
$strings['FilesDeletedMark'] = 'Fail yang ditandakan sebagai dihapus';
$strings['FileDirSize'] = 'Saiz fail direktori';
$strings['ConfirmDelete'] = 'Adakah anda pasti mahu memadam fail ini?';
$strings['ErrorDeleteFile'] = 'Ralat berlaku semasa memadam fail';
$strings['ErrorEmptyPath'] = 'Terdapat masalah memadam fail, laluan tidak boleh kosong';
$strings['DeleteSelectedFiles'] = 'Padam fail yang dipilih';
$strings['ConfirmDeleteFiles'] = 'Adakah anda pasti mahu memadam semua fail yang dipilih?';
$strings['DeletedSuccess'] = 'Penghapusan fail berjaya';
$strings['path_dir'] = 'Direktori';
$strings['size'] = 'Saiz';
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
$strings['ErrorNotCleanablePath'] = 'Fail ini bukan fail yatim yang boleh dibersihkan atau fail dipadamkan legasi.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Fail fizikal yang boleh dibersihkan';
$strings['NoCleanableFiles'] = 'Tiada fail fizikal yang boleh dibersihkan dalam akar storan ini';
$strings['Chamilo2ResourceStorage'] = 'Fail sumber';
$strings['Chamilo2ResourceStorageHelp'] = 'Fail di bawah var/upload/resource hanya disenaraikan apabila ia tidak dirujuk oleh resource_file. Padamkan dokumen daripada alat Dokumen atau API, bukan daripada pemalam ini.';
$strings['Chamilo2AssetStorage'] = 'Fail aset';
$strings['Chamilo2AssetStorageHelp'] = 'Fail di bawah var/upload/assets hanya disenaraikan apabila ia tidak dirujuk oleh aset. Aset yang dirujuk sentiasa dilindungi.';
$strings['LegacyCourseFiles'] = 'Fail kursus legasi';
$strings['LegacyUploadFiles'] = 'Fail muat naik legasi';
$strings['LegacyPublicCourseFiles'] = 'Fail kursus awam legasi';
$strings['LegacyPublicUploadFiles'] = 'Fail muat naik awam legasi';
$strings['LegacyDeletedStorageHelp'] = 'Akar legasi hanya diimbas untuk fail yang namanya mengandungi penanda DELETED.';
$strings['OrphanResourceFile'] = 'Fail sumber yatim';
$strings['OrphanAssetFile'] = 'Fail aset yatim';
$strings['LegacyDeletedFile'] = 'Fail DELETED legasi';
$strings['OrphanResourceFiles'] = 'Fail sumber yatim';
$strings['OrphanAssetFiles'] = 'Fail aset yatim';
$strings['LegacyDeletedFiles'] = 'Fail DELETED legasi';
$strings['Reason'] = 'Sebab';
$strings['ReasonOrphanResource'] = 'Fail fizikal wujud dalam storan sumber tetapi tiada baris resource_file merujuk kepadanya.';
$strings['ReasonOrphanAsset'] = 'Fail fizikal wujud dalam storan aset tetapi tiada baris aset merujuk kepadanya.';
$strings['ReasonLegacyDeletedMarker'] = 'Nama asas fail legasi mengandungi penanda DELETED.';

$strings['StorageNoticeShort'] = 'Fail yang dimuat naik dijejaki melalui metadata resource_file dan aset. Pemalam ini hanya menyenaraikan fail fizikal di bawah var/upload/resource dan var/upload/assets yang tidak lagi dirujuk.';
$strings['SafeNoticeShort'] = 'Fail dengan rujukan pangkalan data yang sah dilindungi. Dokumen dan fail harus masih dipadamkan melalui alat biasa mereka.';
$strings['CheckedLocations'] = 'Lokasi yang disemak';
$strings['DetectionRule'] = 'Peraturan pengesanan';
$strings['NoCleanableFilesFound'] = 'Tiada fail fizikal yang boleh dibersihkan ditemui';
$strings['NoCleanableFilesFoundHelp'] = 'Ini adalah hasil yang dijangkakan apabila storan adalah konsisten. Lokasi yang disemak ditunjukkan di bawah untuk ketelusan.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Jalankan imbasan terhad';
$strings['ScanNotRun'] = 'Imbasan belum dimulakan';
$strings['ScanNotRunHelp'] = 'Halaman ini tidak mengimbas storan secara automatik kerana folder var/upload yang besar boleh menjadi lambat. Klik Jalankan imbasan terhad untuk memeriksa fail yatim setempat.';
$strings['ScanLimitedWarning'] = 'Imbasan dihentikan lebih awal untuk mengekalkan keberkesanan halaman. Jalankannya semula kemudian atau periksa storan dari baris arahan jika perlu.';

$strings['PathFilter'] = 'Penapis laluan';
$strings['PathFilterHelp'] = 'Pilihan. Gunakan ini untuk menguji atau menyemak folder tertentu, contohnya clean-deleted-files-test. Ia hanya sepadan dengan laluan relatif.';
$strings['ActivePathFilter'] = 'Penapis laluan aktif: %s';
