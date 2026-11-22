<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig\Extension;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Security\ContentSecurityPolicy;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ContentSecurityPolicyExtension extends AbstractExtension
{
    public function __construct(
        private readonly ContentSecurityPolicy $policy,
        private readonly RequestStack $requestStack,
    ) {}

    public function getFunctions(): array
    {
        return [new TwigFunction('chamilo_csp_nonce', $this->getNonce(...))];
    }

    public function getNonce(bool $prepare = false): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            if ($request !== $this->requestStack->getMainRequest()) {
                return '';
            }
        } else {
            // Direct legacy PHP renders after its Symfony bootstrap request has returned.
            $request = Container::getRequest();
            if (null === $request || !$request->request->getBoolean('load_legacy')) {
                return '';
            }
        }

        return $prepare ? $this->policy->prepareLayout($request) : $this->policy->getNonce($request);
    }
}
