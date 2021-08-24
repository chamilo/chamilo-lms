<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/lib/validateurlsyntax.php under GNU/GPL license */

/**
 *  BEGINNING OF validateUrlSyntax() function.
 */
function validateUrlSyntax($urladdr, $options = "")
{
    // Force Options parameter to be lower case
    // DISABLED PERMAMENTLY - OK to remove from code
    //    $options = strtolower($options);

    // Check Options Parameter
    if (!preg_match('/^([sHSEFRuPaIpfqr][+?-])*$/', $options)) {
        trigger_error("Options attribute malformed", E_USER_ERROR);
    }

    // Set Options Array, set defaults if options are not specified
    // Scheme
    if (strpos($options, 's') === false) {
        $aOptions['s'] = '?';
    } else {
        $aOptions['s'] = substr($options, strpos($options, 's') + 1, 1);
    }
    // http://
    if (strpos($options, 'H') === false) {
        $aOptions['H'] = '?';
    } else {
        $aOptions['H'] = substr($options, strpos($options, 'H') + 1, 1);
    }
    // https:// (SSL)
    if (strpos($options, 'S') === false) {
        $aOptions['S'] = '?';
    } else {
        $aOptions['S'] = substr($options, strpos($options, 'S') + 1, 1);
    }
    // mailto: (email)
    if (strpos($options, 'E') === false) {
        $aOptions['E'] = '-';
    } else {
        $aOptions['E'] = substr($options, strpos($options, 'E') + 1, 1);
    }
    // ftp://
    if (strpos($options, 'F') === false) {
        $aOptions['F'] = '-';
    } else {
        $aOptions['F'] = substr($options, strpos($options, 'F') + 1, 1);
    }
    // rtmp://
    if (strpos($options, 'R') === false) {
        $aOptions['R'] = '-';
    } else {
        $aOptions['R'] = substr($options, strpos($options, 'R') + 1, 1);
    }
    // User section
    if (strpos($options, 'u') === false) {
        $aOptions['u'] = '?';
    } else {
        $aOptions['u'] = substr($options, strpos($options, 'u') + 1, 1);
    }
    // Password in user section
    if (strpos($options, 'P') === false) {
        $aOptions['P'] = '?';
    } else {
        $aOptions['P'] = substr($options, strpos($options, 'P') + 1, 1);
    }
    // Address Section
    if (strpos($options, 'a') === false) {
        $aOptions['a'] = '+';
    } else {
        $aOptions['a'] = substr($options, strpos($options, 'a') + 1, 1);
    }
    // IP Address in address section
    if (strpos($options, 'I') === false) {
        $aOptions['I'] = '?';
    } else {
        $aOptions['I'] = substr($options, strpos($options, 'I') + 1, 1);
    }
    // Port number
    if (strpos($options, 'p') === false) {
        $aOptions['p'] = '?';
    } else {
        $aOptions['p'] = substr($options, strpos($options, 'p') + 1, 1);
    }
    // File Path
    if (strpos($options, 'f') === false) {
        $aOptions['f'] = '?';
    } else {
        $aOptions['f'] = substr($options, strpos($options, 'f') + 1, 1);
    }
    // Query Section
    if (strpos($options, 'q') === false) {
        $aOptions['q'] = '?';
    } else {
        $aOptions['q'] = substr($options, strpos($options, 'q') + 1, 1);
    }
    // Fragment (Anchor)
    if (strpos($options, 'r') === false) {
        $aOptions['r'] = '?';
    } else {
        $aOptions['r'] = substr($options, strpos($options, 'r') + 1, 1);
    }

    // Loop through options array, to search for and replace "-" to "{0}" and "+" to ""
    foreach ($aOptions as $key => $value) {
        if ($value == '-') {
            $aOptions[$key] = '{0}';
        }
        if ($value == '+') {
            $aOptions[$key] = '';
        }
    }

    // DEBUGGING - Unescape following line to display to screen current option values
    // echo '<pre>'; print_r($aOptions); echo '</pre>';

    // Preset Allowed Characters
    $alphanum = '[a-zA-Z0-9]';  // Alpha Numeric
    $unreserved = '[a-zA-Z0-9_.!~*'.'\''.'()-]';
    $escaped = '(%[0-9a-fA-F]{2})'; // Escape sequence - In Hex - %6d would be a 'm'
    $reserved = '[;/?:@&=+$,]'; // Special characters in the URI

    // Beginning Regular Expression
    // Scheme - Allows for 'http://', 'https://', 'mailto:', 'ftp://' or 'rtmp://'
    $scheme = '(';
    if ($aOptions['H'] === '') {
        $scheme .= 'http://';
    } elseif ($aOptions['S'] === '') {
        $scheme .= 'https://';
    } elseif ($aOptions['E'] === '') {
        $scheme .= 'mailto:';
    } elseif ($aOptions['F'] === '') {
        $scheme .= 'ftp://';
    } elseif ($aOptions['R'] === '') {
        $scheme .= 'rtmp://';
    } else {
        if ($aOptions['H'] === '?') {
            $scheme .= '|(http://)';
        }
        if ($aOptions['S'] === '?') {
            $scheme .= '|(https://)';
        }
        if ($aOptions['E'] === '?') {
            $scheme .= '|(mailto:)';
        }
        if ($aOptions['F'] === '?') {
            $scheme .= '|(ftp://)';
        }
        if ($aOptions['R'] === '?') {
            $scheme .= '|(rtmp://)';
        }
        $scheme = str_replace('(|', '(', $scheme); // fix first pipe
    }
    $scheme .= ')'.$aOptions['s'];
    // End setting scheme

    // User Info - Allows for 'username@' or 'username:password@'. Note: contrary to rfc, I removed ':' from username section, allowing it only in password.
    //   /---------------- Username -----------------------\  /-------------------------------- Password ------------------------------\
    $userinfo = '(('.$unreserved.'|'.$escaped.'|[;&=+$,]'.')+(:('.$unreserved.'|'.$escaped.'|[;:&=+$,]'.')+)'.$aOptions['P'].'@)'.$aOptions['u'];

    // IP ADDRESS - Allows 0.0.0.0 to 255.255.255.255
    $ipaddress = '((((2(([0-4][0-9])|(5[0-5])))|([01]?[0-9]?[0-9]))\.){3}((2(([0-4][0-9])|(5[0-5])))|([01]?[0-9]?[0-9])))';

    // Tertiary Domain(s) - Optional - Multi - Although some sites may use other characters, the RFC says tertiary domains have the same naming restrictions as second level domains
    $domain_tertiary = '('.$alphanum.'(([a-zA-Z0-9-]{0,62})'.$alphanum.')?\.)*';
    $domain_toplevel = '([a-zA-Z](([a-zA-Z0-9-]*)[a-zA-Z0-9])?)';

    if ($aOptions['I'] === '{0}') {       // IP Address Not Allowed
        $address = '('.$domain_tertiary. /* MDL-9295 $domain_secondary . */ $domain_toplevel.')';
    } elseif ($aOptions['I'] === '') {  // IP Address Required
        $address = '('.$ipaddress.')';
    } else {                            // IP Address Optional
        $address = '(('.$ipaddress.')|('.$domain_tertiary. /* MDL-9295 $domain_secondary . */ $domain_toplevel.'))';
    }
    $address = $address.$aOptions['a'];

    // Port Number - :80 or :8080 or :65534 Allows range of :0 to :65535
    //    (0-59999)         |(60000-64999)   |(65000-65499)    |(65500-65529)  |(65530-65535)
    $port_number = '(:(([0-5]?[0-9]{1,4})|(6[0-4][0-9]{3})|(65[0-4][0-9]{2})|(655[0-2][0-9])|(6553[0-5])))'.$aOptions['p'];

    // Path - Can be as simple as '/' or have multiple folders and filenames
    $path = '(/((;)?('.$unreserved.'|'.$escaped.'|'.'[:@&=+$,]'.')+(/)?)*)'.$aOptions['f'];

    // Query Section - Accepts ?var1=value1&var2=value2 or ?2393,1221 and much more
    $querystring = '(\?('.$reserved.'|'.$unreserved.'|'.$escaped.')*)'.$aOptions['q'];

    // Fragment Section - Accepts anchors such as #top
    $fragment = '(\#('.$reserved.'|'.$unreserved.'|'.$escaped.')*)'.$aOptions['r'];

    // Building Regular Expression
    $regexp = '#^'.$scheme.$userinfo.$address.$port_number.$path.$querystring.$fragment.'$#i';

    // DEBUGGING - Uncomment Line Below To Display The Regular Expression Built
    // echo '<pre>' . htmlentities(wordwrap($regexp,70,"\n",1)) . '</pre>';

    // Running the regular expression
    if (preg_match($regexp, $urladdr)) {
        return true; // The domain passed
    } else {
        return false; // The domain didn't pass the expression
    }
} // END Function validateUrlSyntax()

