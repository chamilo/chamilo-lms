<?php
/**
 * Logiciel : HTML2PDF - classe MyPDF
 * 
 * Convertisseur HTML => PDF, utilise fpdf de Olivier PLATHEY 
 * Distribué sous la licence LGPL. 
 *
 * @author		Laurent MINGUET <webmaster@spipu.net>
 * @version		3.22a - 15/06/2009
 */

if (!defined('__CLASS_MYPDF__'))
{
	define('__CLASS_MYPDF__', true);
	
	require_once(dirname(__FILE__).'/99_fpdf_protection.class.php');		// classe fpdf_protection

	class MyPDF extends FPDF_Protection
	{
		var $footer_param = array();
		
		var $underline		= false;
		var $overline		= false;
		var $linethrough	= false;
			
		function MyPDF($sens = 'P', $unit = 'mm', $format = 'A4')
		{
			$this->underline	= false;
			$this->overline		= false;
			$this->linethrough	= false;
			
			$this->FPDF_Protection($sens, $unit, $format);
			$this->AliasNbPages();
			$this->SetMyFooter();
		}
		
		function SetMyFooter($page = null, $date = null, $heure = null, $form = null)
		{
			if ($page===null)	$page	= null;
			if ($date===null)	$date	= null;
			if ($heure===null)	$heure	= null;
			if ($form===null)	$form	= null;
			
			$this->footer_param = array('page' => $page, 'date' => $date, 'heure' => $heure, 'form' => $form);	
		}
		
		function Footer()
		{ 
			$txt = '';
			if ($this->footer_param['form'])	$txt = (HTML2PDF::textGET('pdf05'));
			if ($this->footer_param['date'] && $this->footer_param['heure'])	$txt.= ($txt ? ' - ' : '').(HTML2PDF::textGET('pdf03'));
			if ($this->footer_param['date'] && !$this->footer_param['heure'])	$txt.= ($txt ? ' - ' : '').(HTML2PDF::textGET('pdf01'));
			if (!$this->footer_param['date'] && $this->footer_param['heure'])	$txt.= ($txt ? ' - ' : '').(HTML2PDF::textGET('pdf02'));
			if ($this->footer_param['page'])	$txt.= ($txt ? ' - ' : '').(HTML2PDF::textGET('pdf04'));
			
			$txt = str_replace('[[date_d]]',	date('d'),			$txt);
			$txt = str_replace('[[date_m]]',	date('m'),			$txt);
			$txt = str_replace('[[date_y]]',	date('Y'),			$txt);
			$txt = str_replace('[[date_h]]',	date('H'),			$txt);
			$txt = str_replace('[[date_i]]',	date('i'),			$txt);
			$txt = str_replace('[[date_s]]',	date('s'),			$txt);
			$txt = str_replace('[[current]]',	$this->PageNo(),	$txt);
			$txt = str_replace('[[nb]]',		'{nb}',				$txt);

			if (strlen($txt)>0)
			{
			 	$this->SetY(-11);
			 	$this->setOverline(false);
			 	$this->setLinethrough(false);
				$this->SetFont('Arial','I',8);
				$this->Cell(0, 10, $txt, 0, 0, 'R');
			}
		}
		
		// redéfinition de la fonction Image de FPDF afin de rajouter la gestion des fichiers PHP
		function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='')
		{
			//Put an image on the page
			if(!isset($this->images[$file]))
			{
				//First use of this image, get info
				if($type=='')
				{
					/* MODIFICATION HTML2PDF pour le support des images PHP */
					$type = explode('?', $file);
					$type = pathinfo($type[0]);
					if (!isset($type['extension']) || !$type['extension'])
						$this->Error('Image file has no extension and no type was specified: '.$file);
						
					$type = $type['extension'];
					/* FIN MODIFICATION */
				}

				$type=strtolower($type);

				/* MODIFICATION HTML2PDF pour le support des images PHP */
				if ($type=='php')
				{
					// identification des infos
					$infos=@GetImageSize($file);
					if (!$infos) $this->Error('Unsupported image : '.$file);
				
					// identification du type
					$type = explode('/', $infos['mime']);
					if ($type[0]!='image') $this->Error('Unsupported image : '.$file);
					$type = $type[1];
				}
				/* FIN MODIFICATION */
				
				if($type=='jpeg')
					$type='jpg';
				$mtd='_parse'.$type;
				if(!method_exists($this,$mtd))
					$this->Error('Unsupported image type: '.$type);
				$info=$this->$mtd($file);
				$info['i']=count($this->images)+1;
				$this->images[$file]=$info;
			}
			else
				$info=$this->images[$file];
			//Automatic width and height calculation if needed
			if($w==0 && $h==0)
			{
				//Put image at 72 dpi
				$w=$info['w']/$this->k;
				$h=$info['h']/$this->k;
			}
			elseif($w==0)
				$w=$h*$info['w']/$info['h'];
			elseif($h==0)
				$h=$w*$info['h']/$info['w'];
			//Flowing mode
			if($y===null)
			{
				if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
				{
					//Automatic page break
					$x2=$this->x;
					$this->AddPage($this->CurOrientation,$this->CurPageFormat);
					$this->x=$x2;
				}
				$y=$this->y;
				$this->y+=$h;
			}
			if($x===null)
				$x=$this->x;
				
			$this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
			if($link)
				$this->Link($x,$y,$w,$h,$link);
		}
		
		// Draw a polygon
		// Auteur	: Andrew Meier
		// Licence	: Freeware
		function Polygon($points, $style='D')
		{
			if($style=='F')							$op='f';
			elseif($style=='FD' or $style=='DF')	$op='b';
			else									$op='s';
		
			$h = $this->h;
			$k = $this->k;
		
			$points_string = '';
			for($i=0; $i<count($points); $i+=2)
			{
				$points_string .= sprintf('%.2f %.2f', $points[$i]*$k, ($h-$points[$i+1])*$k);
				if($i==0)	$points_string .= ' m ';
				else		$points_string .= ' l ';
			}
			$this->_out($points_string . $op);
		}
		
		function setOverline($value = true)
		{
			$this->overline = $value;
		}

		function setLinethrough($value = true)
		{
			$this->linethrough = $value;
		}
		
		// redéfinition de la methode Text de FPDF afin de rajouter la gestion des overline et linethrough
		function Text($x, $y, $txt)
		{
			//Output a string
			$s=sprintf('BT %.2F %.2F Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));

			/* MODIFICATION HTML2PDF pour le support de underline, overline, linethrough */
			if ($txt!='')
			{
				if($this->underline)	$s.=' '.$this->_dounderline($x,$y,$txt);
				if($this->overline)		$s.=' '.$this->_dooverline($x,$y,$txt);
				if($this->linethrough)	$s.=' '.$this->_dolinethrough($x,$y,$txt);
			}
			/* FIN MODIFICATION */

			if($this->ColorFlag)
				$s='q '.$this->TextColor.' '.$s.' Q';
			$this->_out($s);
		}

		// redéfinition de la methode Cell de FPDF afin de rajouter la gestion des overline et linethrough
		function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
		{
			//Output a cell
			$k=$this->k;
			if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
			{
				//Automatic page break
				$x=$this->x;
				$ws=$this->ws;
				if($ws>0)
				{
					$this->ws=0;
					$this->_out('0 Tw');
				}
				$this->AddPage($this->CurOrientation,$this->CurPageFormat);
				$this->x=$x;
				if($ws>0)
				{
					$this->ws=$ws;
					$this->_out(sprintf('%.3F Tw',$ws*$k));
				}
			}
			if($w==0)
				$w=$this->w-$this->rMargin-$this->x;
			$s='';
			if($fill || $border==1)
			{
				if($fill)
					$op=($border==1) ? 'B' : 'f';
				else
					$op='S';
				$s=sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
			}
			if(is_string($border))
			{
				$x=$this->x;
				$y=$this->y;
				if(strpos($border,'L')!==false)
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
				if(strpos($border,'T')!==false)
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
				if(strpos($border,'R')!==false)
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
				if(strpos($border,'B')!==false)
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			}
			if($txt!=='')
			{
				if($align=='R')
					$dx=$w-$this->cMargin-$this->GetStringWidth($txt);
				elseif($align=='C')
					$dx=($w-$this->GetStringWidth($txt))/2;
				else
					$dx=$this->cMargin;
				if($this->ColorFlag)
					$s.='q '.$this->TextColor.' ';
				$txt2=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
				$s.=sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt2);
				
				/* MODIFICATION HTML2PDF pour le support de underline, overline, linethrough */
				if($this->underline)	$s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
				if($this->overline)		$s.=' '.$this->_dooverline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
				if($this->linethrough)	$s.=' '.$this->_dolinethrough($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
				/* FIN MODIFICATION */
				
				if($this->ColorFlag)
					$s.=' Q';
				if($link)
					$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
			}
			if($s)
				$this->_out($s);
			$this->lasth=$h;
			if($ln>0)
			{
				//Go to next line
				$this->y+=$h;
				if($ln==1)
					$this->x=$this->lMargin;
			}
			else
				$this->x+=$w;
		}

		function _dounderline($x, $y, $txt)
		{
			//Underline text
			$up=$this->CurrentFont['up'];
			$ut=$this->CurrentFont['ut'];

			$p_x = $x*$this->k;
			$p_y = ($this->h-($y-$up/1000*$this->FontSize))*$this->k;
			$p_w = ($this->GetStringWidth($txt)+$this->ws*substr_count($txt,' '))*$this->k;
			$p_h = -$ut/1000*$this->FontSizePt;

			return sprintf('%.2F %.2F %.2F %.2F re f',$p_x,$p_y,$p_w,$p_h);
		}
		
		function _dooverline($x, $y, $txt)
		{
			//Overline text
			$up=$this->CurrentFont['up'];
			$ut=$this->CurrentFont['ut'];

			$p_x = $x*$this->k;
			$p_y = ($this->h-($y-(1000+1.5*$up)/1000*$this->FontSize))*$this->k;
			$p_w = ($this->GetStringWidth($txt)+$this->ws*substr_count($txt,' '))*$this->k;
			$p_h = -$ut/1000*$this->FontSizePt;
			
			return sprintf('%.2F %.2F %.2F %.2F re f',$p_x,$p_y,$p_w,$p_h);
		}
		
		function _dolinethrough($x, $y, $txt)
		{
			//Linethrough text
			$up=$this->CurrentFont['up'];
			$ut=$this->CurrentFont['ut'];

			$p_x = $x*$this->k;
			$p_y = ($this->h-($y-(1000+2.5*$up)/2000*$this->FontSize))*$this->k;
			$p_w = ($this->GetStringWidth($txt)+$this->ws*substr_count($txt,' '))*$this->k;
			$p_h = -$ut/1000*$this->FontSizePt;
			
			return sprintf('%.2F %.2F %.2F %.2F re f',$p_x,$p_y,$p_w,$p_h);
		}
		
		function clippingPathOpen($x = null, $y = null, $w = null, $h = null, $coin_TL=null, $coin_TR=null, $coin_BL=null, $coin_BR=null)
		{
			$path = '';
			if ($x!==null && $y!==null && $w!==null && $h!==null)
			{
				$x1 = $x*$this->k;
				$y1 = ($this->h-$y)*$this->k;

				$x2 = ($x+$w)*$this->k;
				$y2 = ($this->h-$y)*$this->k;

				$x3 = ($x+$w)*$this->k;
				$y3 = ($this->h-$y-$h)*$this->k;

				$x4 = $x*$this->k;
				$y4 = ($this->h-$y-$h)*$this->k;
				
				if ($coin_TL || $coin_TR || $coin_BL || $coin_BR)
				{
					if ($coin_TL) { $coin_TL[0] = $coin_TL[0]*$this->k; $coin_TL[1] =-$coin_TL[1]*$this->k; }
					if ($coin_TR) { $coin_TR[0] = $coin_TR[0]*$this->k; $coin_TR[1] =-$coin_TR[1]*$this->k; }
					if ($coin_BL) { $coin_BL[0] = $coin_BL[0]*$this->k; $coin_BL[1] =-$coin_BL[1]*$this->k; }
					if ($coin_BR) { $coin_BR[0] = $coin_BR[0]*$this->k; $coin_BR[1] =-$coin_BR[1]*$this->k; }

					$MyArc = 4/3 * (sqrt(2) - 1);
					
					if ($coin_TL)
						$path.= sprintf('%.2f %.2f m ', $x1+$coin_TL[0], $y1);
					else
						$path.= sprintf('%.2f %.2f m ', $x1, $y1);
					
					if ($coin_TR)
					{
						$xt1 = ($x2-$coin_TR[0])+$coin_TR[0]*$MyArc;
						$yt1 = ($y2+$coin_TR[1])-$coin_TR[1];
						$xt2 = ($x2-$coin_TR[0])+$coin_TR[0];
						$yt2 = ($y2+$coin_TR[1])-$coin_TR[1]*$MyArc;

						$path.= sprintf('%.2f %.2f l ', $x2-$coin_TR[0], $y2);						
						$path.= sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $xt1, $yt1, $xt2, $yt2, $x2, $y2+$coin_TR[1]);
					}
					else
						$path.= sprintf('%.2f %.2f l ', $x2, $y2);

					if ($coin_BR)
					{
						$xt1 = ($x3-$coin_BR[0])+$coin_BR[0];
						$yt1 = ($y3-$coin_BR[1])+$coin_BR[1]*$MyArc;
						$xt2 = ($x3-$coin_BR[0])+$coin_BR[0]*$MyArc;
						$yt2 = ($y3-$coin_BR[1])+$coin_BR[1];

						$path.= sprintf('%.2f %.2f l ', $x3, $y3-$coin_BR[1]);						
						$path.= sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $xt1, $yt1, $xt2, $yt2, $x3-$coin_BR[0], $y3);
					}
					else
						$path.= sprintf('%.2f %.2f l ', $x3, $y3);

					if ($coin_BL)
					{
						$xt1 = ($x4+$coin_BL[0])-$coin_BL[0]*$MyArc;
						$yt1 = ($y4-$coin_BL[1])+$coin_BL[1];
						$xt2 = ($x4+$coin_BL[0])-$coin_BL[0];
						$yt2 = ($y4-$coin_BL[1])+$coin_BL[1]*$MyArc;

						$path.= sprintf('%.2f %.2f l ', $x4+$coin_BL[0], $y4);						
						$path.= sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $xt1, $yt1, $xt2, $yt2, $x4, $y4-$coin_BL[1]);
					}
					else
						$path.= sprintf('%.2f %.2f l ', $x4, $y4);
				
					if ($coin_TL)
					{
						$xt1 = ($x1+$coin_TL[0])-$coin_TL[0];
						$yt1 = ($y1+$coin_TL[1])-$coin_TL[1]*$MyArc;
						$xt2 = ($x1+$coin_TL[0])-$coin_TL[0]*$MyArc;
						$yt2 = ($y1+$coin_TL[1])-$coin_TL[1];

						$path.= sprintf('%.2f %.2f l ', $x1, $y1+$coin_TL[1]);						
						$path.= sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $xt1, $yt1, $xt2, $yt2, $x1+$coin_TL[0], $y1);
					}
				}
				else
				{
					$path.= sprintf('%.2f %.2f m ', $x1, $y1);
					$path.= sprintf('%.2f %.2f l ', $x2, $y2);
					$path.= sprintf('%.2f %.2f l ', $x3, $y3);
					$path.= sprintf('%.2f %.2f l ', $x4, $y4);
				}

				$path.= ' h W n';
			}
			$this->_out('q '.$path.' ');			
		}
		
		function clippingPathClose()
		{
			$this->_out(' Q');
		}
		
		function drawCourbe($ext1_x, $ext1_y, $ext2_x, $ext2_y, $int1_x, $int1_y, $int2_x, $int2_y, $cen_x, $cen_y)
		{
			$MyArc = 4/3 * (sqrt(2) - 1);
			
			$ext1_x = $ext1_x*$this->k; $ext1_y = ($this->h-$ext1_y)*$this->k;
			$ext2_x = $ext2_x*$this->k; $ext2_y = ($this->h-$ext2_y)*$this->k;
			$int1_x = $int1_x*$this->k; $int1_y = ($this->h-$int1_y)*$this->k;
			$int2_x = $int2_x*$this->k; $int2_y = ($this->h-$int2_y)*$this->k;
			$cen_x	= $cen_x*$this->k;	$cen_y	= ($this->h-$cen_y) *$this->k;
			
			$path = '';
			
			if ($ext1_x-$cen_x!=0)
			{
				$xt1 = $cen_x+($ext1_x-$cen_x);
				$yt1 = $cen_y+($ext2_y-$cen_y)*$MyArc;
				$xt2 = $cen_x+($ext1_x-$cen_x)*$MyArc;
				$yt2 = $cen_y+($ext2_y-$cen_y);
			}
			else
			{
				$xt1 = $cen_x+($ext2_x-$cen_x)*$MyArc;
				$yt1 = $cen_y+($ext1_y-$cen_y);
				$xt2 = $cen_x+($ext2_x-$cen_x);
				$yt2 = $cen_y+($ext1_y-$cen_y)*$MyArc;

			}

			$path.= sprintf('%.2f %.2f m ', $ext1_x, $ext1_y);
			$path.= sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $xt1, $yt1, $xt2, $yt2, $ext2_x, $ext2_y);

			if ($int1_x-$cen_x!=0)
			{
				$xt1 = $cen_x+($int1_x-$cen_x)*$MyArc;
				$yt1 = $cen_y+($int2_y-$cen_y);
				$xt2 = $cen_x+($int1_x-$cen_x);
				$yt2 = $cen_y+($int2_y-$cen_y)*$MyArc;
			}
			else
			{
				$xt1 = $cen_x+($int2_x-$cen_x);
				$yt1 = $cen_y+($int1_y-$cen_y)*$MyArc;
				$xt2 = $cen_x+($int2_x-$cen_x)*$MyArc;
				$yt2 = $cen_y+($int1_y-$cen_y);

			}
			
			$path.= sprintf('%.2f %.2f l ', $int2_x, $int2_y);
			$path.= sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $xt1, $yt1, $xt2, $yt2, $int1_x, $int1_y);

			$this->_out($path . 'f');
		}
	}
}
?>