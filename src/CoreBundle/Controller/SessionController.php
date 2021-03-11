<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ExtraFieldRelTagRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SequenceRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use CourseDescription;
use Doctrine\ORM\EntityRepository;
use Essence\Essence;
use ExtraFieldValue;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use SessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use UserManager;

/**
 * Class SessionController.
 *
 * @Route("/sessions")
 */
class SessionController extends AbstractController
{
    /**
     * @Route("/{sid}/about", name="chamilo_core_session_about")
     *
     * @Entity("session", expr="repository.find(sid)")
     */
    public function aboutAction(Request $request, Session $session, IllustrationRepository $illustrationRepo, UserRepository $userRepo): Response
    {
        $requestSession = $request->getSession();
        $htmlHeadXtra[] = api_get_asset('readmore-js/readmore.js');
        $em = $this->getDoctrine()->getManager();

        $sessionId = $session->getId();
        $courses = [];
        $sessionCourses = $session->getCourses();

        /** @var EntityRepository $fieldsRepo */
        $fieldsRepo = $em->getRepository(ExtraField::class);
        /** @var ExtraFieldRelTagRepository $fieldTagsRepo */
        $fieldTagsRepo = $em->getRepository(ExtraFieldRelTag::class);

        /** @var SequenceRepository $sequenceResourceRepo */
        $sequenceResourceRepo = $em->getRepository(SequenceResource::class);

        /** @var ExtraField $tagField */
        $tagField = $fieldsRepo->findOneBy([
            'extraFieldType' => ExtraField::COURSE_FIELD_TYPE,
            'variable' => 'tags',
        ]);

        $courseValues = new ExtraFieldValue('course');
        $userValues = new ExtraFieldValue('user');
        $sessionValues = new ExtraFieldValue('session');

        /** @var SessionRelCourse $sessionRelCourse */
        foreach ($sessionCourses as $sessionRelCourse) {
            $sessionCourse = $sessionRelCourse->getCourse();
            $courseTags = [];

            if (null !== $tagField) {
                $courseTags = $fieldTagsRepo->getTags($tagField, $sessionCourse->getId());
            }

            $courseCoaches = $userRepo->getCoachesForSessionCourse($session, $sessionCourse);
            $coachesData = [];
            /** @var User $courseCoach */
            foreach ($courseCoaches as $courseCoach) {
                $coachData = [
                    'complete_name' => UserManager::formatUserFullName($courseCoach),
                    'image' => $illustrationRepo->getIllustrationUrl($courseCoach),
                    'diploma' => $courseCoach->getDiplomas(),
                    'openarea' => $courseCoach->getOpenarea(),
                    'extra_fields' => $userValues->getAllValuesForAnItem(
                        $courseCoach->getId(),
                        null,
                        true
                    ),
                ];

                $coachesData[] = $coachData;
            }

            $cd = new CourseDescription();
            $cd->set_course_id($sessionCourse->getId());
            $cd->set_session_id($session->getId());
            $descriptionsData = $cd->get_description_data();

            $courseDescription = [];
            $courseObjectives = [];
            $courseTopics = [];
            $courseMethodology = [];
            $courseMaterial = [];
            $courseResources = [];
            $courseAssessment = [];
            $courseCustom = [];

            if (!empty($descriptionsData)) {
                foreach ($descriptionsData as $descriptionInfo) {
                    $type = $descriptionInfo->getDescriptionType();
                    switch ($type) {
                        case CCourseDescription::TYPE_DESCRIPTION:
                            $courseDescription[] = $descriptionInfo;

                            break;
                        case CCourseDescription::TYPE_OBJECTIVES:
                            $courseObjectives[] = $descriptionInfo;

                            break;
                        case CCourseDescription::TYPE_TOPICS:
                            $courseTopics[] = $descriptionInfo;

                            break;
                        case CCourseDescription::TYPE_METHODOLOGY:
                            $courseMethodology[] = $descriptionInfo;

                            break;
                        case CCourseDescription::TYPE_COURSE_MATERIAL:
                            $courseMaterial[] = $descriptionInfo;

                            break;
                        case CCourseDescription::TYPE_RESOURCES:
                            $courseResources[] = $descriptionInfo;

                            break;
                        case CCourseDescription::TYPE_ASSESSMENT:
                            $courseAssessment[] = $descriptionInfo;

                            break;
                        case CCourseDescription::TYPE_CUSTOM:
                            $courseCustom[] = $descriptionInfo;

                            break;
                    }
                }
            }

            $courses[] = [
                'course' => $sessionCourse,
                'description' => $courseDescription,
                'image' => Container::getIllustrationRepository()->getIllustrationUrl($sessionCourse),
                'tags' => $courseTags,
                'objectives' => $courseObjectives,
                'topics' => $courseTopics,
                'methodology' => $courseMethodology,
                'material' => $courseMaterial,
                'resources' => $courseResources,
                'assessment' => $courseAssessment,
                'custom' => array_reverse($courseCustom),
                'coaches' => $coachesData,
                'extra_fields' => $courseValues->getAllValuesForAnItem(
                    $sessionCourse->getId(),
                    null,
                    true
                ),
            ];
        }

        $sessionDates = SessionManager::parseSessionDates($session, true);

        /*$sessionRequirements = $sequenceResourceRepo->getRequirements(
            $session->getId(),
            SequenceResource::SESSION_TYPE
        );

        $hasRequirements = false;
        foreach ($sessionRequirements as $sequence) {
            if (!empty($sequence['requirements'])) {
                $hasRequirements = true;

                break;
            }
        }*/

        $plugin = BuyCoursesPlugin::create();
        $checker = $plugin->isEnabled();
        $sessionIsPremium = null;
        if ($checker) {
            $sessionIsPremium = $plugin->getItemByProduct(
                $sessionId,
                BuyCoursesPlugin::PRODUCT_TYPE_SESSION
            );
            if ([] !== $sessionIsPremium) {
                $requestSession->set('SessionIsPremium', true);
                $requestSession->set('sessionId', $sessionId);
            }
        }

        $redirectToSession = api_get_configuration_value('allow_redirect_to_session_after_inscription_about');
        $redirectToSession = $redirectToSession ? '?s='.$sessionId : false;

        $coursesInThisSession = SessionManager::get_course_list_by_session_id($sessionId);
        $coursesCount = count($coursesInThisSession);
        $redirectToSession = 1 === $coursesCount && $redirectToSession
            ? ($redirectToSession.'&cr='.array_values($coursesInThisSession)[0]['directory'])
            : $redirectToSession;

        $essence = new Essence();

        $params = [
            'session' => $session,
            'redirect_to_session' => $redirectToSession,
            'courses' => $courses,
            'essence' => $essence,
            'session_extra_fields' => $sessionValues->getAllValuesForAnItem($session->getId(), null, true),
            //'has_requirements' => $hasRequirements,
            //'sequences' => $sessionRequirements,
            'is_premium' => $sessionIsPremium,
            'show_tutor' => 'true' === api_get_setting('show_session_coach'),
            'page_url' => api_get_path(WEB_PATH).sprintf('sessions/%s/about/', $session->getId()),
            'session_date' => $sessionDates,
            'is_subscribed' => SessionManager::isUserSubscribedAsStudent(
                $session->getId(),
                api_get_user_id()
            ),
            /*'subscribe_button' => \CoursesAndSessionsCatalog::getRegisteredInSessionButton(
                $session->getId(),
                $session->getName(),
                $hasRequirements,
                true,
                true
            ),*/
            'user_session_time' => SessionManager::getDayLeftInSession(
                [
                    'id' => $session->getId(),
                    'duration' => $session->getDuration(),
                ],
                api_get_user_id()
            ),
        ];

        return $this->render('@ChamiloCore/Session/about.html.twig', $params);
    }
}
