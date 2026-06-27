<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

trait SurveyCsrfTokenValidationTrait
{
    /**
     * @param array<string, mixed>|object|null $payload
     */
    private function validateSubmittedCsrfToken(
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        string $tokenId,
        mixed $payload = null,
        ?string $fallbackToken = null,
        string $message = 'Invalid CSRF token.'
    ): void {
        foreach ($this->getSubmittedCsrfTokenCandidates($request, $payload, $fallbackToken) as $token) {
            if ($csrfTokenManager->isTokenValid(new CsrfToken($tokenId, $token))) {
                return;
            }
        }

        throw new AccessDeniedHttpException($message);
    }

    /**
     * @param array<string, mixed>|object|null $payload
     *
     * @return string[]
     */
    private function getSubmittedCsrfTokenCandidates(Request $request, mixed $payload = null, ?string $fallbackToken = null): array
    {
        $tokens = [];
        $this->appendSurveyCsrfTokenCandidate($tokens, $fallbackToken);

        if (\is_array($payload)) {
            $this->appendSurveyCsrfTokenCandidate($tokens, $payload['csrfToken'] ?? null);
            $this->appendSurveyCsrfTokenCandidate($tokens, $payload['_token'] ?? null);
        } elseif (\is_object($payload)) {
            if (property_exists($payload, 'csrfToken')) {
                $this->appendSurveyCsrfTokenCandidate($tokens, $payload->csrfToken ?? null);
            }

            if (property_exists($payload, '_token')) {
                $this->appendSurveyCsrfTokenCandidate($tokens, $payload->_token ?? null);
            }
        }

        $this->appendSurveyCsrfTokenCandidate($tokens, $request->headers->get('X-CSRF-Token'));
        $this->appendSurveyCsrfTokenCandidate($tokens, $request->headers->get('X-CSRFToken'));
        $this->appendSurveyCsrfTokenCandidate($tokens, $request->request->get('csrfToken'));
        $this->appendSurveyCsrfTokenCandidate($tokens, $request->request->get('_token'));

        $content = trim($request->getContent());
        if ('' !== $content) {
            $decoded = json_decode($content, true);
            if (\is_array($decoded)) {
                $this->appendSurveyCsrfTokenCandidate($tokens, $decoded['csrfToken'] ?? null);
                $this->appendSurveyCsrfTokenCandidate($tokens, $decoded['_token'] ?? null);
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @param string[] $tokens
     */
    private function appendSurveyCsrfTokenCandidate(array &$tokens, mixed $token): void
    {
        if (null === $token) {
            return;
        }

        if (!\is_scalar($token) && !$token instanceof \Stringable) {
            return;
        }

        $token = trim((string) $token);
        if ('' === $token) {
            return;
        }

        $tokens[] = $token;
    }
}
