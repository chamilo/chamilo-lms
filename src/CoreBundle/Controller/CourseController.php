<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ExtraFieldRelTagRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use CourseManager;
use Doctrine\ORM\EntityRepository;
use ExtraFieldValue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UserManager;

#[Route('/courses')]
class CourseController extends AbstractController
{
    /**
     * Redirects legacy /courses/ABC/index.php to /courses/1/ (where 1 is the course id) see CourseHomeController.
     */
    #[Route('/{code}/index.php', name: 'chamilo_core_course_home_redirect')]
    public function homeRedirect(Course $course): Response
    {
        return $this->redirectToRoute('chamilo_core_course_home', [
            'cid' => $course->getId(),
        ]);
    }

    /**
     * Redirects legacy /courses/ABC/document/images/file.jpg.
     */
    #[Route('/{code}/document/{path}', name: 'chamilo_core_course_document_redirect', requirements: ['path' => '.*'])]
    public function documentRedirect(Course $course, string $path, CDocumentRepository $documentRepository): Response
    {
        $pathList = explode('/', $path);

        /** @var CDocument|null $document */
        $document = null;
        $parent = $course;
        foreach ($pathList as $part) {
            $document = $documentRepository->findCourseResourceByTitle($part, $parent->getResourceNode(), $course);
            if (null !== $document) {
                $parent = $document;
            }
        }

        if (null !== $document && $document->getResourceNode()->hasResourceFile()) {
            return $this->redirectToRoute('chamilo_core_resource_view', [
                'tool' => 'document',
                'type' => 'file',
                'id' => $document->getResourceNode()->getId(),
            ]);
        }

        throw new AccessDeniedException('File not found');
    }

    #[Route('/{id}/welcome', name: 'chamilo_core_course_welcome')]
    public function welcome(Course $course): Response
    {
        return $this->render('@ChamiloCore/Course/welcome.html.twig', [
            'course' => $course,
        ]);
    }

    #[Route('/{id}/about', name: 'chamilo_core_course_about')]
    public function about(Course $course, IllustrationRepository $illustrationRepository, CCourseDescriptionRepository $courseDescriptionRepository): Response
    {
        $courseId = $course->getId();
        $userId = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $fieldsRepo */
        $fieldsRepo = $em->getRepository(ExtraField::class);
        /** @var ExtraFieldRelTagRepository $fieldTagsRepo */
        $fieldTagsRepo = $em->getRepository(ExtraFieldRelTag::class);

        $courseDescriptions = $courseDescriptionRepository->getResourcesByCourse($course)->getQuery()->getResult();

        $courseValues = new ExtraFieldValue('course');

        $urlCourse = api_get_path(WEB_PATH).sprintf('course/%s/about', $courseId);
        $courseTeachers = $course->getTeachers();
        $teachersData = [];

        foreach ($courseTeachers as $teacherSubscription) {
            $teacher = $teacherSubscription->getUser();
            $userData = [
                'complete_name' => UserManager::formatUserFullName($teacher),
                'image' => $illustrationRepository->getIllustrationUrl($teacher),
                'diploma' => $teacher->getDiplomas(),
                'openarea' => $teacher->getOpenarea(),
            ];

            $teachersData[] = $userData;
        }
        /** @var ExtraField $tagField */
        $tagField = $fieldsRepo->findOneBy([
            'extraFieldType' => ExtraField::COURSE_FIELD_TYPE,
            'variable' => 'tags',
        ]);

        $courseTags = [];
        if (null !== $tagField) {
            $courseTags = $fieldTagsRepo->getTags($tagField, $courseId);
        }

        $courseDescription = $courseObjectives = $courseTopics = $courseMethodology = '';
        $courseMaterial = $courseResources = $courseAssessment = '';
        $courseCustom = [];
        foreach ($courseDescriptions as $descriptionTool) {
            switch ($descriptionTool->getDescriptionType()) {
                case CCourseDescription::TYPE_DESCRIPTION:
                    $courseDescription = $descriptionTool->getContent();

                    break;
                case CCourseDescription::TYPE_OBJECTIVES:
                    $courseObjectives = $descriptionTool;

                    break;
                case CCourseDescription::TYPE_TOPICS:
                    $courseTopics = $descriptionTool;

                    break;
                case CCourseDescription::TYPE_METHODOLOGY:
                    $courseMethodology = $descriptionTool;

                    break;
                case CCourseDescription::TYPE_COURSE_MATERIAL:
                    $courseMaterial = $descriptionTool;

                    break;
                case CCourseDescription::TYPE_RESOURCES:
                    $courseResources = $descriptionTool;

                    break;
                case CCourseDescription::TYPE_ASSESSMENT:
                    $courseAssessment = $descriptionTool;

                    break;
                case CCourseDescription::TYPE_CUSTOM:
                    $courseCustom[] = $descriptionTool;

                    break;
            }
        }

        $topics = [
            'objectives' => $courseObjectives,
            'topics' => $courseTopics,
            'methodology' => $courseMethodology,
            'material' => $courseMaterial,
            'resources' => $courseResources,
            'assessment' => $courseAssessment,
            'custom' => array_reverse($courseCustom),
        ];

        $subscriptionUser = CourseManager::is_user_subscribed_in_course($userId, $course->getCode());

        /*$allowSubscribe = false;
        if ($course->getSubscribe() || api_is_platform_admin()) {
            $allowSubscribe = true;
        }
        $plugin = \BuyCoursesPlugin::create();
        $checker = $plugin->isEnabled();
        $courseIsPremium = null;
        if ($checker) {
            $courseIsPremium = $plugin->getItemByProduct(
                $courseId,
                \BuyCoursesPlugin::PRODUCT_TYPE_COURSE
            );
        }*/

        $image = Container::getIllustrationRepository()->getIllustrationUrl($course, 'course_picture_medium');

        $params = [
            'course' => $course,
            'description' => $courseDescription,
            'image' => $image,
            'syllabus' => $topics,
            'tags' => $courseTags,
            'teachers' => $teachersData,
            'extra_fields' => $courseValues->getAllValuesForAnItem(
                $course->getId(),
                null,
                true
            ),
            'subscription' => $subscriptionUser,
            'url' => '',
            'is_premium' => '',
            'token' => '',
        ];

        $metaInfo = '<meta property="og:url" content="'.$urlCourse.'" />';
        $metaInfo .= '<meta property="og:type" content="website" />';
        $metaInfo .= '<meta property="og:title" content="'.$course->getTitle().'" />';
        $metaInfo .= '<meta property="og:description" content="'.strip_tags($courseDescription).'" />';
        $metaInfo .= '<meta property="og:image" content="'.$image.'" />';

        $htmlHeadXtra[] = $metaInfo;
        $htmlHeadXtra[] = api_get_asset('readmore-js/readmore.js');

        return $this->render('@ChamiloCore/Course/about.html.twig', $params);
    }
}
