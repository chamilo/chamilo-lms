<?PHP
/////////////////////////////////////////////////////////////////////////////
//                                                                         //
// NOTICE OF COPYRIGHT                                                     //
//                                                                         //
// Moodle - Filter for converting asciimath notation to MathML             //
//                                                                         //
// Copyright (C) 2007 by Peter Jipsen                                      //
// This program is free software; you can redistribute it and/or modify    //
// it under the terms of the GNU General Public License as published by    //
// the Free Software Foundation; either version 2 of the License, or       //
// (at your option) any later version.                                     //
//                                                                         //
// This program is distributed in the hope that it will be useful,         //
// but WITHOUT ANY WARRANTY; without even the implied warranty of          //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           //
// GNU General Public License for more details:                            //
//                                                                         //
//          http://www.gnu.org/copyleft/gpl.html                           //
//                                                                         //
/////////////////////////////////////////////////////////////////////////////
//-------------------------------------------------------------------------
// NOTE: This Moodle text filter uses javascript to process ASCIIMath commands
// embedded in the text.  Math should be surrounded by `...` or $...$
// or put    amath ... amathoff   around the whole text to use the
// experimental auto-math-recognize mode.
//
// The filter enables the ASCIIMathML.js code to do most of the work.
//
// A copy of ASCIIMathML (version 2.0 or later) is included and
// automatically loaded in the file asciimath/javascript.php
//
//-------------------------------------------------------------------------

$textfilter_function='asciimath_filter';
if (function_exists($textfilter_function)) {return;}

function asciimath_filter ($courseid, $text) {
  global $CFG;
  $text = '<span id="processasciimathinmoodle" class="'.$CFG->wwwroot.'/filter/asciimath/"></span>'.$text;
  return $text;
}

?>
