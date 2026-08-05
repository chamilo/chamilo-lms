<?php
$strings['plugin_title'] = 'Wyczyść usunięte pliki';
$strings['plugin_comment'] = 'Trwale usuń pliki oznaczone jako usunięte. Włącz to w obszarze menu_administrator, a następnie uzyskaj dostęp z głównej strony administratora.';
$strings['FileList'] = 'Lista plików oznaczonych jako usunięte';
$strings['SizeTotalAllDir'] = 'Całkowity rozmiar (wszystkie katalogi)';
$strings['NoFilesDeleted'] = 'Nie ma plików oznaczonych jako usunięte';
$strings['FilesDeletedMark'] = 'Pliki oznaczone jako usunięte';
$strings['FileDirSize'] = 'Rozmiar plików katalogu';
$strings['ConfirmDelete'] = 'Czy na pewno chcesz usunąć plik?';
$strings['ErrorDeleteFile'] = 'Wystąpił błąd podczas usuwania pliku';
$strings['ErrorEmptyPath'] = 'Wystąpił problem z usuwaniem pliku, ścieżka nie może być pusta';
$strings['DeleteSelectedFiles'] = 'Usuń wybrane pliki';
$strings['ConfirmDeleteFiles'] = 'Czy na pewno chcesz usunąć wszystkie wybrane pliki?';
$strings['DeletedSuccess'] = 'Usunięcie pliku zakończyło się powodzeniem';
$strings['path_dir'] = 'Katalog';
$strings['size'] = 'Rozmiar';
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
$strings['ErrorNotCleanablePath'] = 'Plik nie jest usuwalnym osieroconym plikiem ani plikiem usuniętym w starszej wersji.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Usuwalne pliki fizyczne';
$strings['NoCleanableFiles'] = 'Brak usuwalnych plików fizycznych w tym głównym katalogu przechowywania';
$strings['Chamilo2ResourceStorage'] = 'Pliki zasobów';
$strings['Chamilo2ResourceStorageHelp'] = 'Pliki w var/upload/resource są wyświetlane tylko wtedy, gdy nie są powiązane z resource_file. Usuwaj dokumenty z narzędzia Dokumenty lub API, a nie z tej wtyczki.';
$strings['Chamilo2AssetStorage'] = 'Pliki zasobów systemowych';
$strings['Chamilo2AssetStorageHelp'] = 'Pliki w var/upload/assets są wyświetlane tylko wtedy, gdy nie są powiązane z asset. Powiązane zasoby systemowe są zawsze chronione.';
$strings['LegacyCourseFiles'] = 'Pliki kursów w starszej wersji';
$strings['LegacyUploadFiles'] = 'Pliki przesłane w starszej wersji';
$strings['LegacyPublicCourseFiles'] = 'Publiczne pliki kursów w starszej wersji';
$strings['LegacyPublicUploadFiles'] = 'Publiczne pliki przesłane w starszej wersji';
$strings['LegacyDeletedStorageHelp'] = 'Starsze katalogi główne są skanowane tylko pod kątem plików, których nazwa bazowa zawiera znacznik DELETED.';
$strings['OrphanResourceFile'] = 'Osierocony plik zasobu';
$strings['OrphanAssetFile'] = 'Osierocony plik zasobu systemowego';
$strings['LegacyDeletedFile'] = 'Plik DELETED w starszej wersji';
$strings['OrphanResourceFiles'] = 'Osierocone pliki zasobów';
$strings['OrphanAssetFiles'] = 'Osierocone pliki zasobów systemowych';
$strings['LegacyDeletedFiles'] = 'Pliki DELETED w starszej wersji';
$strings['Reason'] = 'Przyczyna';
$strings['ReasonOrphanResource'] = 'Plik fizyczny istnieje w magazynie zasobów, ale żaden wiersz resource_file do niego nie wskazuje.';
$strings['ReasonOrphanAsset'] = 'Plik fizyczny istnieje w magazynie zasobów systemowych, ale żaden wiersz asset do niego nie wskazuje.';
$strings['ReasonLegacyDeletedMarker'] = 'Nazwa bazowa pliku w starszej wersji zawiera znacznik DELETED.';

$strings['StorageNoticeShort'] = 'Przesłane pliki są śledzone za pomocą metadanych resource_file i asset. Ta wtyczka wyświetla tylko pliki fizyczne w var/upload/resource i var/upload/assets, do których nie ma już odniesień.';
$strings['SafeNoticeShort'] = 'Plik z poprawnym odniesieniem w bazie danych jest chroniony. Dokumenty i pliki powinny być usuwane za pomocą ich standardowych narzędzi.';
$strings['CheckedLocations'] = 'Sprawdzone lokalizacje';
$strings['DetectionRule'] = 'Reguła wykrywania';
$strings['NoCleanableFilesFound'] = 'Nie znaleziono usuwalnych plików fizycznych';
$strings['NoCleanableFilesFoundHelp'] = 'Jest to oczekiwany wynik, gdy magazyn jest spójny. Sprawdzone lokalizacje są pokazane poniżej dla przejrzystości.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Uruchom ograniczone skanowanie';
$strings['ScanNotRun'] = 'Skanowanie nie zostało uruchomione';
$strings['ScanNotRunHelp'] = 'Strona nie skanuje magazynu automatycznie, ponieważ duże foldery var/upload mogą działać wolno. Kliknij Uruchom ograniczone skanowanie, aby sprawdzić lokalne osierocone pliki.';
$strings['ScanLimitedWarning'] = 'Skanowanie zostało zatrzymane wcześniej, aby strona pozostała responsywna. Uruchom je ponownie później lub sprawdź magazyn z poziomu wiersza poleceń, jeśli to konieczne.';

$strings['PathFilter'] = 'Filtr ścieżki';
$strings['PathFilterHelp'] = 'Opcjonalne. Użyj tego do przetestowania lub przejrzenia konkretnego folderu, na przykład clean-deleted-files-test. Dopasowuje tylko ścieżki względne.';
$strings['ActivePathFilter'] = 'Aktywny filtr ścieżki: %s';
