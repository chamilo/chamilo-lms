<?php
$strings['plugin_title'] = 'ล้างไฟล์ที่ถูกลบ';
$strings['plugin_comment'] = 'ลบไฟล์ที่ถูกทำเครื่องหมายว่าถูกลบอย่างถาวร เปิดใช้งานในส่วนเมนูผู้ดูแลระบบ จากนั้นเข้าถึงจากหน้าหลักผู้ดูแลระบบ';
$strings['FileList'] = 'รายการไฟล์ที่ถูกทำเครื่องหมายว่าถูกลบ';
$strings['SizeTotalAllDir'] = 'ขนาดรวม (ทุกไดเรกทอรี)';
$strings['NoFilesDeleted'] = 'ไม่มีไฟล์ที่ถูกทำเครื่องหมายว่าถูกลบ';
$strings['FilesDeletedMark'] = 'ไฟล์ที่ถูกทำเครื่องหมายว่าถูกลบ';
$strings['FileDirSize'] = 'ขนาดไฟล์ไดเรกทอรี';
$strings['ConfirmDelete'] = 'คุณแน่ใจหรือไม่ว่าต้องการลบไฟล์นี้?';
$strings['ErrorDeleteFile'] = 'เกิดข้อผิดพลาดขณะลบไฟล์';
$strings['ErrorEmptyPath'] = 'เกิดปัญหาในการลบไฟล์ เส้นทางไม่สามารถว่างเปล่าได้';
$strings['DeleteSelectedFiles'] = 'ลบไฟล์ที่เลือก';
$strings['ConfirmDeleteFiles'] = 'คุณแน่ใจหรือไม่ว่าต้องการลบไฟล์ที่เลือกทั้งหมด?';
$strings['DeletedSuccess'] = 'การลบไฟล์สำเร็จ';
$strings['path_dir'] = 'ไดเรกทอรี';
$strings['size'] = 'ขนาด';
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
$strings['ErrorNotCleanablePath'] = 'ไฟล์นี้ไม่ใช่ไฟล์ orphan หรือไฟล์ที่ถูกลบแบบ legacy ที่สามารถล้างได้';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'ไฟล์ทางกายภาพที่สามารถล้างได้';
$strings['NoCleanableFiles'] = 'ไม่มีไฟล์ทางกายภาพที่สามารถล้างได้ในพื้นที่เก็บข้อมูลรากนี้';
$strings['Chamilo2ResourceStorage'] = 'ไฟล์ทรัพยากร';
$strings['Chamilo2ResourceStorageHelp'] = 'ไฟล์ภายใต้ var/upload/resource จะแสดงเฉพาะเมื่อไม่ได้ถูกอ้างอิงโดย resource_file โปรดลบเอกสารจากเครื่องมือ Documents หรือ API ไม่ใช่จากปลั๊กอินนี้';
$strings['Chamilo2AssetStorage'] = 'ไฟล์แอสเซท';
$strings['Chamilo2AssetStorageHelp'] = 'ไฟล์ภายใต้ var/upload/assets จะแสดงเฉพาะเมื่อไม่ได้ถูกอ้างอิงโดย asset แอสเซทที่ถูกอ้างอิงจะได้รับการป้องกันเสมอ';
$strings['LegacyCourseFiles'] = 'ไฟล์รายวิชาแบบ legacy';
$strings['LegacyUploadFiles'] = 'ไฟล์อัปโหลดแบบ legacy';
$strings['LegacyPublicCourseFiles'] = 'ไฟล์รายวิชาสาธารณะแบบ legacy';
$strings['LegacyPublicUploadFiles'] = 'ไฟล์อัปโหลดสาธารณะแบบ legacy';
$strings['LegacyDeletedStorageHelp'] = 'ไดเรกทอรี legacy จะถูกสแกนเฉพาะไฟล์ที่มีชื่อไฟล์ (basename) ประกอบด้วยเครื่องหมาย DELETED';
$strings['OrphanResourceFile'] = 'ไฟล์ทรัพยากร orphan';
$strings['OrphanAssetFile'] = 'ไฟล์แอสเซท orphan';
$strings['LegacyDeletedFile'] = 'ไฟล์ DELETED แบบ legacy';
$strings['OrphanResourceFiles'] = 'ไฟล์ทรัพยากร orphan';
$strings['OrphanAssetFiles'] = 'ไฟล์แอสเซท orphan';
$strings['LegacyDeletedFiles'] = 'ไฟล์ DELETED แบบ legacy';
$strings['Reason'] = 'เหตุผล';
$strings['ReasonOrphanResource'] = 'ไฟล์ทางกายภาพมีอยู่ในพื้นที่เก็บทรัพยากร แต่ไม่มีแถว resource_file ชี้ไปที่ไฟล์นั้น';
$strings['ReasonOrphanAsset'] = 'ไฟล์ทางกายภาพมีอยู่ในพื้นที่เก็บแอสเซท แต่ไม่มีแถว asset ชี้ไปที่ไฟล์นั้น';
$strings['ReasonLegacyDeletedMarker'] = 'ชื่อไฟล์ (basename) ของไฟล์ legacy มีเครื่องหมาย DELETED';

