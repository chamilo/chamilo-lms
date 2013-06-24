<?php

namespace Igorw\Silex;

interface ConfigDriver
{
    function load($filename);
    function supports($filename);
}
