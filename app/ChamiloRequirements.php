<?php
/* For licensing terms, see /license.txt */

require_once __DIR__ . '/SymfonyRequirements.php';

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Intl\Intl;

use Chamilo\InstallerBundle\Process\PhpExecutableFinder;

/**
 * This class specifies all requirements and optional recommendations
 * that are necessary to run the Chamilo Application.
 */
class ChamiloRequirements extends SymfonyRequirements
{
    const REQUIRED_PHP_VERSION  = '5.3.3';
    const REQUIRED_GD_VERSION   = '2.0';
    const REQUIRED_CURL_VERSION = '7.0';
    const REQUIRED_ICU_VERSION  = '3.8';

    const EXCLUDE_REQUIREMENTS_MASK = '/5\.3\.(3|4|8|16)|5\.4\.0/';

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct();

        $phpVersion  = phpversion();
        $gdVersion   = defined('GD_VERSION') ? GD_VERSION : null;
        $curlVersion = function_exists('curl_version') ? curl_version() : null;
        $icuVersion  = Intl::getIcuVersion();

        $this->addChamiloRequirement(
            version_compare($phpVersion, self::REQUIRED_PHP_VERSION, '>='),
            sprintf('PHP version must be at least %s (%s installed)', self::REQUIRED_PHP_VERSION, $phpVersion),
            sprintf(
                'You are running PHP version "<strong>%s</strong>", but Chamilo needs at least PHP "<strong>%s</strong>" to run.' .
                'Before using Chamilo, upgrade your PHP installation, preferably to the latest version.',
                $phpVersion,
                self::REQUIRED_PHP_VERSION
            ),
            sprintf('Install PHP %s or newer (installed version is %s)', self::REQUIRED_PHP_VERSION, $phpVersion)
        );

        $this->addChamiloRequirement(
            null !== $gdVersion && version_compare($gdVersion, self::REQUIRED_GD_VERSION, '>='),
            'GD extension must be at least ' . self::REQUIRED_GD_VERSION,
            'Install and enable the <strong>GD</strong> extension at least ' . self::REQUIRED_GD_VERSION . ' version'
        );

        $this->addChamiloRequirement(
            null !== $curlVersion && version_compare($curlVersion['version'], self::REQUIRED_CURL_VERSION, '>='),
            'cURL extension must be at least ' . self::REQUIRED_CURL_VERSION,
            'Install and enable the <strong>cURL</strong> extension at least ' . self::REQUIRED_CURL_VERSION . ' version'
        );

        $this->addChamiloRequirement(
            function_exists('mb_strlen'),
            'mb_strlen() should be available',
            'Install and enable the <strong>mbstring</strong> extension.'
        );

        $this->addChamiloRequirement(
            function_exists('mcrypt_encrypt'),
            'mcrypt_encrypt() should be available',
            'Install and enable the <strong>Mcrypt</strong> extension.'
        );

        $this->addChamiloRequirement(
            class_exists('Locale'),
            'intl extension should be available',
            'Install and enable the <strong>intl</strong> extension.'
        );

        $this->addChamiloRequirement(
            null !== $icuVersion && version_compare($icuVersion, self::REQUIRED_ICU_VERSION, '>='),
            'icu library must be at least ' . self::REQUIRED_ICU_VERSION,
            'Install and enable the <strong>icu</strong> library at least ' . self::REQUIRED_ICU_VERSION . ' version'
        );

        $extensions = $this->getExtensions();
        foreach ($extensions as $type) {
            $isOptional = $type == 'optional' ? true : false;
            foreach ($type as $extension => $url) {
                if (extension_loaded($extension)) {
                    $this->addChamiloRequirement(
                        extension_loaded($extension),
                        "$extension extension should be available",
                        "Install and enable the <strong>$extension</strong>
                        extension.",
                        $isOptional
                    );
                }
            }
        }

        $this->addRecommendation(
            class_exists('SoapClient'),
            'SOAP extension should be installed (API calls)',
            'Install and enable the <strong>SOAP</strong> extension.'
        );

        // Windows specific checks
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addRecommendation(
                function_exists('finfo_open'),
                'finfo_open() should be available',
                'Install and enable the <strong>Fileinfo</strong> extension.'
            );

