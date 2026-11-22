<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator\Cas;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use const LIBXML_NOERROR;
use const LIBXML_NONET;
use const LIBXML_NOWARNING;

final readonly class CasTicketValidator
{
    private const int MAX_BODY = 65536;
    private const string CAS_NS = 'http://www.yale.edu/tp/cas';

    public function __construct(
        #[Autowire(service: 'chamilo.cas_http_client')]
        private HttpClientInterface $client,
    ) {}

    /**
     * @return array{username: string, attributes: array<string, list<string>>}
     */
    public function validate(array $config, string $service, string $ticket): array
    {
        if (!preg_match('/\AST-[\x21-\x7e]{1,1021}\z/D', $ticket)) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }

        try {
            // Do not forward ticket credentials to a redirect or disable TLS verification.
            $response = $this->client->request('GET', $config['base'].$config['validation'], [
                'query' => ['service' => $service, 'ticket' => $ticket],
                'headers' => ['Accept' => '/validate' === $config['validation'] ? 'text/plain' : 'application/xml'],
                'max_redirects' => 0,
                'verify_peer' => true,
                'verify_host' => true,
                'timeout' => 5,
                'max_duration' => 10,
                'buffer' => false,
            ]);
            if (200 !== $response->getStatusCode()) {
                $response->cancel();

                throw new CustomUserMessageAuthenticationException('Authentication failed.');
            }
            $body = '';
            foreach ($this->client->stream($response) as $chunk) {
                $body .= $chunk->getContent();
                if (\strlen($body) > self::MAX_BODY) {
                    $response->cancel();

                    throw new CustomUserMessageAuthenticationException('Authentication failed.');
                }
            }
        } catch (ExceptionInterface) {
            // The HTTP client exception may contain the service ticket. Never log or expose it.
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }

        return self::parse($body, '/validate' === $config['validation']);
    }

    /**
     * @return array{username: string, attributes: array<string, list<string>>}
     */
    public static function parse(string $body, bool $legacy = false): array
    {
        $attributes = [];
        if ('' === $body || \strlen($body) > self::MAX_BODY) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }
        if ($legacy) {
            if (!preg_match('/\Ayes\r?\n([^\r\n]+)\r?\n?\z/D', $body, $matches)) {
                throw new CustomUserMessageAuthenticationException('Authentication failed.');
            }
            $username = $matches[1];
        } else {
            // Reject DTD/entity declarations entirely; do not expand entities or load a DTD.
            if (str_contains($body, "\0") || preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $body)) {
                throw new CustomUserMessageAuthenticationException('Authentication failed.');
            }
            $document = new DOMDocument();
            if (!@$document->loadXML($body, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING) || null !== $document->doctype) {
                throw new CustomUserMessageAuthenticationException('Authentication failed.');
            }
            $root = $document->documentElement;
            if (!$root instanceof DOMElement || self::CAS_NS !== $root->namespaceURI || 'serviceResponse' !== $root->localName) {
                throw new CustomUserMessageAuthenticationException('Authentication failed.');
            }

            // Never let response-defined prefixes override the trusted CAS namespace.
            $xpath = new DOMXPath($document, false);
            $xpath->registerNamespace('cas', self::CAS_NS);
            $success = $xpath->query('/cas:serviceResponse/cas:authenticationSuccess', null, false);
            $failure = $xpath->query('/cas:serviceResponse/cas:authenticationFailure', null, false);
            $users = $xpath->query('/cas:serviceResponse/cas:authenticationSuccess/cas:user', null, false);
            if (1 !== $success->length || 0 !== $failure->length || 1 !== $users->length || 0 !== $xpath->query('/cas:serviceResponse/cas:authenticationSuccess/cas:user/*', null, false)->length) {
                throw new CustomUserMessageAuthenticationException('Authentication failed.');
            }
            $username = $users->item(0)->textContent;
            foreach ($xpath->query('/cas:serviceResponse/cas:authenticationSuccess/cas:attributes/cas:*', null, false) as $attribute) {
                if ($attribute instanceof DOMElement && 0 === $attribute->childElementCount) {
                    $attributes[$attribute->localName][] = $attribute->textContent;
                }
            }
        }
        if ('' === $username || trim($username) !== $username || \strlen($username) > 255 || !preg_match('//u', $username) || preg_match('/[\x00-\x1f\x7f]/', $username)) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }

        return ['username' => $username, 'attributes' => $attributes];
    }
}
