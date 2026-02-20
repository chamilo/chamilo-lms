<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Helpers\IntrusionDetectionLogHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use const PHP_URL_PATH;

/**
 * Minimal in-application Intrusion Detection System.
 *
 * On REQUEST: scans URL query parameters, the request URI (path traversal / scanner
 * patterns) and selected headers (User-Agent, Referer) for common attack signatures.
 * Suspicious requests are logged to var/logs/ids/ids_events.log.
 * When IDS_BLOCK=true the request is immediately terminated with a 400 response.
 *
 * On RESPONSE: injects OWASP-recommended security headers on every main response.
 *
 * Toggle via environment variables (see config/packages/chamilo_ids.yaml):
 *   IDS_ENABLED=true|false           (default true)
 *   IDS_BLOCK=true|false             (default false – log-only)
 *   IDS_SECURITY_HEADERS=true|false  (default true)
 *
 * NOTE: Request bodies are intentionally NOT scanned to avoid false positives from
 * rich-text editors (CKEditor) that legitimately produce HTML/JS in course content.
 */
class IntrusionDetectionSubscriber implements EventSubscriberInterface
{
    // -------------------------------------------------------------------------
    // Attack pattern definitions
    // -------------------------------------------------------------------------

    /**
     * Patterns applied to URL query-parameter values.
     */
    private const PARAM_PATTERNS = [
        // SQL Injection -------------------------------------------------------
        ['pattern' => '/UNION\s+(ALL\s+)?SELECT\s/i', 'type' => 'SQLi'],
        ['pattern' => '/SELECT\s+[\w\s,*\'`]+\s+FROM\s+\w/i', 'type' => 'SQLi'],
        ['pattern' => "/'\\s*(OR|AND)\\s+'?[\\d']/i", 'type' => 'SQLi'],
        ['pattern' => '/\bSLEEP\s*\(\s*\d+\s*\)/i', 'type' => 'SQLi'],
        ['pattern' => '/\bBENCHMARK\s*\(\s*\d+/i', 'type' => 'SQLi'],
        ['pattern' => '/;\s*(SELECT|UPDATE|INSERT|DELETE|DROP|CREATE|ALTER|TRUNCATE|EXEC)\s/i', 'type' => 'SQLi'],
        ['pattern' => '/\bEXEC\s*\(\s*@/i', 'type' => 'SQLi'],   // EXEC(@sql)
        ['pattern' => '/\bINFORMATION_SCHEMA\b/i', 'type' => 'SQLi'],

        // XSS -----------------------------------------------------------------
        ['pattern' => '/<script[^>]*>/i', 'type' => 'XSS'],
        ['pattern' => '/javascript\s*:/i', 'type' => 'XSS'],
        ['pattern' => '/vbscript\s*:/i', 'type' => 'XSS'],
        ['pattern' => '/on(?:click|load|error|mouseover|mouseout|keyup|keydown|submit|change|focus|blur|input|ready)\s*=/i', 'type' => 'XSS'],
        ['pattern' => '/<(?:iframe|object|embed|applet|form)\b/i', 'type' => 'XSS'],
        ['pattern' => '/expression\s*\([^)]*\)/i', 'type' => 'XSS'],
        ['pattern' => '/data\s*:\s*text\/html/i', 'type' => 'XSS'],

        // Path traversal ------------------------------------------------------
        ['pattern' => '/(?:\.\.[\/]){2,}/', 'type' => 'PathTraversal'],             // ../../
        ['pattern' => '/%2e%2e[%\/]/i', 'type' => 'PathTraversal'],                 // %2e%2e/
        ['pattern' => '/%252e%252e/i', 'type' => 'PathTraversal'],                   // double-encoded
        ['pattern' => '/\/etc\/(?:passwd|shadow|group|hosts)\b/', 'type' => 'PathTraversal'],
        ['pattern' => '/[Cc]:[\\\\\/](?:Windows|Users|Program Files)/', 'type' => 'PathTraversal'],

        // Command injection ---------------------------------------------------
        ['pattern' => '/`[^`\r\n]{1,120}`/', 'type' => 'CmdInjection'],             // backtick execution
        ['pattern' => '/\$\([^)\r\n]{1,120}\)/', 'type' => 'CmdInjection'],         // $() substitution
        ['pattern' => '/[;&|]{1,2}\s*(?:ls|cat|id|whoami|uname|pwd|wget|curl|bash|sh|nc|python|python3|perl|ruby)\b/i', 'type' => 'CmdInjection'],
        ['pattern' => '/\|\s*(?:ls|cat|id|whoami|bash|sh|python)\s/i', 'type' => 'CmdInjection'],
    ];

