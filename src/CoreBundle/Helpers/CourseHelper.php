<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Agenda;
use AnnouncementManager;
use Answer;
use AppPlugin;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CGroupCategory;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use DocumentManager;
use Exception;
use Exercise;
use InvalidArgumentException;
use Link;
use LogicException;
use MultipleAnswer;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class CourseHelper
{
    public const MAX_COURSE_LENGTH_CODE = 40;
    private bool $debug = false;

    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly CourseRepository $courseRepository,
        private readonly Security $security,
        private readonly CourseCategoryRepository $courseCategoryRepository,
        private readonly UserRepository $userRepository,
        private readonly SettingsManager $settingsManager,
        private readonly TranslatorInterface $translator,
        private readonly MailerInterface $mailer,
        private readonly EventLoggerHelper $eventLoggerHelper,
        private readonly ParameterBagInterface $parameterBag,
        private readonly RequestStack $requestStack,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly IllustrationRepository $illustrationRepository
    ) {}

    public function createCourse(array $params): ?Course
    {
        $this->debugLog('createCourse:start', [
            'title' => $params['title'] ?? null,
            'wanted_code' => $params['wanted_code'] ?? null,
            'exemplary_content' => !empty($params['exemplary_content']),
        ]);

        if (empty($params['title'])) {
            throw new InvalidArgumentException('The course title cannot be empty.');
        }

        if (empty($params['wanted_code'])) {
            $params['wanted_code'] = $this->generateCourseCode($params['title']);
            $this->debugLog('createCourse:generatedWantedCode', ['wanted_code' => $params['wanted_code']]);
        }

        if ($this->courseRepository->courseCodeExists($params['wanted_code'])) {
            $this->debugLog('createCourse:duplicateCode', ['wanted_code' => $params['wanted_code']]);

            throw new Exception('The course code already exists: '.$params['wanted_code']);
        }

        $keys = $this->defineCourseKeys($params['wanted_code']);
        $this->debugLog('createCourse:keys', $keys);

        $params = array_merge($params, $keys);
        $course = $this->registerCourse($params);
        if ($course) {
            $this->debugLog('createCourse:registered', ['courseId' => $course->getId()]);
            $this->handlePostCourseCreation($course, $params);
            $this->debugLog('createCourse:done', ['courseId' => $course->getId()]);
        }

        return $course;
    }

    public function registerCourse(array $rawParams): ?Course
    {
        $this->debugLog('registerCourse:start', [
            'exemplary_content_raw' => !empty($rawParams['exemplary_content']),
            'has_template' => isset($rawParams['course_template']) && '' !== $rawParams['course_template'],
        ]);

        try {
            /** @var User|null $currentUser */
            $currentUser = $this->security->getUser();

            // Fallback admin user if running from CLI
            if (!$currentUser instanceof User) {
                $currentUser = $this->getFallbackAdminUser();
                $this->debugLog('registerCourse:fallbackAdmin', ['adminId' => $currentUser->getId()]);
            }

            $params = $this->prepareAndValidateCourseData($rawParams);
            $params['_exemplary_content_request'] = !empty($rawParams['exemplary_content']);

            $accessUrl = $this->accessUrlHelper->getCurrent();
            $course = new Course();
            $course
                ->setTitle($params['title'])
                ->setCode($params['code'])
                ->setVisualCode($params['visualCode'])
                ->setCourseLanguage($params['courseLanguage'])
                ->setDescription($this->translator->trans('Course Description'))
                ->setVisibility((int) $params['visibility'])
                ->setShowScore(1)
                ->setDiskQuota((int) $params['diskQuota'])
                ->setExpirationDate($params['expirationDate'])
                ->setDepartmentName($params['departmentName'] ?? '')
                ->setDepartmentUrl($params['departmentUrl'])
                ->setSubscribe($params['subscribe'])
                ->setSticky($params['sticky'] ?? false)
                ->setVideoUrl($params['videoUrl'] ?? '')
                ->setUnsubscribe($params['unsubscribe'])
                ->setCreator($currentUser)
            ;
            $course->addAccessUrl($accessUrl);

            if (!empty($params['categories'])) {
                $this->debugLog('registerCourse:categoriesAttach', ['count' => \count($params['categories'])]);
                foreach ($params['categories'] as $categoryId) {
                    $category = $this->courseCategoryRepository->find($categoryId);
                    if ($category) {
                        $course->addCategory($category);
                    }
                }
            }

            $addTeacher = $params['add_user_as_teacher'] ?? true;
            $user = $currentUser ?? $this->getFallbackAdminUser();
            if (!empty($params['user_id'])) {
                $user = $this->userRepository->find((int) $params['user_id']);
                $this->debugLog('registerCourse:explicitUser', ['userId' => $user?->getId()]);
            }
            if ($addTeacher) {
                $courseRelTutor = (new CourseRelUser())
                    ->setCourse($course)
                    ->setUser($user)
                    ->setStatus(1)
                    ->setTutor(true)
                    ->setRelationType(0)
                    ->setUserCourseCat(0)
                ;
                $course->addSubscription($courseRelTutor);
            }

            if (!empty($params['teachers'])) {
                $this->debugLog('registerCourse:additionalTeachers', ['count' => \count($params['teachers'])]);
                foreach ($params['teachers'] as $teacherId) {
                    $teacher = $this->userRepository->find($teacherId);
                    if ($teacher) {
                        $courseRelTeacher = (new CourseRelUser())
                            ->setCourse($course)
                            ->setUser($teacher)
                            ->setStatus(1)
                            ->setTutor(false)
                            ->setRelationType(0)
                            ->setUserCourseCat(0)
                        ;
                        $course->addSubscription($courseRelTeacher);
                    }
                }
            }

            $this->courseRepository->create($course);
            $this->debugLog('registerCourse:persisted', ['courseId' => $course->getId()]);

            if (!empty($rawParams['illustration_path'])) {
                if (file_exists($rawParams['illustration_path'])) {
                    $mimeType = mime_content_type($rawParams['illustration_path']);
                    $uploadedFile = new UploadedFile(
                        $rawParams['illustration_path'],
                        basename($rawParams['illustration_path']),
                        $mimeType,
                        null,
                        true // test mode to allow local files
                    );

                    $this->illustrationRepository->addIllustration(
                        $course,
                        $currentUser,
                        $uploadedFile
                    );
                    $this->debugLog('registerCourse:illustrationAdded', ['path' => $rawParams['illustration_path']]);
                }
            }

            if (!empty($rawParams['exemplary_content'])) {
                $this->debugLog('registerCourse:willFillCourse', [
                    'request_demo' => true,
                ]);
                $this->fillCourse($course, $params);
            } else {
                $this->debugLog('registerCourse:skipFillCourse', [
                    'request_demo' => false,
                ]);
            }

            if (isset($rawParams['course_template'])) {
                $this->debugLog('registerCourse:maybeTemplate', ['template' => (int) $rawParams['course_template']]);
                $this->useTemplateAsBasisIfRequired(
                    $course->getCode(),
                    (int) $rawParams['course_template']
                );
            }

            $this->debugLog('registerCourse:done', ['courseId' => $course->getId()]);

            return $course;
        } catch (Exception $e) {
            $this->debugLog('registerCourse:exception', ['msg' => $e->getMessage()]);

            throw $e;
        }
    }

    public function sendEmailToAdmin(Course $course): void
    {
        $siteName = $this->getDefaultSetting('platform.site_name');
        $recipientEmail = $this->getDefaultSetting('admin.administrator_email');
        $recipientName = $this->getDefaultSetting('admin.administrator_name').' '.$this->getDefaultSetting('admin.administrator_surname');
        $institutionName = $this->getDefaultSetting('platform.institution');
        $courseName = $course->getTitle();

        $subject = $this->translator->trans('New course created on %s')." $siteName - $institutionName";

        $greeting = $this->translator->trans('Dear %s,');
        $intro = $this->translator->trans('This message is to inform you that a new course has been created on %s');
        $courseNameLabel = $this->translator->trans('Course name');

        $message = \sprintf($greeting, $recipientName)."\n\n";
        $message .= "$intro $siteName - $institutionName.\n";
        $message .= "$courseNameLabel $courseName\n";
        $message .= $this->translator->trans('Course name').': '.$course->getTitle()."\n";

        foreach ($course->getCategories() as $category) {
            $message .= $this->translator->trans('Category').': '.$category->getCode()."\n";
        }

        $message .= $this->translator->trans('Coach').': '.$course->getTutorName()."\n";
        $message .= $this->translator->trans('Language').': '.$course->getCourseLanguage();

        $email = (new Email())
            ->from($recipientEmail)
            ->to($recipientEmail)
            ->subject($subject)
            ->text($message)
        ;

        $this->mailer->send($email);
    }

    public function defineCourseKeys(
        string $wantedCode,
        string $prefixForAll = '',
        string $prefixForPath = '',
        bool $addUniquePrefix = false,
        bool $useCodeIndependentKeys = true
    ): array {
        $wantedCode = $this->generateCourseCode($wantedCode);
        $keysCourseCode = $useCodeIndependentKeys ? $wantedCode : '';

        $uniquePrefix = $addUniquePrefix ? substr(md5(uniqid((string) rand(), true)), 0, 10) : '';

        $keys = [];
        $finalSuffix = ['CourseId' => '', 'CourseDir' => ''];
        $limitNumTry = 100;
        $tryCount = 0;

        $keysAreUnique = false;

        while (!$keysAreUnique && $tryCount < $limitNumTry) {
            $keysCourseId = $prefixForAll.$uniquePrefix.$keysCourseCode.$finalSuffix['CourseId'];
            $keysCourseRepository = $prefixForPath.$uniquePrefix.$wantedCode.$finalSuffix['CourseDir'];

            if ($this->courseRepository->courseCodeExists($keysCourseId)) {
                $finalSuffix['CourseId'] = substr(md5(uniqid((string) rand(), true)), 0, 4);
                $tryCount++;
            } else {
                $keysAreUnique = true;
            }
        }

        if ($keysAreUnique) {
            $keys = [
                'code' => $keysCourseCode,
                'visual_code' => $keysCourseId,
                'directory' => $keysCourseRepository,
            ];
        }

        return $keys;
    }

    private function fillCourse(Course $course, array $params): void
    {
        $this->debugLog('fillCourse:start', [
            'courseId' => $course->getId(),
            'exemplary_request' => !empty($params['_exemplary_content_request']),
        ]);

        $this->insertCourseSettings($course);
        $this->debugLog('fillCourse:insertCourseSettings:ok');

        $this->createGroupCategory($course);
        $this->debugLog('fillCourse:createGroupCategory:ok');

        $gradebook = $this->createRootGradebook($course);
        $this->debugLog('fillCourse:createRootGradebook:ok', ['gradebookId' => $gradebook->getId()]);

        $setting = $this->settingsManager->getSetting('course.example_material_course_creation');
        $this->debugLog('fillCourse:setting.example_material_course_creation', ['value' => $setting]);

        if ('true' === $setting) {
            $this->debugLog('fillCourse:insertExampleContent:willRun');
            $this->insertExampleContent($course, $gradebook);
            $this->debugLog('fillCourse:insertExampleContent:done');
        } else {
            $this->debugLog('fillCourse:insertExampleContent:skippedBySetting');
        }

        $this->entityManager->flush();
        $this->debugLog('fillCourse:end');
    }

    private function insertCourseSettings(Course $course): void
    {
        $defaultEmailExerciseAlert = 0;
        if ('true' === $this->settingsManager->getSetting('exercise.email_alert_manager_on_new_quiz')) {
            $defaultEmailExerciseAlert = 1;
        }

        $settings = [
            'email_alert_manager_on_new_doc' => ['title' => '', 'default' => 0, 'category' => 'work'],
            'email_alert_on_new_doc_dropbox' => ['default' => 0, 'category' => 'dropbox'],
            'allow_user_edit_agenda' => ['default' => 0, 'category' => 'agenda'],
            'allow_user_edit_announcement' => ['default' => 0, 'category' => 'announcement'],
            'email_alert_manager_on_new_quiz' => ['default' => $defaultEmailExerciseAlert, 'category' => 'quiz'],
            'allow_user_image_forum' => ['default' => 1, 'category' => 'forum'],
            'course_theme' => ['default' => '', 'category' => 'theme'],
            'allow_learning_path_theme' => ['default' => 1, 'category' => 'theme'],
            'allow_open_chat_window' => ['default' => 1, 'category' => 'chat'],
            'email_alert_to_teacher_on_new_user_in_course' => ['default' => 0, 'category' => 'registration'],
            'allow_user_view_user_list' => ['default' => 1, 'category' => 'user'],
            'display_info_advance_inside_homecourse' => ['default' => 1, 'category' => 'thematic_advance'],
            'email_alert_students_on_new_homework' => ['default' => 0, 'category' => 'work'],
            'enable_lp_auto_launch' => ['default' => 0, 'category' => 'learning_path'],
            'enable_exercise_auto_launch' => ['default' => 0, 'category' => 'exercise'],
            'enable_document_auto_launch' => ['default' => 0, 'category' => 'document'],
            'pdf_export_watermark_text' => ['default' => '', 'category' => 'learning_path'],
            'allow_public_certificates' => [
                'default' => 'true' === $this->settingsManager->getSetting('certificate.allow_public_certificates') ? 1 : '',
                'category' => 'certificates',
            ],
            'documents_default_visibility' => ['default' => 'visible', 'category' => 'document'],
            'show_course_in_user_language' => ['default' => 2, 'category' => null],
            'email_to_teachers_on_new_work_feedback' => ['default' => 1, 'category' => null],
        ];

        foreach ($settings as $variable => $setting) {
            $courseSetting = new CCourseSetting();
            $courseSetting->setCId($course->getId());
            $courseSetting->setVariable($variable);
            $courseSetting->setTitle($setting['title'] ?? '');
            $courseSetting->setValue((string) $setting['default']);
            $courseSetting->setCategory($setting['category'] ?? '');

            $this->entityManager->persist($courseSetting);
        }

        $this->entityManager->flush();
    }

    private function createGroupCategory(Course $course): void
    {
        $groupCategory = new CGroupCategory();
        $groupCategory
            ->setTitle($this->translator->trans('Default groups'))
            ->setParent($course)
            ->addCourseLink($course)
        ;

        $this->entityManager->persist($groupCategory);
        $this->entityManager->flush();
    }

    private function createRootGradebook(Course $course): GradebookCategory
    {
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            $currentUser = $this->getFallbackAdminUser();
        }

        if (!$currentUser) {
            throw new LogicException('There is no user currently authenticated..');
        }

        $gradebookCategory = new GradebookCategory();
        $gradebookCategory
            ->setTitle($course->getCode())
            ->setLocked(0)
            ->setGenerateCertificates(false)
            ->setDescription('')
            ->setCourse($course)
            ->setWeight(100)
            ->setVisible(false)
            ->setCertifMinScore(75)
            ->setUser($currentUser)
        ;

        $this->entityManager->persist($gradebookCategory);
        $this->entityManager->flush();

        return $gradebookCategory;
    }

    private function insertExampleContent(Course $course, GradebookCategory $gradebook): void
    {
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            $currentUser = $this->getFallbackAdminUser();
        }

        $docsCreated = 0;
        $foldersCreated = 0;
        $filesCreated = 0;
        $linksCreated = 0;
        $announcementId = null;
        $agendaAdded = false;
        $exerciseId = null;
        $forumCategoryId = null;
        $forumId = null;
        $threadId = null;

        $this->debugLog('insertExampleContent:begin', ['courseId' => $course->getId()]);

        $files = [
            ['path' => '/audio', 'title' => $this->translator->trans('Audio'), 'filetype' => 'folder', 'size' => 0],
            ['path' => '/images', 'title' => $this->translator->trans('Images'), 'filetype' => 'folder', 'size' => 0],
            ['path' => '/images/gallery', 'title' => $this->translator->trans('Gallery'), 'filetype' => 'folder', 'size' => 0],
            ['path' => '/video', 'title' => $this->translator->trans('Video'), 'filetype' => 'folder', 'size' => 0],
        ];
        $paths = [];
        $courseInfo = ['real_id' => $course->getId(), 'code' => $course->getCode()];
        foreach ($files as $file) {
            try {
                $doc = DocumentManager::addDocument(
                    $courseInfo,
                    $file['path'],
                    $file['filetype'],
                    $file['size'],
                    $file['title'],
                    null,
                    0,
                    null,
                    0,
                    0,
                    0,
                    false
                );
                $iid = method_exists($doc, 'getIid') ? $doc->getIid() : null;
                $paths[$file['path']] = $iid;
                $foldersCreated++;
                $docsCreated++;
                $this->debugLog('insertExampleContent:document:root', [
                    'path' => $file['path'],
                    'title' => $file['title'],
                    'iid' => $iid,
                ]);
            } catch (Throwable $e) {
                $this->debugLog('insertExampleContent:document:root:error', [
                    'path' => $file['path'],
                    'msg' => $e->getMessage(),
                ]);
            }
        }

        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $defaultPath = $projectDir.'/public/img/document';

        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request->getSchemeAndHttpHost().$request->getBasePath();

        $finder = new Finder();
        if (is_dir($defaultPath)) {
            $finder->in($defaultPath);
            $countFound = 0;

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $countFound++;
                $parentName = \dirname(str_replace($defaultPath, '', $file->getRealPath()));
                if ('/' === $parentName || '/certificates' === $parentName) {
                    continue;
                }

                $title = $file->getFilename();
                $parentId = $paths[$parentName] ?? null;

                if ($file->isDir()) {
                    try {
                        $realPath = str_replace($defaultPath, '', $file->getRealPath());
                        $document = DocumentManager::addDocument(
                            $courseInfo,
                            $realPath,
                            'folder',
                            null,
                            $title,
                            '',
                            null,
                            null,
                            null,
                            null,
                            null,
                            false,
                            null,
                            $parentId,
                            $file->getRealPath()
                        );
                        $iid = method_exists($document, 'getIid') ? $document->getIid() : null;
                        $paths[$realPath] = $iid;
                        $foldersCreated++;
                        $docsCreated++;
                        $this->debugLog('insertExampleContent:document:folder', [
                            'path' => $realPath,
                            'title' => $title,
                            'iid' => $iid,
                            'parentId' => $parentId,
                        ]);
                    } catch (Throwable $e) {
                        $this->debugLog('insertExampleContent:document:folder:error', [
                            'title' => $title,
                            'msg' => $e->getMessage(),
                        ]);
                    }
                } else {
                    try {
                        $realPath = str_replace($defaultPath, '', $file->getRealPath());
                        $document = DocumentManager::addDocument(
                            $courseInfo,
                            $realPath,
                            'file',
                            $file->getSize(),
                            $title,
                            '',
                            null,
                            null,
                            null,
                            null,
                            null,
                            false,
                            null,
                            $parentId,
                            $file->getRealPath()
                        );
                        $iid = method_exists($document, 'getIid') ? $document->getIid() : null;
                        $filesCreated++;
                        $docsCreated++;
                        $this->debugLog('insertExampleContent:document:file', [
                            'path' => $realPath,
                            'title' => $title,
                            'iid' => $iid,
                            'parentId' => $parentId,
                            'size' => $file->getSize(),
                        ]);
                    } catch (Throwable $e) {
                        $this->debugLog('insertExampleContent:document:file:error', [
                            'title' => $title,
                            'msg' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $this->debugLog('insertExampleContent:document:scan', [
                'basePath' => $defaultPath,
                'found' => $countFound,
                'foldersCreated' => $foldersCreated,
                'filesCreated' => $filesCreated,
                'totalDocsCreated' => $docsCreated,
            ]);
        } else {
            $this->debugLog('insertExampleContent:document:basePathMissing', ['basePath' => $defaultPath]);
        }

        try {
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $formattedNow = $now->format('Y-m-d H:i:s');

            $agenda = new Agenda('course');
            $agenda->set_course($courseInfo);
            $agenda->addEvent(
                $formattedNow,
                $formattedNow,
                0,
                $this->translator->trans('Course creation'),
                $this->translator->trans('This course was created at this time'),
                ['everyone' => 'everyone']
            );
            $agendaAdded = true;
            $this->debugLog('insertExampleContent:agenda:ok');
        } catch (Throwable $e) {
            $this->debugLog('insertExampleContent:agenda:error', ['msg' => $e->getMessage()]);
        }

        try {
            $link = new Link();
            $link->setCourse($courseInfo);
            $links = [
                [
                    'c_id' => $course->getId(),
                    'url' => 'http://www.google.com',
                    'title' => 'Quick and powerful search engine',
                    'description' => $this->translator->trans('Quick and powerful search engine'),
                    'category_id' => 0,
                    'on_homepage' => 0,
                    'target' => '_self',
                    'session_id' => 0,
                ],
                [
                    'c_id' => $course->getId(),
                    'url' => 'http://www.wikipedia.org',
                    'title' => 'Free online encyclopedia',
                    'description' => $this->translator->trans('Free online encyclopedia'),
                    'category_id' => 0,
                    'on_homepage' => 0,
                    'target' => '_self',
                    'session_id' => 0,
                ],
            ];

            foreach ($links as $params) {
                $link->save($params, false, false);
                $linksCreated++;
            }
            $this->debugLog('insertExampleContent:links:ok', ['linksCreated' => $linksCreated]);
        } catch (Throwable $e) {
            $this->debugLog('insertExampleContent:links:error', ['msg' => $e->getMessage()]);
        }

        try {
            $announcementId = AnnouncementManager::add_announcement(
                $courseInfo,
                0,
                $this->translator->trans('This is an announcement example'),
                $this->translator->trans('This is an announcement example. Only trainers are allowed to publish announcements.'),
                ['everyone' => 'everyone'],
                null,
                null,
                (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s')
            );
            $this->debugLog('insertExampleContent:announcement:ok', ['announcementId' => $announcementId]);
        } catch (Throwable $e) {
            $this->debugLog('insertExampleContent:announcement:error', ['msg' => $e->getMessage()]);
        }

        try {
            $exercise = new Exercise($course->getId());
            $exercise->exercise = $this->translator->trans('Sample test');
            $html = '<table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td width="220" valign="top" align="left">
                            <img src="'.$baseUrl.'/public/img/document/images/mr_chamilo/doubts.png">
                        </td>
                        <td valign="top" align="left">'.$this->translator->trans('Irony').'</td></tr>
                    </table>';
            $exercise->type = 1;
            $exercise->setRandom(0);
            $exercise->active = 1;
            $exercise->results_disabled = 0;
            $exercise->description = $html;
            $exercise->save();
            $exerciseId = $exercise->id;
            $this->debugLog('insertExampleContent:exercise:created', ['exerciseId' => $exerciseId]);

            $question = new MultipleAnswer();
            $question->course = $courseInfo;
            $question->question = $this->translator->trans('Socratic irony is...');
            $question->description = $this->translator->trans('(more than one answer can be true)');
            $question->weighting = 10;
            $question->position = 1;
            $question->course = $courseInfo;
            $question->save($exercise);
            $questionId = $question->id;

            $answer = new Answer($questionId, $courseInfo['real_id']);
            $answer->createAnswer($this->translator->trans("Ridiculise one's interlocutor in order to have him concede he is wrong."), 0, $this->translator->trans('No. Socratic irony is not a matter of psychology, it concerns argumentation.'), -5, 1);
            $answer->createAnswer($this->translator->trans("Admit one's own errors to invite one's interlocutor to do the same."), 0, $this->translator->trans('No. Socratic irony is not a seduction strategy or a method based on the example.'), -5, 2);
            $answer->createAnswer($this->translator->trans("Compell one's interlocutor, by a series of questions and sub-questions, to admit he doesn't know what he claims to know."), 1, $this->translator->trans('Indeed. Socratic irony is an interrogative method. The Greek "eirotao" means "ask questions"'), 5, 3);
            $answer->createAnswer($this->translator->trans("Use the Principle of Non Contradiction to force one's interlocutor into a dead end."), 1, $this->translator->trans("This answer is not false. It is true that the revelation of the interlocutor's ignorance means showing the contradictory conclusions where lead his premisses."), 5, 4);
            $answer->save();
            $this->debugLog('insertExampleContent:exercise:questionAnswers:ok', ['questionId' => $questionId]);

            // Gradebook link
            $manager = $this->entityManager;
            $childGradebookCategory = new GradebookCategory();
            $childGradebookCategory->setTitle($course->getCode());
            $childGradebookCategory->setLocked(0);
            $childGradebookCategory->setGenerateCertificates(false);
            $childGradebookCategory->setDescription('');
            $childGradebookCategory->setCourse($course);
            $childGradebookCategory->setWeight(100);
            $childGradebookCategory->setVisible(true);
            $childGradebookCategory->setCertifMinScore(75);
            $childGradebookCategory->setParent($gradebook);
            $childGradebookCategory->setUser(api_get_user_entity());

            $manager->persist($childGradebookCategory);
            $manager->flush();

            $gradebookLink = new GradebookLink();
            $gradebookLink->setType(1);
            $gradebookLink->setRefId($exerciseId);
            $gradebookLink->setUserScoreList([]);
            $gradebookLink->setCourse($course);
            $gradebookLink->setCategory($childGradebookCategory);
            $gradebookLink->setCreatedAt(new DateTime());
            $gradebookLink->setWeight(100);
            $gradebookLink->setVisible(1);
            $gradebookLink->setLocked(0);

            $manager->persist($gradebookLink);
            $manager->flush();
            $this->debugLog('insertExampleContent:gradebookLink:ok', [
                'childCategoryId' => $childGradebookCategory->getId(),
                'refId' => $exerciseId,
            ]);
        } catch (Throwable $e) {
            $this->debugLog('insertExampleContent:exercise:error', ['msg' => $e->getMessage()]);
        }

        try {
            $params = [
                'forum_category_title' => $this->translator->trans('Example Forum Category'),
                'forum_category_comment' => '',
            ];
            $forumCategoryId = saveForumCategory($params, $courseInfo, false);
            $this->debugLog('insertExampleContent:forum:category', ['forumCategoryId' => $forumCategoryId]);

            $params = [
                'forum_category' => $forumCategoryId,
                'forum_title' => $this->translator->trans('Example Forum'),
                'forum_comment' => '',
                'default_view_type_group' => ['default_view_type' => 'flat'],
            ];
            $forumId = store_forum($params, $courseInfo, true);
            $this->debugLog('insertExampleContent:forum:forum', ['forumId' => $forumId]);

            $repo = $this->entityManager->getRepository(CForum::class);
            $forumEntity = $repo->find($forumId);

            $params = [
                'post_title' => $this->translator->trans('Example Thread'),
                'forum_id' => $forumId,
                'post_text' => $this->translator->trans('Example content'),
                'calification_notebook_title' => '',
                'numeric_calification' => '',
                'weight_calification' => '',
                'forum_category' => $forumCategoryId,
                'thread_peer_qualify' => 0,
            ];
            $threadId = saveThread($forumEntity, $params, $courseInfo, false);
            $this->debugLog('insertExampleContent:forum:thread', ['threadId' => $threadId]);
        } catch (Throwable $e) {
            $this->debugLog('insertExampleContent:forum:error', ['msg' => $e->getMessage()]);
        }

        $this->debugLog('insertExampleContent:end', [
            'courseId' => $course->getId(),
            'docsCreated' => $docsCreated,
            'foldersCreated' => $foldersCreated,
            'filesCreated' => $filesCreated,
            'linksCreated' => $linksCreated,
            'agendaAdded' => $agendaAdded,
            'announcementId' => $announcementId,
            'exerciseId' => $exerciseId,
            'forumCategoryId' => $forumCategoryId,
            'forumId' => $forumId,
            'threadId' => $threadId,
        ]);
    }

    private function createExampleGradebookContent(Course $course, GradebookCategory $parentCategory, int $refId): void
    {
        $manager = $this->entityManager;

        /* Gradebook tool */
        $courseCode = $course->getCode();

        $childGradebookCategory = new GradebookCategory();
        $childGradebookCategory->setTitle($courseCode);
        $childGradebookCategory->setLocked(0);
        $childGradebookCategory->setGenerateCertificates(false);
        $childGradebookCategory->setDescription('');
        $childGradebookCategory->setCourse($course);
        $childGradebookCategory->setWeight(100);
        $childGradebookCategory->setVisible(true);
        $childGradebookCategory->setCertifMinScore(75);
        $childGradebookCategory->setParent($parentCategory);
        $childGradebookCategory->setUser(api_get_user_entity());

        $manager->persist($childGradebookCategory);
        $manager->flush();

        $gradebookLink = new GradebookLink();

        $gradebookLink->setType(1);
        $gradebookLink->setRefId($refId);
        $gradebookLink->setUserScoreList([]);
        $gradebookLink->setCourse($course);
        $gradebookLink->setCategory($childGradebookCategory);
        $gradebookLink->setCreatedAt(new DateTime());
        $gradebookLink->setWeight(100);
        $gradebookLink->setVisible(1);
        $gradebookLink->setLocked(0);

        $manager->persist($gradebookLink);
        $manager->flush();
    }

    private function installCoursePlugins(int $courseId): void
    {
        $app_plugin = new AppPlugin();
        $app_plugin->install_course_plugins($courseId);
    }

    public function useTemplateAsBasisIfRequired($courseCode, $courseTemplate): void
    {
        $templateSetting = $this->settingsManager->getSetting('course.course_creation_use_template');
        $teacherCanSelectCourseTemplate = 'true' === $this->settingsManager->getSetting('workflows.teacher_can_select_course_template');
        $courseTemplate = isset($courseTemplate) ? (int) $courseTemplate : 0;

        $useTemplate = false;
        if ($teacherCanSelectCourseTemplate && $courseTemplate > 0) {
            $useTemplate = true;
            $originCourse = $this->courseRepository->findCourseAsArray((int) $courseTemplate);
        } elseif (!empty($templateSetting)) {
            $useTemplate = true;
            $originCourse = $this->courseRepository->findCourseAsArray((int) $templateSetting);
        }

        if ($useTemplate && !empty($originCourse)) {
            try {
                $originCourse['official_code'] = $originCourse['code'];
                $cb = new CourseBuilder(null, $originCourse);
                $course = $cb->build(null, $originCourse['code']);
                $cr = new CourseRestorer($course);
                $cr->set_file_option();
                $cr->restore($courseCode);
            } catch (Exception $e) {
                error_log('Error during course template application: '.$e->getMessage());
            } catch (Throwable $t) {
                error_log('Unexpected error during course template application: '.$t->getMessage());
            }
        }
    }

    private function prepareAndValidateCourseData(array $params): array
    {
        $title = str_replace('&amp;', '&', $params['title'] ?? '');
        $code = $params['code'] ?? '';
        $visualCode = $params['visual_code'] ?? '';
        $directory = $params['directory'] ?? '';
        $tutorName = $params['tutor_name'] ?? null;
        $courseLanguage = !empty($params['course_language']) ? $params['course_language'] : $this->getDefaultSetting('language.platform_language');
        $departmentName = $params['department_name'] ?? null;
        $departmentUrl = $this->fixDepartmentUrl($params['department_url'] ?? '');
        $diskQuota = $params['disk_quota'] ?? $this->getDefaultSetting('document.default_document_quotum');
        $visibility = $params['visibility'] ?? $this->getDefaultSetting('course.courses_default_creation_visibility', Course::OPEN_PLATFORM);
        $subscribe = $params['subscribe'] ?? (Course::OPEN_PLATFORM == $visibility);
        $unsubscribe = $params['unsubscribe'] ?? false;
        $expirationDate = $params['expiration_date'] ?? $this->getFutureExpirationDate();
        $teachers = $params['teachers'] ?? [];
        $categories = $params['course_categories'] ?? [];
        $notifyAdmins = $this->getDefaultSetting('course.send_email_to_admin_when_create_course');

        $errors = [];
        if (empty($code)) {
            $errors[] = 'courseSysCode is missing';
        }
        if (empty($visualCode)) {
            $errors[] = 'courseScreenCode is missing';
        }
        if (empty($directory)) {
            $errors[] = 'courseRepository is missing';
        }
        if (empty($title)) {
            $errors[] = 'title is missing';
        }
        if ($visibility < 0 || $visibility > 4) {
            $errors[] = 'visibility is invalid';
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        return [
            'title' => $title,
            'code' => $code,
            'visualCode' => $visualCode,
            'directory' => $directory,
            'tutorName' => $tutorName,
            'courseLanguage' => $courseLanguage,
            'departmentName' => $departmentName,
            'departmentUrl' => $departmentUrl,
            'diskQuota' => $diskQuota,
            'visibility' => $visibility,
            'subscribe' => $subscribe,
            'unsubscribe' => $unsubscribe,
            'expirationDate' => new DateTime($expirationDate),
            'teachers' => $teachers,
            'categories' => $categories,
            'notifyAdmins' => $notifyAdmins,
        ];
    }

    private function getDefaultSetting(string $name, $default = null)
    {
        $settingValue = $this->settingsManager->getSetting($name);

        return null !== $settingValue ? $settingValue : $default;
    }

    private function fixDepartmentUrl(string $url): string
    {
        if (!empty($url) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return 'http://'.$url;
        }

        return 'http://' === $url ? '' : $url;
    }

    private function getFutureExpirationDate(): string
    {
        return (new DateTime())->modify('+1 year')->format('Y-m-d H:i:s');
    }

    private function generateCourseCode(string $title): string
    {
        $cleanTitle = preg_replace('/[^A-Z0-9]/', '', strtoupper($this->replaceDangerousChar($title)));

        return substr($cleanTitle, 0, self::MAX_COURSE_LENGTH_CODE);
    }

    private function replaceDangerousChar(string $text): string
    {
        $encoding = mb_detect_encoding($text, mb_detect_order(), true);
        if ('UTF-8' !== $encoding) {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }

        $text = str_replace(' ', '-', $text);
        $text = preg_replace('/[^-\w]+/', '', $text);

        return preg_replace('/\.+$/', '', $text);
    }

    private function handlePostCourseCreation(Course $course, array $params): void
    {
        if ($params['notifyAdmins'] ?? false) {
            $this->sendEmailToAdmin($course);
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            $currentUser = $this->getFallbackAdminUser();
        }

        $this->eventLoggerHelper->addEvent(
            'course_created',
            'course_id',
            $course->getId(),
            null,
            $currentUser->getId(),
            $course->getId()
        );
    }

    private function getFallbackAdminUser(): User
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('u')
            ->from(User::class, 'u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->setMaxResults(1)
        ;

        $user = $qb->getQuery()->getOneOrNullResult();

        if (!$user instanceof User) {
            throw new RuntimeException('No admin user found for fallback.');
        }

        return $user;
    }

    private function debugLog(string $message, array $context = []): void
    {
        if (!$this->debug) {
            return;
        }
        $suffix = $context ? ' '.json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        error_log('[CourseHelper] '.$message.$suffix);
    }
}
