<?php
$strings['plugin_title'] = 'Καθαρισμός διαγραμμένων αρχείων';
$strings['plugin_comment'] = 'Μόνιμη διαγραφή αρχείων που έχουν επισημανθεί ως διαγραμμένα. Ενεργοποιήστε το στην περιοχή menu_administrator και μετά προσπελάστε το από την κύρια σελίδα διαχειριστή.';
$strings['FileList'] = 'Λίστα αρχείων που έχουν επισημανθεί ως διαγραμμένα';
$strings['SizeTotalAllDir'] = 'Συνολικό μέγεθος (όλοι οι κατάλογοι)';
$strings['NoFilesDeleted'] = 'Δεν υπάρχουν αρχεία που έχουν επισημανθεί ως διαγραμμένα';
$strings['FilesDeletedMark'] = 'Αρχεία που έχουν επισημανθεί ως διαγραμμένα';
$strings['FileDirSize'] = 'Μέγεθος αρχείων καταλόγου';
$strings['ConfirmDelete'] = 'Είστε σίγουροι ότι θέλετε να διαγράψετε το αρχείο;';
$strings['ErrorDeleteFile'] = 'Παρουσιάστηκε σφάλμα κατά τη διαγραφή του αρχείου';
$strings['ErrorEmptyPath'] = 'Παρουσιάστηκε πρόβλημα κατά τη διαγραφή του αρχείου, η διαδρομή δεν μπορεί να είναι κενή';
$strings['DeleteSelectedFiles'] = 'Διαγραφή επιλεγμένων αρχείων';
$strings['ConfirmDeleteFiles'] = 'Είστε σίγουροι ότι θέλετε να διαγράψετε όλα τα επιλεγμένα αρχεία;';
$strings['DeletedSuccess'] = 'Η διαγραφή του αρχείου ήταν επιτυχής';
$strings['path_dir'] = 'Κατάλογος';
$strings['size'] = 'Μέγεθος';
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
$strings['ErrorNotCleanablePath'] = 'Το αρχείο δεν είναι ένα καθαρίσιμο ορφανό ή παλιό διαγραμμένο αρχείο.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Καθαρίσιμα φυσικά αρχεία';
$strings['NoCleanableFiles'] = 'Δεν υπάρχουν καθαρίσιμα φυσικά αρχεία σε αυτή τη ρίζα αποθήκευσης';
$strings['Chamilo2ResourceStorage'] = 'Αρχεία πόρων';
$strings['Chamilo2ResourceStorageHelp'] = 'Τα αρχεία κάτω από var/upload/resource εμφανίζονται μόνο όταν δεν αναφέρονται από το resource_file. Διαγράψτε έγγραφα από το εργαλείο Εγγράφων ή το API, όχι από αυτό το πρόσθετο.';
$strings['Chamilo2AssetStorage'] = 'Αρχεία πόρων';
$strings['Chamilo2AssetStorageHelp'] = 'Τα αρχεία κάτω από var/upload/assets εμφανίζονται μόνο όταν δεν αναφέρονται από το asset. Τα αναφερόμενα assets προστατεύονται πάντα.';
$strings['LegacyCourseFiles'] = 'Παλιά αρχεία μαθημάτων';
$strings['LegacyUploadFiles'] = 'Παλιά αρχεία μεταφόρτωσης';
$strings['LegacyPublicCourseFiles'] = 'Παλιά δημόσια αρχεία μαθημάτων';
$strings['LegacyPublicUploadFiles'] = 'Παλιά δημόσια αρχεία μεταφόρτωσης';
$strings['LegacyDeletedStorageHelp'] = 'Οι παλιές ρίζες σαρώνονται μόνο για αρχεία των οποίων το όνομα βάσης περιέχει το δείκτη DELETED.';
$strings['OrphanResourceFile'] = 'Ορφανό αρχείο πόρου';
$strings['OrphanAssetFile'] = 'Ορφανό αρχείο πόρου';
$strings['LegacyDeletedFile'] = 'Παλιό αρχείο DELETED';
$strings['OrphanResourceFiles'] = 'Ορφανά αρχεία πόρων';
$strings['OrphanAssetFiles'] = 'Ορφανά αρχεία πόρων';
$strings['LegacyDeletedFiles'] = 'Παλιά αρχεία DELETED';
$strings['Reason'] = 'Αιτία';
$strings['ReasonOrphanResource'] = 'Το φυσικό αρχείο υπάρχει στην αποθήκευση πόρων αλλά καμία εγγραφή resource_file δεν το δείχνει.';
$strings['ReasonOrphanAsset'] = 'Το φυσικό αρχείο υπάρχει στην αποθήκευση assets αλλά καμία εγγραφή asset δεν το δείχνει.';
$strings['ReasonLegacyDeletedMarker'] = 'Το όνομα βάσης του παλιού αρχείου περιέχει το δείκτη DELETED.';

$strings['StorageNoticeShort'] = 'Τα μεταφορτωμένα αρχεία παρακολουθούνται μέσω των μεταδεδομένων resource_file και asset. Αυτό το πρόσθετο εμφανίζει μόνο τα φυσικά αρχεία κάτω από var/upload/resource και var/upload/assets που δεν αναφέρονται πλέον.';
$strings['SafeNoticeShort'] = 'Ένα αρχείο με έγκυρη αναφορά στη βάση δεδομένων προστατεύεται. Τα έγγραφα και τα αρχεία πρέπει να διαγράφονται ακόμα μέσω των κανονικών τους εργαλείων.';
$strings['CheckedLocations'] = 'Ελεγμένες τοποθεσίες';
$strings['DetectionRule'] = 'Κανόνας ανίχνευσης';
$strings['NoCleanableFilesFound'] = 'Δεν βρέθηκαν καθαρίσιμα φυσικά αρχεία';
$strings['NoCleanableFilesFoundHelp'] = 'Αυτό είναι το αναμενόμενο αποτέλεσμα όταν η αποθήκευση είναι συνεπής. Οι ελεγμένες τοποθεσίες εμφανίζονται παρακάτω για διαφάνεια.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Εκτέλεση περιορισμένης σάρωσης';
$strings['ScanNotRun'] = 'Η σάρωση δεν ξεκίνησε';
$strings['ScanNotRunHelp'] = 'Η σελίδα δεν σαρώνει αυτόματα την αποθήκευση επειδή μεγάλοι φάκελοι var/upload μπορεί να είναι αργοί. Κάντε κλικ στο Εκτέλεση περιορισμένης σάρωσης για να ελέγξετε τα τοπικά ορφανά αρχεία.';
$strings['ScanLimitedWarning'] = 'Η σάρωση διακόπηκε νωρίς για να διατηρηθεί η απόκριση της σελίδας. Εκτελέστε την ξανά αργότερα ή ελέγξτε την αποθήκευση από τη γραμμή εντολών εάν χρειάζεται.';

$strings['PathFilter'] = 'Φίλτρο διαδρομής';
$strings['PathFilterHelp'] = 'Προαιρετικό. Χρησιμοποιήστε το για να δοκιμάσετε ή να ελέγξετε έναν συγκεκριμένο φάκελο, για παράδειγμα clean-deleted-files-test. Ταιριάζει μόνο σε σχετικές διαδρομές.';
$strings['ActivePathFilter'] = 'Ενεργό φίλτρο διαδρομής: %s';