    /**
     * Patterns applied to the raw request URI (path + query string).
     */
    private const URI_PATTERNS = [
        // Path traversal in URI
        ['pattern' => '/(?:\.\.[\/]){2,}/', 'type' => 'PathTraversal'],
        ['pattern' => '/%2e%2e[%\/]/i', 'type' => 'PathTraversal'],
        ['pattern' => '/%252e%252e/i', 'type' => 'PathTraversal'],
        // Common sensitive-file probes
        ['pattern' => '/\/\.git\/(?:config|HEAD|COMMIT_EDITMSG)/i', 'type' => 'Scanner'],
        ['pattern' => '/\/\.env(?:\.(?:local|prod|dev|test))?(?:$|\?)/', 'type' => 'Scanner'],
        ['pattern' => '/\/(?:wp-admin|wp-login\.php|xmlrpc\.php)\b/i', 'type' => 'Scanner'],
        ['pattern' => '/\/(?:phpMyAdmin|phpmyadmin|pma|myadmin|mysql)\b/i', 'type' => 'Scanner'],
        ['pattern' => '/\/etc\/(?:passwd|shadow|group|hosts)\b/', 'type' => 'Scanner'],
        ['pattern' => '/\/(?:\.htaccess|\.htpasswd|web\.config)\b/i', 'type' => 'Scanner'],
    ];

    /**
     * Patterns applied to the User-Agent header only.
     */
    private const UA_PATTERNS = [
        ['pattern' => '/sqlmap/i', 'type' => 'Scanner'],
        ['pattern' => '/nikto/i', 'type' => 'Scanner'],
        ['pattern' => '/nessus/i', 'type' => 'Scanner'],
        ['pattern' => '/acunetix/i', 'type' => 'Scanner'],
        ['pattern' => '/(?:dirbuster|dirb|gobuster|ffuf|wfuzz)/i', 'type' => 'Scanner'],
        ['pattern' => '/(?:nmap|masscan|zgrab|censys)/i', 'type' => 'Scanner'],
        ['pattern' => '/w3af/i', 'type' => 'Scanner'],
        ['pattern' => '/openvas/i', 'type' => 'Scanner'],
        ['pattern' => '/burpsuite/i', 'type' => 'Scanner'],
        ['pattern' => '/havij/i', 'type' => 'Scanner'],
    ];

    /**
     * Patterns applied to the Referer header.
     */
    private const REFERER_PATTERNS = [
        ['pattern' => '/<script[^>]*>/i', 'type' => 'XSS'],
        ['pattern' => '/javascript\s*:/i', 'type' => 'XSS'],
        ['pattern' => '/UNION\s+(ALL\s+)?SELECT\s/i', 'type' => 'SQLi'],
    ];

    // Paths matching this regex are never scanned (static assets, dev tools).
    private const SKIP_PATH_REGEX = '/^\/(_(profiler|wdt)|build\/|bundles\/|css\/|images\/|js\/)/';

    // Extensions that indicate a static asset request.
    private const STATIC_EXT_REGEX = '/\.(css|js|map|png|jpe?g|gif|svg|ico|woff2?|ttf|eot|pdf)(\?|$)/i';

    /**
     * Top-level directories that exist under public/main/ as of 2026-02-20.
     * Used to recognise legitimate ?redirect= values pointing at legacy PHP scripts.
     * This list can only shrink over time as legacy code is migrated to Symfony.
     */
    private const LEGACY_MAIN_DIRS = [
        'admin', 'announcements', 'attendance', 'auth', 'calendar', 'chat',
        'course_copy', 'course_description', 'course_home', 'course_info',
        'course_progress', 'create_course', 'cron', 'dashboard', 'dropbox',
        'exercise', 'extrafield', 'forum', 'gamification', 'glossary',
        'gradebook', 'group', 'help', 'inc', 'install', 'link', 'lp',
        'mail_template', 'my_space', 'notebook', 'notification_event',
        'palettes', 'plagiarism', 'portfolio', 'search', 'session', 'skills',
        'social', 'survey', 'template', 'ticket', 'tracking', 'upload',
        'user', 'wiki', 'work',
    ];

    public function __construct(
        #[Autowire(param: 'chamilo.ids.enabled')]
        private readonly bool $enabled,
        #[Autowire(param: 'chamilo.ids.block_on_detection')]
        private readonly bool $blockOnDetection,
        #[Autowire(param: 'chamilo.ids.security_headers')]
        private readonly bool $securityHeaders,
        private readonly IntrusionDetectionLogHelper $logHelper,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 32],
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    // -------------------------------------------------------------------------
    // Request scanning
    // -------------------------------------------------------------------------

    public function onRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $uri = $request->getRequestUri();

        if ($this->isSkippable($uri)) {
            return;
        }

        $ip = $request->getClientIp() ?? 'unknown';

        // 1. Scan URI (path + query string) for path-traversal / scanner probes
        $hit = $this->matchPatterns($uri, self::URI_PATTERNS);
        if (null !== $hit) {
            $this->handleHit($event, $ip, $uri, 'uri', $hit['type'], $hit['match']);
            if ($this->blockOnDetection) {
                return;
            }
        }

