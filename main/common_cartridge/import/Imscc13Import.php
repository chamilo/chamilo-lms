<?php
/* For licensing terms, see /license.txt */

class Imscc13Import
{

    const FORMAT_IMSCC13 = 'imscc13';

    public function log($message, $level, $a = null, $depth = null, $display = false)
    {
        error_log("(imscc1) $message , level : $level , extra info: $a, message depth : $depth");
    }


    public static function detectFormat($filepath)
    {

        $manifest = Cc1p3Convert::getManifest($filepath);

        if (file_exists($manifest)) {
            // Looks promising, lets load some information.
            $handle = fopen($manifest, 'r');
            $xml_snippet = fread($handle, 1024);
            fclose($handle);

            // Check if it has the required strings.
            $xml_snippet = strtolower($xml_snippet);
            $xml_snippet = preg_replace('/\s*/m', '', $xml_snippet);
            $xml_snippet = str_replace("'", '', $xml_snippet);
            $xml_snippet = str_replace('"', '', $xml_snippet);

            $search_string = "xmlns=http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1";
            if (strpos($xml_snippet, $search_string) !== false) {
                return self::FORMAT_IMSCC13;
            }
        }

        return null;
    }

    public function execute($filepath)
    {

        $manifest = Cc1p3Convert::getManifest($filepath);

        if (empty($manifest)) {
            throw new RuntimeException('No Manifest detected!');
        }

        $validator = new ManifestValidator('schemas13');

        if (!$validator->validate($manifest)) {
            throw new RuntimeException('validation error(s): '.PHP_EOL.ErrorMessages::instance());
        }

        $cc113Convert = new Cc1p3Convert($manifest);

        if ($cc113Convert->is_auth()) {
            throw new RuntimeException('protected_cc_not_supported');
        }

        $cc113Convert->generateImportData();
    }

    /**
    * Unzip a file into the specified directory. Throws a RuntimeException
    * if the extraction failed.
    */
   public static function unzip($file, $to = 'cache/zip')
   {
       @ini_set('memory_limit', '256M');
       if (!is_dir($to)) {
           mkdir($to);
           chmod($to, 0777);
       }
       if (class_exists('ZipArchive')) {
           // use ZipArchive
           $zip = new ZipArchive();
           $res = $zip->open($file);
           if ($res === true) {
               $zip->extractTo($to);
               $zip->close();
           } else {
               throw new RuntimeException('Could not open zip file [ZipArchive].');
           }
       } else {
           // use PclZip
           $zip = new PclZip($file);
           if ($zip->extract(PCLZIP_OPT_PATH, $to) === 0) {
               throw new RuntimeException('Could not extract zip file [PclZip].');
           }
       }
       return true;
   }

}