/**
 * About ValidateEmailSyntax():
 * This function uses the ValidateUrlSyntax() function to easily check the
 * syntax of an email address. It accepts the same options as ValidateURLSyntax
 * but defaults them for email addresses.
 *
 *  Released under same license as validateUrlSyntax()
 */
function validateEmailSyntax($emailaddr, $options = "")
{
    // Check Options Parameter
    if (!preg_match('/^([sHSEFuPaIpfqr][+?-])*$/', $options)) {
        trigger_error("Options attribute malformed", E_USER_ERROR);
    }

    // Set Options Array, set defaults if options are not specified
    // Scheme
    if (strpos($options, 's') === false) {
        $aOptions['s'] = '-';
    } else {
        $aOptions['s'] = substr($options, strpos($options, 's') + 1, 1);
    }
    // http://
    if (strpos($options, 'H') === false) {
        $aOptions['H'] = '-';
    } else {
        $aOptions['H'] = substr($options, strpos($options, 'H') + 1, 1);
    }
    // https:// (SSL)
    if (strpos($options, 'S') === false) {
        $aOptions['S'] = '-';
    } else {
        $aOptions['S'] = substr($options, strpos($options, 'S') + 1, 1);
    }
    // mailto: (email)
    if (strpos($options, 'E') === false) {
        $aOptions['E'] = '?';
    } else {
        $aOptions['E'] = substr($options, strpos($options, 'E') + 1, 1);
    }
    // ftp://
    if (strpos($options, 'F') === false) {
        $aOptions['F'] = '-';
    } else {
        $aOptions['F'] = substr($options, strpos($options, 'F') + 1, 1);
    }
    // User section
    if (strpos($options, 'u') === false) {
        $aOptions['u'] = '+';
    } else {
        $aOptions['u'] = substr($options, strpos($options, 'u') + 1, 1);
    }
    // Password in user section
    if (strpos($options, 'P') === false) {
        $aOptions['P'] = '-';
    } else {
        $aOptions['P'] = substr($options, strpos($options, 'P') + 1, 1);
    }
    // Address Section
    if (strpos($options, 'a') === false) {
        $aOptions['a'] = '+';
    } else {
        $aOptions['a'] = substr($options, strpos($options, 'a') + 1, 1);
    }
    // IP Address in address section
    if (strpos($options, 'I') === false) {
        $aOptions['I'] = '-';
    } else {
        $aOptions['I'] = substr($options, strpos($options, 'I') + 1, 1);
    }
    // Port number
    if (strpos($options, 'p') === false) {
        $aOptions['p'] = '-';
    } else {
        $aOptions['p'] = substr($options, strpos($options, 'p') + 1, 1);
    }
    // File Path
    if (strpos($options, 'f') === false) {
        $aOptions['f'] = '-';
    } else {
        $aOptions['f'] = substr($options, strpos($options, 'f') + 1, 1);
    }
    // Query Section
    if (strpos($options, 'q') === false) {
        $aOptions['q'] = '-';
    } else {
        $aOptions['q'] = substr($options, strpos($options, 'q') + 1, 1);
    }
    // Fragment (Anchor)
    if (strpos($options, 'r') === false) {
        $aOptions['r'] = '-';
    } else {
        $aOptions['r'] = substr($options, strpos($options, 'r') + 1, 1);
    }

    // Generate options
    $newoptions = '';
    foreach ($aOptions as $key => $value) {
        $newoptions .= $key.$value;
    }

    // DEBUGGING - Uncomment line below to display generated options
    // echo '<pre>' . $newoptions . '</pre>';

    // Send to validateUrlSyntax() and return result
    return validateUrlSyntax($emailaddr, $newoptions);
} // END Function validateEmailSyntax()

