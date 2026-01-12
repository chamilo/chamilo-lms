<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Exception\ScimException;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\ScimHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use const JSON_ERROR_NONE;

abstract class AbstractScimController extends AbstractController
{
    public const SCIM_CONTENT_TYPE = 'application/scim+json';

    protected array $scimConfig;

    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly ScimHelper $scimHelper,
        protected readonly AccessUrlHelper $accessUrlHelper,
        AuthenticationConfigHelper $authenticationConfigHelper,
    ) {
        $this->scimConfig = $authenticationConfigHelper->getScimConfig();
    }

    protected function getAndValidateJson(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            throw new ScimException('No content');
        }

        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ScimException('Invalid JSON: '.json_last_error_msg());
        }

        return $data;
    }

    /**
     * @throws ScimException
     */
    protected function authenticateRequest(Request $request): void
    {
        if (!$this->scimConfig['enabled']) {
            throw new AccessDeniedHttpException($this->translator->trans('SCIM is not enabled.'));
        }

        $authHeader = $request->headers->get('Authorization');

        $invalidTokenException = new ScimException(
            $this->translator->trans('Invalid token.'),
            Response::HTTP_UNAUTHORIZED
        );

        if (!$authHeader) {
            throw $invalidTokenException;
        }

        if (!preg_match('/^Bearer\s+(\S+)/i', $authHeader, $matches)) {
            throw $invalidTokenException;
        }

        $providedToken = $matches[1];

        if (!hash_equals($this->getParameter('scim_token'), $providedToken)) {
            throw $invalidTokenException;
        }
    }
}
