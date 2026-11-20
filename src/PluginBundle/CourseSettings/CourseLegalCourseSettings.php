<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\State\CourseSettings\AbstractCourseSettingsPlugin;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsPluginStore;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

use const ENT_QUOTES;

final class CourseLegalCourseSettings extends AbstractCourseSettingsPlugin
{
    public function __construct(
        PluginHelper $pluginHelper,
        CourseSettingsPluginStore $store,
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly CourseRepository $courseRepository,
        private readonly UrlGeneratorInterface $router,
        private readonly ParameterBagInterface $parameterBag,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($pluginHelper, $store);
    }

    protected function pluginName(): string
    {
        return 'CourseLegal';
    }

    public function getIntegrationKey(): string
    {
        return 'courseLegal';
    }

    public function getIntegration(Course $course, ?Session $session): array
    {
        return ['enabled' => $this->pluginHelper->isPluginEnabled($this->pluginName())
            && $this->tableExists('session_rel_course_legal')];
    }

    public function getSection(Course $course, ?Session $session, array $values): ?array
    {
        return $this->section('course_legal', 'Course legal agreement', 'onlyoffice', [
            $this->field('course_legal_content', 'Agreement content', 'editor'),
            $this->field('course_legal_remove_previous_agreements', 'Remove previous agreements', 'checkbox'),
            $this->field('course_legal_warn_users', 'Notify learners', 'select', [
                ['label' => 'Do not notify', 'value' => 1],
                ['label' => 'Notify without attachment', 'value' => 2],
                ['label' => 'Notify with agreement file', 'value' => 3],
            ]),
        ], collapsed: true);
    }

    public function getValues(Course $course, ?Session $session): array
    {
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $data = $this->getCourseLegalData($courseId, $sessionId);

        return [
            'course_legal_content' => (string) ($data['content'] ?? ''),
            'course_legal_filename' => (string) ($data['filename'] ?? ''),
            'course_legal_remove_previous_agreements' => false,
            'course_legal_warn_users' => 1,
            'course_legal_agreements' => $this->getCourseLegalAgreements($courseId, $sessionId),
        ];
    }

    public function saveValues(Course $course, ?Session $session, array $values): void
    {
        if (!$this->pluginHelper->isPluginEnabled($this->pluginName()) || !$this->tableExists('session_rel_course_legal')) {
            return;
        }
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $existing = $this->getCourseLegalData($courseId, $sessionId);
        $content = (string) ($values['course_legal_content'] ?? '');
        if ([] === $existing) {
            $this->connection->insert('session_rel_course_legal', [
                'c_id' => $courseId,
                'session_id' => $sessionId,
                'content' => $content,
                'filename' => '',
            ], [
                'c_id' => Types::INTEGER,
                'session_id' => Types::INTEGER,
                'content' => Types::TEXT,
                'filename' => Types::STRING,
            ]);
        } else {
            $this->connection->update(
                'session_rel_course_legal',
                ['content' => $content],
                ['id' => (int) $existing['id']],
                ['content' => Types::TEXT],
                ['id' => Types::INTEGER],
            );
        }

        if ((bool) ($values['course_legal_remove_previous_agreements'] ?? false)
            && $this->tableExists('session_rel_course_rel_user_legal')) {
            $this->connection->delete('session_rel_course_rel_user_legal', [
                'c_id' => $courseId,
                'session_id' => $sessionId,
            ], [
                'c_id' => Types::INTEGER,
                'session_id' => Types::INTEGER,
            ]);
        }
    }

    private function getCourseLegalData(int $courseId, int $sessionId): array
    {
        if (!$this->tableExists('session_rel_course_legal')) {
            return [];
        }
        $result = $this->connection->fetchAssociative(
            'SELECT id, content, filename FROM session_rel_course_legal WHERE c_id = :courseId AND session_id = :sessionId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER],
        );

        return false === $result ? [] : $result;
    }

