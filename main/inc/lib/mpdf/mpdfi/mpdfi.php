<?php
//  mPDF v2.4 Extension for PDF templates & overwriting placeholders
//  This was adapted from FPDI - Licence reproduced below as for original
//
//  FPDI - Version 1.2
//
//	Copyright 2004-2007 Setasign - Jan Slabon
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//	  http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//


ini_set('auto_detect_line_endings',1); // Strongly required!

require_once(_MPDF_PATH."mpdfi/pdf_context.php");
require_once(_MPDF_PATH."mpdfi/pdf_parser.php");
require_once(_MPDF_PATH."mpdfi/fpdi_pdf_parser.php");


class mPDFI extends mPDF {
	var $current_filename;
	var $parsers;
	var $current_parser;
	var $_obj_stack;
	var $_don_obj_stack;
	var $_current_obj_id;

	// from FPDF_TPL
	var $tpls = array();
	var $tpl = 0;
	var $tplprefix = "/TPL";
	var $_res = array();



	function mPDFI($codepage='win-1252',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P') {
		parent::mPDF($codepage,$format,$default_font_size,$default_font,$mgl,$mgr,$mgt,$mgb,$mgh,$mgf, $orientation);
	}


	// from FPDF_TPL
	function GetTemplateSize($tplidx, $_w=0, $_h=0) {
		if (!$this->tpls[$tplidx])
			return false;
		$w = $this->tpls[$tplidx]['box']['w'];
		$h = $this->tpls[$tplidx]['box']['h'];
		if ($_w == 0 and $_h == 0) {
			$_w = $w;
			$_h = $h;
		}
		if($_w==0)
			$_w=$_h*$w/$h;
		if($_h==0)
			$_h=$_w*$h/$w;
		return array("w" => $_w, "h" => $_h);
	}

	// Thumbnails
	// mPDF 2.3 Templates
	function Thumbnail($file, $npr=3, $spacing=10) {	//$npr = number per row
		$w = (($this->pgwidth + $spacing)/$npr) - $spacing;
		$oldlinewidth = $this->LineWidth;
		$this->SetLineWidth(0.02); $this->SetDrawColor(0);
		$h = 0;
		$maxh = 0;
		$x = $_x = $this->lMargin;
		$_y = $this->tMargin;
		if ($this->y==0) { $y = $_y; } else { $y = $this->y; }
		$pagecount = $this->SetSourceFile($file);
		for ($n = 1; $n <= $pagecount; $n++) {
			$tplidx = $this->ImportPage($n);
			$size = $this->useTemplate($tplidx, $x, $y, $w);
			$this->Rect($x, $y, $size['w'], $size['h']);
			$h = max($h, $size['h']);
			$maxh = max($h, $maxh);
			if ($n % $npr == 0) {
			   if (($y + $h + $spacing + $maxh)>$this->PageBreakTrigger && $n != $pagecount) {
				$this->AddPage();
				$x = $_x;
				$y = $_y;
			   }
			   else {
				$y += $h+$spacing ;
				$x = $_x;
				$h = 0;
			   }
			}
			else {
				$x += $w+$spacing ;
			}
		}
		$this->SetLineWidth($oldlinewidth);
	}

	function SetSourceFile($filename) {
		$this->current_filename = $filename;
		$fn =& $this->current_filename;
		if (!isset($this->parsers[$fn]))
			$this->parsers[$fn] =& new fpdi_pdf_parser($fn,$this);
		if (!$this->parsers[$fn]->success) {
			$this->Error($this->parsers[$fn]->errormsg);	// Delete this line to return false on fail
			return false;
		}
		$this->current_parser =& $this->parsers[$fn];
		return $this->parsers[$fn]->getPageCount();
	}

