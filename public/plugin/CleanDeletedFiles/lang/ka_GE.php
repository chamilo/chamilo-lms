<?php
$strings['plugin_title'] = 'წაშლილი ფაილების გაწმენდა';
$strings['plugin_comment'] = 'წაშლილად მონიშნული ფაილების სამუდამოდ წაშლა. ჩართეთ menu_administrator სექციაში, შემდეგ გადადით მთავარ ადმინისტრატორის გვერდზე.';
$strings['FileList'] = 'წაშლილად მონიშნული ფაილების სია';
$strings['SizeTotalAllDir'] = 'საერთო ზომა (ყველა დირექტორია)';
$strings['NoFilesDeleted'] = 'წაშლილად მონიშნული ფაილი არ არის';
$strings['FilesDeletedMark'] = 'წაშლილად მონიშნული ფაილები';
$strings['FileDirSize'] = 'დირექტორიის ფაილების ზომა';
$strings['ConfirmDelete'] = 'დარწმუნებული ხართ, რომ გსურთ ფაილის წაშლა?';
$strings['ErrorDeleteFile'] = 'ფაილის წაშლისას შეცდომა მოხდა';
$strings['ErrorEmptyPath'] = 'ფაილის წაშლისას პრობლემა შეგხვდათ, ბილიკი ვერ იქნება ცარიელი';
$strings['DeleteSelectedFiles'] = 'არჩეული ფაილების წაშლა';
$strings['ConfirmDeleteFiles'] = 'დარწმუნებული ხართ, რომ გსურთ ყველა არჩეული ფაილის წაშლა?';
$strings['DeletedSuccess'] = 'ფაილის წაშლა წარმატებით დასრულდა';
$strings['path_dir'] = 'დირექტორია';
$strings['size'] = 'ზომა';
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
$strings['ErrorNotCleanablePath'] = 'ფაილი არ არის გასაწმენდი ორფანი ან მოძველებული წაშლილი ფაილი.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'გასაწმენდი ფიზიკური ფაილები';
$strings['NoCleanableFiles'] = 'ამ საცავის ძირში არ არის გასაწმენდი ფიზიკური ფაილები';
$strings['Chamilo2ResourceStorage'] = 'რესურსის ფაილები';
$strings['Chamilo2ResourceStorageHelp'] = 'var/upload/resource-ის ქვეშ მყოფი ფაილები ჩამოთვლილია მხოლოდ მაშინ, როცა ისინი არ არის მიმართული resource_file-ით. წაშალეთ დოკუმენტები დოკუმენტების ინსტრუმენტიდან ან API-დან, და არა ამ პლაგინიდან.';
$strings['Chamilo2AssetStorage'] = 'აქტივის ფაილები';
$strings['Chamilo2AssetStorageHelp'] = 'var/upload/assets-ის ქვეშ მყოფი ფაილები ჩამოთვლილია მხოლოდ მაშინ, როცა ისინი არ არის მიმართული asset-ით. მიმართული აქტივები ყოველთვის დაცულია.';
$strings['LegacyCourseFiles'] = 'მოძველებული კურსის ფაილები';
$strings['LegacyUploadFiles'] = 'მოძველებული ატვირთული ფაილები';
$strings['LegacyPublicCourseFiles'] = 'მოძველებული საჯარო კურსის ფაილები';
$strings['LegacyPublicUploadFiles'] = 'მოძველებული საჯარო ატვირთული ფაილები';
$strings['LegacyDeletedStorageHelp'] = 'მოძველებული ძირები სკანირდება მხოლოდ იმ ფაილებზე, რომელთა საბაზისო სახელიც შეიცავს DELETED მარკერს.';
$strings['OrphanResourceFile'] = 'ორფანი რესურსის ფაილი';
$strings['OrphanAssetFile'] = 'ორფანი აქტივის ფაილი';
$strings['LegacyDeletedFile'] = 'მოძველებული DELETED ფაილი';
$strings['OrphanResourceFiles'] = 'ორფანი რესურსის ფაილები';
$strings['OrphanAssetFiles'] = 'ორფანი აქტივის ფაილები';
$strings['LegacyDeletedFiles'] = 'მოძველებული DELETED ფაილები';
$strings['Reason'] = 'მიზეზი';
$strings['ReasonOrphanResource'] = 'ფიზიკური ფაილი არსებობს რესურსის საცავში, მაგრამ არცერთი resource_file ჩანაწერი არ მიუთითებს მასზე.';
$strings['ReasonOrphanAsset'] = 'ფიზიკური ფაილი არსებობს აქტივის საცავში, მაგრამ არცერთი asset ჩანაწერი არ მიუთითებს მასზე.';
$strings['ReasonLegacyDeletedMarker'] = 'მოძველებული ფაილის საბაზისო სახელი შეიცავს DELETED მარკერს.';

$strings['StorageNoticeShort'] = 'ატვირთული ფაილები თვალყურს ადევნებს resource_file და asset მეტა-მონაცემები. ეს პლაგინი ჩამოთვლის მხოლოდ var/upload/resource და var/upload/assets-ის ქვეშ მყოფ ფიზიკურ ფაილებს, რომლებიც აღარ არის მიმართული.';
$strings['SafeNoticeShort'] = 'ფაილი სწორი მონაცემთა ბაზის მითითებით დაცულია. დოკუმენტები და ფაილები უნდა წაიშალოს მათი ჩვეულებრივი ინსტრუმენტების საშუალებით.';
$strings['CheckedLocations'] = 'შემოწმებული მდებარეობები';
$strings['DetectionRule'] = 'გამოვლენის წესი';
$strings['NoCleanableFilesFound'] = 'გასაწმენდი ფიზიკური ფაილები ვერ მოიძებნა';
$strings['NoCleanableFilesFoundHelp'] = 'ეს არის მოსალოდნელი შედეგი, როდესაც საცავი თანმიმდევრულია. შემოწმებული მდებარეობები ნაჩვენებია ქვემოთ გამჭვირვალობისთვის.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'შეზღუდული სკანირების გაშვება';
$strings['ScanNotRun'] = 'სკანირება არ დაწყებულა';
$strings['ScanNotRunHelp'] = 'გვერდი არ ახდენს საცავის ავტომატურ სკანირებას, რადგან დიდი var/upload საქაღალდეები შეიძლება იყოს ნელი. დააწკაპუნეთ „შეზღუდული სკანირების გაშვება“ ლოკალური ორფანი ფაილების შესამოწმებლად.';
$strings['ScanLimitedWarning'] = 'სკანირება ადრეულად შეწყდა გვერდის პასუხისმგებლობის შენარჩუნებისთვის. გაუშვით ის კვლავ მოგვიანებით ან შეამოწმეთ საცავი ბრძანების ხაზიდან საჭიროების შემთხვევაში.';

$strings['PathFilter'] = 'ბილიკის ფილტრი';
$strings['PathFilterHelp'] = 'არასავალდებულო. გამოიყენეთ ეს კონკრეტული საქაღალდის შესამოწმებლად ან განსახილველად, მაგალითად clean-deleted-files-test. ის მხოლოდ შედარებით ბილიკებს ემთხვევა.';
$strings['ActivePathFilter'] = 'აქტიური ბილიკის ფილტრი: %s';
