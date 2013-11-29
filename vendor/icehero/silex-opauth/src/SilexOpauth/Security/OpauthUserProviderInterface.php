<?php

namespace SilexOpauth\Security;

/**
 * Loads users using opauth result
 *
 * @author Rafal Lindemann
 */
interface OpauthUserProviderInterface
{

    function loadUserByOpauthResult(OpauthResult $result);
}