	function ImportPage($pageno=1, $crop_x=null, $crop_y=null, $crop_w=0, $crop_h=0, $boxName='/CropBox') {
		$fn =& $this->current_filename;

		$parser =& $this->parsers[$fn];
		$parser->setPageno($pageno);

		$this->tpl++;
		$this->tpls[$this->tpl] = array();
		$tpl =& $this->tpls[$this->tpl];
		$tpl['parser'] =& $parser;
		$tpl['resources'] = $parser->getPageResources();
		$tpl['buffer'] = $parser->getContent();

		if (!in_array($boxName, $parser->availableBoxes))
			return $this->Error(sprintf("Unknown box: %s", $boxName));
		$pageboxes = $parser->getPageBoxes($pageno);

		/**
		 * MediaBox
		 * CropBox: Default -> MediaBox
		 * BleedBox: Default -> CropBox
		 * TrimBox: Default -> CropBox
		 * ArtBox: Default -> CropBox
		 */
		if (!isset($pageboxes[$boxName]) && ($boxName == "/BleedBox" || $boxName == "/TrimBox" || $boxName == "/ArtBox"))
			$boxName = "/CropBox";
		if (!isset($pageboxes[$boxName]) && $boxName == "/CropBox")
			$boxName = "/MediaBox";

		if (!isset($pageboxes[$boxName]))
			return false;

		$box = $pageboxes[$boxName];

		$tpl['box'] = $box;
		// To build an array that can be used by useTemplate()
		$this->tpls[$this->tpl] = array_merge($this->tpls[$this->tpl],$box);
		// An imported page will start at 0,0 everytime. Translation will be set in _putformxobjects()
		$tpl['x'] = 0;
		$tpl['y'] = 0;

		$tpl['w'] = $tpl['box']['w'] ;
		$tpl['h'] = $tpl['box']['h'] ;
		if ($crop_w) { $tpl['box']['w'] = $crop_w; }
		if ($crop_h) { $tpl['box']['h'] = $crop_h; }
		if (isset($crop_x)) { $tpl['box']['x'] = $crop_x; }
		if (isset($crop_y)) {$tpl['box']['y'] = $tpl['h'] - $crop_y  - $crop_h ; }

		$page =& $parser->pages[$parser->pageno];

		// fix for rotated pages
		$rotation = $parser->getPageRotation($pageno);

		if (isset($rotation[1]) && ($angle = $rotation[1] % 360) != 0 && $tpl['box']['w'] == $tpl['w']) {
			$steps = $angle / 90;

			$_w = $tpl['w'];
			$_h = $tpl['h'];
			$tpl['w'] = $steps % 2 == 0 ? $_w : $_h;
			$tpl['h'] = $steps % 2 == 0 ? $_h : $_w;

			if ($steps % 2 != 0) {
				$x = $y = ($steps == 1 || $steps == -3) ? $tpl['h'] : $tpl['w'];
			} else {
				$x = $tpl['w'];
				$y = $tpl['h'];
			}

			$cx=($x/2+$tpl['box']['x'])*$this->k;
			$cy=($y/2+$tpl['box']['y'])*$this->k;

			$angle*=-1;

			$angle*=M_PI/180;
			$c=cos($angle);
			$s=sin($angle);
			$tpl['box']['w'] = $tpl['w'] ;
			$tpl['box']['h'] = $tpl['h'] ;

			$tpl['buffer'] = sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm %s Q',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy, $tpl['buffer']);
		}

		return $this->tpl;
	}

	function UseTemplate($tplidx, $_x=null, $_y=null, $_w=0, $_h=0) {
		if (!isset($this->tpls[$tplidx]))
			$this->Error("Template does not exist!");
		if($this->state==0) { $this->AddPage(); }
		$this->_out('q 0 J 1 w 0 j 0 G'); // reset standard values

		$x = $this->tpls[$tplidx]['x'];
		$y = $this->tpls[$tplidx]['y'];
		$w = $this->tpls[$tplidx]['w'];
		$h = $this->tpls[$tplidx]['h'];
		if ($_x == null) { $_x = $x; }
		if ($_y == null) { $_y = $y; }
		if ($_x === -1) { $_x = $this->x; }
		if ($_y === -1) { $_y = $this->y; }


		$wh = $this->getTemplateSize($tplidx,$_w,$_h);
		$_w = $wh['w'];
		$_h = $wh['h'];

		$this->_out(sprintf("q %.4f 0 0 %.4f %.2f %.2f cm", ($_w/$this->tpls[$tplidx]['box']['w']), ($_h/$this->tpls[$tplidx]['box']['h']), $_x*$this->k, ($this->h-($_y+$_h))*$this->k));
		$this->_out($this->tplprefix.$tplidx." Do Q");

		$s = array("w" => $_w, "h" => $_h);
		$this->_out('Q');
		return $s;
	}