    private function getCourseLegalAgreements(int $courseId, int $sessionId): array
    {
        if (!$this->tableExists('session_rel_course_rel_user_legal')) {
            return [];
        }

        return $this->connection->fetchAllAssociative(
            'SELECT
                legal.id,
                legal.user_id,
                legal.web_agreement,
                legal.web_agreement_date,
                legal.mail_agreement,
                legal.mail_agreement_date,
                user.firstname,
                user.lastname,
                user.username,
                user.email
             FROM session_rel_course_rel_user_legal legal
             INNER JOIN `user` user ON user.id = legal.user_id
             WHERE legal.c_id = :courseId
               AND legal.session_id = :sessionId
             ORDER BY user.lastname, user.firstname, user.username',
            ['courseId' => $courseId, 'sessionId' => $sessionId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER],
        );
    }

    public function afterSave(Course $course, ?Session $session, array $values): void
    {
        if (!$this->pluginHelper->isPluginEnabled($this->pluginName())) {
            return;
        }

        $notificationMode = (int) ($values['course_legal_warn_users'] ?? 1);
        if ($notificationMode < 2) {
            return;
        }

        $editor = $this->security->getUser();
        if (!$editor instanceof User || '' === trim($editor->getEmail())) {
            $this->logger->warning('Course legal notification skipped because the editor has no e-mail address.', [
                'courseId' => $course->getId(),
            ]);

            return;
        }

        $users = $this->getCourseLegalNotificationUsers($course, $session);
        if ([] === $users) {
            return;
        }

        $sessionId = (int) ($session?->getId() ?? 0);
        $courseParameters = ['cid' => (int) $course->getId()];
        if ($sessionId > 0) {
            $courseParameters['sid'] = $sessionId;
        }
        $courseUrl = $this->router->generate(
            'chamilo_core_course_home',
            $courseParameters,
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $subject = 'Course agreement updated';
        $message = '<p>The course agreement for <strong>'.htmlspecialchars($course->getTitle(), ENT_QUOTES).'</strong> was updated.</p>'
            .'<p><a href="'.htmlspecialchars($courseUrl, ENT_QUOTES).'">Open the course to review the agreement</a></p>';
        $attachmentPath = null;
        if (3 === $notificationMode) {
            $legal = $this->getCourseLegalData((int) $course->getId(), $sessionId);
            $filename = basename((string) ($legal['filename'] ?? ''));
            if ('' !== $filename) {
                $candidate = $this->parameterBag->get('kernel.project_dir')
                    .'/var/upload/course_legal/course_'.(int) $course->getId()
                    .'/session_'.$sessionId.'/'.$filename;
                if (is_file($candidate)) {
                    $attachmentPath = $candidate;
                }
            }
        }

        foreach ($users as $user) {
            if (!$user instanceof User || '' === trim($user->getEmail())) {
                continue;
            }

            $email = (new Email())
                ->from($editor->getEmail())
                ->to($user->getEmail())
                ->subject($subject)
                ->html($message)
            ;

            if (null !== $attachmentPath) {
                $email->attachFromPath($attachmentPath, basename($attachmentPath));
            }

            try {
                $this->mailer->send($email);
            } catch (Throwable $exception) {
                $this->logger->error('Course legal notification could not be sent.', [
                    'courseId' => $course->getId(),
                    'sessionId' => $sessionId,
                    'userId' => $user->getId(),
                    'exception' => $exception,
                ]);
            }
        }
    }

    private function getCourseLegalNotificationUsers(Course $course, ?Session $session): array
    {
        if (null === $session) {
            $result = $this->courseRepository->getSubscribedStudents($course)->getQuery()->getResult();

            return array_values(array_filter($result, static fn (mixed $item): bool => $item instanceof User));
        }

        $relations = $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findBy([
            'course' => $course,
            'session' => $session,
            'status' => Session::STUDENT,
        ]);
        $users = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof SessionRelCourseRelUser) {
                continue;
            }
            $user = $relation->getUser();
            if (!$user instanceof User) {
                continue;
            }
            $users[(int) $user->getId()] = $user;
        }

        return array_values($users);
    }

    private function tableExists(string $name): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$name]);
    }
}
