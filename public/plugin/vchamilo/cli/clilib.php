<?php
exit;

/**
 * Opens and parses/checks a VChamilo instance definition file
 * @param string $nodelistlocation
 * @param string $plugin
 * @return mixed
 */
function vchamilo_parse_csv_nodelist($nodelistlocation = '', $plugin = null) {
    global $_configuration;

    $vnodes = array();

    if (empty($nodelistlocation)) {
        $nodelistlocation = $_configuration['root_sys'].'/plugin/vchamilo/nodelist.csv';
    }

    // decode file
    $csv_delimiter = "\;";
    $csv_delimiter2 = ";";

    // make arrays of valid fields for error checking
    $required = array(
        'root_web' => 1,
        'sitename' => 1,
        'institution' => 1,
        'main_database' => 1,
        'statistics_database' => 1,
        'user_personal_database' => 1,
        'db_user' => 1,
        'db_password' => 1,
        'course_folder' => 1,
    );

    $optional = array(
        'db_host' => 1,
        'template' => 1,
        'table_prefix' => 1,
        'single_database' => 1,
        'tracking_enabled' => 1,
        'visible' => 1,
    );

    $optionalDefaults = array(
        'db_host' => $_configuration['db_host'],
        'db_prefix' => 'chm_',
        'table_prefix' => '',
        'tracking_enabled' => 0,
        'single_database' => 1,
        'template' => '',
        'visible' => 1
    );

    $patterns = array();

    // Metas are accepted patterns (optional)
    $metas = array(
        'plugin_.*',
        'config_.*'
    );

    // Get header (field names)

    $textlib = new textlib();

    if (!$fp = fopen($nodelistlocation, 'rb')) {
        cli_error($plugin->get_lang('badnodefile', 'vchamilo', $nodelistlocation));
    }

    // Jump any empty or comment line
    $text = fgets($fp, 1024);
    $i = 0;
    while (vchamilo_is_empty_line_or_format($text, $i == 0)) {
        $text = fgets($fp, 1024);
        $i++;
    }

    $headers = explode($csv_delimiter2, $text);

    // Check for valid field names
    foreach ($headers as $h) {
        $header[] = trim($h);
        $patternized = implode('|', $patterns)."\\d+";
        $metapattern = implode('|', $metas);
        if (!(isset($required[$h]) ||
                isset($optionalDefaults[$h]) ||
                    isset($optional[$h]) ||
                        preg_match("/$patternized/", $h) ||
                            preg_match("/$metapattern/", $h))) {
            cli_error("Node parse : invalidfieldname $h ");
            return;
        }

        if (isset($required[trim($h)])) {
            $required[trim($h)] = 0;
        }
    }

    $expectedcols = count($headers);
    $i++;

    // Check for required fields.
    foreach ($required as $key => $value) {
        if ($value) { // Required field missing.
            cli_error("fieldrequired $key");
            return;
        }
    }
    $linenum = 2; // Since header is line 1.

    // Take some from admin profile, other fixed by hardcoded defaults.
    while (!feof($fp)) {

        // Make a new base record.
        $vnode = new StdClass();
        foreach ($optionalDefaults as $key => $value) {
            $vnode->$key = $value;
        }

        //Note: commas within a field should be encoded as &#44 (for comma separated csv files)
        //Note: semicolon within a field should be encoded as &#59 (for semicolon separated csv files)
        $text = fgets($fp, 1024);
        if (vchamilo_is_empty_line_or_format($text, false)) {
            $i++;
            continue;
        }

        $valueset = explode($csv_delimiter2, $text);
        if (count($valueset) != $expectedcols) {
            cli_error('wrong line count at line '.$i);
        }
        $f = 0;
        foreach ($valueset as $value) {
            // Decode encoded commas.
            $key = $headers[$f];
            if (preg_match('/\|/', $key)) {
                list($plugin, $variable) = explode('|', str_replace('plugin_', '', $key));
                if (empty($variable)) die("Key error in CSV : $key ");
                if (!isset($vnode->$plugin)) {
                    $vnode->$plugin = new StdClass();
                }
                $vnode->$plugin->$variable = trim($value);
            } else {
                if (preg_match('/^config_/', $key)) {
                    $smartkey = str_replace('config_', '', $key);
                    $keyparts = implode('|', $smartkey);
                    $keyvar = $keyparts[0];
                    $subkey = @$keyparts[1];
                    $vnode->config->$smartkey = new StdClass;
                    $vnode->config->$smartkey->subkey = $subkey;
                    $vnode->config->$smartkey->value = trim($value);
                } else {
                    $vnode->$key = trim($value);
                }
            }
            $f++;
        }
        $vnodes[] = $vnode;
    }

    return $vnodes;
}

/**
 * Check a CSV input line format for empty or commented lines
 * Ensures compatbility to UTF-8 BOM or unBOM formats
 * @param resource $text
 * @param bool $resetfirst
 * @return bool
 */
