<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Controller\ErrorController;
use Chamilo\CoreBundle\EventListener\ExceptionListener;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;

// Use when running PHPUnit tests.
if (isset($fileToLoad)) {
    return;
}

/**
 * All legacy Chamilo scripts should include this important file.
 */
require_once __DIR__.'/../../../vendor/autoload.php';

// Get settings from the created .env file.
$envFile = __DIR__.'/../../../.env';
if (file_exists($envFile)) {
    (new Dotenv())->load($envFile);
} else {
    throw new \RuntimeException('APP_ENV environment variable is not defined.
        You need to define environment variables for configuration to load variables from a .env file.');
}

$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = 'dev' === $env;
if ($debug) {
    Debug::enable();
}

$isCli = php_sapi_name() === 'cli';
if ($isCli) {

    $kernel = new Chamilo\Kernel($env, $debug);
    $kernel->boot();

    if (!$kernel->isInstalled()) {
        throw new Exception('Chamilo is not installed');
    }

    $container = $kernel->getContainer();
    Container::setContainer($container);
    $session = Container::getLegacyHelper()->getSession();
    $request = Request::create('/');
    $request->setSession($session);
    $container->get('request_stack')->push($request);
    Container::setLegacyServices($container);
    $router = $container->get('router');
    $context = $router->getContext();
    $router->setContext($context);

    $cliOptions = getopt('', ['url:']);
    if (!empty($cliOptions['url'])) {
        $baseUrl = $cliOptions['url'];
        $context->setBaseUrl($baseUrl);
    }
} else {
    $kernel = new Chamilo\Kernel($env, $debug);
    // Loading Request from Sonata. In order to use Sonata Pages Bundle.
    $request = Request::createFromGlobals();
    if (!empty($_SERVER['TRUSTED_PROXIES'])) {
        $request->setTrustedProxies(
            preg_split('#,#', $_SERVER['TRUSTED_PROXIES']),
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT
        );
        // TRUSTED_PROXIES must be defined in .env. For non-legacy code, check config/packages/framework.yaml
    }
    // This 'load_legacy' variable is needed to know that symfony is loaded using old style legacy mode,
    // and not called from a symfony controller from public/
    $request->request->set('load_legacy', true);
    $currentBaseUrl = $request->getBaseUrl();

    if (empty($currentBaseUrl)) {
        $currentBaseUrl = $request->getSchemeAndHttpHost() . $request->getBasePath();
    }

    // Catch Symfony kernel exceptions (e.g. CidReqListener) in prod.
    // Needed because set_exception_handler() won't catch them here.
    try {
        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, false);
    } catch (\Throwable $exception) {
        if (\in_array($kernel->getEnvironment(), ['dev', 'test'], true)) {
            throw $exception;
        }

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $listener = $kernel->getContainer()->get(ExceptionListener::class);
        if (is_callable($listener)) {
            $listener($event);
        }

        $response = $event->getResponse();
        if (!$response) {
            $response = new Response('An error occurred.', 500);
        }

        $response->send();
        exit;
    }

    // Start session for legacy code just in case not already started by Symfony.
    if (!$request->hasSession()) {
        $request->setSession(
            Container::getLegacyHelper()->getSession()
        );
    }

    $container = $kernel->getContainer();
    $router = $container->get('router');
    $context = $router->getContext();
    $router->setContext($context);

    // Catch legacy exceptions after kernel execution.
    // Complements the try/catch above for full coverage.
    set_exception_handler(function ($exception) use ($kernel, $container, $request) {
        if (
            in_array($kernel->getEnvironment(), ['dev', 'test'], true) &&
            !($exception instanceof NotAllowedException)
        ) {
            throw $exception;
        }

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        /** @var callable $listener */
        $listener = $container->get(ExceptionListener::class);
        if (is_callable($listener)) {
            $listener($event);
        }
        $response = $event->getResponse();
        if (!$response) {
            $controller = Container::$container->get(ErrorController::class);
            $response = $controller->show($request, $exception, null);
        }
        $response->send();
    });

    $context = Container::getRouter()->getContext();

    $currentUri = $request->getRequestUri();

    $fullUrl = $currentBaseUrl . $currentUri;
    $posMain = strpos($fullUrl, '/main');
    $posPlugin = strpos($fullUrl, '/plugin');
    $posCourse = strpos($fullUrl, '/course');
    $posCertificate = strpos($fullUrl, '/certificate');

    if (false === $posMain && false === $posPlugin && false === $posCourse && false === $posCertificate) {
        echo 'Cannot load current URL';
        exit;
    }

    if (false !== $posMain) {
        $newBaseUrl = substr($fullUrl, 0, $posMain);
    } elseif (false !== $posPlugin) {
        $newBaseUrl = substr($fullUrl, 0, $posPlugin);
    } elseif (false !== $posCourse) {
        $newBaseUrl = substr($fullUrl, 0, $posCourse);
    } elseif (false !== $posCertificate) {
        $newBaseUrl = substr($fullUrl, 0, $posCertificate);
    }

    $context->setBaseUrl($newBaseUrl);

    // Do not overuse this variable. It is only for this script's local use.
    $libraryPath = __DIR__.'/lib/';
    $container = $kernel->getContainer();

    // Symfony uses request_stack now
    $container->get('request_stack')->push($request);
    $container->get('translator')->setLocale($request->getLocale());

    $container->get('stof_doctrine_extensions.tool.locale_synchronizer')->setLocale($request->getLocale());

    /** @var FlashBag $flashBag */
    $flashBag = $request->getSession()->getFlashBag();
    $saveFlashBag = !empty($flashBag->keys()) ? $flashBag->all() : null;

    if (!empty($saveFlashBag)) {
        foreach ($saveFlashBag as $typeMessage => $messageList) {
            foreach ($messageList as $message) {
                Container::getSession()->getFlashBag()->add($typeMessage, $message);
            }
        }
    }

    $charset = 'UTF-8';
    ini_set('log_errors', '1');
    $this_section = SECTION_GLOBAL;
    define('DEFAULT_DOCUMENT_QUOTA', 100000000);
}
