<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// Access restrictions
api_protect_admin_script(true);

if (api_get_configuration_value('sync_db_with_schema') != true) {
    api_not_allowed(true);
}

$em = Database::getManager();
$connection = Database::getManager()->getConnection();
$sm = $connection->getSchemaManager();
$fromSchema = $sm->createSchema();

$tool = new \Doctrine\ORM\Tools\SchemaTool(Database::getManager());
$metadatas = $em->getMetadataFactory()->getAllMetadata();
$toSchema = $tool->getSchemaFromMetadata($metadatas);
$comparator = new \Doctrine\DBAL\Schema\Comparator();
$schemaDiff = $comparator->compare($fromSchema, $toSchema);

$sqlList = $schemaDiff->toSaveSql($connection->getDatabasePlatform());
$content = '';
if (!empty($sqlList)) {
    $form = new FormValidator('update');
    $form->addHtml(
        Display::return_message('If you click in update database. The SQL queries below will be executed in the database. Consider creating a DB dump before doing this.')
    );
    $form->addButtonSave(get_lang('Update database'));
    $content = $form->returnForm();

    if ($form->validate()) {
        error_log('---- Sync DB with schema ---');
        foreach ($sqlList as $sql) {
            Database::query($sql);
            error_log($sql);
        }
        error_log('---- End sync ---');
        Display::addFlash(
            Display::return_message(
                get_lang(count($sqlList).' queries were executed. Check your error.log'),
                'success'
            )
        );
        header('Location: '.api_get_self());
        exit;
    }

    $content .= '<pre>';
    foreach ($sqlList as $sql) {
        $content .= ($sql).';  <br />';
    }
    $content .= '</pre>';
} else {
    Display::addFlash(Display::return_message(get_lang('Nothing else to update')));
}

Display::display_header(get_lang('SyncDatabaseWithSchema'));
echo $content;
Display::display_footer();
