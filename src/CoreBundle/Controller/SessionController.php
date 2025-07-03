<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SequenceRepository;
use Chamilo\CoreBundle\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Repository\TagRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use CourseDescription;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Essence\Essence;
use ExtraFieldValue;
use Graphp\GraphViz\GraphViz;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use SessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UserManager;

#[Route('/sessions')]
class SessionController extends AbstractController
{
    /**
     * @Entity("session", expr="repository.find(sid)")
     */
    #[Route(path: '/{sid}/about', name: 'chamilo_core_session_about')]
    public function about(
        Request $request,
        Session $session,
        IllustrationRepository $illustrationRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $requestSession = $request->getSession();
        $htmlHeadXtra[] = api_get_asset('readmore-js/readmore.js');

        $sessionId = $session->getId();
        $courses = [];
        $sessionCourses = $session->getCourses();

        /** @var EntityRepository $fieldsRepo */
        $fieldsRepo = $em->getRepository(ExtraField::class);

        /** @var TagRepository $tagRepo */
        $tagRepo = $em->getRepository(Tag::class);

        /** @var SequenceRepository $sequenceResourceRepo */
        $sequenceResourceRepo = $em->getRepository(SequenceResource::class);

        /** @var ExtraField $tagField */
        $tagField = $fieldsRepo->findOneBy([
            'itemType' => ExtraField::COURSE_FIELD_TYPE,
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
                $courseTags = $tagRepo->getTagsByItem($tagField, $sessionCourse->getId());
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

        $hasRequirements = false;

        /*$sessionRequirements = $sequenceResourceRepo->getRequirements(
         * $session->getId(),
         * SequenceResource::SESSION_TYPE
         * );
         * foreach ($sessionRequirements as $sequence) {
         * if (!empty($sequence['requirements'])) {
         * $hasRequirements = true;
         * break;
         * }
         * }*/
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

        $redirectToSession = ('true' === Container::getSettingsManager()->getSetting('session.allow_redirect_to_session_after_inscription_about'));
        $redirectToSession = $redirectToSession ? '?s='.$sessionId : false;

        $coursesInThisSession = SessionManager::get_course_list_by_session_id($sessionId);
        $coursesCount = \count($coursesInThisSession);
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
            'has_requirements' => $hasRequirements,
            // 'sequences' => $sessionRequirements,
            'is_premium' => $sessionIsPremium,
            'show_tutor' => 'true' === api_get_setting('show_session_coach'),
            'page_url' => api_get_path(WEB_PATH).\sprintf('sessions/%s/about/', $session->getId()),
            'session_date' => $sessionDates,
            'is_subscribed' => SessionManager::isUserSubscribedAsStudent(
                $session->getId(),
                api_get_user_id()
            ),
            'user_session_time' => SessionManager::getDayLeftInSession(
                [
                    'id' => $session->getId(),
                    'duration' => $session->getDuration(),
                ],
                api_get_user_id()
            ),
            'base_url' => $request->getSchemeAndHttpHost(),
        ];

        return $this->render('@ChamiloCore/Session/about.html.twig', $params);
    }

    #[Route('/{sessionId}/next-session', name: 'chamilo_session_next_session')]
    public function getNextSession(
        int $sessionId,
        Request $request,
        SequenceResourceRepository $repo,
        Security $security
    ): JsonResponse {

        $requirementAndDependencies = $repo->getRequirementAndDependencies(
            $sessionId,
            SequenceResource::SESSION_TYPE
        );

        $requirements = [];
        $dependencies = [];

        if (!empty($requirementAndDependencies['requirements'])) {
            foreach ($requirementAndDependencies['requirements'] as $requirement) {
                $requirements[] = [
                    'id' => $requirement['id'],
                    'name' => $requirement['name'],
                    'admin_link' => $requirement['admin_link'],
                ];
            }
        }

        if (!empty($requirementAndDependencies['dependencies'])) {
            foreach ($requirementAndDependencies['dependencies'] as $dependency) {
                $dependencies[] = [
                    'id' => $dependency['id'],
                    'name' => $dependency['name'],
                    'admin_link' => $dependency['admin_link'],
                ];
            }
        }

        $sequenceResource = $repo->findRequirementForResource(
            $sessionId,
            SequenceResource::SESSION_TYPE
        );

        $graphImage = null;
        if ($sequenceResource && $sequenceResource->hasGraph()) {
            $graph = $sequenceResource->getSequence()->getUnSerializeGraph();
            if ($graph !== null) {
                $graph->setAttribute('graphviz.node.fontname', 'arial');
                $graphviz = new GraphViz();
                $graphImage = $graphviz->createImageSrc($graph);
            }
        }

        return new JsonResponse([
            'requirements' => $requirements,
            'dependencies' => $dependencies,
            'graph' => $graphImage,
        ]);
    }
}
