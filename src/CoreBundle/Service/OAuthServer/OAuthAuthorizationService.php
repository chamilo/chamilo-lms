<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Dto\OAuthServer\OAuthAuthorizeRequest;
use Chamilo\CoreBundle\Entity\OAuthAuthorizationCode;
use Chamilo\CoreBundle\Entity\OAuthClient;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

use const ARRAY_FILTER_USE_BOTH;

/**
 * /oauth/authorize request validation, session stash/pop, and one-time code
 * issuance. Consent is always shown — there is no auto-approve path anywhere
 * in this class.
 */
final readonly class OAuthAuthorizationService
{
    public const int CODE_TTL_SECONDS = 120;
    public const int PENDING_REQUEST_TTL_SECONDS = 600;
    public const string SESSION_KEY = '_oauth_authorize_requests';
    public const string SCOPE = 'mcp';
    public const int MAX_PENDING_REQUESTS = 10;

    public function __construct(
        private OAuthClientResolver $clientResolver,
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private AccessUrlRepository $accessUrlRepository,
    ) {}

    /**
     * Phase 1: resolve the client and verify redirect_uri is exactly
     * registered. Failures here must NEVER redirect (RFC 6749 §4.1.2.1) — the
     * caller must render an HTML error page instead.
     *
     * @return array{client: OAuthClient, redirectUri: string}
     */
    public function resolveClientAndRedirectUri(Request $request): array
    {
        $clientId = (string) $request->query->get('client_id', '');
        $redirectUri = (string) $request->query->get('redirect_uri', '');

        if ('' === $clientId || '' === $redirectUri) {
            throw new RuntimeException('A client_id and redirect_uri are required.');
        }

        $client = $this->clientResolver->resolveActive($clientId);
        if (!$client instanceof OAuthClient) {
            throw new RuntimeException('Unknown or revoked OAuth client.');
        }

        if (!$client->supportsRedirectUri($redirectUri)) {
            throw new RuntimeException('This redirect_uri is not registered for this client.');
        }

        return ['client' => $client, 'redirectUri' => $redirectUri];
    }

    /**
     * Phase 2: everything else. Once the client/redirect_uri are trusted,
     * failures here ARE reported by redirecting with ?error=...&state=....
     */
    public function validateAuthorizeParameters(Request $request): OAuthAuthorizeRequest
    {
        $state = (string) $request->query->get('state', '');

        if ('code' !== (string) $request->query->get('response_type', '')) {
            throw OAuthException::unsupportedResponseType();
        }

        $codeChallengeMethod = (string) $request->query->get('code_challenge_method', '');
        if ('S256' !== $codeChallengeMethod) {
            throw OAuthException::invalidRequest('PKCE with code_challenge_method=S256 is required.');
        }

        $codeChallenge = (string) $request->query->get('code_challenge', '');
        if (1 !== preg_match('/^[A-Za-z0-9_-]{43}$/', $codeChallenge)) {
            throw OAuthException::invalidRequest('A valid code_challenge is required.');
        }

        $resource = $request->query->get('resource');
        $resource = \is_string($resource) && '' !== $resource ? $resource : null;

        return new OAuthAuthorizeRequest(
            clientId: (string) $request->query->get('client_id', ''),
            redirectUri: (string) $request->query->get('redirect_uri', ''),
            state: $state,
            codeChallenge: $codeChallenge,
            codeChallengeMethod: $codeChallengeMethod,
            resource: $resource,
            createdAt: time(),
        );
    }

    /**
     * Stores the pending request under a fresh random identifier and returns
     * it. The consent form must send that identifier back, so the approval
     * only ever applies to the request that was displayed, even when several
     * authorize requests are pending in the same session.
     */
    public function stashInSession(Request $request, OAuthAuthorizeRequest $authorizeRequest): string
    {
        $now = time();
        $pending = array_filter(
            $this->getPendingRequests($request),
            static fn (OAuthAuthorizeRequest $item): bool => !$item->isStale($now, self::PENDING_REQUEST_TTL_SECONDS),
        );

        $requestId = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $pending[$requestId] = $authorizeRequest;

        $request->getSession()->set(
            self::SESSION_KEY,
            \array_slice($pending, -self::MAX_PENDING_REQUESTS, null, true),
        );

        return $requestId;
    }

    public function popFromSession(Request $request, string $requestId): ?OAuthAuthorizeRequest
    {
        $pending = $this->getPendingRequests($request);
        $authorizeRequest = $pending[$requestId] ?? null;

        if (null === $authorizeRequest) {
            return null;
        }

        unset($pending[$requestId]);
        $request->getSession()->set(self::SESSION_KEY, $pending);

        return $authorizeRequest;
    }

    public function assertUserActiveOnCurrentPortal(User $user): bool
    {
        if (User::ACTIVE !== $user->getActive()) {
            return false;
        }

        $expirationDate = $user->getExpirationDate();
        if (null !== $expirationDate && $expirationDate <= new DateTime()) {
            return false;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            return false;
        }

        return $this->accessUrlRepository->isUrlActiveForUser($accessUrl, $user);
    }

    public function issueCode(
        OAuthClient $client,
        OAuthAuthorizeRequest $authorizeRequest,
        User $user,
        Request $consentRequest,
    ): string {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        $now = new DateTime();
        $plainCode = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $code = (new OAuthAuthorizationCode())
            ->setCodeHash(hash('sha256', $plainCode))
            ->setGrantId(Uuid::v4()->toRfc4122())
            ->setClient($client)
            ->setUser($user)
            ->setAccessUrlId(null !== $accessUrl ? (int) $accessUrl->getId() : null)
            ->setRedirectUri($authorizeRequest->redirectUri)
            ->setCodeChallenge($authorizeRequest->codeChallenge)
            ->setCodeChallengeMethod($authorizeRequest->codeChallengeMethod)
            ->setScope(self::SCOPE)
            ->setResource($authorizeRequest->resource)
            // Captured now, at the real browser consent moment, so the
            // eventual OAuthRefreshToken "connected app" record reflects who
            // actually clicked Allow rather than whichever backend later
            // exchanges the code at /token.
            ->setConsentIp($consentRequest->getClientIp())
            ->setConsentUserAgent(mb_substr((string) $consentRequest->headers->get('User-Agent', ''), 0, 255))
            ->setCreatedAt($now)
            ->setExpiresAt((clone $now)->modify('+'.self::CODE_TTL_SECONDS.' seconds'))
        ;

        $this->entityManager->persist($code);
        $this->entityManager->flush();

        return $plainCode;
    }

    /**
     * @return array<string, OAuthAuthorizeRequest>
     */
    private function getPendingRequests(Request $request): array
    {
        $pending = $request->getSession()->get(self::SESSION_KEY);
        if (!\is_array($pending)) {
            return [];
        }

        return array_filter(
            $pending,
            static fn (mixed $item, mixed $key): bool => \is_string($key) && $item instanceof OAuthAuthorizeRequest,
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @param array<string, string> $params
     */
    public function buildRedirectUrl(string $redirectUri, array $params): string
    {
        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return $redirectUri.$separator.http_build_query($params);
    }
}