	function SetPageTemplate($tplidx='') {
		if (!isset($this->tpls[$tplidx])) {
			$this->pageTemplate = '';
			return false;
		}
		$this->pageTemplate = $tplidx;
	}

	function SetDocTemplate($file='', $continue=0) {
		$this->docTemplate = $file;
		$this->docTemplateContinue = $continue;
	}


//=========================================================================
// Overwrite mPDF functions

function _putresources() {
	$this->_putextgstates();
	$this->_putfonts();
	$this->_putimages();

	// mPDF 2.2 for WMF
	$this->_putformobjects();
	// from FPDF_TPL
	$this->_putformxobjects();
	$this->_putimportedobjects();

	//Resource dictionary
	$this->offsets[2]=strlen($this->buffer);
	$this->_out('2 0 obj');
	$this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
	$this->_out('/Font <<');
	foreach($this->fonts as $font)
		$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
	$this->_out('>>');

	// mPDF 1.2
	if (count($this->extgstates)) {
		$this->_out('/ExtGState <<');
		foreach($this->extgstates as $k=>$extgstate)
			$this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
		$this->_out('>>');
	}

	// mPDF 2.2. for WMF
	// Edited
	if(count($this->images) or count($this->formobjects) || count($this->tpls))	{
		$this->_out('/XObject <<');
		foreach($this->images as $image)
			$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
		foreach($this->formobjects as $formobject)
			$this->_out('/FO'.$formobject['i'].' '.$formobject['n'].' 0 R');
		// from FPDF_TPL function _putxobjectdict()
	   	if (count($this->tpls)) {
			foreach($this->tpls as $tplidx => $tpl) {
				$this->_out($this->tplprefix.$tplidx.' '.$tpl['n'].' 0 R');
			}
		}
		$this->_out('>>');
	}
	$this->_out('>>');
	$this->_out('endobj');	// end resource dictionary

	$this->_putbookmarks(); //EDITEI

	if ($this->encrypted) {
		$this->_newobj();
		$this->enc_obj_id = $this->n;
		$this->_out('<<');
		$this->_putencryption();
		$this->_out('>>');
		$this->_out('endobj');
	}
}

// Overwrite mPDF functions
	function _enddoc() {
		parent::_enddoc();
		if ($this->state > 2 && count($this->parsers) > 0) {
		  	foreach ($this->parsers as $k => $_){
				$this->parsers[$k]->closeFile();
				$this->parsers[$k] = null;
				unset($this->parsers[$k]);
			}
		}
	}

// Overwrite mPDF functions
	function _newobj($obj_id=false,$onlynewobj=false) {
		if (!$obj_id) {
			$obj_id = ++$this->n;
		}
		//Begin a new object
		if (!$onlynewobj) {
			$this->offsets[$obj_id] = strlen($this->buffer);
			$this->_out($obj_id.' 0 obj');
			$this->_current_obj_id = $obj_id; // for later use with encryption
		}
	}

// These all use $this->_current_obj_id instead of $this->n (cf. _newobj above)
function _UTF16BEtextstring($s) {
	$s = $this->UTF8ToUTF16BE($s, true);
	if ($this->encrypted) {
		$s = $this->_RC4($this->_objectkey($this->_current_obj_id), $s);
	}
	return '('. $this->_escape($s).')';
}

function _textstring($s) {
	if ($this->encrypted) {
		$s = $this->_RC4($this->_objectkey($this->_current_obj_id), $s);
	}
	return '('. $this->_escape($s).')';
}


function _putstream($s) {
	if ($this->encrypted) {
		$s = $this->_RC4($this->_objectkey($this->_current_obj_id), $s);
	}
	$this->_out('stream');
	$this->_out($s);
	$this->_out('endstream');
}

//=========================================================================



// New functions
	function _putimportedobjects() {
		if (is_array($this->parsers) && count($this->parsers) > 0) {
			foreach($this->parsers AS $filename => $p) {
				$this->current_parser =& $this->parsers[$filename];
				if (is_array($this->_obj_stack[$filename])) {
					while($n = key($this->_obj_stack[$filename])) {
						$nObj = $this->current_parser->pdf_resolve_object($this->current_parser->c,$this->_obj_stack[$filename][$n][1]);
						$this->_newobj($this->_obj_stack[$filename][$n][0]);
						if ($nObj[0] == PDF_TYPE_STREAM) {
							$this->pdf_write_value($nObj);
						}
						else {
							$this->pdf_write_value($nObj[1]);
						}
						$this->_out('endobj');
						$this->_obj_stack[$filename][$n] = null; // free memory
						unset($this->_obj_stack[$filename][$n]);
						reset($this->_obj_stack[$filename]);
					}
				}
			}
		}
	}



