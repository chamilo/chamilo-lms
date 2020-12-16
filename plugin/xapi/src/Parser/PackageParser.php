<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * Class PackageParser.
 *
 * @package Chamilo\PluginBundle\XApi\Parser
 */
abstract class PackageParser
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
     * @param string                                  $packageType
     * @param string                                  $filePath
     * @param \Chamilo\CoreBundle\Entity\Course       $course
     * @param \Chamilo\CoreBundle\Entity\Session|null $session
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public static function create(string $packageType, string $filePath, Course $course, Session $session = null)
    {
        switch ($packageType) {
            case 'tincan':
                return new TinCanParser($filePath, $course, $session);
            case 'cmi5':
                return new Cmi5Parser($filePath, $course, $session);
            default:
                throw new \Exception('Invalid package.');
        }
    }

    /**
     * @return \Chamilo\PluginBundle\Entity\XApi\ToolLaunch
     */
    abstract public function parse(): \Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
}
