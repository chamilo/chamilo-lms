<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class AnonymousUserSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private EntityManagerInterface $entityManager;
    private SessionInterface $session;
    private SettingsManager $settingsManager;
    private const MAX_ANONYMOUS_USERS = 5;

    public function __construct(Security $security, EntityManagerInterface $entityManager, SessionInterface $session, SettingsManager $settingsManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->settingsManager = $settingsManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->security->getUser() !== null) {
            return;
        }

        $request = $event->getRequest();

        // Set the platform locale
        $locale = $this->getCurrentLanguage($request, $this->settingsManager);
        $request->setLocale($locale);
        $request->getSession()->set('_locale', $locale);

        $userIp = $request->getClientIp();
        $anonymousUserId = $this->getOrCreateAnonymousUserId($userIp);
        if ($anonymousUserId !== null) {
            $trackLoginRepository = $this->entityManager->getRepository(TrackELogin::class);

            // Check if a login record already exists for this user and IP
            $existingLogin = $trackLoginRepository->findOneBy(['userIp' => $userIp, 'user' => $anonymousUserId]);
            if (!$existingLogin) {
                // Record the access if it does not exist
                $trackLogin = new TrackELogin();
                $trackLogin->setUserIp($userIp)
                    ->setLoginDate(new \DateTime())
                    ->setUser($this->entityManager->getReference(User::class, $anonymousUserId));

                $this->entityManager->persist($trackLogin);
                $this->entityManager->flush();
            }

            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->find($anonymousUserId);

            if ($user) {
                // Store user information in the session
                $userInfo = [
                    'user_id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'firstName' => $user->getFirstname(),
                    'lastName' => $user->getLastname(),
                    'email' => $user->getEmail(),
                    'official_code' => $user->getOfficialCode(),
                    'picture_uri' => $user->getPictureUri(),
                    'status' => $user->getStatus(),
                    'active' => $user->getActive(),
                    'auth_source' => $user->getAuthSource(),
                    'theme' => $user->getTheme(),
                    'language' => $user->getLocale(),
                    'registration_date' => $user->getRegistrationDate()->format('Y-m-d H:i:s'),
                    'expiration_date' => $user->getExpirationDate() ? $user->getExpirationDate()->format('Y-m-d H:i:s') : null,
                    'last_login' => $user->getLastLogin() ? $user->getLastLogin()->format('Y-m-d H:i:s') : null,
                    'is_anonymous' => true,
                ];

                $this->session->set('_user', $userInfo);
            }
        }
    }

    private function getOrCreateAnonymousUserId(string $userIp): ?int {
        $userRepository = $this->entityManager->getRepository(User::class);
        $trackLoginRepository = $this->entityManager->getRepository(TrackELogin::class);

        $maxAnonymousUsers = (int) $this->settingsManager->getSetting('admin.max_anonymous_users');
        if (0 === $maxAnonymousUsers) {
            $maxAnonymousUsers = self::MAX_ANONYMOUS_USERS;
        }
        $anonymousUsers = $userRepository->findBy(['status' => User::ANONYMOUS], ['registrationDate' => 'ASC']);

        // Check in TrackELogin if there is an anonymous user with the same IP
        foreach ($anonymousUsers as $user) {
            $loginRecord = $trackLoginRepository->findOneBy(['userIp' => $userIp, 'user' => $user]);
            if ($loginRecord) {
                error_log('Existing login found for user ID: ' . $user->getId());
                return $user->getId();
            }
        }

        // Delete excess anonymous users
        while (count($anonymousUsers) >= $maxAnonymousUsers) {
            $oldestAnonymousUser = array_shift($anonymousUsers);
            if ($oldestAnonymousUser) {
                error_log('Deleting oldest anonymous user: ' . $oldestAnonymousUser->getId());
                $this->entityManager->remove($oldestAnonymousUser);
                $this->entityManager->flush();
            }
        }

        // Create a new anonymous user
        $uniqueId = uniqid();
        $anonymousUser = (new User())
            ->setSkipResourceNode(true)
            ->setLastname('Joe')
            ->setFirstname('Anonymous')
            ->setUsername('anon_' . $uniqueId)
            ->setStatus(User::ANONYMOUS)
            ->setPlainPassword('anon')
            ->setEmail('anon_' . $uniqueId . '@localhost.local')
            ->setOfficialCode('anonymous')
            ->setCreatorId(1)
            ->addRole('ROLE_ANONYMOUS');

        $this->entityManager->persist($anonymousUser);
        $this->entityManager->flush();

        error_log('New anonymous user created: ' . $anonymousUser->getId());
        return $anonymousUser->getId();
    }

    public function getCurrentLanguage(Request $request, SettingsManager $settingsManager): string
    {
        $localeList = [];

        // 1. Check platform locale
        $platformLocale = $settingsManager->getSetting('language.platform_language');

        if (!empty($platformLocale)) {
            $localeList['platform_lang'] = $platformLocale;
        }

        // 2. Check user locale
        // _locale_user is set when user logins the system check UserLocaleListener
        $userLocale = $request->getSession()->get('_locale_user');

        if (!empty($userLocale)) {
            $localeList['user_profil_lang'] = $userLocale;
        }

        // 3. Check course locale
        $courseId = $request->get('cid');

        if (!empty($courseId)) {
            /** @var Course|null $course */
            $course = $request->getSession()->get('course');
            // 3. Check course locale
            if (!empty($course)) {
                $courseLocale = $course->getCourseLanguage();
                if (!empty($courseLocale)) {
                    $localeList['course_lang'] = $platformLocale;
                }
            }
        }

        // 4. force locale if it was selected from the URL
        $localeFromUrl = $request->get('_locale');
        if (!empty($localeFromUrl)) {
            $localeList['user_selected_lang'] = $platformLocale;
        }

        $priorityList = [
            'language_priority_1',
            'language_priority_2',
            'language_priority_3',
            'language_priority_4',
        ];

        $locale = '';
        foreach ($priorityList as $setting) {
            $priority = $settingsManager->getSetting(sprintf('language.%s', $setting));
            if (!empty($priority) && isset($localeList[$priority]) && !empty($localeList[$priority])) {
                $locale = $localeList[$priority];

                break;
            }
        }

        if (empty($locale)) {
            // Use default order
            $priorityList = [
                'platform_lang',
                'user_profil_lang',
                'course_lang',
                'user_selected_lang',
            ];
            foreach ($priorityList as $setting) {
                if (isset($localeList[$setting]) && !empty($localeList[$setting])) {
                    $locale = $localeList[$setting];
                }
            }
        }

        if (empty($locale)) {
            $locale = 'en';
        }

        return $locale;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
