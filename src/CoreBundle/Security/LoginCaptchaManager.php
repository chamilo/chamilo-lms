<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LoginCaptchaManager
{
    private const SESSION_CODE_KEY = 'login_captcha.code';
    private const CACHE_PREFIX = 'login_captcha.state.';
    private const DEFAULT_CODE_LENGTH = 5;

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly CacheInterface $cache,
    ) {}

    public function isEnabled(): bool
    {
        return 'true' === $this->settingsManager->getSetting('security.allow_captcha', true);
    }

    public function getMistakeLimit(): int
    {
        return max(
            1,
            (int) $this->settingsManager->getSetting('security.captcha_number_mistakes_to_block_account', true)
        );
    }

    public function getBlockMinutes(): int
    {
        return max(
            1,
            (int) $this->settingsManager->getSetting('security.captcha_time_to_block', true)
        );
    }

    public function isBlocked(string $username): bool
    {
        $state = $this->getState($username);

        if (empty($state['blocked_until'])) {
            return false;
        }

        return (int) $state['blocked_until'] > time();
    }

    public function getRemainingBlockedSeconds(string $username): int
    {
        $state = $this->getState($username);
        $blockedUntil = (int) ($state['blocked_until'] ?? 0);

        if ($blockedUntil <= time()) {
            return 0;
        }

        return $blockedUntil - time();
    }

    public function registerCaptchaMistake(string $username): void
    {
        $state = $this->getState($username);
        $mistakes = (int) ($state['mistakes'] ?? 0) + 1;

        $newState = [
            'mistakes' => $mistakes,
            'blocked_until' => null,
        ];

        if ($mistakes >= $this->getMistakeLimit()) {
            $newState['blocked_until'] = time() + ($this->getBlockMinutes() * 60);
            $newState['mistakes'] = 0;
        }

        $this->saveState($username, $newState);
    }

    public function resetCaptchaState(string $username): void
    {
        $this->cache->delete($this->buildCacheKey($username));
    }

    public function generateCaptchaCode(SessionInterface $session): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < self::DEFAULT_CODE_LENGTH; $i++) {
            $code .= $alphabet[random_int(0, $maxIndex)];
        }

        $session->set(self::SESSION_CODE_KEY, $code);

        return $code;
    }

    public function validateCaptcha(Request $request, ?string $submittedCode): bool
    {
        if (!$request->hasSession()) {
            return false;
        }

        $expectedCode = (string) $request->getSession()->get(self::SESSION_CODE_KEY, '');
        $request->getSession()->remove(self::SESSION_CODE_KEY);

        $submittedCode = strtoupper(trim((string) $submittedCode));

        if ('' === $expectedCode || '' === $submittedCode) {
            return false;
        }

        return hash_equals($expectedCode, $submittedCode);
    }

    public function buildSvg(string $code): string
    {
        $letters = '';
        $x = 24;

        foreach (str_split($code) as $char) {
            $y = random_int(48, 62);
            $rotate = random_int(-10, 10);

            $letters .= sprintf(
                '<text x="%d" y="%d" font-family="Arial, sans-serif" font-size="28" font-weight="700" fill="#0F172A" transform="rotate(%d %d %d)">%s</text>',
                $x,
                $y,
                $rotate,
                $x,
                $y,
                htmlspecialchars($char, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );

            $x += 34;
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="220" height="80" viewBox="0 0 220 80">
  <rect x="0" y="0" width="220" height="80" rx="8" fill="#F8FAFC"/>
  <line x1="10" y1="15" x2="210" y2="65" stroke="#CBD5E1" stroke-width="1"/>
  <line x1="20" y1="70" x2="200" y2="10" stroke="#CBD5E1" stroke-width="1"/>
  {$letters}
</svg>
SVG;
    }

    private function getState(string $username): array
    {
        return $this->cache->get($this->buildCacheKey($username), function (ItemInterface $item): array {
            $item->expiresAfter(86400);

            return [
                'mistakes' => 0,
                'blocked_until' => null,
            ];
        });
    }

    private function saveState(string $username, array $state): void
    {
        $key = $this->buildCacheKey($username);
        $this->cache->delete($key);

        $this->cache->get($key, function (ItemInterface $item) use ($state): array {
            $item->expiresAfter(86400);

            return $state;
        });
    }

    private function buildCacheKey(string $username): string
    {
        return self::CACHE_PREFIX.sha1(mb_strtolower(trim($username)));
    }
}