	function _putformxobjects() {
		$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
		reset($this->tpls);
		foreach($this->tpls AS $tplidx => $tpl) {
			$p=($this->compress) ? gzcompress($tpl['buffer']) : $tpl['buffer'];
			$this->_newobj();
			$this->tpls[$tplidx]['n'] = $this->n;
			$this->_out('<<'.$filter.'/Type /XObject');
			$this->_out('/Subtype /Form');
			$this->_out('/FormType 1');

			// Left/Bottom/Right/Top
			$this->_out(sprintf('/BBox [%.2f %.2f %.2f %.2f]',
				$tpl['box']['x']*$this->k,
				$tpl['box']['y']*$this->k,
				($tpl['box']['x'] + $tpl['box']['w'])*$this->k,
				($tpl['box']['y'] + $tpl['box']['h'])*$this->k  )
			);


			if (isset($tpl['box']))
				$this->_out(sprintf('/Matrix [1 0 0 1 %.5f %.5f]',-$tpl['box']['x']*$this->k, -$tpl['box']['y']*$this->k));

			$this->_out('/Resources ');

			if (isset($tpl['resources'])) {
				$this->current_parser =& $tpl['parser'];
				$this->pdf_write_value($tpl['resources']);
			} else {
				$this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
					if (isset($this->_res['tpl'][$tplidx]['fonts']) && count($this->_res['tpl'][$tplidx]['fonts'])) {
						$this->_out('/Font <<');
						foreach($this->_res['tpl'][$tplidx]['fonts'] as $font)
							$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
						$this->_out('>>');
				}
					if(isset($this->_res['tpl'][$tplidx]['images']) && count($this->_res['tpl'][$tplidx]['images']) ||
					   isset($this->_res['tpl'][$tplidx]['tpls']) && count($this->_res['tpl'][$tplidx]['tpls']))
					{
						$this->_out('/XObject <<');
						if (isset($this->_res['tpl'][$tplidx]['images']) && count($this->_res['tpl'][$tplidx]['images'])) {
							foreach($this->_res['tpl'][$tplidx]['images'] as $image)
								$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
						}
						if (isset($this->_res['tpl'][$tplidx]['tpls']) && count($this->_res['tpl'][$tplidx]['tpls'])) {
							foreach($this->_res['tpl'][$tplidx]['tpls'] as $i => $tpl)
								$this->_out($this->tplprefix.$i.' '.$tpl['n'].' 0 R');
						}
						$this->_out('>>');
					}
					$this->_out('>>');
			}

			$this->_out('/Length '.strlen($p).' >>');
			$this->_putstream($p);
			$this->_out('endobj');
		}
	}

//=========================================================================
	function hex2str($hex) {
		return pack("H*", str_replace(array("\r","\n"," "),"", $hex));
	}

	function str2hex($str) {
		return current(unpack("H*",$str));
	}




