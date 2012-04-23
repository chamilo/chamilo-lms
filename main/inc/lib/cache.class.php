<?php

/**
 * Cache for storing data. 
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Cache
{

    /**
     * Retrive an item from the cache if item creation date is greater than limit.
     * If item does not exists or is stale returns false.
     * 
     * @param any $key
     * @param int $limit
     * @return false|object 
     */
    static function get($key, $limit = 0)
    {
        if (!self::has($key, $limit))
        {
            return false;
        }
        $path = self::path($key);
        return file_get_contents($path);
    }

    /**
     * Returnsn true if the cache has the item and it is not staled.
     * 
     * @param any $key
     * @param int $limit
     * @return boolean
     */
    static function has($key, $limit = 0)
    {
        $path = self::path($key);
        if (!is_readable($path))
        {
            return false;
        }
        if ($limit)
        {
            $mtime = filemtime($path);
            if ($mtime < $limit)
            {
                return false;
            }
        }
        return true;
    }

    /**
     * Put something on the cache.
     * 
     * @param any $key
     * @param string $value 
     */
    static function put($key, $value)
    {
        $path = self::path($key);
        file_put_contents($path, $value);
    }

    /**
     * Remove an item from the cache.
     * 
     * @param any $key 
     */
    static function remove($key)
    {
        $path = self::path($key);
        if (is_readable($path))
        {
            unlink($path);
        }
    }

    /**
     * Clear the cache. Remove all entries. 
     */
    static function clear()
    {
        $dir = self::path();
        $files = scandir($dir);
        $files = array_diff($files, array('.', '..'));
        foreach ($files as $file)
        {
            $path = $dir . $file;
            unlink($path);
        }
    }

    /**
     * Returns the file path based on the key.
     * 
     * @param any $key
     * @return string 
     */
    static function path($key = '')
    {
        return api_get_path(SYS_PATH) . 'main/inc/cache/' . self::key($key);
    }

    /**
     * Returns the internal string key from the external key.
     * For internal use.
     * 
     * @param any $item
     * @return string
     */
    static function key($item)
    {
        if (is_object($item))
        {
            $f = array($item, 'get_unique_id');
            if (is_callable($f))
            {
                return call_user_func($f);
            }
        }
        $result = (string)$item;
    }

}