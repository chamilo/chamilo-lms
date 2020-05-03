<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class NavBuilder.
 */
class MenuVoter implements VoterInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function matchItem(ItemInterface $item): ?bool
    {
        $request = $this->requestStack->getCurrentRequest();
        $currentUri = $item->getUri();
        $currentUrl = $request->getUri();

        if (null !== $item->getExtra('routes') &&
            in_array($request->attributes->get('_route'), $item->getExtra('routes'))
        ) {
            return true;
        }

        if (false !== strpos($currentUri, 'user_portal.php') &&
            false !== strpos($currentUrl, 'user_portal.php')
        ) {
            return true;
        }

        if (false !== strpos($currentUri, '/main/')) {
            $pos = strpos($currentUri, '/main/');
            $partSelected = substr($currentUri, $pos);
            $partSelected = str_replace('/%2E%2E/', '', $partSelected);

            $pos = strpos($currentUrl, '/main/');
            $partCurrent = substr($currentUrl, $pos);
            $partCurrent = rtrim($partCurrent, '/');

            if ($partSelected === $partCurrent) {
                return true;
            }
        }

        return false;
    }
}