	function pdf_write_value(&$value) {
		switch ($value[0]) {
			case PDF_TYPE_NUMERIC :
			case PDF_TYPE_TOKEN :
				// A numeric value or a token.
				// Simply output them
				$this->_out($value[1]." ", false);
				break;

			case PDF_TYPE_ARRAY :
				// An array. Output the proper
				// structure and move on.
				$this->_out("[",false);
				for ($i = 0; $i < count($value[1]); $i++) {
					$this->pdf_write_value($value[1][$i]);
				}
				$this->_out("]");
				break;

			case PDF_TYPE_DICTIONARY :
				// A dictionary.
				$this->_out("<<",false);
				reset ($value[1]);
				while (list($k, $v) = each($value[1])) {
					$this->_out($k . " ",false);
					$this->pdf_write_value($v);
				}
				$this->_out(">>");
				break;

			case PDF_TYPE_OBJREF :
				// An indirect object reference
				// Fill the object stack if needed
				$cpfn =& $this->current_parser->filename;
				if (!isset($this->_don_obj_stack[$cpfn][$value[1]])) {
						$this->_newobj(false,true);
						$this->_obj_stack[$cpfn][$value[1]] = array($this->n, $value);
						$this->_don_obj_stack[$cpfn][$value[1]] = array($this->n, $value);
				}
				$objid = $this->_don_obj_stack[$cpfn][$value[1]][0];
				$this->_out("{$objid} 0 R"); //{$value[2]}
				break;

			case PDF_TYPE_STRING :
				if ($this->encrypted) {
					$value[1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[1]);
					$value[1] = $this->_escape($value[1]);
				}
				// A string.
				$this->_out('('.$value[1].')');
				break;

			case PDF_TYPE_STREAM :
				// A stream. First, output the
				// stream dictionary, then the
				// stream data itself.
				$this->pdf_write_value($value[1]);
				if ($this->encrypted) {
					$value[2][1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[2][1]);
				}
				$this->_out("stream");
				$this->_out($value[2][1]);
				$this->_out("endstream");
				break;

			case PDF_TYPE_HEX :
				if ($this->encrypted) {
					$value[1] = $this->hex2str($value[1]);
					$value[1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[1]);
					// remake hexstring of encrypted string
					$value[1] = $this->str2hex($value[1]);
				}
				$this->_out("<".$value[1].">");
				break;

			case PDF_TYPE_NULL :
				// The null object.
				$this->_out("null");
				break;
		}
	}