function vchamilo_is_empty_line_or_format(&$text, $resetfirst = false) {
    global $CFG;

    static $textlib;
    static $first = true;

    // We may have a risk the BOM is present on first line
    if ($resetfirst) $first = true;
    if (!isset($textlib)) $textlib = new textlib(); // Singleton
    $text = $textlib->trim_utf8_bom($text);
    $first = false;

    $text = preg_replace("/\n?\r?/", '', $text);

    // last chance
    if ('ASCII' == mb_detect_encoding($text)) {
        $text = utf8_encode($text);
    }

    // Check the text is empty or comment line and answer true if it is.
    return preg_match('/^$/', $text) || preg_match('/^(\(|\[|-|#|\/| )/', $text);
}

/**
 * Get input from user
 * @param string $prompt text prompt, should include possible options
 * @param string $default default value when enter pressed
 * @param array $options list of allowed options, empty means any text
 * @param bool $casesensitiveoptions true if options are case sensitive
 * @return string entered text
 */
function cli_input($prompt, $default = '', array $options = null, $casesensitiveoptions = false) {
    echo $prompt;
    echo "\n: ";
    $input = fread(STDIN, 2048);
    $input = trim($input);
    if ($input === '') {
        $input = $default;
    }
    if ($options) {
        if (!$casesensitiveoptions) {
            $input = strtolower($input);
        }
        if (!in_array($input, $options)) {
            echo "Incorrect value, please retry.\n"; // TODO: localize, mark as needed in install
            return cli_input($prompt, $default, $options, $casesensitiveoptions);
        }
    }
    return $input;
}

/**
 * Returns cli script parameters.
 * @param array $longoptions array of --style options ex:('verbose'=>false)
 * @param array $shortmapping array describing mapping of short to long style options ex:('h'=>'help', 'v'=>'verbose')
 * @return array array of arrays, options, unrecognised as optionlongname=>value
 */
function cli_get_params(array $longoptions, array $shortmapping = null) {
    $shortmapping = (array) $shortmapping;
    $options      = array();
    $unrecognized = array();

    if (empty($_SERVER['argv'])) {
        // Bad luck, we can continue in interactive mode ;-)
        return array($options, $unrecognized);
    }
    $rawoptions = $_SERVER['argv'];

    // Remove anything after '--', options can not be there.
    if (($key = array_search('--', $rawoptions)) !== false) {
        $rawoptions = array_slice($rawoptions, 0, $key);
    }

    // Remove script.
    unset($rawoptions[0]);
    foreach ($rawoptions as $raw) {
        if (substr($raw, 0, 2) === '--') {
            $value = substr($raw, 2);
            $parts = explode('=', $value);
            if (count($parts) == 1) {
                $key   = reset($parts);
                $value = true;
            } else {
                $key = array_shift($parts);
                $value = implode('=', $parts);
            }
            if (array_key_exists($key, $longoptions)) {
                $options[$key] = $value;
            } else {
                $unrecognized[] = $raw;
            }

        } else if (substr($raw, 0, 1) === '-') {
            $value = substr($raw, 1);
            $parts = explode('=', $value);
            if (count($parts) == 1) {
                $key   = reset($parts);
                $value = true;
            } else {
                $key = array_shift($parts);
                $value = implode('=', $parts);
            }
            if (array_key_exists($key, $shortmapping)) {
                $options[$shortmapping[$key]] = $value;
            } else {
                $unrecognized[] = $raw;
            }
        } else {
            $unrecognized[] = $raw;
            continue;
        }
    }
    // Apply defaults.
    foreach ($longoptions as $key=>$default) {
        if (!array_key_exists($key, $options)) {
            $options[$key] = $default;
        }
    }
    // Finished.
    return array($options, $unrecognized);
}

/**
 * Print or return section separator string
 * @param bool $return false means print, true return as string
 * @return mixed void or string
 */
function cli_separator($return = false) {
    $separator = str_repeat('-', 79)."\n";
    if ($return) {
        return $separator;
    } else {
        echo $separator;
    }
}

/**
 * Print or return section heading string
 * @param string $string text
 * @param bool $return false means print, true return as string
 * @return mixed void or string
 */
function cli_heading($string, $return = false) {
    $string = "== $string ==\n";
    if ($return) {
        return $string;
    } else {
        echo $string;
    }
}

/**
 * Write error notification
 * @param $text
 * @return void
 */
function cli_problem($text) {
    fwrite(STDERR, $text."\n");
}

/**
 * Write to standard out and error with exit in error.
 *
 * @param string $text
 * @param int $errorCode
 * @return void (does not return)
 */
function cli_error($text, $errorCode = 1) {
    fwrite(STDERR, $text);
    fwrite(STDERR, "\n");
    die($errorCode);
}
