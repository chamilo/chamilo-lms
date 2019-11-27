<?php


/// Copyright 2006 Rudy Desjardins

/// This program is free software; you can redistribute it and/or modify
/// it under the terms of the GNU General Public License as published by
/// the Free Software Foundation; either version 2 of the License, or
/// (at your option) any later version.
///
/// This program is distributed in the hope that it will be useful,
/// but WITHOUT ANY WARRANTY; without even the implied warranty of
/// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
/// GNU General Public License for more details.
///
/// You should have received a copy of the GNU General Public License
/// along with this program; if not, write to the Free Software
/// Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


///
/// This file defines constants for use as regular expressions for validating
/// RFC 2396 compliant URI strings
///
/// See Appendix A of RFC 2396 for the origin of these expressions
///

define('ALPHA_2396', '[a-zA-Z]');
define('DIGIT_2396', '[0-9]');

define('LOWALPHA_2396', '[a-z]');
define('UPALPHA_2396', '[A-Z]');

define('ALPHANUM_2396', '(?:'.ALPHA_2396.'|'.DIGIT_2396.')');
define('ALPHANUM_OPT_2396', '[a-zA-Z0-9]');

define('HEX_2396', '(?:'.DIGIT_2396.'|[a-fA-F])');
define('HEX_OPT_2396', '[0-9a-fA-F]');

define('ESCAPED_2396', '(?:%'.HEX_OPT_2396.HEX_OPT_2396.')');

define('MARK_2396', '[\\x2D\\x2E\\x5F\\x21\\x7E\\x2A\\x27\\x28\\x29]');
define('RESERVED_2396', '[\\x3B\\x3F\\x2F3A\\x40\\x26\\x2B\\x3D\\x24\\x2C]');
define('UNRESERVED_2396', '(?:'.ALPHANUM_OPT_2396.'|'.MARK_2396.')');

define('URIC_2396', '(?:'.RESERVED_2396.'|'.UNRESERVED_2396.'|'.ESCAPED_2396.')');

define('QUERY_2396', '(?:'.URIC_2396.'*)');
define('FRAGMENT_2396', QUERY_2396);

define('PCHAR_2396', '(?:'.UNRESERVED_2396.'|'.ESCAPED_2396.'|[\\x3A\\x40\\x26\\x3D\\x2B\\x24\\x2C])');

define('PARAM_2396', '(?:'.PCHAR_2396.'*)');

define('SEGMENT_2396', '(?:'.PCHAR_2396.'*(?:;'.PARAM_2396.')*)');

define('PATH_SEGMENTS_2396', '(?:'.SEGMENT_2396.'(?:\\/'.SEGMENT_2396.')*)');

define('ABS_PATH_2396', '(?:\\/'.PATH_SEGMENTS_2396.')');

define('URIC_NO_SLASH_2396', '(?:'.UNRESERVED_2396.'|'.ESCAPED_2396.'|'.'[\\x3A\\x3B\\x3F\\x40\\x26\\x3D\\x24\\x2B\\x2C])');

define('OPAQUE_PART_2396', '(?:'.URIC_NO_SLASH_2396.URIC_2396.'*)');

define('PATH_2396', '(?:'.ABS_PATH_2396.'|'.OPAQUE_PART_2396.')');

/// This is probably wrong... should end in '+', not '*'...
///
define('PORT_2396', '(?:'.DIGIT_2396.'*)');

/// Same here... pieces should be '[012]*'.DIGIT_2396.'{1,2}', although that's not perfect either...
///
define('IPV4ADDRESS_2396', '(?:'.DIGIT_2396.'+\\.'.DIGIT_2396.'+\\.'.DIGIT_2396.'+\\.'.DIGIT_2396.'+)');

define('TOPLABEL_2396', '(?:'.ALPHA_2396.'|'.ALPHA_2396.'(?:'.ALPHANUM_OPT_2396.'|\\x2D)*'.ALPHANUM_OPT_2396.')');
define('DOMAINLABEL_2396', '(?:'.ALPHANUM_OPT_2396.'|'.ALPHANUM_OPT_2396.'(?:'.ALPHANUM_OPT_2396.'|\\x2D)*'.ALPHANUM_OPT_2396.')');

define('HOSTNAME_2396', '(?:(?:'.DOMAINLABEL_2396.'\\.)*'.TOPLABEL_2396.'\\.?)');

define('HOST_2396', '(?:'.HOSTNAME_2396.'|'.IPV4ADDRESS_2396.')');

define('HOSTPORT_2396', '(?:'.HOST_2396.PORT_2396.'?)');

define('USERINFO_2396', '(?:(?:'.UNRESERVED_2396.'|'.ESCAPED_2396.'|[\\x26\\x3A\\x3B\\x3D\\x2B\\x24\\x2C])*)');

/// This should probably be '(?:'.USERINFO_2396.'\\x40)?'.HOSTPORT_2396'...
///
define('SERVER_2396', '(?:(?:'.USERINFO_2396.'\\x40)?'.HOSTPORT_2396.')');

define('REGNAME_2396', '(?:(?:'.UNRESERVED_2396.'|'.ESCAPED_2396.'|[\\x24\\x2C\\x3A\\x3B\\x40\\x26\\x3D\\x2B])+)');

define('AUTHORITY_2396', '(?:'.SERVER_2396.'|'.REGNAME_2396.')');

define('SCHEME_2396', '(?:'.ALPHA_2396.'(?:'.ALPHA_2396.'|'.DIGIT_2396.'|[\\x2B\\x2D\\x2E])*)');

define('REL_SEGMENT_2396', '(?:(?:'.UNRESERVED_2396.'|'.ESCAPED_2396.'|[\\x40\\x26\\x3B\\x3D\\x2B\\x24\\x2C])+)');

define('REL_PATH_2396', '(?:'.REL_SEGMENT_2396.ABS_PATH_2396.'?)');
define('NET_PATH_2396', '(?:\\/\\/'.AUTHORITY_2396.ABS_PATH_2396.'?)');

define('HIER_PART_2396', '(?:(?:'.NET_PATH_2396.'|'.ABS_PATH_2396.')(?:\\?'.QUERY_2396.')?)');

define('RELATIVEURI_2396', '(?:('.NET_PATH_2396.'|'.ABS_PATH_2396.'|'.REL_PATH_2396.')(?:\\?'.QUERY_2396.')?)');
define('ABSOLUTEURI_2396', '(?:'.SCHEME_2396.'\\x3A(?:'.HIER_PART_2396.'|'.OPAQUE_PART_2396.'))');

/// This was the only expression changed to use parens instead of brackets around the URI_2396 options, because
/// otherwise a blank string would match the whole expression incorrectly...
///
define('URI_REFERENCE_2396', '(?:(?:'.ABSOLUTEURI_2396.'|'.RELATIVEURI_2396.')(?:\\x23'.FRAGMENT_2396.')?)');

define('URI_2396', URI_REFERENCE_2396);

?>
