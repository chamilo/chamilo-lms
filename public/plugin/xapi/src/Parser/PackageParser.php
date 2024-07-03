<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Exception;

/**
 * Class PackageParser.
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
     */
    protected function __construct($filePath, Course $course, ?Session $session = null)
    {
        $this->filePath = $filePath;
        $this->course = $course;
        $this->session = $session;
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public static function create(string $packageType, string $filePath, Course $course, ?Session $session = null)
    {
        switch ($packageType) {
            case 'tincan':
                return new TinCanParser($filePath, $course, $session);

            case 'cmi5':
                return new Cmi5Parser($filePath, $course, $session);

            default:
                throw new Exception('Invalid package.');
        }
    }

    abstract public function parse(): ToolLaunch;
}
