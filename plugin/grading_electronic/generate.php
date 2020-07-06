<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;

require_once '../../main/inc/global.inc.php';

$allowed = api_is_teacher() || api_is_platform_admin() || api_is_course_tutor();

$gradingElectronic = GradingElectronicPlugin::create();

try {
    if (!$allowed) {
        throw new Exception(get_lang('NotAllowed'));
    }

    $toolIsEnabled = $gradingElectronic->get('tool_enable') === 'true';

    if (!$toolIsEnabled) {
        throw new Exception($gradingElectronic->get_lang('PluginDisabled'));
    }

    $form = $gradingElectronic->getForm();

    if (!$form->validate()) {
        throw new Exception(implode('<br>', $form->_errors));
    }

    $em = Database::getManager();

    /** @var Course $course */
    $course = $em->find('ChamiloCoreBundle:Course', api_get_course_int_id());
    /** @var Session $session */
    $session = $em->find('ChamiloCoreBundle:Session', api_get_session_id());

    $values = $form->exportValues();

    $cFieldValue = new ExtraFieldValue('course');
    $uFieldValue = new ExtraFieldValue('user');

    $cFieldValue->save([
        'variable' => GradingElectronicPlugin::EXTRAFIELD_COURSE_ID,
        'item_id' => $course->getId(),
        'value' => $values['course'],
    ]);

    $item = $cFieldValue->get_item_id_from_field_variable_and_field_value(
        GradingElectronicPlugin::EXTRAFIELD_COURSE_ID,
        $values['course']
    );

    $fieldProvider = $cFieldValue->get_values_by_handler_and_field_variable(
        $course->getId(),
        GradingElectronicPlugin::EXTRAFIELD_COURSE_PROVIDER_ID
    );
    $fieldHours = $cFieldValue->get_values_by_handler_and_field_variable(
        $course->getId(),
        GradingElectronicPlugin::EXTRAFIELD_COURSE_HOURS
    );

    $students = [];

    if ($session) {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('relationType', Session::STUDENT)
        );

        $subscriptions = $session->getUsers()->matching($criteria);

        /** @var SessionRelUser $subscription */
        foreach ($subscriptions as $subscription) {
            $students[] = $subscription->getUser();
        }
    } else {
        $subscriptions = $course->getStudents();

        /** @var CourseRelUser $subscription */
        foreach ($subscriptions as $subscription) {
            $students[] = $subscription->getUser();
        }
    }

    $cats = Category::load(
        null,
        null,
        $course->getCode(),
        null,
        null,
        $session ? $session->getId() : 0,
        'ORDER By id'
    );

    /** @var \Category $gradebook */
    $gradebook = $cats[0];
    /** @var \ExerciseLink $exerciseLink */
    /** commented until we get clear understanding of how to use the dates refs BT#12404.
        $exerciseInfo = ExerciseLib::get_exercise_by_id($exerciseId, $course->getId());
     */
    $dateStart = new DateTime($values['range_start'].' 00:00:00', new DateTimeZone('UTC'));
    $dateEnd = new DateTime($values['range_end'].' 23:59:59', new DateTimeZone('UTC'));

    $fileData = [];
    $fileData[] = sprintf(
        '1 %s %s%s',
        $fieldProvider ? $fieldProvider['value'] : null,
        $values['course'],
        $dateStart->format('m/d/Y')
    );

    /** @var User $student */
    foreach ($students as $student) {
        $userFinishedCourse = Category::userFinishedCourse(
            $student->getId(),
            $gradebook,
            true
        );
        if (!$userFinishedCourse) {
            continue;
        }
        /** commented until we get clear understanding of how to use the dates refs BT#12404.
                }
         */
        $fieldStudent = $uFieldValue->get_values_by_handler_and_field_variable(
            $student->getId(),
            GradingElectronicPlugin::EXTRAFIELD_STUDENT_ID
        );
        $scoretotal = $gradebook->calc_score($student->getId());
        $scoredisplay = ScoreDisplay::instance();
        $score = $scoredisplay->display_score(
            $scoretotal,
            SCORE_SIMPLE
        );

        /** old method to get the score.
                );
         */
        $fileData[] = sprintf(
            "2 %sPASS%s %s %s",
            $fieldStudent ? $fieldStudent['value'] : null,
            $fieldHours ? $fieldHours['value'] : null,
            $score,
            $dateEnd->format('m/d/Y')
        );

        if (!$gradebook->getGenerateCertificates()) {
            continue;
        }

        Category::generateUserCertificate(
            $gradebook->get_id(),
            $student->getId(),
            true
        );
    }

    $fileName = implode('_', [
        $gradingElectronic->get_title(),
        $values['course'],
        $values['range_start'],
        $values['range_end'],
    ]);
    $fileName = api_replace_dangerous_char($fileName).'.txt';
    $fileData[] = null;

    file_put_contents(
        api_get_path(SYS_ARCHIVE_PATH).$fileName,
        implode("\r\n", $fileData)
    );

    echo Display::toolbarButton(
        get_lang('Download'),
        api_get_path(WEB_ARCHIVE_PATH).$fileName,
        'download',
        'success',
        ['target' => '_blank', 'download' => $fileName]
    );
} catch (Exception $e) {
    echo Display::return_message($e->getMessage(), 'error');
}
