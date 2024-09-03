<?php
/* For licensing terms, see /license.txt */

abstract class CcHelpers
{
    /**
     * Checks extension of the supplied filename.
     *
     * @param string $filename
     */
    public static function isHtml($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, ['htm', 'html']);
    }

    /**
     * Generates unique identifier.
     *
     * @param string $prefix
     * @param string $suffix
     *
     * @return string
     */
    public static function uuidgen($prefix = '', $suffix = '', $uppercase = true)
    {
        $uuid = trim(sprintf('%s%04x%04x%s', $prefix, mt_rand(0, 65535), mt_rand(0, 65535), $suffix));
        $result = $uppercase ? strtoupper($uuid) : strtolower($uuid);

        return $result;
    }

    /**
     * Creates new folder with random name.
     *
     * @param string $where
     * @param string $prefix
     * @param string $suffix
     *
     * @return mixed - directory short name or false in case of failure
     */
    public static function randomdir($where, $prefix = '', $suffix = '')
    {
        $permDirs = api_get_permissions_for_new_directories();

        $dirname = false;
        $randomname = self::uuidgen($prefix, $suffix, false);
        $newdirname = $where.DIRECTORY_SEPARATOR.$randomname;
        if (mkdir($newdirname)) {
            chmod($newdirname, $permDirs);
            $dirname = $randomname;
        }

        return $dirname;
    }

    public static function buildQuery($attributes, $search)
    {
        $result = '';
        foreach ($attributes as $attribute) {
            if ($result != '') {
                $result .= ' | ';
            }
            $result .= "//*[starts-with(@{$attribute},'{$search}')]/@{$attribute}";
        }

        return $result;
    }

    public static function processEmbeddedFiles(XMLGenericDocument &$doc, $attributes, $search, $customslash = null)
    {
        $result = [];
        $query = self::buildQuery($attributes, $search);
        $list = $doc->nodeList($query);
        foreach ($list as $filelink) {
            // Prepare the return value of just the filepath from within the course's document folder
            $rvalue = str_replace($search, '', $filelink->nodeValue);
            if (!empty($customslash)) {
                $rvalue = str_replace($customslash, '/', $rvalue);
            }
            $result[] = rawurldecode($rvalue);
        }

        return array_unique($result);
    }

    /**
     * Get list of embedded files.
     *
     * @return multitype:mixed
     */
    public static function embeddedFiles(string $html, string $courseDir = null)
    {
        $result = [];
        $doc = new XMLGenericDocument();
        $doc->doc->validateOnParse = false;
        $doc->doc->strictErrorChecking = false;
        if (!empty($html) && $doc->loadHTML($html)) {
            $attributes = ['src', 'href'];
            $result1 = [];
            if (!empty($courseDir) && is_dir(api_get_path(SYS_COURSE_PATH).$courseDir)) {
                // get a list of files within the course's "document" directory (only those... for now)
                $result1 = self::processEmbeddedFiles($doc, $attributes, '/courses/'.$courseDir.'/document/');
            }
            $result2 = [];
            //$result2 = self::processEmbeddedFiles($doc, $attributes, '/app/upload/users/');
            $result = array_merge($result1, $result2);
        }

        return $result;
    }

    /**
     * Return an array of static media dependencies found in a given document file or a document and its neighbours in the same folder.
     *
     * @param $packageroot
     * @param $contextid
     * @param $folder
     * @param $docfilepath
     *
     * @return array
     */
    public static function embeddedMapping($packageroot, $contextid = null, $folder = null, $docfilepath = null)
    {
        if (isset($folder)) {
            $files = array_diff(scandir($folder), ['.', '..']);
        } else {
            $folder = dirname($docfilepath);
            $files[] = basename($docfilepath);
        }
        $basePath = api_get_path(SYS_APP_PATH);

        $depfiles = [];
        foreach ($files as $file) {
            $mainfile = 1;
            $filename = $file;
            $filepath = DIRECTORY_SEPARATOR;
            $source = '';
            $author = '';
            $license = '';
            $hashedname = '';
            $hashpart = '';

            $location = $folder.DIRECTORY_SEPARATOR.$file;
            $type = mime_content_type($basePath.$location);

            $depfiles[$filepath.$filename] = [$location,
                                                    ($mainfile == 1),
                                                    strtolower(str_replace(' ', '_', $filename)),
                                                    $type,
                                                    $source,
                                                    $author,
                                                    $license,
                                                    strtolower(str_replace(' ', '_', $filepath)), ];
        }

        return $depfiles;
    }

    public static function addFiles(CcIManifest &$manifest, $packageroot, $outdir, $allinone = true, $folder = null, $docfilepath = null)
    {
        $permDirs = api_get_permissions_for_new_directories();

        $files = CcHelpers::embeddedMapping($packageroot, null, $folder, $docfilepath);
        $basePath = api_get_path(SYS_APP_PATH);

        $rdir = $allinone ? new CcResourceLocation($outdir) : null;
        foreach ($files as $virtual => $values) {
            $clean_filename = $values[2];
            if (!$allinone) {
                $rdir = new CcResourceLocation($outdir);
            }
            $rtp = $rdir->fullpath().$values[7].$clean_filename;
            //Are there any relative virtual directories?
            //let us try to recreate them
            $justdir = $rdir->fullpath(false).$values[7];
            if (!file_exists($justdir)) {
                if (!mkdir($justdir, $permDirs, true)) {
                    throw new RuntimeException('Unable to create directories!');
                }
            }

            $source = $values[0];
            if (is_dir($basePath.$source)) {
                continue;
            }

            if (!copy($basePath.$source, $rtp)) {
                throw new RuntimeException('Unable to copy files from '.$basePath.$source.' to '.$rtp.'!');
            }
            $resource = new CcResources($rdir->rootdir(),
                                        $values[7].$clean_filename,
                                        $rdir->dirname(false));

            $res = $manifest->addResource($resource, null, CcVersion13::WEBCONTENT);

            PkgStaticResources::instance()->add($virtual,
                                                  $res[0],
                                                  $rdir->dirname(false).$values[7].$clean_filename,
                                                  $values[1],
                                                  $resource);
        }

        PkgStaticResources::instance()->finished = true;
    }

    /**
     * Excerpt from IMS CC 1.1 overview :
     * No spaces in filenames, directory and file references should
     * employ all lowercase or all uppercase - no mixed case.
     *
     * @param string $packageroot
     * @param int    $contextid
     * @param string $outdir
     * @param bool   $allinone
     *
     * @throws RuntimeException
     */
    public static function handleStaticContent(CcIManifest &$manifest, $packageroot, $contextid, $outdir, $allinone = true, $folder = null)
    {
        self::addFiles($manifest, $packageroot, $outdir, $allinone, $folder);

        return PkgStaticResources::instance()->getValues();
    }

    public static function handleResourceContent(CcIManifest &$manifest, $packageroot, $contextid, $outdir, $allinone = true, $docfilepath = null)
    {
        $result = [];

        self::addFiles($manifest, $packageroot, $outdir, $allinone, null, $docfilepath);

        $files = self::embeddedMapping($packageroot, $contextid, null, $docfilepath);
        $rootnode = null;
        $rootvals = null;
        $depfiles = [];
        $depres = [];
        $flocation = null;
        foreach ($files as $virtual => $values) {
            $vals = PkgStaticResources::instance()->getIdentifier($virtual);
            $resource = $vals[3];
            $identifier = $resource->identifier;
            $flocation = $vals[1];
            if ($values[1]) {
                $rootnode = $resource;
                $rootvals = $flocation;
                continue;
            }
            $depres[] = $identifier;
            $depfiles[] = $vals[1];
            $result[$virtual] = [$identifier, $flocation, false];
        }

        if (!empty($rootnode)) {
            $rootnode->files = array_merge($rootnode->files, $depfiles);
            $result[$virtual] = [$rootnode->identifier, $rootvals, true];
        }

        return $result;
    }

    /**
     * Detect embedded files in the given HTML string.
     *
     * @param string      $content     The HTML string content
     * @param CcIManifest $manifest    Manifest object (usually empty at this point) that will be filled
     * @param             $packageroot
     * @param             $contextid
     * @param             $outdir
     * @param             $webcontent
     *
     * @return array
     */
    public static function processLinkedFiles(
        string $content,
        CcIManifest &$manifest,
        $packageroot,
        $contextid,
        $outdir,
        $webcontent = false
    ) {
        // Detect all embedded files
        // copy all files in the cc package stripping any spaces and using only lowercase letters
        // add those files as resources of the type webcontent to the manifest
        // replace the links to the resource using $1Edtech-CC-FILEBASE$ and their new locations
        // cc_resource has array of files and array of dependencies
        // most likely we would need to add all files as independent resources and than
        // attach them all as dependencies to the forum tag.
        $courseDir = $internalCourseDocumentsPath = null;
        $courseInfo = api_get_course_info();
        $replaceprefix = '$1EdTech-CC-FILEBASE$';
        $tokenSyntax = api_get_configuration_value('commoncartridge_path_token');
        if (!empty($tokenSyntax)) {
            $replaceprefix = $tokenSyntax;
        }
        if (!empty($courseInfo)) {
            $courseDir = $courseInfo['directory'];
            $internalCourseDocumentsPath = '/courses/'.$courseDir.'/document';
        }
        $lfiles = self::embeddedFiles($content, $courseDir);
        $text = $content;
        $deps = [];
        if (!empty($lfiles)) {
            foreach ($lfiles as $lfile) {
                $lfile = DIRECTORY_SEPARATOR.$lfile; // results of handleResourceContent() come prefixed by DIRECTORY_SEPARATOR
                $files = self::handleResourceContent(
                    $manifest,
                    $packageroot,
                    $contextid,
                    $outdir,
                    true,
                    $internalCourseDocumentsPath.$lfile
                );
                $bfile = DIRECTORY_SEPARATOR.basename($lfile);
                if (isset($files[$bfile])) {
                    $filename = str_replace('%2F', '/', rawurlencode($lfile));
                    $content = str_replace($internalCourseDocumentsPath.$filename,
                                           $replaceprefix.'../'.$files[$bfile][1],
                                           $content);
                    $deps[] = $files[$bfile][0];
                }
            }
            $text = $content;
        }

        return [$text, $deps];
    }
}