/**
 * About ValidateFtpSyntax():
 * This function uses the ValidateUrlSyntax() function to easily check the
 * syntax of an FTP address. It accepts the same options as ValidateURLSyntax
 * but defaults them for FTP addresses.
 *
 * Usage:
 * <code>
 *  validateFtpSyntax( url_to_check[, options])
 * </code>
 *  url_to_check - string - The url to check
 *
 *  options - string - A optional string of options to set which parts of
 *          the url are required, optional, or not allowed. Each option
 *          must be followed by a "+" for required, "?" for optional, or
 *          "-" for not allowed. See ValidateUrlSyntax() docs for option list.
 *
 *  The default options are changed to:
 *      s?H-S-E-F+u?P?a+I?p?f?q-r-
 *
 * Examples:
 * <code>
 *  validateFtpSyntax('ftp://netscape.com')
 *  validateFtpSyntax('moz:iesucks@netscape.com')
 *  validateFtpSyntax('ftp://netscape.com:2121/browsers/ns7/', 'u-')
 * </code>
 *
 * Author(s):
 *  Rod Apeldoorn - rod(at)canowhoopass(dot)com
 *
 *
 * Homepage:
 *  http://www.canowhoopass.com/
 *
 *
 * License:
 *  Copyright 2004 - Rod Apeldoorn
 *
 *  Released under same license as validateUrlSyntax(). For details, contact me.
 */