        // 2. Scan individual query-parameter values
        foreach ($request->query->all() as $name => $value) {
            if (\is_array($value)) {
                continue; // Skip nested arrays
            }
            $stringValue = (string) $value;
            // A value that points to a known legacy /main/ PHP script (e.g. coming
            // from a ?redirect= parameter after session expiry) is legitimate.
            // URL query-string delimiters like &id= would otherwise produce
            // false positives against the CmdInjection patterns, so we skip those
            // patterns for these values while keeping SQLi, XSS and PathTraversal.
            $patterns = $this->isLegacyMainRedirect($stringValue)
                ? array_filter(self::PARAM_PATTERNS, static fn ($p) => 'CmdInjection' !== $p['type'])
                : self::PARAM_PATTERNS;
            $hit = $this->matchPatterns($stringValue, $patterns);
            if (null !== $hit) {
                $this->handleHit($event, $ip, $uri, (string) $name, $hit['type'], $hit['match']);
                if ($this->blockOnDetection) {
                    return;
                }
            }
        }

        // 3. Scan User-Agent for known scanner signatures
        $ua = (string) $request->headers->get('User-Agent', '');
        if ('' !== $ua) {
            $hit = $this->matchPatterns($ua, self::UA_PATTERNS);
            if (null !== $hit) {
                $this->handleHit($event, $ip, $uri, 'User-Agent', $hit['type'], $hit['match']);
                if ($this->blockOnDetection) {
                    return;
                }
            }
        }

        // 4. Scan Referer for XSS / SQLi reflection attempts
        $referer = (string) $request->headers->get('Referer', '');
        if ('' !== $referer) {
            $hit = $this->matchPatterns($referer, self::REFERER_PATTERNS);
            if (null !== $hit) {
                $this->handleHit($event, $ip, $uri, 'Referer', $hit['type'], $hit['match']);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Response hardening
    // -------------------------------------------------------------------------

    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->securityHeaders || !$event->isMainRequest()) {
            return;
        }

        $this->addSecurityHeaders($event->getResponse(), $event->getRequest());
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Match a value against a list of pattern definitions.
     * Returns ['type' => ..., 'match' => ...] for the first match, or null.
     *
     * @param array<int, array{pattern: string, type: string}> $patterns
     *
     * @return array{type: string, match: string}|null
     */
    private function matchPatterns(string $value, array $patterns): ?array
    {
        foreach ($patterns as $def) {
            if (preg_match($def['pattern'], $value, $m)) {
                return ['type' => $def['type'], 'match' => $m[0]];
            }
        }

        return null;
    }

    /**
     * Log and optionally block a detected attack.
     */
    private function handleHit(
        RequestEvent $event,
        string $ip,
        string $uri,
        string $param,
        string $type,
        string $matchedValue
    ): void {
        // Truncate the matched value to avoid storing excessively long payloads
        $truncated = mb_substr($matchedValue, 0, 100);
        $detail = "Detected {$type} pattern in param '{$param}': ".addslashes($truncated);

        $this->logHelper->logEvent($type, $ip, $uri, $param, $detail);

        if ($this->blockOnDetection) {
            $event->setResponse(new Response('Bad Request', Response::HTTP_BAD_REQUEST));
        }
    }

    private function addSecurityHeaders(Response $response, Request $request): void
    {
        $headers = $response->headers;

        // Prevent clickjacking
        if (!$headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // Prevent MIME-type sniffing
        if (!$headers->has('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', 'nosniff');
        }

        // Control referrer information sent with requests
        if (!$headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        // Restrict browser feature access
        if (!$headers->has('Permissions-Policy')) {
            $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        }

        // Remove server-fingerprinting headers if present
        $headers->remove('X-Powered-By');
        $headers->remove('Server');
    }

    /**
     * Returns true when $value is a URL that points to a known legacy PHP script
     * inside the /main/ tree (e.g. "/main/forum/index.php?cid=1&id=5").
     *
     * Such values appear legitimately in ?redirect= parameters when a user's
     * session expires while they are on a legacy page.  The CmdInjection patterns
     * would fire false positives on URL parameters like &id= because the
     * URL query-string delimiter & looks like a shell metacharacter to those regexes.
     *
     * The regex is built lazily from LEGACY_MAIN_DIRS, which lists every top-level
     * directory that existed under public/main/ on 2026-02-20.  Because legacy code
     * is only removed, never added, the list will never need to grow.
     */
    private function isLegacyMainRedirect(string $value): bool
    {
        static $pattern = null;
        if (null === $pattern) {
            $dirs = implode('|', array_map('preg_quote', self::LEGACY_MAIN_DIRS, array_fill(0, \count(self::LEGACY_MAIN_DIRS), '/')));
            // Query string: allow only typical URL-safe characters (%xx, =, &, +, -, _, ., ~, :, @, #).
            // Explicitly excludes shell metacharacters (;  `  $  |  <  >) so a crafted value
            // like "?cid=1; bash" does NOT bypass CmdInjection detection.
            $pattern = '/^\/main\/(?:'.$dirs.')\/[a-zA-Z0-9_.\-\/]+\.php(?:\?[\w%=&+\-.,~:@#]*)?$/i';
        }

        return (bool) preg_match($pattern, $value);
    }

    private function isSkippable(string $uri): bool
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? $uri;

        return (bool) preg_match(self::SKIP_PATH_REGEX, (string) $path)
            || (bool) preg_match(self::STATIC_EXT_REGEX, (string) $path);
    }
}
