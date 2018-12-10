<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class NavBuilder.
 *
 * @package Chamilo\CoreBundle\Menu
 */
class MenuVoter implements VoterInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param ItemInterface $item
     *
     * @return bool|null
     */
    public function matchItem(ItemInterface $item)
    {
        $request = $this->requestStack->getCurrentRequest();
        $currentUri = $item->getUri();
        $currentUrl = $request->getUri();

        if ($item->getExtra('routes') !== null &&
            in_array($request->attributes->get('_route'), $item->getExtra('routes'))
        ) {
            return true;
        }

        if (strpos($currentUri, 'user_portal.php') !== false &&
            strpos($currentUrl, 'user_portal.php') !== false
        ) {
            return true;
        }

        if (strpos($currentUri, '/main/') !== false) {
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
