<?php

/**
 * Description of code_utilities
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva 
 */
class CodeUtilities
{

    const CLASS_PATTERN = '/(?:\s+class\s+[a-zA-Z_0-9\x7f-\xff]+\s*{)|(?:\s+class\s+[a-zA-Z_0-9\x7f-\xff]+\s*extends)|(?:\s+class\s+[a-zA-Z_0-9\x7f-\xff]+\s*implements)|(?:\s+interface\s+[a-zA-Z_0-9\x7f-\xff]+\s*{)|(?:\s+interface\s+[a-zA-Z_0-9\x7f-\xff]+\s*extends)/mi';
    const INLINE_COMMENT_PATTERN = '#//.*$#m';
    const MULTILINE_COMMENT_PATTERN = '#/\*.*?\*/#ms';
    const NAMESPACE_PATTERN = '/namespace\s*(.*);/';
    const IDENTIFIER_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    static function remove_comments($content)
    {
        $content = preg_replace(self::INLINE_COMMENT_PATTERN, '', $content);
        $content = preg_replace(self::MULTILINE_COMMENT_PATTERN, '', $content);
        return $content;
    }

    /**
     * Returns the name of classes and interfaces contained in content.
     * 
     * @param text $content
     * @return array
     */
    static function get_classes($content)
    {
        $result = array();
        $cls_pattern = self::CLASS_PATTERN;

        $content = self::remove_comments($content); //comments may contains class declaration we don't want to capture.      

        $matches = array();
        if (preg_match_all($cls_pattern, $content, $matches))
        {
            $matches = reset($matches);
            foreach ($matches as $match)
            {
                $match = str_replace("\n", ' ', $match);
                $match = str_replace('{', ' ', $match);
                $words = explode(' ', $match);
                foreach ($words as $word)
                {
                    $word = trim($word);
                    //we capture the interface/class name with the current pattern
                    if (strtolower($word) != 'class' && strtolower($word) != 'interface' && strtolower($word) != 'implements' && strtolower($word) != 'extends' && !empty($word))
                    {
                        $result[] = $word;
                        break; //we only take the first name as we don't want to capture the name of the interface or of the parent class name
                    }
                }
            }
        }
        return $result;
    }

    static function get_namespace($content)
    {
        $namespace_pattern = self::NAMESPACE_PATTERN;
        if (preg_match($namespace_pattern, $content, $matches))
        {
            $result = end($matches);
            if (self::is_valid_identifier($result))
            {
                return $result;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    static function is_valid_identifier($name)
    {
        $pattern = self::IDENTIFIER_PATTERN;
        $r = preg_match($pattern, $name);
        return $r;
    }

    /**
     * Make path relative to root.
     * 
     * @param string $root
     * @param string $path
     * @return string
     */
    static function relative_path($root, $path)
    {
        $path = realpath($path);
        $root = realpath($root);
        $path = str_ireplace($root, '', $path);
        $path = str_ireplace('\\', '/', $path);
        return $path;
    }

}
