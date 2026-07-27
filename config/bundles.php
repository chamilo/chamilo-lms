<?php

declare(strict_types=1);

use A2lix\AutoFormBundle\A2lixAutoFormBundle;
use A2lix\TranslationFormBundle\A2lixTranslationFormBundle;
use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Chamilo\CoreBundle\ChamiloCoreBundle;
use Chamilo\CourseBundle\ChamiloCourseBundle;
use Chamilo\LtiBundle\ChamiloLtiBundle;
use Cocur\Slugify\Bridge\Symfony\CocurSlugifyBundle;
use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle;
use FOS\JsRoutingBundle\FOSJsRoutingBundle;
use Gregwar\CaptchaBundle\GregwarCaptchaBundle;
use Hautelook\AliceBundle\HautelookAliceBundle;
use KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle;
use Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle;
use Nelmio\Alice\Bridge\Symfony\NelmioAliceBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Oh\GoogleMapFormTypeBundle\OhGoogleMapFormTypeBundle;
use Oneup\FlysystemBundle\OneupFlysystemBundle;
use Sonata\Exporter\Bridge\Symfony\SonataExporterBundle;
use Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle;
use Sylius\Bundle\SettingsBundle\SyliusSettingsBundle;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;
use SymfonyCasts\Bundle\ResetPassword\SymfonyCastsResetPasswordBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;
use Vich\UploaderBundle\VichUploaderBundle;

return [
    FrameworkBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    DebugBundle::class => ['dev' => true, 'test' => true],
    WebProfilerBundle::class => ['dev' => true, 'test' => true],
    FOSJsRoutingBundle::class => ['all' => true],
    ChamiloCoreBundle::class => ['all' => true],
    ChamiloCourseBundle::class => ['all' => true],
    ChamiloLtiBundle::class => ['all' => true],
    SyliusSettingsBundle::class => ['all' => true],
    OneupFlysystemBundle::class => ['all' => true],
    A2lixAutoFormBundle::class => ['all' => true],
    A2lixTranslationFormBundle::class => ['all' => true],
    WebpackEncoreBundle::class => ['all' => true],
    VichUploaderBundle::class => ['all' => true],
    CocurSlugifyBundle::class => ['all' => true],
    KnpUOAuth2ClientBundle::class => ['all' => true],
    NelmioCorsBundle::class => ['all' => true],
    ApiPlatformBundle::class => ['all' => true],
    TwigExtraBundle::class => ['all' => true],
    MakerBundle::class => ['dev' => true],
    SymfonyCastsResetPasswordBundle::class => ['all' => true],
    StofDoctrineExtensionsBundle::class => ['all' => true],
    LexikJWTAuthenticationBundle::class => ['all' => true],
    DAMADoctrineTestBundle::class => ['test' => true],
    NelmioAliceBundle::class => ['dev' => true, 'test' => true],
    FidryAliceDataFixturesBundle::class => ['dev' => true, 'test' => true],
    HautelookAliceBundle::class => ['dev' => true, 'test' => true],
    SonataExporterBundle::class => ['all' => true],
    OhGoogleMapFormTypeBundle::class => ['all' => true],
    GregwarCaptchaBundle::class => ['all' => true],
    McpBundle::class => ['all' => true],
];
