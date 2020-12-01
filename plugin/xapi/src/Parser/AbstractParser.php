<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * Class AbstractParser.
 *
 * @package Chamilo\PluginBundle\XApi\Parser
 */
abstract class AbstractParser
{
    /**
     * @var string
     */
    protected $filePath;
    /**
     * @var Course
     */
    protected $course;
    /**
     * @var Session|null
     */
    protected $session;

    /**
     * AbstractParser constructor.
     *
     * @param $filePath
     */
    protected function __construct($filePath, Course $course, Session $session = null)
    {
        $this->filePath = $filePath;
        $this->course = $course;
        $this->session = $session;
    }

    /**
     * @param string $filePath
     *
     * @return mixed
     */
    abstract public static function create($filePath, Course $course, Session $session = null);

    /**
     * @return \Chamilo\PluginBundle\Entity\XApi\ToolLaunch
     */
    abstract public function parse();
}