            $this->addRecommendation(
                class_exists('COM'),
                'COM extension should be installed',
                'Install and enable the <strong>COM</strong> extension.'
            );
        }

        // Unix specific checks
        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addRequirement(
                $this->checkFileNameLength(),
                'Cache folder should not be inside encrypted directory',
                'Move <strong>app/cache</strong> folder outside encrypted directory.'
            );
        }

        // Web installer specific checks
        if ('cli' !== PHP_SAPI) {
            $output = $this->checkCliRequirements();

            $requirement = new CliRequirement(
                !$output,
                'Requirements validation for PHP CLI',
                'If you have multiple PHP versions installed, you need to configure CHAMILO_PHP_PATH variable with PHP binary path used by web server'
            );

            $requirement->setOutput($output);

            $this->add($requirement);
        }

        $baseDir = realpath(__DIR__ . '/..');
        $mem     = $this->getBytes(ini_get('memory_limit'));

        $this->addPhpIniRequirement(
            'memory_limit',
            function ($cfgValue) use ($mem) {
                return $mem >= 256 * 1024 * 1024 || -1 == $mem;
            },
            false,
            'memory_limit should be at least 256M',
            'Set the "<strong>memory_limit</strong>" setting in php.ini<a href="#phpini">*</a> to at least "256M".'
        );

        /*$this->addRecommendation(
            $this->checkNodeExists(),
            'NodeJS should be installed',
            'Install the <strong>NodeJS</strong>.'
        );*/

        $this->addChamiloRequirement(
            is_writable($baseDir . '/web/uploads'),
            'web/uploads/ directory must be writable',
            'Change the permissions of the "<strong>web/uploads/</strong>" directory so that the web server can write into it.'
        );

        $this->addChamiloRequirement(
            is_writable($baseDir . '/web/bundles'),
            'web/bundles/ directory must be writable',
            'Change the permissions of the "<strong>web/bundles/</strong>" directory so that the web server can write into it.'
        );

        /*$this->addChamiloRequirement(
            is_writable($baseDir . '/web/media'),
            'web/media/ directory must be writable',
            'Change the permissions of the "<strong>web/media/</strong>" directory so that the web server can write into it.'
        );



        /*$this->addChamiloRequirement(
            is_writable($baseDir . '/app/attachment'),
            'app/attachment/ directory must be writable',
            'Change the permissions of the "<strong>app/attachment/</strong>" directory so that the web server can write into it.'
        );*/

        if (is_dir($baseDir . '/web/js')) {
            $this->addChamiloRequirement(
                is_writable($baseDir . '/web/js'),
                'web/js directory must be writable',
                'Change the permissions of the "<strong>web/js</strong>" directory so that the web server can write into it.'
            );
        }

        if (is_dir($baseDir . '/web/css')) {
            $this->addChamiloRequirement(
                is_writable($baseDir . '/web/css'),
                'web/css directory must be writable',
                'Change the permissions of the "<strong>web/css</strong>" directory so that the web server can write into it.'
            );
        }

        if (!is_dir($baseDir . '/web/css') || !is_dir($baseDir . '/web/js')) {
            $this->addChamiloRequirement(
                is_writable($baseDir . '/web'),
                'web directory must be writable',
                'Change the permissions of the "<strong>web</strong>" directory so that the web server can write into it.'
            );
        }

        if (is_file($baseDir . '/app/config/parameters.yml')) {
            $this->addChamiloRequirement(
                is_writable($baseDir . '/app/config/parameters.yml'),
                'app/config/parameters.yml file must be writable',
                'Change the permissions of the "<strong>app/config/parameters.yml</strong>" file so that the web server can write into it.'
            );
        }
    }

    private function getExtensions()
    {
        return
            array(
                'required' => array(
                    'mysql' => array('url' => 'http://php.net/manual/en/book.mysql.php'),
                    'curl' => array('url' => 'http://php.net/manual/fr/book.curl.php'),
                    'zlib' => array('url' => 'http://php.net/manual/en/book.zlib.php'),
                    'pcre' => array('url' => 'http://php.net/manual/en/book.pcre.php'),
                    'xml' => array('url' => 'http://php.net/manual/en/book.xml.php'),
                    'mbstring' => array('url' => 'http://php.net/manual/en/book.mbstring.php'),
                    'iconv' => array('url' => 'http://php.net/manual/en/book.iconv.php'),
                    'intl' => array('url' => 'http://php.net/manual/en/book.intl.php'),
                    'gd' => array('url' => 'http://php.net/manual/en/book.image.php'),
                    'json' => array('url' => 'http://php.net/manual/en/book.json.php')
                ),
                'optional' =>  array(
                    'imagick' => array('url' => 'http://php.net/manual/en/book.imagick.php'),
                    'ldap' => array('url' => 'http://php.net/manual/en/book.ldap.php'),
                    'xapian' => array('url' => 'http://php.net/manual/en/book.xapian.php')
                )
            );
    }

    /**
     * Adds an Chamilo specific requirement.
     *
     * @param boolean     $fulfilled Whether the requirement is fulfilled
     * @param string      $testMessage The message for testing the requirement
     * @param string      $helpHtml The help text formatted in HTML for resolving the problem
     * @param string|null $helpText The help text (when null, it will be inferred from $helpHtml, i.e. stripped from HTML tags)
     */
    public function addChamiloRequirement(
        $fulfilled,
        $testMessage,
        $helpHtml,
        $helpText = null,
        $optional = false
    ) {
        $this->add(new ChamiloRequirement($fulfilled, $testMessage, $helpHtml, $helpText, $optional));
    }

    /**
     * Get the list of mandatory requirements (all requirements excluding PhpIniRequirement)
     *
     * @return array
     */
    public function getMandatoryRequirements()
    {
        return array_filter(
            $this->getRequirements(),
            function ($requirement) {
                return !($requirement instanceof PhpIniRequirement)
                && !($requirement instanceof ChamiloRequirement)
                && !($requirement instanceof CliRequirement);
            }
        );
    }

    /**
     * Get the list of PHP ini requirements
     *
     * @return array
     */
    public function getPhpIniRequirements()
    {
        return array_filter(
            $this->getRequirements(),
            function ($requirement) {
                return $requirement instanceof PhpIniRequirement;
            }
        );
    }

    /**
     * Get the list of Chamilo specific requirements
     *
     * @return array
     */
    public function getChamiloRequirements()
    {
        return array_filter(
            $this->getRequirements(),
            function ($requirement) {
                return $requirement instanceof ChamiloRequirement;
            }
        );
    }

    /**
     * @return array
     */
    public function getCliRequirements()
    {
        return array_filter(
            $this->getRequirements(),
            function ($requirement) {
                return $requirement instanceof CliRequirement;
            }
        );
    }

    /**
     * @param  string $val
     * @return int
     */
    protected function getBytes($val)
    {
        if (empty($val)) {
            return 0;
        }

        preg_match('/([\-0-9]+)[\s]*([a-z]*)$/i', trim($val), $matches);

        if (isset($matches[1])) {
            $val = (int)$matches[1];
        }

        switch (strtolower($matches[2])) {
            case 'g':
            case 'gb':
                $val *= 1024;
            // no break
            case 'm':
            case 'mb':
                $val *= 1024;
            // no break
            case 'k':
            case 'kb':
                $val *= 1024;
            // no break
        }

        return (float)$val;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        $requirements = parent::getRequirements();

        foreach ($requirements as $key => $requirement) {
            $testMessage = $requirement->getTestMessage();
            if (preg_match_all(self::EXCLUDE_REQUIREMENTS_MASK, $testMessage, $matches)) {
                unset($requirements[$key]);
            }
        }

        return $requirements;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecommendations()
    {
        $recommendations = parent::getRecommendations();

        foreach ($recommendations as $key => $recommendation) {
            $testMessage = $recommendation->getTestMessage();
            if (preg_match_all(self::EXCLUDE_REQUIREMENTS_MASK, $testMessage, $matches)) {
                unset($recommendations[$key]);
            }
        }

        return $recommendations;
    }

    /**
     * @return bool
     */
    protected function checkNodeExists()
    {
        $nodeExists = new ProcessBuilder(array('node', '--version'));
        $nodeExists = $nodeExists->getProcess();

        if (isset($_SERVER['PATH'])) {
            $nodeExists->setEnv(array('PATH' => $_SERVER['PATH']));
        }
        $nodeExists->run();

        return $nodeExists->getErrorOutput() === null;
    }

    /**
     * @return bool
     */
    protected function checkFileNameLength()
    {
        $getConf = new ProcessBuilder(array('getconf', 'NAME_MAX', __DIR__));
        $getConf = $getConf->getProcess();

        if (isset($_SERVER['PATH'])) {
            $getConf->setEnv(array('PATH' => $_SERVER['PATH']));
        }
        $getConf->run();

        if ($getConf->getErrorOutput()) {
            // getconf not installed
            return true;
        }

        $fileLength = trim($getConf->getOutput());

        return $fileLength == 255;
    }

    /**
     * @return null|string
     */
    protected function checkCliRequirements()
    {
        $finder  = new PhpExecutableFinder();
        $command = sprintf(
            '%s %schamilo-check.php',
            $finder->find(),
            __DIR__ . DIRECTORY_SEPARATOR
        );

        return shell_exec($command);
    }
}

class ChamiloRequirement extends Requirement
{
}

class CliRequirement extends Requirement
{
    /**
     * @var string
     */
    protected $output;

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }
}
