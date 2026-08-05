<?php
$strings['plugin_title'] = 'Silinen dosyaları temizle';
$strings['plugin_comment'] = 'Silinmiş olarak işaretlenmiş dosyaları kalıcı olarak silin. Bunu menü_yönetici bölümünde etkinleştirin ardından ana yönetici sayfasından erişin.';
$strings['FileList'] = 'Silinmiş olarak işaretlenmiş dosyaların listesi';
$strings['SizeTotalAllDir'] = 'Toplam boyut (tüm dizinler)';
$strings['NoFilesDeleted'] = 'Silinmiş olarak işaretlenmiş dosya yok';
$strings['FilesDeletedMark'] = 'Silinmiş olarak işaretlenmiş dosyalar';
$strings['FileDirSize'] = 'Dizin dosyaları boyutu';
$strings['ConfirmDelete'] = 'Dosyayı silmek istediğinizden emin misiniz?';
$strings['ErrorDeleteFile'] = 'Dosya silinirken bir hata oluştu';
$strings['ErrorEmptyPath'] = 'Dosya silinirken bir sorun oluştu, yol boş olamaz';
$strings['DeleteSelectedFiles'] = 'Seçili dosyaları sil';
$strings['ConfirmDeleteFiles'] = 'Seçili tüm dosyaları silmek istediğinizden emin misiniz?';
$strings['DeletedSuccess'] = 'Dosya silme işlemi başarılı';
$strings['path_dir'] = 'Dizin';
$strings['size'] = 'Boyut';
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
$strings['ErrorNotCleanablePath'] = 'Dosya temizlenebilir bir yetim dosya veya eski silinmiş dosya değil.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Temizlenebilir fiziksel dosyalar';
$strings['NoCleanableFiles'] = 'Bu depolama kökünde temizlenebilir fiziksel dosya yok';
$strings['Chamilo2ResourceStorage'] = 'Kaynak dosyaları';
$strings['Chamilo2ResourceStorageHelp'] = "var/upload/resource altındaki dosyalar yalnızca resource_file tarafından referans gösterilmediklerinde listelenir. Belgeleri Belgeler aracından veya API'den silin, bu eklentiden silmeyin.";
$strings['Chamilo2AssetStorage'] = 'Varlık dosyaları';
$strings['Chamilo2AssetStorageHelp'] = 'var/upload/assets altındaki dosyalar yalnızca asset tarafından referans gösterilmediklerinde listelenir. Referans verilen varlıklar her zaman korunur.';
$strings['LegacyCourseFiles'] = 'Eski ders dosyaları';
$strings['LegacyUploadFiles'] = 'Eski yükleme dosyaları';
$strings['LegacyPublicCourseFiles'] = 'Eski genel ders dosyaları';
$strings['LegacyPublicUploadFiles'] = 'Eski genel yükleme dosyaları';
$strings['LegacyDeletedStorageHelp'] = 'Eski kökler yalnızca temel adı DELETED işaretçisi içeren dosyalar için taranır.';
$strings['OrphanResourceFile'] = 'Yetim kaynak dosyası';
$strings['OrphanAssetFile'] = 'Yetim varlık dosyası';
$strings['LegacyDeletedFile'] = 'Eski DELETED dosyası';
$strings['OrphanResourceFiles'] = 'Yetim kaynak dosyaları';
$strings['OrphanAssetFiles'] = 'Yetim varlık dosyaları';
$strings['LegacyDeletedFiles'] = 'Eski DELETED dosyaları';
$strings['Reason'] = 'Neden';
$strings['ReasonOrphanResource'] = 'Fiziksel dosya kaynak depolamada mevcut ancak hiçbir resource_file satırı ona işaret etmiyor.';
$strings['ReasonOrphanAsset'] = 'Fiziksel dosya varlık depolamada mevcut ancak hiçbir asset satırı ona işaret etmiyor.';
$strings['ReasonLegacyDeletedMarker'] = 'Eski dosya temel adı DELETED işaretçisi içeriyor.';

$strings['StorageNoticeShort'] = 'Yüklenen dosyalar resource_file ve asset meta verileri aracılığıyla izlenir. Bu eklenti yalnızca var/upload/resource ve var/upload/assets altındaki artık referans gösterilmeyen fiziksel dosyaları listeler.';
$strings['SafeNoticeShort'] = 'Geçerli bir veritabanı referansı olan dosya korunur. Belgeler ve dosyalar normal araçları aracılığıyla silinmelidir.';
$strings['CheckedLocations'] = 'Kontrol edilen konumlar';
$strings['DetectionRule'] = 'Algılama kuralı';
$strings['NoCleanableFilesFound'] = 'Temizlenebilir fiziksel dosya bulunamadı';
$strings['NoCleanableFilesFoundHelp'] = 'Bu, depolama tutarlı olduğunda beklenen sonuçtur. Şeffaflık için kontrol edilen konumlar aşağıda gösterilmiştir.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Sınırlı tarama çalıştır';
$strings['ScanNotRun'] = 'Tarama başlatılmadı';
$strings['ScanNotRunHelp'] = "Sayfa depolamayı otomatik taramaz çünkü büyük var/upload klasörleri yavaş olabilir. Yerel yetim dosyaları incelemek için Sınırlı tarama çalıştır'a tıklayın.";
$strings['ScanLimitedWarning'] = 'Sayfanın yanıt verebilir kalması için tarama erken durduruldu. Daha sonra tekrar çalıştırın veya gerekirse komut satırından depolamayı inceleyin.';

$strings['PathFilter'] = 'Yol filtresi';
$strings['PathFilterHelp'] = 'İsteğe bağlı. Belirli bir klasörü test etmek veya incelemek için bunu kullanın, örneğin clean-deleted-files-test. Yalnızca göreli yollarla eşleşir.';
$strings['ActivePathFilter'] = 'Aktif yol filtresi: %s';
