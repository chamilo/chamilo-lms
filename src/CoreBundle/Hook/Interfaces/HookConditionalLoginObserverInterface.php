<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookConditionalLoginObserverInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookConditionalLoginObserverInterface extends HookObserverInterface
{
    /**
     * Return an associative array (callable, url) needed for Conditional Login.
     * <code>
     * [
     *     'conditional_function' => function (array $userInfo) {},
     *     'url' => '',
     * ]
     * </code>
     * conditional_function returns false to redirect to the url and returns true to continue with the classical login.
     *
     * @param HookConditionalLoginEventInterface $hook
     *
     * @return array
     */
    public function hookConditionalLogin(HookConditionalLoginEventInterface $hook);
}
