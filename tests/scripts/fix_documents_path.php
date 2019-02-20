<?php
/* For licensing terms, see /license.txt */

exit;
require_once __DIR__.'/../../main/inc/global.inc.php';

$courses = CourseManager::get_courses_list();

$pathToSearch = api_get_path(WEB_PATH).'../../../../../../../';
$chamilo = VChamiloPlugin::create();
$chamilo->getAdminUrl()
$courseSysPath = api_get_path(SYS_COURSE_PATH);
foreach ($courses as $course) {
    $course['directory'] = 'FORMATIONSCHAMILO';
    $docsPath = $courseSysPath.$course['directory'].'/document/';
    $finder = new \Symfony\Component\Finder\Finder();
    $finder->files()->in($docsPath)->name('*.html');

    foreach ($finder as $file) {
        echo $file->getRealPath().PHP_EOL;
        $contents = file_get_contents($file->getRealPath());
        echo $pathToSearch;
        $newContent = str_replace($pathToSearch, 'courses', $contents);
       // file_put_contents($file->getRealPath())
    }exit;
}