function validateFtpSyntax($ftpaddr, $options = "")
{
    // Check Options Parameter
    if (!preg_match('/^([sHSEFuPaIpfqr][+?-])*$/', $options)) {
        trigger_error("Options attribute malformed", E_USER_ERROR);
    }

    // Set Options Array, set defaults if options are not specified
    // Scheme
    if (strpos($options, 's') === false) {
        $aOptions['s'] = '?';
    } else {
        $aOptions['s'] = substr($options, strpos($options, 's') + 1, 1);
    }
    // http://
    if (strpos($options, 'H') === false) {
        $aOptions['H'] = '-';
    } else {
        $aOptions['H'] = substr($options, strpos($options, 'H') + 1, 1);
    }
    // https:// (SSL)
    if (strpos($options, 'S') === false) {
        $aOptions['S'] = '-';
    } else {
        $aOptions['S'] = substr($options, strpos($options, 'S') + 1, 1);
    }
    // mailto: (email)
    if (strpos($options, 'E') === false) {
        $aOptions['E'] = '-';
    } else {
        $aOptions['E'] = substr($options, strpos($options, 'E') + 1, 1);
    }
    // ftp://
    if (strpos($options, 'F') === false) {
        $aOptions['F'] = '+';
    } else {
        $aOptions['F'] = substr($options, strpos($options, 'F') + 1, 1);
    }
    // User section
    if (strpos($options, 'u') === false) {
        $aOptions['u'] = '?';
    } else {
        $aOptions['u'] = substr($options, strpos($options, 'u') + 1, 1);
    }
    // Password in user section
    if (strpos($options, 'P') === false) {
        $aOptions['P'] = '?';
    } else {
        $aOptions['P'] = substr($options, strpos($options, 'P') + 1, 1);
    }
    // Address Section
    if (strpos($options, 'a') === false) {
        $aOptions['a'] = '+';
    } else {
        $aOptions['a'] = substr($options, strpos($options, 'a') + 1, 1);
    }
    // IP Address in address section
    if (strpos($options, 'I') === false) {
        $aOptions['I'] = '?';
    } else {
        $aOptions['I'] = substr($options, strpos($options, 'I') + 1, 1);
    }
    // Port number
    if (strpos($options, 'p') === false) {
        $aOptions['p'] = '?';
    } else {
        $aOptions['p'] = substr($options, strpos($options, 'p') + 1, 1);
    }
    // File Path
    if (strpos($options, 'f') === false) {
        $aOptions['f'] = '?';
    } else {
        $aOptions['f'] = substr($options, strpos($options, 'f') + 1, 1);
    }
    // Query Section
    if (strpos($options, 'q') === false) {
        $aOptions['q'] = '-';
    } else {
        $aOptions['q'] = substr($options, strpos($options, 'q') + 1, 1);
    }
    // Fragment (Anchor)
    if (strpos($options, 'r') === false) {
        $aOptions['r'] = '-';
    } else {
        $aOptions['r'] = substr($options, strpos($options, 'r') + 1, 1);
    }

    // Generate options
    $newoptions = '';
    foreach ($aOptions as $key => $value) {
        $newoptions .= $key.$value;
    }

    // Send to validateUrlSyntax() and return result
    return validateUrlSyntax($ftpaddr, $newoptions);
} // END Function validateFtpSyntax()
