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
     * @param                                         $filePath
     * @param \Chamilo\CoreBundle\Entity\Course       $course
     * @param \Chamilo\CoreBundle\Entity\Session|null $session
     */
    protected function __construct($filePath, Course $course, Session $session = null)
    {
        $this->filePath = $filePath;
        $this->course = $course;
        $this->session = $session;
    }

    /**
     * @param string                                  $filePath
     * @param \Chamilo\CoreBundle\Entity\Course       $course
     * @param \Chamilo\CoreBundle\Entity\Session|null $session
     *
     * @return mixed
     */
    abstract public static function create($filePath, Course $course, Session $session = null);

    /**
     * @return \Chamilo\PluginBundle\Entity\XApi\ToolLaunch
     */
    abstract public function parse();
}