    // ========== OVERWRITE SEARCH STRING IN A PDF FILE ================
    function OverWrite($file_in, $search, $replacement, $dest="D", $file_out="mpdf" ) {
	$pdf = file_get_contents($file_in);

	if (!is_array($search)) {
		$x = $search;
		$search = array($x);
	}
	if (!is_array($replacement)) {
		$x = $replacement;
		$search = array($x);
	}

	if ($this->isunicode && !$this->isCJK) {
	  foreach($search AS $k=>$val) {
		$search[$k] = $this->UTF8ToUTF16BE($search[$k] , false);
		$search[$k] = $this->_escape($search[$k]);
		$replacement[$k] = $this->UTF8ToUTF16BE($replacement[$k], false);
		$replacement[$k] = $this->_escape($replacement[$k]);
	  }
	}
	else {
	  foreach($replacement AS $k=>$val) {
	  	// Modified by Ivan Tcholakov, 28-JAN-2010.
		//$replacement[$k] = mb_convert_encoding($replacement[$k],$this->mb_encoding,'utf-8');
		$replacement[$k] = api_convert_encoding($replacement[$k],$this->mb_encoding,'utf-8');
		//
		$replacement[$k] = $this->_escape($replacement[$k]);
	  }
	}

	// Get xref into array
	$xref = array();
	preg_match("/xref\n0 (\d+)\n(.*?)\ntrailer/s",$pdf,$m);
	$xref_objid = $m[1];
	preg_match_all('/(\d{10}) (\d{5}) (f|n)/',$m[2],$x);
	for($i=0; $i<count($x[0]); $i++) {
		$xref[] = array(intval($x[1][$i]), $x[2][$i], $x[3][$i]);
	}

	$changes = array();
	preg_match("/<<\/Type \/Pages\n\/Kids \[(.*?)\]\n\/Count/s",$pdf,$m);
	preg_match_all("/(\d+) 0 R /s",$m[1],$o);
	$objlist = $o[1];
	foreach($objlist AS $obj) {
	  if ($this->compress) {
	  	preg_match("/".($obj+1)." 0 obj\n<<\/Filter \/FlateDecode \/Length (\d+)>>\nstream\n(.*?)\nendstream\n/s",$pdf,$m);
	  }
	  else {
	  	preg_match("/".($obj+1)." 0 obj\n<<\/Length (\d+)>>\nstream\n(.*?)\nendstream\n/s",$pdf,$m);
	  }
	  $s = $m[2];
	  $oldlen = $m[1];
	  if ($this->encrypted) {
		$s = $this->_RC4($this->_objectkey($obj+1), $s);
	  }
	  if ($this->compress) {
	  	$s = gzuncompress($s);
	  }
  	  foreach($search AS $k=>$val) {
		$s = str_replace($search[$k],$replacement[$k],$s);
	  }
	  if ($this->compress) {
		$s = gzcompress($s);
	  }
	  if ($this->encrypted) {
		$s = $this->_RC4($this->_objectkey($obj+1), $s);
	  }
	  $newlen = strlen($s);
	  $changes[($xref[$obj+1][0])] = ($newlen - $oldlen) + (strlen($newlen) - strlen($oldlen ));
	  if ($this->compress) {
	  	$newstr = ($obj+1) . " 0 obj\n<</Filter /FlateDecode /Length ".$newlen.">>\nstream\n".$s."\nendstream\n";
	  }
	  else {
	  	$newstr = ($obj+1) . " 0 obj\n<</Length ".$newlen.">>\nstream\n".$s."\nendstream\n";
	  }
	  $pdf = str_replace($m[0],$newstr,$pdf);
	}

	// Update xref in PDF
	krsort($changes);
	$newxref = "xref\n0 ".$xref_objid."\n";
	foreach($xref AS $v) {
		foreach($changes AS $ck => $cv) {
			if ($v[0] > $ck) { $v[0] += $cv; }
		}
		$newxref .= sprintf('%010d',$v[0]) . ' ' . $v[1] . ' ' .$v[2] . " \n";
	}
	$newxref .= "trailer";
	$pdf = preg_replace("/xref\n0 \d+\n.*?\ntrailer/s",$newxref,$pdf);

	// Update startxref in PDF
	preg_match("/startxref\n(\d+)\n%%EOF/s", $pdf, $m);
	$startxref = $m[1];
	$startxref += array_sum($changes);
	$pdf = preg_replace("/startxref\n(\d+)\n%%EOF/s","startxref\n".$startxref."\n%%EOF",$pdf);

	// OUTPUT
	switch($dest) {
		case 'I':
			//Send to standard output
			if(isset($_SERVER['SERVER_NAME']))
			{
				//We send to a browser
				Header('Content-Type: application/pdf');
				Header('Content-Length: '.strlen($pdf));
				Header('Content-disposition: inline; filename='.$file_out);
			}
			echo $pdf;
			break;
		case 'F':
			//Save to local file
			if (!$file_out) { $file_out = 'mpdf.pdf'; }
			$f=fopen($file_out,'wb');
			if(!$f) die('Unable to create output file: '.$file_out);
			fwrite($f,$pdf,strlen($pdf));
			fclose($f);
			break;
		case 'S':
			//Return as a string
			return $pdf;
		case 'D':
		default:
			//Download file
			if(isset($_SERVER['HTTP_USER_AGENT']) and strpos($_SERVER['HTTP_USER_AGENT'],'MSIE'))
				Header('Content-Type: application/force-download');
			else
				Header('Content-Type: application/octet-stream');
			Header('Content-Length: '.strlen($pdf));
			Header('Content-disposition: attachment; filename='.$file_out);
 			echo $pdf;
			break;
	}
    }

    //==========================================================================



}
// END OF CLASS

function _strspn($str1, $str2, $start=null, $length=null) {
	$numargs = func_num_args();
	if ($numargs == 2) {
		return strspn($str1, $str2);
	}
	else if ($numargs == 3) {
		return strspn($str1, $str2, $start);
	}
	else {
		return strspn($str1, $str2, $start, $length);
	}
}


function _strcspn($str1, $str2, $start=null, $length=null) {
	$numargs = func_num_args();
	if ($numargs == 2) {
		return strcspn($str1, $str2);
	}
	else if ($numargs == 3) {
		return strcspn($str1, $str2, $start);
	}
	else {
		return strcspn($str1, $str2, $start, $length);
	}
}

function _fgets (&$h, $force=false) {
	$startpos = ftell($h);
	$s = fgets($h, 1024);
	if ($force && preg_match("/^([^\r\n]*[\r\n]{1,2})(.)/",trim($s), $ns)) {
		$s = $ns[1];
		fseek($h,$startpos+strlen($s));
	}
	return $s;
}



?>