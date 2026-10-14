<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container as LegacyContainer;
use Database;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Twig\Environment;
use UserManager;

use const PHP_URL_PATH;

/**
 * Re-anchors the legacy static bridges (Container::$container, Database's
 * static EntityManager) to the current command's own Symfony container and
 * EntityManager, in a plain CLI console command.
 *
 * In CLI, public/main/inc/global.inc.php's own $isCli branch boots a SECOND,
 * independent Chamilo\Kernel and points the legacy Container::$container
 * (and whatever reads it, e.g. Container::getGradebookCertificateRepository())
 * at that one — completely separate from the container the command, and every
 * service autowired into it, came from. Without this, any legacy code path a
 * command touches either crashes on a null Container::$container (a plain
 * "Call to a member function get() on null"), or silently reads/writes
 * through a different EntityManager instance than the rest of the run.
 *
 * Previously duplicated ad hoc in SendNotificationsCommand and
 * AchievementCertificateBatchService::bootstrapLegacy() — factored here so a
 * new CLI command that has to cross into legacy code only needs to call
 * bootstrap() once, up front.
 */
final class LegacyCliBootstrapper
{
    /**
     * Pass $twig when the command renders legacy templates (Template, e.g. a
     * certificate): a web request gets Container::$twig from LegacyListener,
     * which never runs in CLI, and the 'twig' service is private so legacy
     * code cannot fetch it from the container itself.
     */
    public static function bootstrap(
        KernelInterface $kernel,
        EntityManagerInterface $entityManager,
        ?Environment $twig = null
    ): void {
        $globalFile = $kernel->getProjectDir().'/public/main/inc/global.inc.php';
        if (!is_file($globalFile)) {
            throw new RuntimeException(\sprintf('Legacy bootstrap was not found: %s', $globalFile));
        }

        // Included in its own scope: global.inc.php assigns $kernel (and $container), which
        // would otherwise overwrite this method's $kernel and re-anchor the legacy Container
        // below to the second kernel global.inc.php boots, instead of this command's.
        (static function (string $file): void {
            require_once $file;
        })($globalFile);

        LegacyContainer::setContainer($kernel->getContainer());
        // global.inc.php prepared the request, session and router of its own kernel only.
        self::prepareCliRequest($kernel->getContainer());
        if ($entityManager instanceof EntityManager) {
            Database::setManager($entityManager);
        }
        if (null !== $twig) {
            LegacyContainer::$twig = $twig;
        }
    }

    /**
     * Gives a CLI run the request, session and router context legacy code expects from
     * a web request. Expects LegacyContainer to already point at $container.
     */
    public static function prepareCliRequest(ContainerInterface $container): void
    {
        $session = LegacyContainer::getLegacyHelper()->getSession();
        // --url gives the portal's public address, which a CLI run has no request to read from:
        // build the request from it so absolute URLs (router and request alike) use that host,
        // instead of Symfony's http://localhost default.
        $cliOptions = getopt('', ['url:']);
        $cliUrl = !empty($cliOptions['url']) ? rtrim($cliOptions['url'], '/').'/' : '/';
        $request = Request::create($cliUrl);
        $request->setSession($session);
        $container->get('request_stack')->push($request);
        LegacyContainer::setLegacyServices($container);
        $router = $container->get('router');
        $context = $router->getContext();
        $context->fromRequest($request);
        // The base URL is only the sub-folder prefix of an installation like https://host/chamilo,
        // never the whole address, or the router prepends its default host to it.
        $context->setBaseUrl(rtrim((string) parse_url($cliUrl, PHP_URL_PATH), '/'));
        $router->setContext($context);
    }

    /**
     * Validates a platform administrator to act as the current user for the rest of this
     * CLI run, and returns them so the caller can also use them as an explicit resource
     * creator.
     *
     * Without a real request, Security::getUser() is null in CLI. Code that creates a new
     * AbstractResource-derived entity (a certificate, a document, ...) without an explicit
     * creator relies on that fallback and throws "User creator not found" the moment it's
     * missing (see ResourceListener::prePersist()) — this both supplies the Security token
     * for code that reads it, and gives the caller the same User to pass explicitly to code
     * that takes a creator directly, which is the more reliable of the two (see, e.g.,
     * GradebookCertificateRepository::registerUserInfoAboutCertificate()).
     */
    public static function authenticateSender(
        int $senderId,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
    ): User {
        $sender = $entityManager->find(User::class, $senderId);

        if (!$sender instanceof User || !$sender->isActive()) {
            throw new RuntimeException(\sprintf('Sender %d was not found or is inactive.', $senderId));
        }

        if (!UserManager::is_admin($senderId)) {
            throw new RuntimeException(\sprintf('Sender %d must be a platform administrator.', $senderId));
        }

        $tokenStorage->setToken(new UsernamePasswordToken($sender, 'main', $sender->getRoles()));

        return $sender;
    }
}
