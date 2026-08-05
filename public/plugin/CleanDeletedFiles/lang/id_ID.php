<?php
$strings['plugin_title'] = 'Bersihkan file terhapus';
$strings['plugin_comment'] = 'Hapus permanen file yang ditandai terhapus. Aktifkan di wilayah menu_administrator lalu akses dari halaman admin utama.';
$strings['FileList'] = 'Daftar file yang ditandai terhapus';
$strings['SizeTotalAllDir'] = 'Total ukuran (semua direktori)';
$strings['NoFilesDeleted'] = 'Tidak ada file yang ditandai terhapus';
$strings['FilesDeletedMark'] = 'File yang ditandai terhapus';
$strings['FileDirSize'] = 'Ukuran file direktori';
$strings['ConfirmDelete'] = 'Apakah Anda yakin ingin menghapus file ini?';
$strings['ErrorDeleteFile'] = 'Terjadi kesalahan saat menghapus file';
$strings['ErrorEmptyPath'] = 'Ada masalah saat menghapus file, jalur tidak boleh kosong';
$strings['DeleteSelectedFiles'] = 'Hapus file terpilih';
$strings['ConfirmDeleteFiles'] = 'Apakah Anda yakin ingin menghapus semua file terpilih?';
$strings['DeletedSuccess'] = 'Penghapusan file berhasil';
$strings['path_dir'] = 'Direktori';
$strings['size'] = 'Ukuran';
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
$strings['ErrorNotCleanablePath'] = 'Berkas ini bukanlah berkas yatim atau warisan yang dapat dibersihkan.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Berkas fisik yang dapat dibersihkan';
$strings['NoCleanableFiles'] = 'Tidak ada berkas fisik yang dapat dibersihkan di akar penyimpanan ini';
$strings['Chamilo2ResourceStorage'] = 'Berkas sumber daya';
$strings['Chamilo2ResourceStorageHelp'] = 'Berkas di bawah var/upload/resource hanya terdaftar jika tidak direferensikan oleh resource_file. Hapus dokumen dari alat Dokumen atau API, bukan dari plugin ini.';
$strings['Chamilo2AssetStorage'] = 'Berkas aset';
$strings['Chamilo2AssetStorageHelp'] = 'Berkas di bawah var/upload/assets hanya terdaftar jika tidak direferensikan oleh aset. Aset yang direferensikan selalu dilindungi.';
$strings['LegacyCourseFiles'] = 'Berkas kursus warisan';
$strings['LegacyUploadFiles'] = 'Berkas unggah warisan';
$strings['LegacyPublicCourseFiles'] = 'Berkas kursus publik warisan';
$strings['LegacyPublicUploadFiles'] = 'Berkas unggah publik warisan';
$strings['LegacyDeletedStorageHelp'] = 'Akar warisan hanya dipindai untuk berkas yang namanya mengandung penanda DELETED.';
$strings['OrphanResourceFile'] = 'Berkas sumber daya yatim';
$strings['OrphanAssetFile'] = 'Berkas aset yatim';
$strings['LegacyDeletedFile'] = 'Berkas DELETED warisan';
$strings['OrphanResourceFiles'] = 'Berkas sumber daya yatim';
$strings['OrphanAssetFiles'] = 'Berkas aset yatim';
$strings['LegacyDeletedFiles'] = 'Berkas DELETED warisan';
$strings['Reason'] = 'Alasan';
$strings['ReasonOrphanResource'] = 'Berkas fisik ada di penyimpanan sumber daya tetapi tidak ada baris resource_file yang menunjuk ke sana.';
$strings['ReasonOrphanAsset'] = 'Berkas fisik ada di penyimpanan aset tetapi tidak ada baris aset yang menunjuk ke sana.';
$strings['ReasonLegacyDeletedMarker'] = 'Nama dasar berkas warisan mengandung penanda DELETED.';

$strings['StorageNoticeShort'] = 'Berkas yang diunggah dilacak melalui metadata resource_file dan aset. Plugin ini hanya mencantumkan berkas fisik di bawah var/upload/resource dan var/upload/assets yang tidak lagi direferensikan.';
$strings['SafeNoticeShort'] = 'Berkas dengan referensi basis data yang valid dilindungi. Dokumen dan berkas harus dihapus melalui alat normalnya.';
$strings['CheckedLocations'] = 'Lokasi yang diperiksa';
$strings['DetectionRule'] = 'Aturan deteksi';
$strings['NoCleanableFilesFound'] = 'Tidak ditemukan berkas fisik yang dapat dibersihkan';
$strings['NoCleanableFilesFoundHelp'] = 'Ini adalah hasil yang diharapkan ketika penyimpanan konsisten. Lokasi yang diperiksa ditampilkan di bawah ini untuk transparansi.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Jalankan pemindaian terbatas';
$strings['ScanNotRun'] = 'Pemindaian belum dimulai';
$strings['ScanNotRunHelp'] = 'Halaman ini tidak memindai penyimpanan secara otomatis karena folder var/upload yang besar dapat menjadi lambat. Klik Jalankan pemindaian terbatas untuk memeriksa berkas yatim lokal.';
$strings['ScanLimitedWarning'] = 'Pemindaian dihentikan lebih awal agar halaman tetap responsif. Jalankan lagi nanti atau periksa penyimpanan dari baris perintah jika diperlukan.';

$strings['PathFilter'] = 'Filter jalur';
$strings['PathFilterHelp'] = 'Opsional. Gunakan ini untuk menguji atau meninjau folder tertentu, misalnya clean-deleted-files-test. Ini hanya mencocokkan jalur relatif.';
$strings['ActivePathFilter'] = 'Filter jalur aktif: %s';
