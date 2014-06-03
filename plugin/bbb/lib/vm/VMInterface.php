<?php

/**
 * Interface VirtualMachineInterface
 */
interface VirtualMachineInterface
{
    /**
     * @return mixed
     */
    function connect();

    /**
     * @return mixed
     */
    function runCron();

    /**
     * @return mixed
     */
    function resizeToMaxLimit();

    /**
     * @return mixed
     */
    function resizeToMinLimit();
}

