<?php

/* For licensing terms, see /license.txt */

/**
 * Interface VirtualMachineInterface.
 */
interface VirtualMachineInterface
{
    public function connect();

    public function runCron();

    public function resizeToMaxLimit();

    public function resizeToMinLimit();
}
