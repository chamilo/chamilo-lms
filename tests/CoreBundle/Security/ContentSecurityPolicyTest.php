<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\EventSubscriber\ContentSecurityPolicySubscriber;
use Chamilo\CoreBundle\Security\ContentSecurityPolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Twig\Extension\ContentSecurityPolicyExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ContentSecurityPolicyTest extends TestCase
{
    private const string POLICY = "default-src 'self'; script-src 'nonce-{nonce}' 'strict-dynamic'; script-src-attr 'none'; object-src 'none';";
    private const string ENFORCE = 'Content-Security-Policy';
    private const string REPORT = 'Content-Security-Policy-Report-Only';

    public function testDisabledIsInert(): void
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->expects(self::once())->method('getSetting')
            ->with('security.content_security_policy_mode', true)->willReturn('disabled')
        ;
        $policy = new ContentSecurityPolicy($settings, new NullLogger());
        $request = Request::create('/login');
        $response = $this->html();
        $originalHeaders = $response->headers->all();

        self::assertSame('', $policy->prepareLayout($request));
        self::assertSame('', $policy->prepareLayout($request));
        $policy->apply($request, $response);
        self::assertSame($originalHeaders, $response->headers->all());
    }

    public function testReportOnlyUsesItsOwnPolicyAndFreshNonces(): void
    {
        $policy = $this->policy('report_only', 'default-src *', self::POLICY);
        $request = Request::create('/login');
        $nonce = $policy->prepareLayout($request);
        self::assertSame(32, \strlen((string) base64_decode($nonce, true)));
        self::assertSame($nonce, $policy->prepareLayout($request));
        self::assertSame($nonce, $policy->getNonce($request));
        self::assertNotSame($nonce, $policy->prepareLayout(Request::create('/login')));

        $response = $this->html();
        $response->setPublic();
        $response->setEtag('old');
        $response->headers->set('Last-Modified', 'Fri, 01 Jan 2021 00:00:00 GMT');
        $policy->apply($request, $response);

        self::assertFalse($response->headers->has(self::ENFORCE));
        self::assertSame(str_replace('{nonce}', $nonce, self::POLICY), $response->headers->get(self::REPORT));
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
        self::assertFalse($response->headers->has('ETag'));
        self::assertFalse($response->headers->has('Last-Modified'));
    }

    public function testEnforceUsesItsOwnPolicy(): void
    {
        $policy = $this->policy('enforce', self::POLICY, 'default-src *');
        $request = Request::create('/courses');
        $nonce = $policy->prepareLayout($request);
        $response = $this->html();
        $policy->apply($request, $response);

        self::assertSame(str_replace('{nonce}', $nonce, self::POLICY), $response->headers->get(self::ENFORCE));
        self::assertFalse($response->headers->has(self::REPORT));
    }

    public function testOrdinaryCspDirectivesDoNotRequireANonce(): void
    {
        $configured = "default-src 'self'; script-src-attr 'none'; worker-src 'self' blob:;";
        $policy = $this->policy('enforce', $configured);
        $request = Request::create('/courses');
        $response = $this->html();
        self::assertSame('', $policy->prepareLayout($request));
        $policy->apply($request, $response);
        self::assertSame($configured, $response->headers->get(self::ENFORCE));
    }

    public function testUnpreparedContentAndNonDocumentResponsesAreUntouched(): void
    {
        $policy = $this->policy('enforce', self::POLICY);
        $request = Request::create('/document');
        $response = $this->html();
        $policy->apply($request, $response);
        self::assertFalse($response->headers->has(self::ENFORCE));
        self::assertSame('', $policy->getNonce($request));

        $policy->prepareLayout($request);
        foreach ([
            new Response('{}', 200, ['Content-Type' => 'application/json']),
            new Response('', 302, ['Location' => '/login']),
            new Response('', 304),
            new Response('file', 200, ['Content-Type' => 'text/html', 'Content-Disposition' => 'attachment']),
        ] as $response) {
            $policy->apply($request, $response);
            self::assertFalse($response->headers->has(self::ENFORCE));
            self::assertFalse($response->headers->has(self::REPORT));
        }
    }

    public function testExplicitControllerAndNativePoliciesTakePrecedence(): void
    {
        $policy = $this->policy('enforce', self::POLICY);
        $request = Request::create('/special');
        $policy->prepareLayout($request);
        foreach ([self::ENFORCE, self::REPORT] as $header) {
            $response = $this->html();
            $response->headers->set($header, "default-src 'none'; sandbox");
            $policy->apply($request, $response);
            self::assertSame("default-src 'none'; sandbox", $response->headers->get($header));
            self::assertFalse($response->headers->has(self::ENFORCE === $header ? self::REPORT : self::ENFORCE));
        }
        $response = $this->html();
        $policy->apply($request, $response, true);
        self::assertFalse($response->headers->has(self::ENFORCE));
    }

    public function testInvalidSettingsDoNotIntroduceInvalidHttpHeaders(): void
    {
        foreach ([['enforce', ''], ['enforce', "default-src 'self';\r\nSet-Cookie: bad=1"], ['enforce', str_repeat('x', 8193)], ['wrong', self::POLICY]] as [$mode, $configured]) {
            $policy = $this->policy($mode, $configured);
            $request = Request::create('/login');
            $response = $this->html();
            self::assertSame('', $policy->prepareLayout($request));
            $policy->apply($request, $response);
            self::assertFalse($response->headers->has(self::ENFORCE));
            self::assertFalse($response->headers->has(self::REPORT));
        }
    }

    public function testSubscriberUsesMainResponseOnly(): void
    {
        $policy = $this->policy('report_only', '', self::POLICY);
        $request = Request::create('/login');
        $policy->prepareLayout($request);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $subscriber = new ContentSecurityPolicySubscriber($policy);
        $subResponse = $this->html();
        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $subResponse));
        self::assertFalse($subResponse->headers->has(self::REPORT));
        $mainResponse = $this->html();
        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $mainResponse));
        self::assertTrue($mainResponse->headers->has(self::REPORT));
    }

    public function testTwigNonceBelongsToPreparedMainRequest(): void
    {
        $policy = $this->policy('enforce', self::POLICY);
        $stack = new RequestStack();
        $extension = new ContentSecurityPolicyExtension($policy, $stack);
        $main = Request::create('/login');
        $stack->push($main);
        self::assertSame('', $extension->getNonce());
        $nonce = $extension->getNonce(true);
        self::assertNotSame('', $nonce);
        self::assertSame($nonce, $extension->getNonce());
        $stack->push(Request::create('/fragment'));
        self::assertSame('', $extension->getNonce(true));
        $stack->pop();
        self::assertSame($nonce, $extension->getNonce());
    }

    private function policy(string $mode, string $enforce, string $report = ''): ContentSecurityPolicy
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')->willReturnMap([
            ['security.content_security_policy_mode', true, $mode],
            ['security.security_content_policy', true, $enforce],
            ['security.security_content_policy_report_only', true, $report],
        ]);

        return new ContentSecurityPolicy($settings, new NullLogger());
    }

    private function html(): Response
    {
        return new Response('<!doctype html><html></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
