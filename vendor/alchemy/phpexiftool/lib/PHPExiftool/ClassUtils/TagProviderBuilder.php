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

class TagProviderBuilder extends Builder
{
    protected $classes = array();

    public function generateContent()
    {
        $content = "<?php\n\n<license>\n\nnamespace <namespace>;\n\n";

        foreach ($this->uses as $use) {
            $content .= "use " . ltrim($use, "\\") . "\;n";
        }
        if ($this->uses) {
            $content .= "\n";
        }

        $content .= "class <classname>";

        if ($this->extends) {
            $content .= " extends <extends>";
        }

        $content .= "\n{\n";

        $content .= $this->generateClassProperties($this->properties);

        $content .= "\n<spaces>public function __construct()\n<spaces>{\n";

        foreach ($this->classes as $groupname => $group) {

            $content .= "<spaces><spaces>\$this['$groupname'] = \$this->share(function(){\n";
            $content .= "<spaces><spaces><spaces>return array(\n";

            foreach ($group as $tagname => $classname) {
                $content .= "<spaces><spaces><spaces><spaces>'$tagname' => new \\$classname(),\n";
            }

            $content .= "<spaces><spaces><spaces>);\n";
            $content .= "<spaces><spaces>});\n";
        }

        $content .= "\n<spaces>}\n";

        $content .= "\n<spaces>public function getAll()\n<spaces>{\n";

        $content .= "\n<spaces><spaces>return array(\n";

        foreach ($this->classes as $groupname => $group) {

            $content .= "<spaces><spaces><spaces>'$groupname' => \$this['$groupname'],\n";
        }

        $content .= "\n<spaces><spaces>);\n";

        $content .= "\n<spaces>}\n";

        $content .= "\n<spaces>public function getLookupTable()\n<spaces>{\n";

        $content .= "\n<spaces><spaces>return array(\n";

        foreach ($this->classes as $groupname => $group) {

            $content .= "<spaces><spaces><spaces>'" . strtolower($groupname) . "' => array(\n";

            foreach ($group as $tagname => $tagclass) {
                $content .= "<spaces><spaces><spaces><spaces>'" . str_replace(array('\'', '\\'),array('\\\'','\\\\'),strtolower($tagname)) . "' => array(\n";
                $content .= "<spaces><spaces><spaces><spaces><spaces>'namespace' => '$groupname',\n";
                $content .= "<spaces><spaces><spaces><spaces><spaces>'tagname' => '$tagname',\n";
                $content .= "<spaces><spaces><spaces><spaces><spaces>'classname' => '".str_replace('\'','\\\'',$tagclass)."',\n";
                $content .= "<spaces><spaces><spaces><spaces>),\n";
            }

            $content .= "\n<spaces><spaces><spaces>),\n";
        }

        $content .= "\n<spaces><spaces>);\n";

        $content .= "\n<spaces>}\n";

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

    public function setClasses(Array $classes)
    {
        $this->classes = $classes;
    }
}
