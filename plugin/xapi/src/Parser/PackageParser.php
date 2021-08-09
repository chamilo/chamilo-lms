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
     * @param $filePath
     */
    protected function __construct($filePath, Course $course, Session $session = null)
    {
        $this->filePath = $filePath;
        $this->course = $course;
        $this->session = $session;
    }

    /**
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

    abstract public function parse(): \Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
}
