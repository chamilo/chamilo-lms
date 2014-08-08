<?php

namespace ChamiloLMS\InstallerBundle;

use Symfony\Component\HttpKernel\Kernel;

class ScriptManager
{
    const CHAMILO_INSTALLER_SCRIPT_FILE_NAME = 'install.php';

    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * @var array
     *      key   -> script md5 key
     *      value -> array
     *                  'index' - script index (used for execute scripts in properly order)
     *                  'key'   - script file md5 key
     *                  'file'  - script file name
     *                  'label' - script label
     */
    protected $scripts = null;

    /**
     * Constructor
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * get array with scripts keys and labels
     *
     * @return array
     *      key   -> script file md5 key
     *      value -> script label
     */
    public function getScriptLabels()
    {
        $this->ensureScriptsLoaded();

        $result = [];
        if (!empty($this->scripts)) {
            foreach ($this->scripts as $script) {
                $result[$script['key']] = $script['label'];
            }
        }

        return $result;
    }

    /**
     * Get list of scripts
     *
     * @return array
     *      key   -> script md5 key
     *      value -> script file name
     */
    public function getScriptFiles()
    {
        $this->ensureScriptsLoaded();

        $result = [];
        if (!empty($this->scripts)) {
            foreach ($this->scripts as $script) {
                $result[$script['key']] = $script['file'];
            }
        }

        return $result;
    }

    /**
     * Get script file name array by script md5 key
     *
     * @param string $scriptKey Script md5 key
     * @return string|bool
     */
    public function getScriptFileByKey($scriptKey)
    {
        $this->ensureScriptsLoaded();

        if (!empty($this->scripts) && isset($this->scripts[$scriptKey])) {
            return $this->scripts[$scriptKey]['file'];
        }

        return false;
    }

    /**
     * Checks if scripts were loaded and load they if needed
     */
    protected function ensureScriptsLoaded()
    {
        if ($this->scripts === null) {
            $this->loadScripts();
        }
    }

    /**
     * Read scripts info from files
     */
    protected function loadScripts()
    {
        $index         = 0;
        $scripts       = [];
        $this->scripts = [];
        $rootDir       = realpath(
            $this->kernel->getRootDir() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
        );
        $bundles       = $this->kernel->getBundles();
        foreach ($bundles as $bundle) {
            $bundleDirName = $bundle->getPath();
            $this->getScriptInfo($bundleDirName, $index, $scripts);

            $relativePathArray = explode(DIRECTORY_SEPARATOR, str_replace($rootDir, '', $bundleDirName));
            if ($relativePathArray[0] == '') {
                unset ($relativePathArray[0]);
            }
            for ($i = count($relativePathArray); $i >= 0; $i--) {
                unset($relativePathArray[$i]);
                $checkPath = $rootDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $relativePathArray);
                if ($this->getScriptInfo($checkPath, $index, $scripts)) {
                    break;
                }
            }
        }
        if (!empty($scripts)) {
            usort($scripts, array($this, "compareScripts"));
            foreach ($scripts as $script) {
                $this->scripts[$script['key']] = $script;
            }
        }
    }

    /**
     * Get script info from dir
     *
     * @param string $dirName
     * @param int    $index
     * @param array  $scripts
     * @return array|bool
     */
    protected function getScriptInfo($dirName, &$index, &$scripts)
    {
        $file = $dirName . DIRECTORY_SEPARATOR . self::CHAMILO_INSTALLER_SCRIPT_FILE_NAME;
        if (is_file($file) && !isset($scripts[md5($file)])) {
            $data = $this->getScriptInfoFromFile($file);
            if ($data) {
                $data['index'] = $index;
                $index++;
                $scripts[$data['file']] = $data;

                return $data;
            }
        }

        return false;
    }

    /**
     * Compare two scripts for sorting
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function compareScripts($a, $b)
    {
        $pathA = dirname($a['file']) . DIRECTORY_SEPARATOR;
        $pathB = dirname($b['file']) . DIRECTORY_SEPARATOR;

        if (strpos($pathA, $pathB) === 0) {
            return -1;
        } elseif (strpos($pathB, $pathA) === 0) {
            return 1;
        }

        return $a['index'] < $b['index'] ? -1 : 1;
    }

    /**
     * Get info about script file
     *
     * @param string $fileName
     * @return array|bool
     */
    protected function getScriptInfoFromFile($fileName)
    {
        $tokens = [];
        if (preg_match(
            '/@' . ScriptExecutor::CHAMILO_SCRIPT_ANNOTATION. '\("([\w -]+)"\)/i',
            file_get_contents($fileName),
            $tokens
        )
        ) {
            if (isset($tokens[1])) {
                return [
                    'key'   => md5($fileName),
                    'file'  => $fileName,
                    'label' => str_replace('"', '', $tokens[1])
                ];
            }
        }

        return false;
    }
}
