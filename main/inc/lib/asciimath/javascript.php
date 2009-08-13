/////////////////////////////////////////////////////////////////////////////
//                                                                         //
// NOTICE OF COPYRIGHT                                                     //
//                                                                         //
// Moodle - Filter for converting ASCIImath notation to MathML             //
// Now also handles a larger subset of LaTeX, as well as ASCIIsvg          //
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

// if this script url sniffing does not work try setting wwwroot explicitly
// var wwwroot = "http://path/to/your/moodledir"
// or copy the ASCIIMathML.js file into this file.

var wwwroot = document.getElementsByTagName("script")[0].src.replace(/((.*?)\/lib\/.*)/,"$2"); 
document.write('<script src="'+wwwroot+'/filter/asciimath/ASCIIMathML.js"></script>');
