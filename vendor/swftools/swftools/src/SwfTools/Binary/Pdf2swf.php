<?php

/*
 * This file is part of PHP-SwfTools.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwfTools\Binary;

use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Psr\Log\LoggerInterface;
use SwfTools\Exception\InvalidArgumentException;
use SwfTools\Exception\RuntimeException;

/**
 * The Pdf2Swf adapter
 */
class Pdf2swf extends Binary
{
    const CONVERT_POLY2BITMAP = 'poly2bitmap';
    const CONVERT_BITMAP = 'bitmap';
    const OPTION_LINKS_DISABLE = 'disablelinks';
    const OPTION_LINKS_OPENNEWWINDOW = 'linksopennewwindow';
    const OPTION_ZLIB_ENABLE = 'enablezlib';
    const OPTION_ZLIB_DISABLE = 'disablezlib';
    const OPTION_ENABLE_SIMPLEVIEWER = 'simpleviewer';
    const OPTION_DISABLE_SIMPLEVIEWER = 'nosimpleviewer';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Pdf2swf';
    }

    /**
     * Transcode a PDF to SWF
     *
     * @param string  $pathfile
     * @param string  $outputFile
     * @param array   $options
     * @param string  $convertType
     * @param integer $resolution
     * @param integer $pageRange
     * @param integer $frameRate
     * @param integer $jpegquality
     * @param integer $timelimit   The time limit for the process (deprecated)
     *
     * @return Pdf2swf
     *
     * @throws InvalidArgumentException
     */

	public function toSwf($pathfile, $outputFile, Array $options = array(), $convertType = self::CONVERT_POLY2BITMAP, $resolution = 72, $pageRange = '1-', $frameRate = 15, 
$jpegquality = 75, $timelimit = null)
	{
	    if (!file_exists($pathfile) || !is_file($pathfile) || !is_readable($pathfile)) {
	        throw new InvalidArgumentException('Provided file does not exists or is not readable.');
	    }
	
	    if (!trim($outputFile)) {
	        throw new InvalidArgumentException('Invalid resolution argument');
	    }
	
	    if ((int) $resolution < 1) {
	        throw new InvalidArgumentException('Invalid resolution argument');
	    }
	
	    if ((int) $frameRate < 1) {
	        throw new InvalidArgumentException('Invalid framerate argument');
	    }
	
	    if ((int) $jpegquality < 0 || (int) $jpegquality > 100) {
	        throw new InvalidArgumentException('Invalid jpegquality argument');
	    }
	
	    if (!preg_match('/\d+-\d?/', $pageRange)) {
	        throw new InvalidArgumentException('Invalid pages argument');
	    }
	
	    $args = array();
	
	    $args[] = $pathfile;
	    
	    switch ($convertType) {
	        case self::CONVERT_POLY2BITMAP:
	        case self::CONVERT_BITMAP:
	        $args_s [] = $convertType;
	            break;
	    }
	
	    $args_s [] = 'zoom=' . (int) $resolution;
	    $args_s [] = 'framerate=' . (int) $frameRate;
	    $args_s [] = 'jpegquality=' . (int) $jpegquality;
	    $args_s [] = 'pages=' . $pageRange;
	
	    if (!in_array(self::OPTION_ZLIB_DISABLE, $options) && in_array(self::OPTION_ZLIB_ENABLE, $options)) {
	        $args_s [] = 'enablezlib';
	    }
	    if (!in_array(self::OPTION_DISABLE_SIMPLEVIEWER, $options) && in_array(self::OPTION_ENABLE_SIMPLEVIEWER, $options)) {
	        $args_s [] = 'simpleviewer';
	    }
	    if (in_array(self::OPTION_LINKS_DISABLE, $options)) {
	        $args_s [] = 'disablelinks';
	    }
	    if (in_array(self::OPTION_LINKS_OPENNEWWINDOW, $options)) {
	        $args_s [] = 'linksopennewwindow';
	    }

            foreach($args_s as $a){
                $args[]='-s';
                $args[]=$a;
            }
	
	    $args[] = '-o';
	    $args[] = $outputFile;
	    $args[] = '-T';
	    $args[] = '-9';
	    $args[] = '-f';
	
	    if (null !== $timelimit) {
	        trigger_error('Use Configuration timeout instead of Pdf2Swf timelimit', E_USER_DEPRECATED);
	    }
	
	    try {
	        $this->command($args);
	    } catch (ExecutionFailureException $e) {
	        throw new RuntimeException(sprintf(
	            '%s failed to run command', $this->getName()
	        ), $e->getCode(), $e);
	    }
	
	    return $this;
	}

    /**
     * Creates the Pdf2swf binary driver
     *
     * @param array|ConfigurationInterface $conf
     * @param LoggerInterface              $logger
     *
     * @return Pdf2swf
     *
     * @throws ExecutableNotFound In case the executable is not found
     */
    public static function create($conf = array(), LoggerInterface $logger = null)
    {
        if (!$conf instanceof ConfigurationInterface) {
            $conf = new Configuration($conf);
        }

        $binaries = $conf->get('pdf2swf.binaries', array('pdf2swf'));

        return static::load($binaries, $logger, $conf);
    }
}
