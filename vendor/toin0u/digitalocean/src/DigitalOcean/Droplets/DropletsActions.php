<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Droplets;

/**
 * DropletActions class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class DropletsActions
{
    /**
     * Available actions.
     *
     * @var string
     */
    const ACTION_NEW                 = 'new';
    const ACTION_REBOOT              = 'reboot';
    const ACTION_POWER_CYCLE         = 'power_cycle';
    const ACTION_SHUTDOWN            = 'shutdown';
    const ACTION_POWER_ON            = 'power_on';
    const ACTION_POWER_OFF           = 'power_off';
    const ACTION_RESET_ROOT_PASSWORD = 'password_reset';
    const ACTION_RESIZE              = 'resize';
    const ACTION_SNAPSHOT            = 'snapshot';
    const ACTION_RESTORE             = 'restore';
    const ACTION_REBUILD             = 'rebuild';
    const ACTION_RENAME              = 'rename';
    const ACTION_DESTROY             = 'destroy';
}
