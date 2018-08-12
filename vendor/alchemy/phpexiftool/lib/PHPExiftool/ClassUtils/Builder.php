<?php

/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\ClassUtils;

/**
 * Build and write Tag classes
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class Builder
{
    protected $license = '/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */';
    protected $namespace;
    protected $classname;
    protected $properties;
    protected $extends;
    protected $uses;
    protected $classAnnotations;

    public function __construct($namespace, $classname, array $properties, $extends = null, Array $uses = array(), Array $classAnnotations = array())
    {
        $namespace = trim($namespace, '\\');

        foreach (explode('\\', $namespace) as $piece) {
            if ($piece == '') {
                continue;
            }

            if ( ! $this->checkPHPVarName($piece)) {
                throw new \Exception(sprintf('Invalid namespace %s', $namespace));
            }
        }
        if ( ! $this->checkPHPVarName($classname)) {
            throw new \Exception(sprintf('Invalid namespace %s', $namespace));
        }

        $this->namespace = trim('PHPExiftool\\Driver\\' . $namespace, '\\');
        $this->classname = $classname;
        $this->properties = $properties;
        $this->extends = $extends;
        $this->uses = $uses;
        $this->classAnnotations = $classAnnotations;

        return $this;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getClassname()
    {
        return $this->classname;
    }

    public function getProperty($property)
    {
        return isset($this->properties[$property]) ? $this->properties[$property] : null;
    }

    public function setProperty($property, $value)
    {
        $this->properties[$property] = $value;
    }

    public function getPathfile()
    {
        return __DIR__ . '/../../'
            . str_replace('\\', '/', $this->namespace) . "/"
            . $this->classname . '.php';
    }

    public function write($force = false)
    {
        if ( ! $force && file_exists($this->getPathfile()))
            throw new \Exception(sprintf('%s already exists', $this->getPathfile()));

        if (file_exists($this->getPathfile())) {
            unlink($this->getPathfile());
        }

        file_put_contents($this->getPathfile(), $this->generateContent());

        return $this;
    }

    public function generateContent()
    {
        $content = "<?php\n\n<license>\n\nnamespace <namespace>;\n\n";

        foreach ($this->uses as $use) {
            $content .= "use " . ltrim($use, "\\") . ";\n";
        }
        if ($this->uses) {
            $content .= "\n";
        }

        if ($this->classAnnotations) {
            $content .= "/**\n";
            foreach ($this->classAnnotations as $annotation) {
                $content .= " * " . $annotation . "\n";
            }
            $content .= " */\n";
        }

        $content .= "class <classname>";

        if ($this->extends) {
            $content .= " extends <extends>";
        }

        $content .= "\n{\n";

        $content .= $this->generateClassProperties($this->properties);

        $content .= "\n}\n";

        if ( ! is_dir(dirname($this->getPathfile()))) {
            mkdir(dirname($this->getPathfile()), 0754, true);
        }

        $content = str_replace(
            array('<license>', '<namespace>', '<classname>', '<spaces>', '<extends>')
            , array($this->license, $this->namespace, $this->classname, '    ', $this->extends)
            , $content
        );

        return $content;
    }

    protected function generateClassProperties(array $properties, $depth = 0)
    {
        $buffer = "";

        foreach ($properties as $key => $value) {
            if (is_array($value)) {
                $val = "array(\n" . $this->generateClassProperties($value, $depth + 1);

                for ($i = 0; $i != $depth; $i ++) {
                    $val .= "<spaces>";
                }

                $val .= "<spaces>)";
            } else {
                $val = $this->quote($value);
            }
            if ($depth == 0) {
                $buffer .= "\n<spaces>protected \$$key = $val;\n";
            } else {
                for ($i = 0; $i != $depth; $i ++) {
                    $buffer .= "<spaces>";
                }
                $buffer .= "<spaces>" . $this->quote($key) . " => " . $val . ",\n";
            }
        }

        return $buffer;
    }

    protected function checkPHPVarName($var)
    {
        return preg_match('/^[a-zA-Z]+[a-zA-Z0-9]*$/', $var);
    }

    protected function quote($value)
    {
        if (ctype_digit(trim($value))) {
            $data = strval(intval($value));

            // Do not use PHP_INT_MAX as 32/64 bit dependant
            if ($data <= -2147483648 || $data >= 2147483647) {
                return "'" . $value . "'";
            }

            return $data;
        }
        if (in_array(strtolower($value), array('true', 'false'))) {
            return strtolower($value);
        }

        return "'" . str_replace(array('\\', '\''), array('\\\\', '\\\''), $value) . "'";
    }
}
