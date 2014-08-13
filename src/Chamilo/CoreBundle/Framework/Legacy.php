<?php

class Legacy
{
    public static function getRouter()
    public static function setConfiguration($configuration)
    {
        self::$configuration = $configuration;
    }

    public static function getConfiguration()
    {
        return self::$configuration;
    }
}