$strings['StorageNoticeShort'] = 'ไฟล์ที่อัปโหลดจะถูกติดตามผ่าน resource_file และ metadata ของ asset ปลั๊กอินนี้แสดงเฉพาะไฟล์ทางกายภาพภายใต้ var/upload/resource และ var/upload/assets ที่ไม่ถูกอ้างอิงอีกต่อไป';
$strings['SafeNoticeShort'] = 'ไฟล์ที่มีการอ้างอิงฐานข้อมูลที่ถูกต้องจะได้รับการป้องกัน เอกสารและไฟล์ควรยังคงถูกลบผ่านเครื่องมือปกติของตน';
$strings['CheckedLocations'] = 'ตำแหน่งที่ตรวจสอบ';
$strings['DetectionRule'] = 'กฎการตรวจจับ';
$strings['NoCleanableFilesFound'] = 'ไม่พบไฟล์ทางกายภาพที่สามารถล้างได้';
$strings['NoCleanableFilesFoundHelp'] = 'นี่คือผลลัพธ์ที่คาดหวังเมื่อพื้นที่เก็บข้อมูลมีความสอดคล้องกัน ตำแหน่งที่ตรวจสอบจะแสดงด้านล่างเพื่อความโปร่งใส';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'เรียกใช้การสแกนแบบจำกัด';
$strings['ScanNotRun'] = 'ยังไม่ได้เริ่มการสแกน';
$strings['ScanNotRunHelp'] = 'หน้านี้ไม่สแกนพื้นที่เก็บข้อมูลโดยอัตโนมัติ เนื่องจากโฟลเดอร์ var/upload ขนาดใหญ่อาจทำงานช้า คลิก เรียกใช้การสแกนแบบจำกัด เพื่อตรวจสอบไฟล์ orphan ในเครื่อง';
$strings['ScanLimitedWarning'] = 'การสแกนถูกหยุดก่อนกำหนดเพื่อให้หน้ายังตอบสนองได้ เรียกใช้ใหม่อีกครั้งในภายหลัง หรือตรวจสอบพื้นที่เก็บข้อมูลจาก command line หากจำเป็น';

$strings['PathFilter'] = 'ตัวกรองพาธ';
$strings['PathFilterHelp'] = 'ไม่บังคับ ใช้เพื่อทดสอบหรือตรวจสอบโฟลเดอร์เฉพาะ เช่น clean-deleted-files-test ใช้ได้กับพาธแบบ relative เท่านั้น';
$strings['ActivePathFilter'] = 'ตัวกรองพาธที่ใช้งานอยู่: %s';
