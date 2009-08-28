<?php
/**
 * Logiciel : HTML2PDF
 * 
 * Convertisseur HTML => PDF, utilise fpdf de Olivier PLATHEY 
 * Distribué sous la licence LGPL. 
 *
 * @author		Laurent MINGUET <webmaster@spipu.net>
 * @version		3.24 - 05/08/2009
 */

/**
* 	Author: Juan Carlos Raña.
* 	forced to read first because another similar library (but different version) can be found elsewhere in the system
*/
 
$configurationPearPath = $configuration['root_sys'].'/main/inc/lib/html2pdf';
ini_set('include_path', $configurationPearPath);

if (!defined('__CLASS_HTML2PDF__'))
{
	define('__CLASS_HTML2PDF__', '3.24');

	// vous pouvez utiliser cette fonction de debug comme suit
	// pour voir le temps et la mémoire utilisés (sous linux) pour la conversion :
	//	echo HTML2PDFgetTimerDebug();
	//	$html2pdf->WriteHTML($content);
	//	echo HTML2PDFgetTimerDebug();
	function HTML2PDFgetTimerDebug($debug=false) 
	{
		global $TIMER_ACTION_LAST;
		list($usec, $sec) = explode(" ", microtime()); 
		$time = (float)$sec + (float)$usec;
		$mem =	HTML2PDFgetMem();
		
		if (!$TIMER_ACTION_LAST)
		{
			if ($debug)	$ret = null;
			else		$ret = 'Debug : init'."<br />\n"; 
		}
		else
		{
			$aff_time	= $time-$TIMER_ACTION_LAST[0];
			$aff_mem	= $mem;
			if ($debug)	$ret = array($aff_time, $aff_mem);
			else		$ret = 'Timer : '.number_format($aff_time, 3, '.', '').'s - Memory used '.$aff_mem.' Ko'."<br />\n";
		}
		$TIMER_ACTION_LAST = array($time, $mem);
		return $ret;
	}
	function HTML2PDFgetMem() { return function_exists('memory_get_usage') ? floor(memory_get_usage()/1024) : 0; }

	require_once(dirname(__FILE__).'/_mypdf/mypdf.class.php');	// classe mypdf dérivé de fpdf de Olivier PLATHEY 
	require_once(dirname(__FILE__).'/parsingHTML.class.php');	// classe de parsing HTML
	require_once(dirname(__FILE__).'/styleHTML.class.php');		// classe de gestion des styles

	global $HTML2PDF_TABLEAU;	$HTML2PDF_TABLEAU = array();	// tableau global necessaire à la gestion des tables imbriquées 

	class HTML2PDF
	{
		var $langue			= 'fr';		// langue des messages
		var $sens			= 'P';		// sens d'affichage Portrait ou Landscape
		var $format			= 'A4';		// format de la page : A4, A3, ...
		var $background		= array();	// informations sur le background
		var $testTDin1page	= true;		// activer le test de TD ne devant pas depasser une page
		
		var $style			= null;		// objet de style
		var $parsing		= null;		// objet de parsing
		var $parse_pos		= 0;		// position du parsing
		var $temp_pos		= 0;		// position temporaire pour multi tableau
		
		var $sub_html		= null;		// sous html
		var $sub_part		= false;	// indicateur de sous html
		var $isSubPart		= false;	// indique que le convertisseur courant est un sous html
		
		var $pdf			= null;		// objet PDF
		var $maxX			= 0;		// zone maxi X
		var $maxY			= 0;		// zone maxi Y

		var $FirstPage		= true;		// premier page
		
		var $defaultLeft	= 0;		// marges par default de la page
		var $defaultTop		= 0;
		var $defaultRight	= 0;
		var $defaultBottom	= 0;
		
		var $margeLeft		= 0;		//marges réelles de la page
		var $margeTop		= 0;
		var $margeRight		= 0;
		var $margeBottom	= 0;
		var $marges			= array();
		var $Maxs			= array();
		
		var $maxH			= 0;		// plus grande hauteur dans la ligne, pour saut de ligne à corriger
		var $inLink			= '';		// indique si on est à l'interieur d'un lien
		var $lstAncre		= array();	// liste des ancres détectées ou créées
		var $subHEADER		= array();	// tableau des sous commandes pour faire l'HEADER
		var $subFOOTER		= array();	// tableau des sous commandes pour faire le FOOTER
		var $subSTATES		= array();	// tableau de sauvegarde de certains paramètres
		var $defLIST		= array();	// tableau de sauvegarde de l'etat des UL et OL
		
		var $lstChamps		= array();	// liste des champs
		var $lstSelect		= array();	// options du select en cours
		var $previousCall	= null;		// dernier appel
		var $isInTfoot		= false;	// indique si on est dans un tfoot
		var $pageMarges		= array();	// marges spécifiques dues aux floats
		var $isAfterFloat	= false;	// indique si on est apres un float
		var $forOneLine		= false;	// indique si on est dans un sous HTML ne servant qu'a calculer la taille de la prochaine ligne
		
		/**
		 * Constructeur
		 *
		 * @param	string		sens portrait ou landscape
		 * @param	string		format A4, A5, ...
		 * @param	string		langue : fr, en, it...
		 * @param	array		marges par defaut, dans l'ordre (left, top, right, bottom)
		 * @param	boolean		forcer la création de la premiere page, ne pas utiliser, c'est utilisé en interne pour la gestion des tableaux
		 * @return	null
		 */
		function HTML2PDF($sens = 'P', $format = 'A4', $langue='fr', $marges = array(5, 5, 5, 8), $force_page = false)
		{
			// sauvegarde des paramètres 
			$this->sens			= $sens;
			$this->format		= $format;
			$this->FirstPage	= true;
			$this->langue		= strtolower($langue);
			$this->setTestTdInOnePage(true);
			
			// chargement du fichier de langue
			$this->textLOAD($this->langue);
			
			// création de l' objet PDF
			$this->pdf = new MyPDF($sens, 'mm', $format);

			// initialisation des styles
			$this->style = new styleHTML($this->pdf);
			$this->style->FontSet();
			$this->defLIST = array();
			
			// initialisation du parsing
			$this->parsing = new parsingHTML();
			$this->sub_html = null; 
			$this->sub_part	= false;
			
			// initialisation des marges
			$this->setDefaultMargins($marges[0], $marges[1], $marges[2], $marges[3]);
			$this->setMargins();
			$this->marges = array();

			// initialisation des champs de formulaire
			$this->lstChamps = array();

			// premier page forcée
			if ($force_page) $this->setNewPage($this->sens);
		}
		
		/**
		* activer ou desactiver le test de TD ne devant pas depasser une page
		*
		* @param	boolean	nouvel etat
		* @return	boolean ancien etat
		*/
		function setTestTdInOnePage($mode = true)
		{
			$old = $this->testTDin1page;
			
			$this->testTDin1page = $mode ? true : false;
			
			return $old;
		}

		/**
		* définir les marges par défault
		*
		* @param	int		en mm, marge left
		* @param	int		en mm, marge top
		* @param	int		en mm, marge right. si null, left=right
		* @param	int		en mm, marge bottom. si null, bottom=8
		* @return	null
		*/
		function setDefaultMargins($left, $top, $right = null, $bottom = null)
		{
			if ($right===null)	$right = $left;
			if ($bottom===null)	$bottom = 8;
			
			$this->defaultLeft		= $this->style->ConvertToMM($left.'mm');
			$this->defaultTop		= $this->style->ConvertToMM($top.'mm');
			$this->defaultRight		= $this->style->ConvertToMM($right.'mm');
			$this->defaultBottom	= $this->style->ConvertToMM($bottom.'mm');
		}

		/**
		* définir les marges réelles, fonctions de la balise page
		*
		* @return	null
		*/
		function setMargins()
		{
			$this->margeLeft	= $this->defaultLeft	+ (isset($this->background['left'])		? $this->background['left']		: 0);
			$this->margeRight	= $this->defaultRight	+ (isset($this->background['right'])	? $this->background['right']	: 0);
			$this->margeTop		= $this->defaultTop 	+ (isset($this->background['top'])		? $this->background['top']		: 0);
			$this->margeBottom	= $this->defaultBottom	+ (isset($this->background['bottom'])	? $this->background['bottom']	: 0);
			
			$this->pdf->SetMargins($this->margeLeft, $this->margeTop, $this->margeRight);			
			$this->pdf->cMargin = 0;
			$this->pdf->SetAutoPageBreak(false, $this->margeBottom);
		}
		
		/**
		* recuperer les positions x minimales et maximales en fonction d'une hauteur
		*
		* @param	float	y
		* @return	array(float, float)
		*/
		function getMargins($y)
		{
			$y = floor($y*100);
			$x = array($this->pdf->lMargin, $this->pdf->w-$this->pdf->rMargin);
			
			foreach($this->pageMarges as $m_y => $m_x)
				if ($m_y<=$y) $x = $m_x;
			
			return $x;
		}
		
		/**
		* ajouter une marge suite a un float
		*
		* @param	string	left ou right
		* @param	float	x1
		* @param	float	y1
		* @param	float	x2
		* @param	float	y2
		* @return	null
		*/
		function addMargins($float, $x1, $y1, $x2, $y2)
		{
			$old1 = $this->getMargins($y1);
			$old2 = $this->getMargins($y2);
			if ($float=='left')  $old1[0] = $x2;
			if ($float=='right') $old1[1] = $x1;
			
			$y1 = floor($y1*100);
			$y2 = floor($y2*100);

			foreach($this->pageMarges as $m_y => $m_x)
			{
				if ($m_y<$y1) continue;				
				if ($m_y>$y2) break;	
				if ($float=='left'  && $this->pageMarges[$m_y][0]<$x2) unset($this->pageMarges[$m_y]);
				if ($float=='right' && $this->pageMarges[$m_y][1]>$x1) unset($this->pageMarges[$m_y]);
			}

			$this->pageMarges[$y1] = $old1;
			$this->pageMarges[$y2] = $old2;
			
			ksort($this->pageMarges);
			
			$this->isAfterFloat = true;
		}
	
		/**
		* définir des nouvelles marges et sauvegarder les anciennes
		*
		* @param	float	marge left
		* @param	float	marge top
		* @param	float	marge right
		* @return	null
		*/
		function saveMargin($ml, $mt, $mr)
		{
			$this->marges[] = array('l' => $this->pdf->lMargin, 't' => $this->pdf->tMargin, 'r' => $this->pdf->rMargin, 'page' => $this->pageMarges);
			$this->pdf->SetMargins($ml, $mt, $mr);

			$this->pageMarges = array();
			$this->pageMarges[floor($mt*100)] = array($ml, $this->pdf->w-$mr);
		}
						
		/**
		* récuperer les dernières marches sauvées
		*
		* @return	null
		*/
		function loadMargin()
		{
			$old = array_pop($this->marges);
			if ($old)
			{
				$ml = $old['l'];
				$mt = $old['t'];
				$mr = $old['r'];
				$mP = $old['page'];
			}
			else
			{
				$ml = $this->margeLeft;
				$mt = 0;
				$mr = $this->margeRight;
				$mP = array($mt => array($ml, $this->pdf->w-$mr));
			}
			
			$this->pdf->SetMargins($ml, $mt, $mr);
			$this->pageMarges = $mP;
		}
		
		/**
		* permet d'ajouter une fonte.
		*
		* @param	string nom de la fonte
		* @param	string style de la fonte
		* @param	string fichier de la fonte
		* @return	null
		*/
		function AddFont($family, $style='', $file='')
		{
			$this->pdf->AddFont($family, $style, $file);
		}
		
		/**
		* sauvegarder l'état actuelle des maximums
		*
		* @return	null
		*/
		function saveMax()
		{
			$this->Maxs[] = array($this->maxX, $this->maxY, $this->maxH);
		}
				
		/**
		* charger le dernier état sauvé des maximums
		*
		* @return	null
		*/
		function loadMax()
		{
			$old = array_pop($this->Maxs);

			if ($old)
			{
				$this->maxX = $old[0];
				$this->maxY = $old[1];
				$this->maxH = $old[2];
			}
			else
			{
				$this->maxX = 0;
				$this->maxY = 0;
				$this->maxH = 0;
			}
		}
		
		/**
		* afficher l'header contenu dans page_header
		*
		* @return	null
		*/
		function SetPageHeader()
		{
			if (!count($this->subHEADER)) return false;

			$OLD_parse_pos = $this->parse_pos;
			$OLD_parse_code = $this->parsing->code;
			
			$this->parse_pos = 0;
			$this->parsing->code = $this->subHEADER;
			$this->MakeHTMLcode();
			
			$this->parse_pos = 	$OLD_parse_pos;
			$this->parsing->code = $OLD_parse_code;
		}

		/**
		* afficher le footer contenu dans page_footer
		*
		* @return	null
		*/
		function SetPageFooter()
		{
			if (!count($this->subFOOTER)) return false;

			$OLD_parse_pos = $this->parse_pos;
			$OLD_parse_code = $this->parsing->code;
			
			$this->parse_pos = 0;
			$this->parsing->code = $this->subFOOTER;
			$this->MakeHTMLcode();
			
			$this->parse_pos = 	$OLD_parse_pos;
			$this->parsing->code = $OLD_parse_code;
		}
		
		/**
		* saut de ligne avec une hauteur spécifique
		*
		* @param	float	hauteur de la ligne
		* @param	integer	position reelle courante si saut de ligne pendant l'ecriture d'un texte 
		* @return	null
		*/
		function setNewLine($h, $curr = null)
		{
			$this->pdf->Ln($h);
			
			$this->setNewPositionForNewLine($curr);
		}
			
		/**
		* création d'une nouvelle page avec une orientation particuliere
		*
		* @param	string	sens P=portrait ou L=landscape
		* @param	array	tableau des propriétés du fond de la page
		* @param	integer	position reelle courante si saut de ligne pendant l'ecriture d'un texte 
		* @return	null
		*/
		function setNewPage($orientation = '', $background = null, $curr = null)
		{
/*
			if (!$this->FirstPage)
			{
				$info = debug_backtrace(); foreach($info as $k => $v) { unset($info[$k]['object']); unset($info[$k]['type']); unset($info[$k]['args']);} 
				echo '<pre>'.print_r($info, true).'</pre><hr>';
			}
*/
			$this->FirstPage = false;

			$this->sens = $orientation ? $orientation : $this->sens;
			$this->background = $background!==null ? $background : $this->background;
			$this->maxY = 0;	
			$this->maxX = 0;

			$this->pdf->lMargin = $this->defaultLeft;
			$this->pdf->rMargin = $this->defaultRight;
			$this->pdf->tMargin = $this->defaultTop;
			$this->pdf->AddPage($this->sens);
			
			if (!$this->sub_part && !$this->isSubPart)
			{
				if (is_array($this->background))
				{
					if (isset($this->background['color']) && $this->background['color'])
					{
						$this->pdf->SetFillColor($this->background['color'][0], $this->background['color'][1], $this->background['color'][2]);
						$this->pdf->Rect(0, 0, $this->pdf->w, $this->pdf->h, 'F');
					}

					if (isset($this->background['img']) && $this->background['img'])
						$this->pdf->Image($this->background['img'], $this->background['posX'], $this->background['posY'], $this->background['width']);
				}	
				
				$this->SetPageHeader();
				$this->SetPageFooter();
			}
			
			$this->SetMargins();
			$this->pdf->y = $this->margeTop;
			
			$this->setNewPositionForNewLine($curr);
		}
		
		/**
		* calcul de la position de debut de la prochaine ligne en fonction de l'alignement voulu
		*
		* @param	integer	position reelle courante si saut de ligne pendant l'ecriture d'un texte 
		* @return	null
		*/
		function setNewPositionForNewLine($curr = null)
		{
			list($lx, $rx) = $this->getMargins($this->pdf->y);
			$this->pdf->x=$lx;
			
			if (
				$this->style->value['text-align']!='right' && 
				$this->style->value['text-align']!='center'
				)
				return null;

			$sub = null;
			$this->CreateSubHTML($sub);
			$sub->saveMargin(0, 0,  $sub->pdf->w-$rx+$lx);
			$sub->forOneLine = true;
			$sub->parse_pos = $this->parse_pos;
			$sub->parsing->code = $this->parsing->code;
			
			if ($curr!==null && $sub->parsing->code[$this->parse_pos]['name']=='write')
				$sub->parsing->code[$this->parse_pos]['param']['txt'] = substr($sub->parsing->code[$this->parse_pos]['param']['txt'], $curr);
			else
				$sub->parse_pos++;
				
			// pour chaque element identifié par le parsing
			for($sub->parse_pos; $sub->parse_pos<count($sub->parsing->code); $sub->parse_pos++)
			{
				$todo = $sub->parsing->code[$sub->parse_pos];
				if (!$sub->loadAction($todo)) break;
			}

			$w = $sub->maxX;

			unset($sub);
			if ($this->style->value['text-align']=='center')
				$this->pdf->x+= ($rx-$this->pdf->x-$w)*0.5-0.01;
			else
				$this->pdf->x = $rx-$w-0.01;
		}
		
		/** 
		* récupération du PDF 
		* 
		* @param	string	nom du fichier PDF 
		* @param	boolean	destination 
		* @return	string	contenu éventuel du pdf
		* 
		*
		* Destination où envoyer le document. Le paramètre peut prendre les valeurs suivantes :
		* true  : equivalent à I
		* false : equivalent à S  
		* I : envoyer en inline au navigateur. Le plug-in est utilisé s'il est installé. Le nom indiqué dans name est utilisé lorsque l'on sélectionne "Enregistrer sous" sur le lien générant le PDF.
		* D : envoyer au navigateur en forçant le téléchargement, avec le nom indiqué dans name.
		* F : sauver dans un fichier local, avec le nom indiqué dans name (peut inclure un répertoire).
		* S : renvoyer le document sous forme de chaîne. name est ignoré.
		*/
		function Output($name = '', $dest = false)
		{
			// nettoyage
			global $HTML2PDF_TABLEAU; $HTML2PDF_TABLEAU = array();

			// interpretation des paramètres
			if ($dest===false)	$dest = 'I';
			if ($dest===true)	$dest = 'S';
			if ($dest==='')		$dest = 'I';
			if ($name=='')		$name='document.pdf';
			
			// verification de la destination
			$dest = strtoupper($dest);
			if (!in_array($dest, array('I', 'D', 'F', 'S'))) $dest = 'I';
	
			// verification du nom
			if (strtolower(substr($name, -4))!='.pdf')
			{
				echo 'ERROR : The output document name "'.$name.'" is not a PDF name';
				exit;
			}
			

			return $this->pdf->Output($name, $dest);
		}
		
		/**
		* création d'un sous HTML2PDF pour la gestion des tableaux imbriqués
		*
		* @param	HTML2PDF	futur sous HTML2PDF passé en référence pour création
		* @param	integer		marge eventuelle de l'objet si simulation d'un TD
		* @return	null
		*/		
		function CreateSubHTML(&$sub_html, $cellmargin=0)
		{
			// initialisation du sous objet
			$sub_html = new HTML2PDF(
										$this->sens,
										$this->format,
										$this->langue,
										array($this->defaultLeft,$this->defaultTop,$this->defaultRight,$this->defaultBottom),
										true
									);
			$sub_html->isSubPart = true;
			$sub_html->setTestTdInOnePage($this->testTDin1page);
			
			$sub_html->style->css			= $this->style->css;
			$sub_html->style->css_keys		= $this->style->css_keys;
			$sub_html->style->table			= $this->style->table;
			$sub_html->style->value			= $this->style->value;
			$sub_html->style->value['text-align'] = 'left';
			$sub_html->defLIST				= $this->defLIST;
			$sub_html->style->onlyLeft = true;
			
			// initialisation de la largeur
			if ($this->style->value['width'])
			{
				$marge = $cellmargin*2;
				$marge+= $this->style->value['padding']['l'] + $this->style->value['padding']['r'];
				$marge+= $this->style->value['border']['l']['width'] + $this->style->value['border']['r']['width'];
				$marge = $sub_html->pdf->w - $this->style->value['width'] + $marge;
			}
			else
				$marge = $this->margeLeft+$this->margeRight;
			
			$sub_html->saveMargin(0, 0, $marge);

			// initialisation des fontes
			$sub_html->pdf->fonts		= &$this->pdf->fonts;
			$sub_html->pdf->FontFiles	= &$this->pdf->FontFiles;
			$sub_html->pdf->diffs		= &$this->pdf->diffs;

			// initialisation des positions et autre
			$sub_html->maxX = 0;
			$sub_html->maxY = 0;
			$sub_html->maxH = 0;
			$sub_html->pdf->setX(0);
			$sub_html->pdf->setY(0);
			$sub_html->style->FontSet();
		}
		
		/**
		* destruction d'un sous HTML2PDF pour la gestion des tableaux imbriqués
		*
		* @return	null
		*/	
		function DestroySubHTML()
		{
			
			unset($this->sub_html);
			$this->sub_html = null;	
		}
		
		/**
		* Convertir un nombre arabe en nombre romain
		*
		* @param	integer	nombre à convertir
		* @return	string	nombre converti
		*/
		function listeArab2Rom($nb_ar)
		{
			$nb_b10	= array('I','X','C','M');
			$nb_b5	= array('V','L','D');
			$nb_ro	= '';

			if ($nb_ar<1)		return $nb_ar;
			if ($nb_ar>3999)	return $nb_ar;

			for($i=3; $i>=0 ; $i--)
			{
				$chiffre=floor($nb_ar/pow(10,$i));
				if($chiffre>=1)
				{
					$nb_ar=$nb_ar-$chiffre*pow(10,$i);
					if($chiffre<=3)
					{
						for($j=$chiffre; $j>=1; $j--)
						{
							$nb_ro=$nb_ro.$nb_b10[$i];
						}
					}
					else if($chiffre==9)
					{
						$nb_ro=$nb_ro.$nb_b10[$i].$nb_b10[$i+1];
					}
					elseif($chiffre==4)
					{
					$nb_ro=$nb_ro.$nb_b10[$i].$nb_b5[$i];
					}
					else
					{
						$nb_ro=$nb_ro.$nb_b5[$i];
						for($j=$chiffre-5; $j>=1; $j--)
						{
							$nb_ro=$nb_ro.$nb_b10[$i];
						}
					}
				}
			}
			return $nb_ro;
		}
		
		/**
		* Ajouter un LI au niveau actuel
		*
		* @return	null
		*/
		function listeAddLi()
		{
			$this->defLIST[count($this->defLIST)-1]['nb']++;
		}

		function listeGetWidth()	{ return '7mm'; }
		function listeGetPadding()	{ return '1mm'; }

		/**
		* Recuperer le LI du niveau actuel
		*
		* @return	string	chaine à afficher
		*/
		function listeGetLi()
		{
			$im = $this->defLIST[count($this->defLIST)-1]['img'];
			$st = $this->defLIST[count($this->defLIST)-1]['style'];
			$nb = $this->defLIST[count($this->defLIST)-1]['nb'];
			$up = (substr($st, 0, 6)=='upper-');
			
			if ($im) return array(false, false, $im);
			
			switch($st)
			{
				case 'none':
					return array('arial', true, ' ');
					
				case 'upper-alpha':
				case 'lower-alpha':
					$str = '';
					while($nb>26)
					{
						$str = chr(96+$nb%26).$str; 
						$nb = floor($nb/26);	
					}
					$str = chr(96+$nb).$str; 
					
					return array('arial', false, ($up ? strtoupper($str) : $str).'.');

				case 'upper-roman':
				case 'lower-roman':
					$str = $this->listeArab2Rom($nb);
					
					return array('arial', false, ($up ? strtoupper($str) : $str).'.');
					
				case 'decimal':
					return array('arial', false, $nb.'.');

				case 'square':
					return array('zapfdingbats', true, chr(110));

				case 'circle':
					return array('zapfdingbats', true, chr(109));

				case 'disc':
				default:
					return array('zapfdingbats', true, chr(108));
			}
		}
				
		/**
		* Ajouter un niveau de liste
		*
		* @param	string	type de liste : ul, ol
		* @param	string	style de la liste
		* @return	null
		*/
		function listeAddLevel($type = 'ul', $style = '', $img = null)
		{
			if ($img)
			{
				if (preg_match('/^url\(([^)]+)\)$/isU', trim($img), $match))
					$img = $match[1];
				else
					$img = null;
			}
			else
				$img = null;
			
			if (!in_array($type, array('ul', 'ol'))) $type = 'ul';
			if (!in_array($style, array('lower-alpha', 'upper-alpha', 'upper-roman', 'lower-roman', 'decimal', 'square', 'circle', 'disc', 'none'))) $style = '';
			
			if (!$style)
			{
				if ($type=='ul')	$style = 'disc';
				else				$style = 'decimal';
			}
			$this->defLIST[count($this->defLIST)] = array('style' => $style, 'nb' => 0, 'img' => $img);
		}
		
		/**
		* Supprimer un niveau de liste
		*
		* @return	null
		*/
		function listeDelLevel()
		{
			if (count($this->defLIST))
			{
				unset($this->defLIST[count($this->defLIST)-1]);
				$this->defLIST = array_values($this->defLIST);
			}
		}
		
		/**
		* traitement d'un code HTML
		*
		* @param	string	code HTML à convertir
		* @param	boolean	afficher en pdf (false) ou en html (true)
		* @return	null
		*/	
		function WriteHTML($html, $vue = false)
		{
			$html = str_replace('[[page_nb]]',	'{nb}',	 $html);
			
			$html = str_replace('[[date_y]]',	date('Y'),	 $html);
			$html = str_replace('[[date_m]]',	date('m'),	 $html);
			$html = str_replace('[[date_d]]',	date('d'),	 $html);

			$html = str_replace('[[date_h]]',	date('H'),	 $html);
			$html = str_replace('[[date_i]]',	date('i'),	 $html);
			$html = str_replace('[[date_s]]',	date('s'),	 $html);

			// si on veut voir le résultat en HTML => on appelle la fonction
			if ($vue)	$this->vueHTML($html);	

			// sinon, traitement pour conversion en PDF :
			// parsing
			$this->sub_pdf = false;
			$this->style->readStyle($html);
			$this->parsing->setHTML($html);
			$this->parsing->parse();
			$this->MakeHTMLcode();
		}

		function MakeHTMLcode()
		{
			// pour chaque element identifié par le parsing
			for($this->parse_pos=0; $this->parse_pos<count($this->parsing->code); $this->parse_pos++)
			{
				// récupération de l'élément
				$todo = $this->parsing->code[$this->parse_pos];
				
				// si c'est une ouverture de tableau
				if (in_array($todo['name'], array('table', 'ul', 'ol')) && !$todo['close'])
				{
					// on va créer un sous HTML, et on va travailler sur une position temporaire
					$tag_open = $todo['name'];

					$this->sub_part = true;
					$this->temp_pos = $this->parse_pos;
					
					// pour tous les éléments jusqu'à la fermeture de la table afin de préparer les dimensions
					while(isset($this->parsing->code[$this->temp_pos]) && !($this->parsing->code[$this->temp_pos]['name']==$tag_open && $this->parsing->code[$this->temp_pos]['close']))
					{
						$this->loadAction($this->parsing->code[$this->temp_pos]);
						$this->temp_pos++;
					}
					if (isset($this->parsing->code[$this->temp_pos])) 	$this->loadAction($this->parsing->code[$this->temp_pos]);
					$this->sub_part = false;
				}
				
				// chargement de l'action correspondant à l'élément
				$this->loadAction($todo);
			}			
		} 


	
		/**
		* affichage en mode HTML du contenu
		*
		* @param	string	contenu
		* @return	null
		*/	
		function vueHTML($content)
		{
			$content = preg_replace('/<page_header([^>]*)>/isU',	'<hr>'.@HTML2PDF::textGET('vue01').' : $1<hr><div$1>', $content);
			$content = preg_replace('/<page_footer([^>]*)>/isU',	'<hr>'.@HTML2PDF::textGET('vue02').' : $1<hr><div$1>', $content);
			$content = preg_replace('/<page([^>]*)>/isU',			'<hr>'.@HTML2PDF::textGET('vue03').' : $1<hr><div$1>', $content);
			$content = preg_replace('/<\/page([^>]*)>/isU',			'</div><hr>', $content);
			$content = preg_replace('/<bookmark([^>]*)>/isU',		'<hr>bookmark : $1<hr>', $content);
			$content = preg_replace('/<\/bookmark([^>]*)>/isU',		'', $content);
			
			echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>'.@HTML2PDF::textGET('vue04').' HTML</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
	</head>
	<body style="padding: 10px; font-size: 10pt;font-family:	Arial;">
'.$content.'
	</body>
</html>';
			exit;	
		}

		/**
		* chargement de l'action correspondante à un element de parsing
		*
		* @param	array	élément de parsing
		* @return	null
		*/		
		function loadAction($row)
		{
			// nom de l'action
			$fnc	= ($row['close'] ? 'c_' : 'o_').strtoupper($row['name']);
			
			// parametres de l'action
			$param	= $row['param'];
			
			// si aucune page n'est créé, on la créé
			if ($fnc!='o_PAGE' && $this->FirstPage)
			{
				$this->setNewPage();
			}
			
			// lancement de l'action
			if (is_callable(array(&$this, $fnc)))
			{
				$res = $this->{$fnc}($param);
				$this->previousCall = $fnc;
				return $res;
			}
			else
			{
				@HTML2PDF::makeError(1, __FILE__, __LINE__, strtoupper($row['name']));
				return false;
			}
		}
		
		/**
		* balise	: PAGE
		* mode		: OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_PAGE($param)
		{
			if ($this->forOneLine) return false;

			$newPageSet= (!isset($param['pageset']) || $param['pageset']!='old');
			
			$this->maxH = 0;
			if ($newPageSet)
			{
				$this->subHEADER = array();
				$this->subFOOTER = array();
						
				// identification de l'orientation demandée
				$orientation = '';
				if (isset($param['orientation']))
				{
					$param['orientation'] = strtolower($param['orientation']);
					if ($param['orientation']=='p')			$orientation = 'P';
					if ($param['orientation']=='portrait')	$orientation = 'P';
	
					if ($param['orientation']=='l')			$orientation = 'L';
					if ($param['orientation']=='paysage')	$orientation = 'L';
					if ($param['orientation']=='landscape')	$orientation = 'L';
				}
	
				// identification des propriétés du background
				$background = array();
				if (isset($param['backimg']))
				{
					$background['img']		= isset($param['backimg'])	? $param['backimg']		: '';		// nom de l'image
					$background['posX']		= isset($param['backimgx'])	? $param['backimgx']	: 'center'; // position horizontale de l'image
					$background['posY']		= isset($param['backimgy'])	? $param['backimgy']	: 'middle'; // position verticale de l'image
					$background['width']	= isset($param['backimgw'])	? $param['backimgw']	: '100%';	// taille de l'image (100% = largueur de la feuille)
					
					// conversion du nom de l'image, en cas de paramètres en _GET
					$background['img'] = str_replace('&amp;', '&', $background['img']);
					// conversion des positions
					if ($background['posX']=='left')	$background['posX'] = '0%';
					if ($background['posX']=='center')	$background['posX'] = '50%';
					if ($background['posX']=='right')	$background['posX'] = '100%';
					if ($background['posY']=='top')		$background['posY'] = '0%';
					if ($background['posY']=='middle')	$background['posY'] = '50%';
					if ($background['posY']=='bottom')	$background['posY'] = '100%';
	
	
					// si il y a une image de précisé
					if ($background['img'])	
					{
						// est-ce que c'est une image ?
						$infos=@GetImageSize($background['img']);
						if (count($infos)>1)
						{
							// taille de l'image, en fonction de la taille spécifiée. 
							$Wi = $this->style->ConvertToMM($background['width'], $this->pdf->w);
							$Hi = $Wi*$infos[1]/$infos[0];
							
							// récupération des dimensions et positions de l'image
							$background['width']	= $Wi;	
							$background['posX']		= $this->style->ConvertToMM($background['posX'], $this->pdf->w - $Wi);
							$background['posY']		= $this->style->ConvertToMM($background['posY'], $this->pdf->h - $Hi);
						}
						else
							$background = array();	
					}
					else
						$background = array();
				}
				
				// marges TOP et BOTTOM pour le texte.
				$background['top']		= isset($param['backtop'])			? $param['backtop'] 		: '0';
				$background['bottom']	= isset($param['backbottom'])		? $param['backbottom']		: '0';
				$background['left']		= isset($param['backleft'])			? $param['backleft'] 		: '0';
				$background['right']	= isset($param['backright'])		? $param['backright']		: '0';

				if (preg_match('/^([0-9]*)$/isU', $background['top']))		$background['top']		.= 'mm';
				if (preg_match('/^([0-9]*)$/isU', $background['bottom']))	$background['bottom']	.= 'mm';
				if (preg_match('/^([0-9]*)$/isU', $background['left']))		$background['left']		.= 'mm';
				if (preg_match('/^([0-9]*)$/isU', $background['right']))	$background['right']	.= 'mm';

				$background['top']		= $this->style->ConvertToMM($background['top'],		$this->pdf->h);
				$background['bottom']	= $this->style->ConvertToMM($background['bottom'],	$this->pdf->h);
				$background['left']		= $this->style->ConvertToMM($background['left'],	$this->pdf->w);
				$background['right']	= $this->style->ConvertToMM($background['right'],	$this->pdf->w);

				$res = false;
				$background['color']	= isset($param['backcolor'])	? $this->style->ConvertToRVB($param['backcolor'], $res) : null;
				if (!$res) $background['color'] = null;

				$this->style->save();
				$this->style->analyse('PAGE', $param);
				$this->style->setPosition($this->pdf->x, $this->pdf->y);
				$this->style->FontSet();
				
				// nouvelle page
				$this->setNewPage($orientation, $background);
	
				// footer automatique
				if (isset($param['footer']))
				{
					$lst = explode(';', $param['footer']);
					foreach($lst as $key => $val) $lst[$key] = trim(strtolower($val));
					$page	= in_array('page', $lst);
					$date	= in_array('date', $lst);
					$heure	= in_array('heure', $lst);
					$form	= in_array('form', $lst);
				}
				else
				{
					$page	= null;
					$date	= null;
					$heure	= null;
					$form	= null;
				}
				$this->pdf->SetMyFooter($page, $date, $heure, $form);
			}
			else
			{
				$this->style->save();
				$this->style->analyse('PAGE', $param);
				$this->style->setPosition($this->pdf->x, $this->pdf->y);
				$this->style->FontSet();
				
				$this->setNewPage();
			}			
			
			return true;
		}

		/**
		* balise : PAGE
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_PAGE($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;

			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		
		
		function o_PAGE_HEADER($param)
		{
			if ($this->forOneLine) return false;

			$this->subHEADER = array();
			for($this->parse_pos; $this->parse_pos<count($this->parsing->code); $this->parse_pos++)
			{
				$todo = $this->parsing->code[$this->parse_pos];
				if ($todo['name']=='page_header') $todo['name']='page_header_sub';
				$this->subHEADER[] = $todo;
				if (strtolower($todo['name'])=='page_header_sub' && $todo['close']) break;
			}

			$this->SetPageHeader();
			
			return true;
		}
		
		function o_PAGE_FOOTER($param)
		{
			if ($this->forOneLine) return false;

			$this->subFOOTER = array();
			for($this->parse_pos; $this->parse_pos<count($this->parsing->code); $this->parse_pos++)
			{
				$todo = $this->parsing->code[$this->parse_pos];
				if ($todo['name']=='page_footer') $todo['name']='page_footer_sub';
				$this->subFOOTER[] = $todo;
				if (strtolower($todo['name'])=='page_footer_sub' && $todo['close']) break;
			}
			
			$this->SetPageFooter();
			
			return true;
		}

		function o_PAGE_HEADER_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->subSTATES = array();
			$this->subSTATES['x']	= $this->pdf->x;
			$this->subSTATES['y']	= $this->pdf->y;
			$this->subSTATES['s']	= $this->style->value;
			$this->subSTATES['t']	= $this->style->table;
			$this->subSTATES['ml']	= $this->pdf->lMargin;
			$this->subSTATES['mr']	= $this->pdf->rMargin;
			$this->subSTATES['mt']	= $this->pdf->tMargin;
			$this->subSTATES['mb']	= $this->pdf->bMargin;
	
			$this->pdf->x						= $this->defaultLeft;
			$this->pdf->y						= $this->defaultTop;
			$this->style->initStyle();
			$this->style->resetStyle();
			$this->style->value['width']		= $this->pdf->w - $this->defaultLeft - $this->defaultRight;
			$this->style->table					= array();
			$this->pdf->lMargin					= $this->defaultLeft;
			$this->pdf->rMargin					= $this->defaultRight;
			$this->pdf->tMargin					= $this->defaultTop;
			$this->pdf->bMargin					= $this->defaultBottom;
			$this->pdf->PageBreakTrigger		= $this->pdf->h - $this->pdf->bMargin;

			$this->style->save();
			$this->style->analyse('page_header_sub', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}

		function c_PAGE_HEADER_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();

			$this->pdf->x						= $this->subSTATES['x'];
			$this->pdf->y						= $this->subSTATES['y'];
			$this->style->value					= $this->subSTATES['s'];
			$this->style->table					= $this->subSTATES['t'];
			$this->pdf->lMargin					= $this->subSTATES['ml'];
			$this->pdf->rMargin					= $this->subSTATES['mr'];
			$this->pdf->tMargin					= $this->subSTATES['mt'];
			$this->pdf->bMargin					= $this->subSTATES['mb'];
			$this->pdf->PageBreakTrigger		= $this->pdf->h - $this->pdf->bMargin;

			$this->style->FontSet();			
			
			return true;
		}
				
		function o_PAGE_FOOTER_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->subSTATES = array();
			$this->subSTATES['x']	= $this->pdf->x;
			$this->subSTATES['y']	= $this->pdf->y;
			$this->subSTATES['s']	= $this->style->value;
			$this->subSTATES['t']	= $this->style->table;
			$this->subSTATES['ml']	= $this->pdf->lMargin;
			$this->subSTATES['mr']	= $this->pdf->rMargin;
			$this->subSTATES['mt']	= $this->pdf->tMargin;
			$this->subSTATES['mb']	= $this->pdf->bMargin;
	
			$this->pdf->x						= $this->defaultLeft;
			$this->pdf->y						= $this->defaultTop;
			$this->style->initStyle();
			$this->style->resetStyle();
			$this->style->value['width']		= $this->pdf->w - $this->defaultLeft - $this->defaultRight;
			$this->style->table					= array();			
			$this->pdf->lMargin					= $this->defaultLeft;
			$this->pdf->rMargin					= $this->defaultRight;
			$this->pdf->tMargin					= $this->defaultTop;
			$this->pdf->bMargin					= $this->defaultBottom;
			$this->pdf->PageBreakTrigger		= $this->pdf->h - $this->pdf->bMargin;

			// on en créé un sous HTML que l'on transforme en PDF
			// pour récupérer la hauteur
			// on extrait tout ce qui est contenu dans le FOOTER
			$sub = null;
			$res = $this->parsing->getLevel($this->parse_pos);
			$this->CreateSubHTML($sub);
			$sub->writeHTML($res[1]);
			$this->pdf->y = $this->pdf->h - $sub->maxY - $this->defaultBottom - 0.01;
			unset($sub);
			
			$this->style->save();			
			$this->style->analyse('page_footer_sub', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();			
			
			return true;
		}

		function c_PAGE_FOOTER_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();

			$this->pdf->x						= $this->subSTATES['x'];
			$this->pdf->y						= $this->subSTATES['y'];
			$this->style->value					= $this->subSTATES['s'];
			$this->style->table					= $this->subSTATES['t'];
			$this->pdf->lMargin					= $this->subSTATES['ml'];
			$this->pdf->rMargin					= $this->subSTATES['mr'];
			$this->pdf->tMargin					= $this->subSTATES['mt'];
			$this->pdf->bMargin					= $this->subSTATES['mb'];
			$this->pdf->PageBreakTrigger		= $this->pdf->h - $this->pdf->bMargin;

			$this->style->FontSet();			
			
			return true;
		}
		
		/**
		* balise : NOBREAK
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/
		function o_NOBREAK($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			// on extrait tout ce qui est contenu dans le NOBREAK
			$res = $this->parsing->getLevel($this->parse_pos);

			// on en créé un sous HTML que l'on transforme en PDF
			// pour analyse les dimensions
			// et voir si ca rentre
			$sub = null;
			$this->CreateSubHTML($sub);
			$sub->writeHTML($res[1]);
			
			$y = $this->pdf->getY();
			if (
					$sub->maxY < ($this->pdf->h - $this->pdf->tMargin-$this->pdf->bMargin) &&
					$y + $sub->maxY>=($this->pdf->h - $this->pdf->bMargin)
				)
				$this->setNewPage();
			unset($sub);
			
			return true;
		}
		

		/**
		* balise : NOBREAK
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_NOBREAK($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
					
			return true;
		}
		
		/**
		* balise : DIV
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_DIV($param, $other = 'div')
		{
			if ($this->forOneLine) return false;

			$this->style->save();
			$this->style->analyse($other, $param);
			$this->style->FontSet();

			$align_object = null;
			if ($this->style->value['margin-auto']) $align_object = 'center';

			$marge = array();
			$marge['l'] = $this->style->value['border']['l']['width'] + $this->style->value['padding']['l']+0.03;
			$marge['r'] = $this->style->value['border']['r']['width'] + $this->style->value['padding']['r']+0.03;
			$marge['t'] = $this->style->value['border']['t']['width'] + $this->style->value['padding']['t']+0.03;
			$marge['b'] = $this->style->value['border']['b']['width'] + $this->style->value['padding']['b']+0.03;
			
			// on extrait tout ce qui est contenu dans la DIV
			$res = $this->parsing->getLevel($this->parse_pos);

			// on en créé un sous HTML que l'on transforme en PDF
			// pour analyse les dimensions
			$w = 0; $h = 0;
			if (trim($res[1]))
			{
				$sub = null;
				$this->CreateSubHTML($sub);
				$sub->writeHTML($res[1]);				
				$w = $sub->maxX;
				$h = $sub->maxY;
				unset($sub);
			}
			if (($w==0 && $this->style->value['width']==0) || $this->style->value['position']=='absolute')
			{
				$w+= $marge['l']+$marge['r']+0.001;			
			}
			$h+= $marge['t']+$marge['b']+0.001;
			
			$this->style->value['width']	= max($w, $this->style->value['width']);
			$this->style->value['height']	= max($h, $this->style->value['height']);
			
			if (!$this->style->value['position'])
			{
				if (
					$this->style->value['width'] < ($this->pdf->w - $this->pdf->lMargin-$this->pdf->rMargin) &&
					$this->pdf->x + $this->style->value['width']>=($this->pdf->w - $this->pdf->rMargin)
					)
					$this->o_BR(array());
	
				if (
						$this->style->value['height'] < ($this->pdf->h - $this->pdf->tMargin-$this->pdf->bMargin) &&
						$this->pdf->y + $this->style->value['height']>=($this->pdf->h - $this->pdf->bMargin)
					)
					$this->setNewPage();
				
				// en cas d'alignement => correction
				$w = $this->style->value['width'];
				$old = isset($this->style->table[count($this->style->table)-1]) ? $this->style->table[count($this->style->table)-1] : $this->style->value;
				$parent_w = $old['width'] ? $old['width'] : $this->pdf->w - $this->pdf->lMargin - $this->pdf->rMargin;
				
				if ($parent_w>$w)
				{				
					if ($align_object=='center')		$this->pdf->x = $this->pdf->x + ($parent_w-$w)*0.5;
					else if ($align_object=='right')	$this->pdf->x = $this->pdf->x + $parent_w-$w;
				}
				
				$this->style->setPosition($this->pdf->x, $this->pdf->y);
			}
			else
			{
				$this->style->setPosition($this->pdf->x, $this->pdf->y);
				$this->saveMax();
				$this->saveX = 0;
				$this->saveY = 0;
				$this->saveH = 0;
			}		
			
			// initialisation du style des bordures de la premiere partie de tableau
			$this->Rectangle(
					$this->style->value['x'],
					$this->style->value['y'],
					$this->style->value['width'],
					$this->style->value['height'],
					$this->style->value['border'],
					$this->style->value['padding'],
					0,
					$this->style->value['background']
				);

			
			$marge = array();
			$marge['l'] = $this->style->value['border']['l']['width'] + $this->style->value['padding']['l']+0.03;
			$marge['r'] = $this->style->value['border']['r']['width'] + $this->style->value['padding']['r']+0.03;
			$marge['t'] = $this->style->value['border']['t']['width'] + $this->style->value['padding']['t']+0.03;
			$marge['b'] = $this->style->value['border']['b']['width'] + $this->style->value['padding']['b']+0.03;

			$this->style->value['width']	= $this->style->value['width']-$marge['l']-$marge['r'];
			$this->style->value['height']	= $this->style->value['height']-$marge['r']-$marge['b'];

			// limitation des marges aux dimensions de la div
			$mL = $this->style->value['x']+$marge['l'];
			$mR = $this->pdf->w - $mL - $this->style->value['width'];
			$this->saveMargin($mL, 0, $mR);
			
			// positionnement en fonction
			$h_corr = $this->style->value['height'];
			$h_reel = $h-$marge['b']-$marge['t'];
			switch($this->style->value['vertical-align'])
			{
				case 'bottom':
					$y_corr = $h_corr-$h_reel;
					break;
					
				case 'middle':
					$y_corr = ($h_corr-$h_reel)*0.5;
					break;
					
				case 'top':
				default:
					$y_corr = 0;
					break;	
			}
			
			$this->pdf->setX($this->style->value['x']+$marge['l']);
			$this->pdf->setY($this->style->value['y']+$marge['t']+$y_corr);
			
			$this->setNewPositionForNewLine();
			
			return true;
		}
		function o_BLOCKQUOTE($param) { return $this->o_DIV($param, 'blockquote'); }
		
		/**
		* balise : DIV
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_DIV($param)
		{
			if ($this->forOneLine) return false;

			$marge = array();
			$marge['l'] = $this->style->value['border']['l']['width'] + $this->style->value['padding']['l']+0.03;
			$marge['r'] = $this->style->value['border']['r']['width'] + $this->style->value['padding']['r']+0.03;
			$marge['t'] = $this->style->value['border']['t']['width'] + $this->style->value['padding']['t']+0.03;
			$marge['b'] = $this->style->value['border']['b']['width'] + $this->style->value['padding']['b']+0.03;
			
			$x = $this->style->value['x'];
			$y = $this->style->value['y'];
			$w = $this->style->value['width']+$marge['l']+$marge['r'];
			$h = $this->style->value['height']+$marge['t']+$marge['b'];

			// correction pour les margins
			$w+= $this->style->value['margin']['r'];
			$h+= $this->style->value['margin']['b'];

			if ($this->style->value['position']!='absolute')
			{
				// position
				$this->pdf->x = $x+$w; 	
				$this->pdf->y = $y;
				 	
				// position MAX
				$this->maxX = max($this->maxX, $x+$w);
				$this->maxY = max($this->maxY, $y+$h);
		 		$this->maxH = max($this->maxH, $h);
			}
			else
			{
				// position
				$this->pdf->x = $this->style->value['xc']; 	
				$this->pdf->y = $this->style->value['yc'];
				 	
				$this->loadMax();
			}
	 	
	 		$block = ($this->style->value['display']!='inline' && $this->style->value['position']!='absolute');
	 		
	 		$this->style->load();
			$this->style->FontSet();
			$this->loadMargin();
			
			if ($block) $this->o_BR(array());
			
			return true;
		}
		function c_BLOCKQUOTE($param) { return $this->c_DIV($param); }
		
		/**
		* balise : BARCODE
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_BARCODE($param)
		{
			
			$lst_barcode = array(
								'EAN13'		=> '0.35mm',
								'UPC_A'		=> '0.35mm',
								'CODE39'	=> '1.00mm',
							);
			if (isset($param['type'])) $param['type'] = strtoupper($param['type']);
			
			if (!isset($param['type']) || !isset($lst_barcode[$param['type']])) $param['type']=='CODE39';
			if (!isset($param['value']))	$param['value']	= 0;
			if (!isset($param['bar_w']))	$param['bar_w']	= $lst_barcode[$param['type']];
			if (!isset($param['bar_h']))	$param['bar_h']	= '10mm';
			
			if (!isset($param['style']['color'])) $param['style']['color'] = '#000000';
			$param['style']['background-color'] = $param['style']['color'];
			
			$this->style->save();
			$this->style->analyse('barcode', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			$w = $this->style->ConvertToMM($param['bar_w']);
			$h = $this->style->ConvertToMM($param['bar_h']);

			$infos = $this->pdf->{'BARCODE_'.$param['type']}($x, $y, $param['value'], $h, $w);

			// position maximale globale
			$this->maxX = max($this->maxX, $x+$infos[0]);
			$this->maxY = max($this->maxY, $y+$infos[1]);
 			$this->maxH = max($this->maxH, $infos[1]);
 			
 			$this->pdf->setX($x+$infos[0]);
 			
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : BARCODE
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_BARCODE($param)
		{
			// completement inutile
			
			return true;
		}

		/**
		* balise : BOOKMARK
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_BOOKMARK($param)
		{
			$titre = isset($param['title']) ? trim($param['title']) : '';
			$level = isset($param['level']) ? floor($param['level']) : 0;
			
			if ($level<0) $level = 0;
			if ($titre) $this->pdf->Bookmark($titre, $level, -1);
			
			return true;
		}
			
		/**
		* balise : BOOKMARK
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_BOOKMARK($param)
		{
			// completement inutile
			
			return true;
		}
					
		/**
		* balise : WRITE
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_WRITE($param)
		{
			$fill = ($this->style->value['background']['color']!==null && $this->style->value['background']['image']===null);
			
			// récupération du texte à écrire, et conversion
			$txt = $param['txt'];
			$txt = str_replace('&euro;', '€', $txt);

			if ($this->isAfterFloat)
			{
				$txt = preg_replace('/^([\s]*)([^\s])/isU', '$2', $txt);
				$this->isAfterFloat = false;
			}

			$txt = html_entity_decode($txt, ENT_QUOTES, 'ISO-8859-15');

			$txt = str_replace('[[page_cu]]',	$this->pdf->PageNo(),	$txt);
			
			// tailles du texte
			$h	= 1.08*$this->style->value['font-size'];
			$dh	= $h*$this->style->value['mini-decal'];
				
			$w = $this->pdf->GetStringWidth($txt);
			// identification de l'alignement
			$align = 'L';		

			if ($this->style->value['text-align']=='li_right')
			{
				$w = $this->style->value['width'];					
				$align = 'R';
			}

			$curr_max = strlen($txt);						// taille maxi du texte
			$maxX = 0;										// plus grande largeur du texte apres retour à la ligne
			$x = $this->pdf->getX();						// position du texte
			$y = $this->pdf->getY();
			$w = $this->pdf->GetStringWidth($txt);			// largeur du texte
			list($left, $right) = $this->getMargins($y);	// marges autorisees
			$nb = 0;										// nbr de lignes découpées
	
			// tant que ca ne rentre pas sur la ligne et qu'on a du texte => on découpe
			while($x+$w>$right && $x<$right && strlen($txt))
			{
				// liste des mots
				$lst = explode(' ', $txt);
				
				// trouver une phrase qui rentre dans la largeur, en ajoutant les mots 1 à 1
				$i=0;
				$old = '';
				$str = $lst[0];
				while(($x+$this->pdf->GetStringWidth($str))<$right)
				{
					unset($lst[$i]);
					$old = $str;

					$i++;
					$str.= ' '.$lst[$i];
				}
				$str = $old;
				
				// si rien de rentre, et que le premier mot ne rentre de toute facon pas dans une ligne, on le force...
				if ($i==0 && (($left+$this->pdf->GetStringWidth($lst[0]))>=$right))
				{
					$str = $lst[0];
					unset($lst[0]);
				}
				
				// récupération des mots restant, et calcul de la largeur
				$txt = implode(' ', $lst);
				$w = $this->pdf->GetStringWidth($str);

				// ecriture du bout de phrase extrait et qui rentre
				$wc = ($align=='L' ? $w : $this->style->value['width']);
				if ($right - $left<$wc) $wc = $right - $left;
				if (strlen($str)) $this->pdf->Cell($wc, $h+$dh, $str, 0, 0, $align, $fill, $this->inLink);
				$this->maxH = max($this->maxH, $this->style->getLineHeight());
				
				// détermination de la largeur max
				$maxX = max($maxX, $this->pdf->getX());

				// nouvelle position et nouvelle largeur pour la boucle
				$w = $this->pdf->GetStringWidth($txt);
				$y = $this->pdf->getY();
				$x = $this->pdf->getX();

				// si il reste du text à afficher
				if (strlen($txt))
				{
					if ($this->forOneLine)
					{
						$this->maxX = max($this->maxX, $maxX);
						return false;
					}
					
					// retour à la ligne
					$this->o_BR(array('style' => ''), $curr_max - strlen($txt));

					$y = $this->pdf->getY();
					$x = $this->pdf->getX();
					
					// si la prochaine ligne ne rentre pas dans la page => nouvelle page 
					if ($y + $h>$this->pdf->h - $this->pdf->bMargin) $this->setNewPage('', null, $curr_max - strlen($txt));
				
					// ligne suplémentaire. au bout de 1000 : trop long => erreur
					$nb++;
					if ($nb>1000) @HTML2PDF::makeError(2, __FILE__, __LINE__, array($txt, $right-$left, $this->pdf->GetStringWidth($txt))); 

					list($left, $right) = $this->getMargins($y);	// marges autorisees
				}
			}

			// si il reste du text apres découpe, c'est qu'il rentre direct => on l'affiche
			if (strlen($txt))
			{
				$this->pdf->Cell(($align=='L' ? $w : $this->style->value['width']), $h+$dh, $txt, 0, 0, $align, $fill, $this->inLink);
				$this->maxH = max($this->maxH, $this->style->getLineHeight());
			}
			
			// détermination des positions MAX
			$maxX = max($maxX, $this->pdf->getX());
			$maxY = $this->pdf->getY()+$h;

			// position maximale globale
			$this->maxX = max($this->maxX, $maxX);
			$this->maxY = max($this->maxY, $maxY);
			
			return true;
		}

		/**
		* tracer une image
		* 
		* @param	string	nom du fichier source
		* @return	null
		*/	
		function Image($src, $sub_li=false)
		{
			// est-ce que c'est une image ?
			$infos=@GetImageSize($src);

			if (count($infos)<2)
			{
				@HTML2PDF::makeError(6, __FILE__, __LINE__, $src);
				return false;
			}
			
			// récupération des dimensions dans l'unité du PDF
			$wi = $infos[0]/$this->pdf->k;
			$hi = $infos[1]/$this->pdf->k;
			
			// détermination des dimensions d'affichage en fonction du style
			if ($this->style->value['width'] && $this->style->value['height'])
			{
				$w = $this->style->value['width'];
				$h = $this->style->value['height'];
			}
			else if ($this->style->value['width'])
			{
				$w = $this->style->value['width'];
				$h = $hi*$w/$wi;
				
			}
			else if ($this->style->value['height'])
			{
				$h = $this->style->value['height'];
				$w = $wi*$h/$hi;
			}
			else
			{
				$w = 72./96.*$wi;
				$h = 72./96.*$hi;					
			}
			
			// detection du float
			$float = $this->style->getFloat();
			if ($float && $this->maxH) $this->o_BR(array());

			// position d'affichage
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			
			// si l'image ne rentre pas dans la ligne => nouvelle ligne 
			if (!$float && ($x + $w>$this->pdf->w - $this->pdf->rMargin))
			{
				$hnl = $this->style->getLineHeight();
				$hnl = max($this->maxH, $hnl);
				$this->setNewLine($hnl);
				$x = $this->pdf->getX();
				$y = $this->pdf->getY();
			}
						
			// si l'image ne rentre pas dans la page => nouvelle page 
			if ($y + $h>$this->pdf->h - $this->pdf->bMargin)
			{
				$this->setNewPage();
				$x = $this->pdf->getX();
				$y = $this->pdf->getY();
			}

			// correction pour l'affichage d'une puce image
			$hT = 0.80*$this->style->value['font-size'];
			if ($sub_li && $h<$hT)
			{
				$y+=($hT-$h);
			}

			$yc = $y-$this->style->value['margin']['t'];

			// détermination de la position réelle d'affichage en fonction du text-align du parent
			$old = isset($this->style->table[count($this->style->table)-1]) ? $this->style->table[count($this->style->table)-1] : $this->style->value;

			if ( $old['width'])
			{
				$parent_w = $old['width'];
				$parent_x = $x;
			}
			else
			{
 				$parent_w = $this->pdf->w - $this->pdf->lMargin - $this->pdf->rMargin;
				$parent_x = $this->pdf->lMargin;
			}
			
			if ($float)
			{
				list($lx, $rx) = $this->getMargins($yc);
				$parent_x = $lx;
				$parent_w = $rx-$lx;
			}

			if ($parent_w>$w && $float!='left')
			{
				if ($float=='right' || $this->style->value['text-align']=='li_right')	$x = $parent_x + $parent_w - $w-$this->style->value['margin']['r']-$this->style->value['margin']['l'];
			}
			
			// affichage de l'image, et positionnement à la suite
			if (!$this->sub_part && !$this->isSubPart) $this->pdf->Image($src, $x, $y, $w, $h, '', $this->inLink);

			$x-= $this->style->value['margin']['l'];
			$y-= $this->style->value['margin']['t'];
			$w+= $this->style->value['margin']['l'] + $this->style->value['margin']['r'];
			$h+= $this->style->value['margin']['t'] + $this->style->value['margin']['b'];

			if ($float=='left')
			{
				$this->maxX = max($this->maxX, $x+$w);
				$this->maxY = max($this->maxY, $y+$h);

				$this->addMargins($float, $x, $y, $x+$w, $y+$h);

				list($lx, $rx) = $this->getMargins($yc);
				$this->pdf->x = $lx;
				$this->pdf->y = $yc;				
	 		}
			else if ($float=='right')
			{
//				$this->maxX = max($this->maxX, $x+$w);
				$this->maxY = max($this->maxY, $y+$h);

				$this->addMargins($float, $x, $y, $x+$w, $y+$h);

				list($lx, $rx) = $this->getMargins($yc);
				$this->pdf->x = $lx;
				$this->pdf->y = $yc;
			}
			else
			{
				$this->pdf->SetX($x+$w);
				$this->maxX = max($this->maxX, $x+$w);
				$this->maxY = max($this->maxY, $y+$h);
	 			$this->maxH = max($this->maxH, $h);
			}
		}
		
		/**
		* Tracer un rectanble
		* 
		* @param	float	position X
		* @param	float	position Y
		* @param	float	Largeur
		* @param	float	Hauteur
		* @param	array	Tableau de style de définition des borders
		* @param	float	padding - marge intérieur au rectangle => non utile mais on le passe en paramètre
		* @param	float	margin - marge exterieur au rectangle
		* @param	array	Tableau de style de définition du background
		* @return	null
		*/	
		function Rectangle($x, $y, $w, $h, $border, $padding, $margin, $background)
		{
			if ($this->sub_part || $this->isSubPart) return false;
			if ($h===null) return false;
			
			$x+= $margin;
			$y+= $margin;
			$w-= $margin*2;
			$h-= $margin*2;
			
			// récupération des radius
			$radius_h = $border['radius'][0];
			$radius_v = $border['radius'][1];
			
			// verification des coins en radius
			$coin_TL = ($radius_h && $radius_v && $radius_v>$border['t']['width'] && $radius_h>$border['l']['width']) ? array($radius_h, $radius_v) : null;
			$coin_TR = ($radius_h && $radius_v && $radius_v>$border['t']['width'] && $radius_h>$border['r']['width']) ? array($radius_h, $radius_v) : null;
			$coin_BL = ($radius_h && $radius_v && $radius_v>$border['b']['width'] && $radius_h>$border['l']['width']) ? array($radius_h, $radius_v) : null;
			$coin_BR = ($radius_h && $radius_v && $radius_v>$border['b']['width'] && $radius_h>$border['r']['width']) ? array($radius_h, $radius_v) : null;
			


			// traitement de la couleur de fond
			$STYLE = '';
			if ($background['color'])
			{
				$this->pdf->SetFillColor($background['color'][0], $background['color'][1], $background['color'][2]);
				$STYLE.= 'F';		
			}
			
			if ($STYLE)
			{
				$this->pdf->clippingPathOpen($x, $y, $w, $h, $coin_TL,$coin_TR, $coin_BL, $coin_BR);		
				$this->pdf->Rect($x, $y, $w, $h, $STYLE);
				$this->pdf->clippingPathClose();		
			}
			
			// traitement de l'image de fond
			if ($background['image'])
			{
				$i_name		= $background['image'];
				$i_position	= $background['position']!==null	? $background['position']	: array(0, 0);
				$i_repeat	= $background['repeat']!==null		? $background['repeat']		: array(true, true);
				
				// taile du fond (il faut retirer les borders
				$b_x = $x;
				$b_y = $y;
				$b_w = $w;
				$b_h = $h;
				
				if ($border['b']['width']) { $b_h-= $border['b']['width']; }
				if ($border['l']['width']) { $b_w-= $border['l']['width']; $b_x+= $border['l']['width']; }
				if ($border['t']['width']) { $b_h-= $border['t']['width']; $b_y+= $border['t']['width']; }
				if ($border['r']['width']) { $b_w-= $border['r']['width']; }

				// est-ce que c'est une image ?
				$i_infos=@GetImageSize($i_name);
	
				if (count($i_infos)<2)
				{
					@HTML2PDF::makeError(6, __FILE__, __LINE__, $i_name);
					return false;
				}
				
				// récupération des dimensions dans l'unité du PDF
				$i_width	= 72./96.*$i_infos[0]/$this->pdf->k;
				$i_height	= 72./96.*$i_infos[1]/$this->pdf->k;
				
				if ($i_repeat[0]) $i_position[0] = $b_x;
				else if(preg_match('/^([-]?[0-9\.]+)%/isU', $i_position[0], $match)) $i_position[0] = $b_x + $match[1]*($b_w-$i_width)/100;
				else $i_position[0] = $b_x+$i_position[0];
			
				if ($i_repeat[1]) $i_position[1] = $b_y;
				else if(preg_match('/^([-]?[0-9\.]+)%/isU', $i_position[1], $match)) $i_position[1] = $b_y + $match[1]*($b_h-$i_height)/100;
				else $i_position[1] = $b_y+$i_position[1];
				
				$i_x_min = $b_x;
				$i_x_max = $b_x+$b_w;
				$i_y_min = $b_y;
				$i_y_max = $b_y+$b_h;
				
				if (!$i_repeat[0] && !$i_repeat[1])
				{
					$i_x_min = 	$i_position[0]; $i_x_max = 	$i_position[0]+$i_width;
					$i_y_min = 	$i_position[1]; $i_y_max = 	$i_position[1]+$i_height;					
				}
				else if ($i_repeat[0] && !$i_repeat[1])
				{
					$i_y_min = 	$i_position[1]; $i_y_max = 	$i_position[1]+$i_height;					
				}
				elseif (!$i_repeat[0] && $i_repeat[1])
				{
					$i_x_min = 	$i_position[0]; $i_x_max = 	$i_position[0]+$i_width;
				}
				
				if (is_array($coin_TL)) { $coin_TL[0]-= $border['l']['width']; $coin_TL[1]-= $border['t']['width']; }
				if (is_array($coin_TR)) { $coin_TR[0]-= $border['r']['width']; $coin_TR[1]-= $border['t']['width']; }
				if (is_array($coin_BL)) { $coin_BL[0]-= $border['l']['width']; $coin_BL[1]-= $border['b']['width']; }
				if (is_array($coin_BR)) { $coin_BR[0]-= $border['r']['width']; $coin_BR[1]-= $border['b']['width']; }
				
				$this->pdf->clippingPathOpen($b_x, $b_y, $b_w, $b_h, $coin_TL, $coin_TR, $coin_BL, $coin_BR);		
				for ($i_y=$i_y_min; $i_y<$i_y_max; $i_y+=$i_height)
				{
					for ($i_x=$i_x_min; $i_x<$i_x_max; $i_x+=$i_width)
					{
						$c_x = null;
						$c_y = null;
						$c_w = $i_width;
						$c_h = $i_height;
						if ($i_y_max-$i_y<$i_height)
						{
							$c_x = $i_x;
							$c_y = $i_y;
							$c_h = $i_y_max-$i_y;
						}
						if ($i_x_max-$i_x<$i_width)
						{
							$c_x = $i_x;
							$c_y = $i_y;
							$c_w = $i_x_max-$i_x;
						}

						$this->pdf->Image($i_name, $i_x, $i_y, $i_width, $i_height, '', '');						
					}
				}
				$this->pdf->clippingPathClose();
			}
						
			$x-= 0.01;
			$y-= 0.01;
			$w+= 0.02;
			$h+= 0.02;
			if ($border['b']['width']) $border['b']['width']+= 0.02;
			if ($border['l']['width']) $border['l']['width']+= 0.02;
			if ($border['t']['width']) $border['t']['width']+= 0.02;
			if ($border['r']['width']) $border['r']['width']+= 0.02;
			
			if ($border['b']['width'] && $border['b']['color'][0]!==null)
			{
				$pt = array();
				$pt[] = $x+$w;							$pt[] = $y+$h;
				$pt[] = $x+$w-$border['r']['width'];	$pt[] = $y+$h;
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$h;
				$pt[] = $x;								$pt[] = $y+$h;
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$h-$border['b']['width'];
				$pt[] = $x+$w-$border['r']['width'];	$pt[] = $y+$h-$border['b']['width'];
				
				$bord = 3;			
				if (is_array($coin_BL))
				{
					$bord-=2;
					$pt[4] += $radius_h-$border['l']['width'];
					$pt[8] += $radius_h-$border['l']['width'];
					unset($pt[6]);unset($pt[7]);
				}
				if (is_array($coin_BR))
				{
					$courbe = array();
					$courbe[] = $x+$w; 							$courbe[] = $y+$h-$radius_v;
					$courbe[] = $x+$w-$radius_h; 				$courbe[] = $y+$h;
					$courbe[] = $x+$w-$border['r']['width'];	$courbe[] = $y+$h-$radius_v;
					$courbe[] = $x+$w-$radius_h;				$courbe[] = $y+$h-$border['b']['width'];
					$courbe[] = $x+$w-$radius_h;				$courbe[] = $y+$h-$radius_v;
					$this->Courbe($courbe, $border['b']['color']);

					$bord-=1;
					$pt[2] -= $radius_h-$border['r']['width'];
					$pt[10]-= $radius_h-$border['r']['width'];
					unset($pt[0]);unset($pt[1]);
					
				}

				$pt = array_values($pt);
				$this->Line($pt, $border['b']['color'], $border['b']['type'], $border['b']['width'], $bord);
			}

			if ($border['l']['width'] && $border['l']['color'][0]!==null)
			{
				$pt = array();
				$pt[] = $x;								$pt[] = $y+$h;
				$pt[] = $x;								$pt[] = $y+$h-$border['b']['width'];
				$pt[] = $x;								$pt[] = $y+$border['t']['width'];
				$pt[] = $x;								$pt[] = $y;
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$border['t']['width'];
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$h-$border['b']['width'];

				$bord = 3;			
				if (is_array($coin_BL))
				{
					$courbe = array();
					$courbe[] = $x+$radius_h; 				$courbe[] = $y+$h;
					$courbe[] = $x; 						$courbe[] = $y+$h-$radius_v;
					$courbe[] = $x+$radius_h;				$courbe[] = $y+$h-$border['b']['width'];
					$courbe[] = $x+$border['l']['width'];	$courbe[] = $y+$h-$radius_v;
					$courbe[] = $x+$radius_h;				$courbe[] = $y+$h-$radius_v;
					$this->Courbe($courbe, $border['l']['color']);
					
					$bord-=1;
					$pt[3] -= $radius_v-$border['b']['width'];
					$pt[11]-= $radius_v-$border['b']['width'];	
					unset($pt[0]);unset($pt[1]);
				}
				if (is_array($coin_TL))
				{
					$bord-=2;
					$pt[5] += $radius_v-$border['t']['width'];
					$pt[9] += $radius_v-$border['t']['width'];
					unset($pt[6]);unset($pt[7]);
				}
				
				$pt = array_values($pt);
				$this->Line($pt, $border['l']['color'], $border['l']['type'], $border['l']['width'], $bord);
			}
			
			if ($border['t']['width'] && $border['t']['color'][0]!==null)
			{
				$pt = array();
				$pt[] = $x;								$pt[] = $y;
				$pt[] = $x+$border['l']['width'];		$pt[] = $y;
				$pt[] = $x+$w-$border['r']['width'];	$pt[] = $y;
				$pt[] = $x+$w;							$pt[] = $y;
				$pt[] = $x+$w-$border['r']['width'];	$pt[] = $y+$border['t']['width'];
				$pt[] = $x+$border['l']['width'];		$pt[] = $y+$border['t']['width'];

				$bord = 3;			
				if (is_array($coin_TL))
				{
					$courbe = array();
					$courbe[] = $x; 						$courbe[] = $y+$radius_v;
					$courbe[] = $x+$radius_h; 				$courbe[] = $y;
					$courbe[] = $x+$border['l']['width'];	$courbe[] = $y+$radius_v;
					$courbe[] = $x+$radius_h;				$courbe[] = $y+$border['t']['width'];
					$courbe[] = $x+$radius_h;				$courbe[] = $y+$radius_v;
					$this->Courbe($courbe, $border['t']['color']);

					$bord-=1;
					$pt[2] += $radius_h-$border['l']['width'];
					$pt[10]+= $radius_h-$border['l']['width'];
					unset($pt[0]);unset($pt[1]);
				}
				if (is_array($coin_TR))
				{
					$bord-=2;
					$pt[4] -= $radius_h-$border['r']['width'];
					$pt[8] -= $radius_h-$border['r']['width'];
					unset($pt[6]);unset($pt[7]);
				}
				
				$pt = array_values($pt);
				$this->Line($pt, $border['t']['color'], $border['t']['type'], $border['t']['width'], $bord);
			}

			if ($border['r']['width'] && $border['r']['color'][0]!==null)
			{
				$pt = array();
				$pt[] = $x+$w;								$pt[] = $y;
				$pt[] = $x+$w;								$pt[] = $y+$border['t']['width'];
				$pt[] = $x+$w;								$pt[] = $y+$h-$border['b']['width'];
				$pt[] = $x+$w;								$pt[] = $y+$h;
				$pt[] = $x+$w-$border['r']['width'];		$pt[] = $y+$h-$border['b']['width'];
				$pt[] = $x+$w-$border['r']['width'];		$pt[] = $y+$border['t']['width'];
				
				$bord = 3;			
				if (is_array($coin_TR))
				{
					$courbe = array();
					$courbe[] = $x+$w-$radius_h; 				$courbe[] = $y;
					$courbe[] = $x+$w; 							$courbe[] = $y+$radius_v;
					$courbe[] = $x+$w-$radius_h;				$courbe[] = $y+$border['t']['width'];
					$courbe[] = $x+$w-$border['r']['width'];	$courbe[] = $y+$radius_v;
					$courbe[] = $x+$w-$radius_h;				$courbe[] = $y+$radius_v;
					$this->Courbe($courbe, $border['r']['color']);

					$bord-=1;
					$pt[3] += $radius_v-$border['t']['width'];
					$pt[11]+= $radius_v-$border['t']['width'];
					unset($pt[0]);unset($pt[1]);
				}
				if (is_array($coin_BR))
				{
					$bord-=2;
					$pt[5] -= $radius_v-$border['b']['width'];
					$pt[9] -= $radius_v-$border['b']['width'];
					unset($pt[6]);unset($pt[7]);
				}
				
				$pt = array_values($pt);
				$this->Line($pt, $border['r']['color'], $border['r']['type'], $border['r']['width'], $bord);
			}

			if ($background) $this->pdf->SetFillColor($background['color'][0], $background['color'][1], $background['color'][2]);
		}
		
		function Courbe($pt, $color)
		{
			$this->pdf->SetFillColor($color[0], $color[1], $color[2]);
			
			$this->pdf->drawCourbe($pt[0], $pt[1], $pt[2], $pt[3], $pt[4], $pt[5], $pt[6], $pt[7], $pt[8], $pt[9]);
		}
		
		/**
		* Tracer une ligne epaisse défini par ses points avec des extreminites en biseau
		* 
		* @param	array	liste des points definissant le tour de la ligne
		* @param	float	couleur RVB
		* @param	string	type de ligne
		* @param	float	largeur de la ligne
		* @return	null
		*/	
		function Line($pt, $color, $type, $width, $bord=3)
		{
			$this->pdf->SetFillColor($color[0], $color[1], $color[2]);
			if ($type=='dashed' || $type=='dotted')
			{
				if ($bord==1)
				{
					$tmp = array(); $tmp[]=$pt[0]; $tmp[]=$pt[1]; $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[8]; $tmp[]=$pt[9];
					$this->pdf->Polygon($tmp, 'F');
					
					$tmp = array(); $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[4]; $tmp[]=$pt[5]; $tmp[]=$pt[6]; $tmp[]=$pt[7]; $tmp[]=$pt[8]; $tmp[]=$pt[9];
					$pt = $tmp;
				}
				else if ($bord==2)
				{
					$tmp = array(); $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[4]; $tmp[]=$pt[5]; $tmp[]=$pt[6]; $tmp[]=$pt[7];
					$this->pdf->Polygon($tmp, 'F');
					
					$tmp = array(); $tmp[]=$pt[0]; $tmp[]=$pt[1]; $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[6]; $tmp[]=$pt[7]; $tmp[]=$pt[8]; $tmp[]=$pt[9];
					$pt = $tmp;					
				}
				else if ($bord==3)
				{
					$tmp = array(); $tmp[]=$pt[0]; $tmp[]=$pt[1]; $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[10]; $tmp[]=$pt[11];
					$this->pdf->Polygon($tmp, 'F');
	
					$tmp = array(); $tmp[]=$pt[4]; $tmp[]=$pt[5]; $tmp[]=$pt[6]; $tmp[]=$pt[7]; $tmp[]=$pt[8]; $tmp[]=$pt[9];
					$this->pdf->Polygon($tmp, 'F');
					
					$tmp = array(); $tmp[]=$pt[2]; $tmp[]=$pt[3]; $tmp[]=$pt[4]; $tmp[]=$pt[5]; $tmp[]=$pt[8]; $tmp[]=$pt[9]; $tmp[]=$pt[10]; $tmp[]=$pt[11];
					$pt = $tmp;
				}
				
				if ($pt[2]==$pt[0])
				{
					$l = abs(($pt[3]-$pt[1])*0.5);
					$px = 0;
					$py = $width;
					$x1 = $pt[0]; $y1 = ($pt[3]+$pt[1])*0.5;
					$x2 = $pt[6]; $y2 = ($pt[7]+$pt[5])*0.5;
				}
				else
				{
					$l = abs(($pt[2]-$pt[0])*0.5);
					$px = $width;
					$py = 0;					
					$x1 = ($pt[2]+$pt[0])*0.5; $y1 = $pt[1];
					$x2 = ($pt[6]+$pt[4])*0.5; $y2 = $pt[7];
				}
				if ($type=='dashed')
				{
					$px = $px*3.;
					$py = $py*3.;
				}
				$mode = ($l/($px+$py)<.5);
				
				for($i=0; $l-($px+$py)*($i-0.5)>0; $i++)
				{
					if (($i%2)==$mode)
					{
						$j = $i-0.5;
						$lx1 = $px*($j);	if ($lx1<-$l)	$lx1 =-$l;
						$ly1 = $py*($j);	if ($ly1<-$l)	$ly1 =-$l;
						$lx2 = $px*($j+1);	if ($lx2>$l)	$lx2 = $l;
						$ly2 = $py*($j+1);	if ($ly2>$l)	$ly2 = $l;
						
						$tmp = array();
						$tmp[] = $x1+$lx1;	$tmp[] = $y1+$ly1;	
						$tmp[] = $x1+$lx2; 	$tmp[] = $y1+$ly2;	
						$tmp[] = $x2+$lx2; 	$tmp[] = $y2+$ly2;	
						$tmp[] = $x2+$lx1;	$tmp[] = $y2+$ly1;
						$this->pdf->Polygon($tmp, 'F');	

						if ($j>0)
						{
							$tmp = array();
							$tmp[] = $x1-$lx1;	$tmp[] = $y1-$ly1;	
							$tmp[] = $x1-$lx2; 	$tmp[] = $y1-$ly2;	
							$tmp[] = $x2-$lx2; 	$tmp[] = $y2-$ly2;	
							$tmp[] = $x2-$lx1;	$tmp[] = $y2-$ly1;
							$this->pdf->Polygon($tmp, 'F');	
						}
					}
				}		
			}
			else if ($type=='solid')
			{
				$this->pdf->Polygon($pt, 'F');
			}
		}
	
		/**
		* balise : BR
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @param	integer	position reelle courante si saut de ligne pendant l'ecriture d'un texte 
		* @return	null
		*/	
		function o_BR($param, $curr = null)
		{
			if ($this->forOneLine) return false;
			
			$h = $this->style->getLineHeight();
			$h = max($this->maxH, $h);
			$y = $this->pdf->getY();

			// si la ligne est vide, la position maximale n'a pas été mise à jour => on la met à jour
			if ($this->maxH==0) $this->maxY = max($this->maxY, $y+$h);
			
			// si le saut de ligne rentre => on le prend en compte, sinon nouvelle page
			if ($y+$h<$this->pdf->h - $this->pdf->bMargin) $this->setNewLine($h, $curr);
			else $this->setNewPage('', null, $curr);
			
			$this->maxH = 0;
			
			return true;
		}
		
		/**
		* balise : HR
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_HR($param)
		{
			if ($this->forOneLine) return false;
			$old_align = $this->style->value['text-align'];
			$this->style->value['text-align'] = 'left';

			if ($this->maxH) $this->o_BR($param);
			
			$f_size = $this->style->value['font-size'];
			$this->style->value['font-size']=$f_size*0.5; $this->o_BR($param);
			$this->style->value['font-size']=0;
			
			$param['style']['width'] = '100%';
			
			$this->style->save();
			$this->style->value['height']=$this->style->ConvertToMM('1mm');

			$this->style->analyse('hr', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			$h = $this->style->value['height'];
			if ($h)		$h-= $this->style->value['border']['t']['width']+$this->style->value['border']['b']['width'];
			if ($h<=0)	$h = $this->style->value['border']['t']['width']+$this->style->value['border']['b']['width'];

			$this->Rectangle($this->pdf->x, $this->pdf->y, $this->style->value['width'], $h, $this->style->value['border'], 0, 0, $this->style->value['background']);
			$this->maxH = $h;

			$this->style->load();
			$this->style->FontSet();
			
			$this->o_BR($param);

			$this->style->value['font-size']=$f_size*0.5; $this->o_BR($param);
			$this->style->value['font-size']=$f_size;

			$this->style->value['text-align'] = $old_align;
			$this->setNewPositionForNewLine();
						
			return true;
		}

		/**
		* balise : B
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_B($param, $other = 'b')
		{
			$this->style->save();
			$this->style->value['font-bold'] = true;
			$this->style->analyse($other, $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}
		function o_STRONG($param) { return $this->o_B($param, 'strong'); }
				
		/**
		* balise : B
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_B($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		function c_STRONG($param) { return $this->c_B($param); }
		
		/**
		* balise : I
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_I($param, $other = 'i')
		{
			$this->style->save();
			$this->style->value['font-italic'] = true;
			$this->style->analyse($other, $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}	
		function o_ADDRESS($param)	{ return $this->o_I($param, 'address');	}
		function o_CITE($param)		{ return $this->o_I($param, 'cite');		}
		function o_EM($param)		{ return $this->o_I($param, 'em');			}
		function o_SAMP($param)		{ return $this->o_I($param, 'samp');		}

		/**
		* balise : I
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_I($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}	
		function c_ADDRESS($param)	{ return $this->c_I($param); }
		function c_CITE($param)		{ return $this->c_I($param); }
		function c_EM($param) 		{ return $this->c_I($param); }
		function c_SAMP($param)		{ return $this->c_I($param); }

		/**
		* balise : S
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_S($param)
		{
			$this->style->save();
			$this->style->value['font-linethrough'] = true;
			$this->style->analyse('s', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}	

		/**
		* balise : S
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_S($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : U
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_U($param)
		{
			$this->style->save();
			$this->style->value['font-underline'] = true;
			$this->style->analyse('u', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}	

		/**
		* balise : U
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_U($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : A
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_A($param)
		{
			$this->inLink = str_replace('&amp;', '&', isset($param['href']) ? $param['href'] : '');
			
			if (isset($param['name']))
			{
				$nom = 	$param['name'];
				if (!isset($this->lstAncre[$nom])) $this->lstAncre[$nom] = array($this->pdf->AddLink(), false);
				
				if (!$this->lstAncre[$nom][1])
				{
					$this->lstAncre[$nom][1] = true;
					$this->pdf->SetLink($this->lstAncre[$nom][0], -1, -1);
				}
			}
			
			if (preg_match('/^#([^#]+)$/isU', $this->inLink, $match))
			{
				$nom = $match[1];
				if (!isset($this->lstAncre[$nom])) $this->lstAncre[$nom] = array($this->pdf->AddLink(), false);
				
				$this->inLink = $this->lstAncre[$nom][0];
			}
			
			$this->style->save();
			$this->style->value['font-underline'] = true;
			$this->style->value['color'] = array(20, 20, 250);
			$this->style->analyse('a', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;		
		}

		/**
		* balise : A
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_A($param)
		{
			$this->inLink	= '';
			$this->style->load();
			$this->style->FontSet();			
			
			return true;
		}

		/**
		* balise : H1
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_H1($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->save();
			$this->style->value['font-bold'] = true;
			$this->style->value['font-size'] = $this->style->ConvertToMM('28px');
			$this->style->analyse('h1', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			$this->setNewPositionForNewLine();
			
			return true;
		}
		
		/**
		* balise : H1
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_H1($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->load();
			$this->style->FontSet();
			$this->o_BR(array());
			
			return true;
		}

		/**
		* balise : H2
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_H2($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->save();
			$this->style->value['font-bold'] = true;
			$this->style->value['font-size'] = $this->style->ConvertToMM('24px');
			$this->style->analyse('h2', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			$this->setNewPositionForNewLine();
			
			return true;
		}
		
		/**
		* balise : H2
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_H2($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->load();
			$this->style->FontSet();
			$this->o_BR(array());
			
			return true;
		}		

		/**
		* balise : H3
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_H3($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->save();
			$this->style->value['font-bold'] = true;
			$this->style->value['font-size'] = $this->style->ConvertToMM('20px');
			$this->style->analyse('h3', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			$this->setNewPositionForNewLine();
			
			return true;
		}
		
		/**
		* balise : H3
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_H3($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->load();
			$this->style->FontSet();
			$this->o_BR(array());
			
			return true;
		}

		/**
		* balise : H4
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_H4($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->save();
			$this->style->value['font-bold'] = true;			
			$this->style->value['font-size'] = $this->style->ConvertToMM('16px');
			$this->style->analyse('h4', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			$this->setNewPositionForNewLine();
			
			return true;
		}
		
		/**
		* balise : H4
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_H4($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->load();
			$this->style->FontSet();
			$this->o_BR(array());
			
			return true;
		}

		/**
		* balise : H5
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_H5($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->save();
			$this->style->value['font-bold'] = true;
			$this->style->value['font-size'] = $this->style->ConvertToMM('12px');
			$this->style->analyse('h5', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			$this->setNewPositionForNewLine();
			
			return true;
		}
		
		/**
		* balise : H5
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_H5($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->load();
			$this->style->FontSet();
			$this->o_BR(array());
			
			return true;
		}

		/**
		* balise : H6
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_H6($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->save();
			$this->style->value['font-bold'] = true;			
			$this->style->value['font-size'] = $this->style->ConvertToMM('9px');
			$this->style->analyse('h6', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			$this->setNewPositionForNewLine();
			
			return true;
		}
		
		/**
		* balise : H6
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_H6($param)
		{
			if ($this->forOneLine) return false;

			$this->o_BR(array());
			$this->style->load();
			$this->style->FontSet();
			$this->o_BR(array());
			
			return true;
		}

		/**
		* balise : SPAN
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_SPAN($param, $other = 'span')
		{
			$this->style->save();
			$this->style->analyse($other, $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();		
			
			return true;
		}	
		function o_FONT($param)		{ return $this->o_SPAN($param, 'font');	}

		/**
		* balise : SPAN
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_SPAN($param)
		{
			$this->style->restorePosition($this->pdf->x, $this->pdf->y);
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		function c_FONT($param)		{ return $this->c_SPAN($param); }


		/**
		* balise : P
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_P($param)
		{
			if ($this->forOneLine) return false;

			if (!in_array($this->previousCall, array('c_P', 'c_UL')))
			{
				if ($this->maxH) $this->o_BR(array());
				$this->o_BR(array());
			}
			
			$this->style->save();
			$this->style->analyse('p', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			if ($this->style->value['text-indent']>0) $this->pdf->x+= $this->style->value['text-indent'];
			
			return true;
		}
		
		/**
		* balise : P
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_P($param)
		{
			if ($this->forOneLine) return false;

			if ($this->maxH) $this->o_BR(array());
			$this->o_BR(array());
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : PRE
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_PRE($param, $other = 'pre')
		{
			if ($other=='pre' && $this->maxH) $this->o_BR(array());
			
			$this->style->save();
			$this->style->value['font-family']	= 'courier';
			$this->style->analyse($other, $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();

			if ($other=='pre') return $this->o_DIV($param, $other);
			
			return true;
		}
		function o_CODE($param) { return $this->o_PRE($param, 'code'); }
		
		/**
		* balise : PRE
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_PRE($param, $other = 'pre')
		{
			if ($other=='pre')
			{
				if ($this->forOneLine) return false;

				$this->c_DIV($param);
				$this->o_BR(array());
			}
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		function c_CODE($param) { return $this->c_PRE($param, 'code'); }
				
		/**
		* balise : BIG
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_BIG($param)
		{
			$this->style->save();
			$this->style->value['mini-decal']-= $this->style->value['mini-size']*0.2;
			$this->style->value['mini-size'] *= 1.2;
			$this->style->analyse('big', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : BIG
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_BIG($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : SMALL
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_SMALL($param)
		{
			$this->style->save();
			$this->style->value['mini-decal']+= $this->style->value['mini-size']*0.18;
			$this->style->value['mini-size'] *= 0.82;
			$this->style->analyse('small', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}
		 
		/**
		* balise : SMALL
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_SMALL($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}


		/**
		* balise : SUP
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_SUP($param)
		{
			$this->style->save();
			$this->style->value['mini-decal']-= $this->style->value['mini-size']*0.25;
			$this->style->value['mini-size'] *= 0.75;
			$this->style->analyse('sup', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}
		 
		/**
		* balise : SUP
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_SUP($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : SUB
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_SUB($param)
		{
			$this->style->save();
			$this->style->value['mini-decal']+= $this->style->value['mini-size']*0.25;
			$this->style->value['mini-size'] *= 0.75;
			$this->style->analyse('sub', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			$this->inSub = 1;
			
			return true;
		}
		 
		/**
		* balise : SUB
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_SUB($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : UL
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_UL($param, $other = 'ul')
		{
			if ($this->forOneLine) return false;

			if (!in_array($this->previousCall, array('c_P', 'c_UL')))
			{
				if ($this->maxH) $this->o_BR(array());
				if (!count($this->defLIST)) $this->o_BR(array());
			}
			
			if (!isset($param['style']['width'])) $param['allwidth'] = true;
			$param['cellspacing'] = 0;

			// une liste est traitée comme un tableau
			$this->o_TABLE($param, $other);

			// ajouter un niveau de liste
			$this->listeAddLevel($other, $this->style->value['list-style-type'], $this->style->value['list-style-image']);
			
			return true;
		}
		function o_OL($param) { return $this->o_UL($param, 'ol'); }	
		
		/**
		* balise : UL
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/
		function c_UL($param)
		{
			if ($this->forOneLine) return false;

			// fin du tableau
			$this->c_TABLE($param);
			
			// enlever un niveau de liste
			$this->listeDelLevel();

			if (!$this->sub_part)
			{
				if (!count($this->defLIST)) $this->o_BR(array());
			}
			
			return true;
		}
		function c_OL($param) { return $this->c_UL($param); }

		/**
		* balise : LI
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/
		function o_LI($param)
		{
			if ($this->forOneLine) return false;

			// ajouter une puce au niveau actuel
			$this->listeAddLi();

			if (!isset($param['style']['width'])) $param['style']['width'] = '100%';

			// preparation du style de la puce
			$paramPUCE = $param;
			
			$inf = $this->listeGetLi();
			if ($inf[0])
			{
				$paramPUCE['style']['font-family']		= $inf[0];
				$paramPUCE['style']['text-align']		= 'li_right';
				$paramPUCE['style']['vertical-align']	= 'top';
				$paramPUCE['style']['width']			= $this->listeGetWidth();
				$paramPUCE['style']['padding-right']	= $this->listeGetPadding();
				$paramPUCE['txt'] = $inf[2];
			}
			else
			{
				$paramPUCE['style']['text-align']		= 'li_right';
				$paramPUCE['style']['vertical-align']	= 'top';
				$paramPUCE['style']['width']			= $this->listeGetWidth();
				$paramPUCE['style']['padding-right']	= $this->listeGetPadding();
				$paramPUCE['src'] = $inf[2];
				$paramPUCE['sub_li'] = true;
			}
			
			// nouvelle ligne
			$this->o_TR($param, 'li');

			$this->style->save();
			
			if ($inf[1])
			{
				$this->style->value['mini-decal']+= $this->style->value['mini-size']*0.25;
				$this->style->value['mini-size'] *= 0.75;
			}
			
			// si on est dans un sub_html => preparation, sinon affichage classique
			if ($this->sub_part)
			{
				// TD pour la puce
				$tmp_pos = $this->temp_pos;
				$tmp_lst1 = $this->parsing->code[$tmp_pos+1];
				$tmp_lst2 = $this->parsing->code[$tmp_pos+2];
				$this->parsing->code[$tmp_pos+1] = array();
				$this->parsing->code[$tmp_pos+1]['name']	= (isset($paramPUCE['src'])) ? 'img' : 'write';
				$this->parsing->code[$tmp_pos+1]['param']	= $paramPUCE; unset($this->parsing->code[$tmp_pos+1]['param']['style']['width']);
				$this->parsing->code[$tmp_pos+1]['close']	= 0;
				$this->parsing->code[$tmp_pos+2] = array();
				$this->parsing->code[$tmp_pos+2]['name']	= 'li';
				$this->parsing->code[$tmp_pos+2]['param']	= $paramPUCE;
				$this->parsing->code[$tmp_pos+2]['close']	= 1;
				$this->o_TD($paramPUCE, 'li_sub');
				$this->c_TD($param);
				$this->temp_pos = $tmp_pos;
				$this->parsing->code[$tmp_pos+1] = $tmp_lst1;
				$this->parsing->code[$tmp_pos+2] = $tmp_lst2;
			}
			else
			{
				// TD pour la puce
				$this->o_TD($paramPUCE, 'li_sub');
				unset($paramPUCE['style']['width']);
				if (isset($paramPUCE['src']))	$this->o_IMG($paramPUCE);
				else							$this->o_WRITE($paramPUCE);
				$this->c_TD($paramPUCE);
			}
			$this->style->load();

				
			// td pour le contenu
			$this->o_TD($param, 'li');
			
			return true;
		}

		/**
		* balise : LI
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/
		function c_LI($param)
		{
			if ($this->forOneLine) return false;

			// fin du contenu
			$this->c_TD($param, 'li');
			
			// fin de la ligne
			$this->c_TR($param, 'li');
			
			return true;
		}
		
		/**
		* balise : TBODY
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_TBODY($param)
		{
			if ($this->forOneLine) return false;

			$this->style->save();
			$this->style->analyse('tbody', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}	

		/**
		* balise : TBODY
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_TBODY($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : THEAD
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_THEAD($param)
		{
			if ($this->forOneLine) return false;

			global $HTML2PDF_TABLEAU;
			
			$this->style->save();
			$this->style->analyse('thead', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			// si on est en mode sub_html : sauvegarde du numéro du TR 
			if ($this->sub_part)
			{
				$HTML2PDF_TABLEAU[$param['num']]['thead']['tr'][0] = $HTML2PDF_TABLEAU[$param['num']]['tr_curr'];
				$HTML2PDF_TABLEAU[$param['num']]['thead']['code'] = array(); 
				for($pos=$this->temp_pos; $pos<count($this->parsing->code); $pos++)
				{
					$todo = $this->parsing->code[$pos];
					if (strtolower($todo['name'])=='thead') $todo['name'] = 'thead_sub';
					$HTML2PDF_TABLEAU[$param['num']]['thead']['code'][] = $todo;
					if (strtolower($todo['name'])=='thead_sub' && $todo['close']) break;
				}
			}
			else
			{
				$res = $this->parsing->getLevel($this->parse_pos);
				$this->parse_pos = $res[0]-1;
				$HTML2PDF_TABLEAU[$param['num']]['tr_curr']+= count($HTML2PDF_TABLEAU[$param['num']]['thead']['tr']);
			}
			
			return true;
		}	

		/**
		* balise : THEAD
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_THEAD($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();

			// si on est en mode sub_html : sauvegarde du numéro du TR 
			if ($this->sub_part)
			{
				global $HTML2PDF_TABLEAU;				
				$min = $HTML2PDF_TABLEAU[$param['num']]['thead']['tr'][0];
				$max = $HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1;				
				$HTML2PDF_TABLEAU[$param['num']]['thead']['tr'] = range($min, $max);
			}
			
			return true;
		}

		/**
		* balise : TFOOT
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_TFOOT($param)
		{
			if ($this->forOneLine) return false;

			global $HTML2PDF_TABLEAU; 		

			$this->style->save();
			$this->style->analyse('tfoot', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			// si on est en mode sub_html : sauvegarde du numéro du TR 
			if ($this->sub_part)
			{
				$HTML2PDF_TABLEAU[$param['num']]['tfoot']['tr'][0] = $HTML2PDF_TABLEAU[$param['num']]['tr_curr'];
				$HTML2PDF_TABLEAU[$param['num']]['tfoot']['code'] = array(); 
				for($pos=$this->temp_pos; $pos<count($this->parsing->code); $pos++)
				{
					$todo = $this->parsing->code[$pos];
					if (strtolower($todo['name'])=='tfoot') $todo['name'] = 'tfoot_sub';
					$HTML2PDF_TABLEAU[$param['num']]['tfoot']['code'][] = $todo;
					if (strtolower($todo['name'])=='tfoot_sub' && $todo['close']) break;
				}
			}
			else
			{
				$res = $this->parsing->getLevel($this->parse_pos+1);
				$this->parse_pos = $res[0];
				$HTML2PDF_TABLEAU[$param['num']]['tr_curr']+= count($HTML2PDF_TABLEAU[$param['num']]['tfoot']['tr']);
			}
			
			return true;
		}	

		/**
		* balise : TFOOT
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_TFOOT($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();
			
			// si on est en mode sub_html : sauvegarde du numéro du TR 
			if ($this->sub_part)
			{
				global $HTML2PDF_TABLEAU;
				
				$min = $HTML2PDF_TABLEAU[$param['num']]['tfoot']['tr'][0];
				$max = $HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1;				
				$HTML2PDF_TABLEAU[$param['num']]['tfoot']['tr'] = range($min, $max);
			}
			
			return true;
		}

		/**
		* balise : THEAD_SUB
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_THEAD_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->save();
			$this->style->analyse('thead', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : THEAD_SUB
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_THEAD_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}

		/**
		* balise : TFOOT_SUB
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_TFOOT_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->save();
			$this->style->analyse('tfoot', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : TFOOT_SUB
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_TFOOT_SUB($param)
		{
			if ($this->forOneLine) return false;

			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
				
		/**
		* balise : FORM
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_FORM($param)
		{
			$this->style->save();
			$this->style->analyse('form', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			return true;
		}	

		/**
		* balise : FORM
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_FORM($param)
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		
		/**
		* balise : TABLE
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_TABLE($param, $other = 'table')
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			// utilisation du tableau des paramétres des tables
			global $HTML2PDF_TABLEAU; 

			$align_object = isset($param['align']) ? strtolower($param['align']) : 'left';
			if (isset($param['align'])) unset($param['align']);
			if (!in_array($align_object, array('left', 'center', 'right'))) $align_object = 'left';
			
			// lecture et initialisation du style
			$this->style->save();
			$this->style->analyse($other, $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();

			if ($this->style->value['margin-auto']) $align_object = 'center';

			// est-on en collapse
			$collapse = false;			
			if ($other=='table')
				$collapse = isset($this->style->value['border']['collapse']) ? $this->style->value['border']['collapse'] : false;

			// si oui il faut adapté les borders
			if ($collapse)
			{
				$param['style']['border'] = 'none';
				$param['cellspacing'] = 0;
				$none = $this->style->readBorder('none');
				$this->style->value['border']['t'] = $none;
				$this->style->value['border']['r'] = $none;
				$this->style->value['border']['b'] = $none;
				$this->style->value['border']['l'] = $none;
			}				
			
			// si on est en mode sub_html : initialisation des dimensions et autres 
			if ($this->sub_part)
			{
				$HTML2PDF_TABLEAU[$param['num']] = array();
				$HTML2PDF_TABLEAU[$param['num']]['cellpadding']	= $this->style->ConvertToMM(isset($param['cellpadding']) ? $param['cellpadding'] : '1px'); // cellpadding du tableau
				$HTML2PDF_TABLEAU[$param['num']]['cellspacing']	= $this->style->ConvertToMM(isset($param['cellspacing']) ? $param['cellspacing'] : '2px'); // cellspacing du tableau
				$HTML2PDF_TABLEAU[$param['num']]['cases']		= array();				// liste des propriétés des cases
				$HTML2PDF_TABLEAU[$param['num']]['td_curr']		= 0;					// colonne courante
				$HTML2PDF_TABLEAU[$param['num']]['tr_curr']		= 0;					// ligne courante
				$HTML2PDF_TABLEAU[$param['num']]['curr_x']		= $this->pdf->getX();	// position courante X
				$HTML2PDF_TABLEAU[$param['num']]['curr_y']		= $this->pdf->getY();	// position courante Y
				$HTML2PDF_TABLEAU[$param['num']]['width']		= 0;					// largeur globale
				$HTML2PDF_TABLEAU[$param['num']]['height']		= 0;					// hauteur globale
				$HTML2PDF_TABLEAU[$param['num']]['align']		= $align_object;
				$HTML2PDF_TABLEAU[$param['num']]['marge']		= array();
				$HTML2PDF_TABLEAU[$param['num']]['marge']['t']	= $this->style->value['padding']['t']+$this->style->value['border']['t']['width']+$HTML2PDF_TABLEAU[$param['num']]['cellspacing']*0.5;
				$HTML2PDF_TABLEAU[$param['num']]['marge']['r']	= $this->style->value['padding']['r']+$this->style->value['border']['r']['width']+$HTML2PDF_TABLEAU[$param['num']]['cellspacing']*0.5;
				$HTML2PDF_TABLEAU[$param['num']]['marge']['b']	= $this->style->value['padding']['b']+$this->style->value['border']['b']['width']+$HTML2PDF_TABLEAU[$param['num']]['cellspacing']*0.5;
				$HTML2PDF_TABLEAU[$param['num']]['marge']['l']	= $this->style->value['padding']['l']+$this->style->value['border']['l']['width']+$HTML2PDF_TABLEAU[$param['num']]['cellspacing']*0.5;
				$HTML2PDF_TABLEAU[$param['num']]['page']		= 0;					// nombre de pages
				$HTML2PDF_TABLEAU[$param['num']]['new_page']	= true;					// nouvelle page pour le TR courant
				$HTML2PDF_TABLEAU[$param['num']]['style_value'] = null;					// style du tableau
				$HTML2PDF_TABLEAU[$param['num']]['thead']		= array();				// infos sur le thead
				$HTML2PDF_TABLEAU[$param['num']]['tfoot']		= array();				// infos sur le tfoot
				$HTML2PDF_TABLEAU[$param['num']]['thead']['tr']	= array();				// tr du thead
				$HTML2PDF_TABLEAU[$param['num']]['tfoot']['tr']	= array();				// tr du tfoot
				$HTML2PDF_TABLEAU[$param['num']]['thead']['height']	= 0;				// hauteur du thead
				$HTML2PDF_TABLEAU[$param['num']]['tfoot']['height']	= 0;				// hauteur du tfoot
				$HTML2PDF_TABLEAU[$param['num']]['thead']['code'] = array();			// contenu HTML du thead
				$HTML2PDF_TABLEAU[$param['num']]['tfoot']['code'] = array();			// contenu HTML du tfoot
				
				$this->saveMargin($this->pdf->lMargin, $this->pdf->tMargin, $this->pdf->rMargin);
				
				// adaptation de la largeur en fonction des marges du tableau
				$this->style->value['width']-= $HTML2PDF_TABLEAU[$param['num']]['marge']['l'] + $HTML2PDF_TABLEAU[$param['num']]['marge']['r'];
			}
			else
			{
				// on repart à la premiere page du tableau et à la premiere case
				$HTML2PDF_TABLEAU[$param['num']]['page'] = 0;
				$HTML2PDF_TABLEAU[$param['num']]['td_curr']	= 0;
				$HTML2PDF_TABLEAU[$param['num']]['tr_curr']	= 0;
				$HTML2PDF_TABLEAU[$param['num']]['td_x']		= $HTML2PDF_TABLEAU[$param['num']]['marge']['l']+$HTML2PDF_TABLEAU[$param['num']]['curr_x'];
				$HTML2PDF_TABLEAU[$param['num']]['td_y']		= $HTML2PDF_TABLEAU[$param['num']]['marge']['t']+$HTML2PDF_TABLEAU[$param['num']]['curr_y'];				

				// initialisation du style des bordures de la premiere partie de tableau
				$this->Rectangle(
						$HTML2PDF_TABLEAU[$param['num']]['curr_x'],
						$HTML2PDF_TABLEAU[$param['num']]['curr_y'],
						$HTML2PDF_TABLEAU[$param['num']]['width'],
						isset($HTML2PDF_TABLEAU[$param['num']]['height'][0]) ? $HTML2PDF_TABLEAU[$param['num']]['height'][0] : null,
						$this->style->value['border'],
						$this->style->value['padding'],
						0,
						$this->style->value['background']
					);

				$HTML2PDF_TABLEAU[$param['num']]['style_value'] = $this->style->value;
			}
			
			return true;
		}

		/**
		* balise : TABLE
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_TABLE($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			global $HTML2PDF_TABLEAU; 

			// restauration du style
			$this->style->load();
			$this->style->FontSet();

			// si on est en mode sub_html : initialisation des dimensions et autres 
			if ($this->sub_part)
			{
				// ajustement de la taille des cases
				$this->calculTailleCases($HTML2PDF_TABLEAU[$param['num']]['cases']);

				// calcul de la hauteur du THEAD et du TFOOT
				$lst = array('thead', 'tfoot');
				foreach($lst as $mode)
				{
					$HTML2PDF_TABLEAU[$param['num']][$mode]['height'] = 0;
					foreach($HTML2PDF_TABLEAU[$param['num']][$mode]['tr'] as $tr)
					{
						// hauteur de la ligne tr
						$h = 0;
						for($i=0; $i<count($HTML2PDF_TABLEAU[$param['num']]['cases'][$tr]); $i++)
							if ($HTML2PDF_TABLEAU[$param['num']]['cases'][$tr][$i]['rowspan']==1)
								$h = max($h, $HTML2PDF_TABLEAU[$param['num']]['cases'][$tr][$i]['h']);
						$HTML2PDF_TABLEAU[$param['num']][$mode]['height']+= $h;	
					}
				}

				// calcul des dimensions du tableau - Largeur
				$HTML2PDF_TABLEAU[$param['num']]['width'] = $HTML2PDF_TABLEAU[$param['num']]['marge']['l'] + $HTML2PDF_TABLEAU[$param['num']]['marge']['r'];
				if (isset($HTML2PDF_TABLEAU[$param['num']]['cases'][0]))
					foreach($HTML2PDF_TABLEAU[$param['num']]['cases'][0] as $case)
						$HTML2PDF_TABLEAU[$param['num']]['width']+= $case['w'];

				// positionnement du tableau horizontalement;
				$old = isset($this->style->table[count($this->style->table)-1]) ? $this->style->table[count($this->style->table)-1] : $this->style->value;
				$parent_w = $old['width'] ? $old['width'] : $this->pdf->w - $this->pdf->lMargin - $this->pdf->rMargin;
				$x = $HTML2PDF_TABLEAU[$param['num']]['curr_x'];
				$w = $HTML2PDF_TABLEAU[$param['num']]['width'];
				if ($parent_w>$w)
				{
					if ($HTML2PDF_TABLEAU[$param['num']]['align']=='center')
						$x = $x + ($parent_w-$w)*0.5;
					else if ($HTML2PDF_TABLEAU[$param['num']]['align']=='right')
						$x = $x + $parent_w-$w;

					$HTML2PDF_TABLEAU[$param['num']]['curr_x'] = $x;
				}					


				// calcul des dimensions du tableau - hauteur du tableau sur chaque page
				$HTML2PDF_TABLEAU[$param['num']]['height'] = array();

				$h0 = $HTML2PDF_TABLEAU[$param['num']]['marge']['t'] + $HTML2PDF_TABLEAU[$param['num']]['marge']['b'];	// minimum de hauteur à cause des marges
				$h0+= $HTML2PDF_TABLEAU[$param['num']]['thead']['height'] + $HTML2PDF_TABLEAU[$param['num']]['tfoot']['height']; // et du tfoot et thead
				$max = $this->pdf->h - $this->pdf->bMargin;			// max de hauteur par page
				$y = $HTML2PDF_TABLEAU[$param['num']]['curr_y'];	// position Y actuelle
				$height = $h0;
				
				// on va lire les hauteurs de chaque ligne, une à une, et voir si ca rentre sur la page.
				for($k=0; $k<count($HTML2PDF_TABLEAU[$param['num']]['cases']); $k++)
				{
					// si c'est des lignes du thead ou du tfoot : on passe
					if (in_array($k, $HTML2PDF_TABLEAU[$param['num']]['thead']['tr'])) continue;
					if (in_array($k, $HTML2PDF_TABLEAU[$param['num']]['tfoot']['tr'])) continue;

					// hauteur de la ligne $k
					$th = 0;
					$h = 0;
					for($i=0; $i<count($HTML2PDF_TABLEAU[$param['num']]['cases'][$k]); $i++)
					{
						$h = max($h, $HTML2PDF_TABLEAU[$param['num']]['cases'][$k][$i]['h']);
						
						if ($HTML2PDF_TABLEAU[$param['num']]['cases'][$k][$i]['rowspan']==1)
							$th = max($th, $HTML2PDF_TABLEAU[$param['num']]['cases'][$k][$i]['h']);
					}
			
					// si la ligne ne rentre pas dans la page
					// => la hauteur sur cette page est trouvée, et on passe à la page d'apres
					if ($y+$h+$height>$max)
					{
						if ($height==$h0) $height = null;
						$HTML2PDF_TABLEAU[$param['num']]['height'][] = $height;
						$height = $h0;						
						$y = $this->margeTop;
					}
					$height+= $th;
				}
				// rajout du reste de tableau (si il existe) à la derniere page
				if ($height!=$h0 || $k==0) $HTML2PDF_TABLEAU[$param['num']]['height'][] = $height;
			}
			else
			{
				if (count($HTML2PDF_TABLEAU[$param['num']]['tfoot']['code']))
				{
					$tmp_tr = $HTML2PDF_TABLEAU[$param['num']]['tr_curr'];
					$tmp_td = $HTML2PDF_TABLEAU[$param['num']]['td_curr'];
					$OLD_parse_pos = $this->parse_pos;
					$OLD_parse_code = $this->parsing->code;
					
					$HTML2PDF_TABLEAU[$param['num']]['tr_curr'] = $HTML2PDF_TABLEAU[$param['num']]['tfoot']['tr'][0];
					$HTML2PDF_TABLEAU[$param['num']]['td_curr'] = 0;
					$this->parse_pos = 0;
					$this->parsing->code = $HTML2PDF_TABLEAU[$param['num']]['tfoot']['code'];
					$this->MakeHTMLcode();
					
					$this->parse_pos = 	$OLD_parse_pos;
					$this->parsing->code = $OLD_parse_code;
					$HTML2PDF_TABLEAU[$param['num']]['tr_curr'] = $tmp_tr;
					$HTML2PDF_TABLEAU[$param['num']]['td_curr'] = $tmp_td;
				}
					
				// determination des coordonnées de sortie du tableau
				$x = $HTML2PDF_TABLEAU[$param['num']]['curr_x'] + $HTML2PDF_TABLEAU[$param['num']]['width'];
				if (count($HTML2PDF_TABLEAU[$param['num']]['height'])>1)
					$y = $this->margeTop+$HTML2PDF_TABLEAU[$param['num']]['height'][count($HTML2PDF_TABLEAU[$param['num']]['height'])-1];
				else if (count($HTML2PDF_TABLEAU[$param['num']]['height'])==1)
					$y = $HTML2PDF_TABLEAU[$param['num']]['curr_y']+$HTML2PDF_TABLEAU[$param['num']]['height'][count($HTML2PDF_TABLEAU[$param['num']]['height'])-1];
				else
					$y = $HTML2PDF_TABLEAU[$param['num']]['curr_y'];					

				// restauration des marges
				$this->loadMargin();
				
				// position de sortie du tableau
				$this->pdf->setX($x);
				$this->pdf->setY($y);
				$this->maxX = max($this->maxX, $x);
				$this->maxY = max($this->maxY, $y);
			}
			
			return true;
		}

		/**
		* balise : TR
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_TR($param, $other = 'tr')
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			global $HTML2PDF_TABLEAU; 

			// analyse du style
			$this->style->save();
			$this->style->analyse($other, $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			// positionnement dans le tableau
			$HTML2PDF_TABLEAU[$param['num']]['tr_curr']++;
			$HTML2PDF_TABLEAU[$param['num']]['td_curr']= 0;
			
			// si on est pas dans un sub_html
			if (!$this->sub_part)
			{
				// Y courant apres la ligne
				$ty=null;
				for($ii=0; $ii<count($HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1]); $ii++)
					$ty = max($ty, $HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$ii]['h']);	
				
				$hfoot = $HTML2PDF_TABLEAU[$param['num']]['tfoot']['height'];
				
				// si la ligne ne rentre pas dans la page => nouvelle page
				if (!$this->isInTfoot && $HTML2PDF_TABLEAU[$param['num']]['td_y'] + $HTML2PDF_TABLEAU[$param['num']]['marge']['b'] + $ty +$hfoot> $this->pdf->h - $this->pdf->bMargin)
				{
					if (count($HTML2PDF_TABLEAU[$param['num']]['tfoot']['code']))
					{
						$tmp_tr = $HTML2PDF_TABLEAU[$param['num']]['tr_curr'];
						$tmp_td = $HTML2PDF_TABLEAU[$param['num']]['td_curr'];
						$OLD_parse_pos = $this->parse_pos;
						$OLD_parse_code = $this->parsing->code;
						
						$HTML2PDF_TABLEAU[$param['num']]['tr_curr'] = $HTML2PDF_TABLEAU[$param['num']]['tfoot']['tr'][0];
						$HTML2PDF_TABLEAU[$param['num']]['td_curr'] = 0;
						$this->parse_pos = 0;
						$this->parsing->code = $HTML2PDF_TABLEAU[$param['num']]['tfoot']['code'];
						$this->isInTfoot = true;
						$this->MakeHTMLcode();
						$this->isInTfoot = false;
						
						$this->parse_pos = 	$OLD_parse_pos;
						$this->parsing->code = $OLD_parse_code;
						$HTML2PDF_TABLEAU[$param['num']]['tr_curr'] = $tmp_tr;
						$HTML2PDF_TABLEAU[$param['num']]['td_curr'] = $tmp_td;
					}
					
					$HTML2PDF_TABLEAU[$param['num']]['new_page'] = true;
					$this->setNewPage();

					$HTML2PDF_TABLEAU[$param['num']]['page']++;
					$HTML2PDF_TABLEAU[$param['num']]['curr_y'] = $this->pdf->getY();
					$HTML2PDF_TABLEAU[$param['num']]['td_y'] = $HTML2PDF_TABLEAU[$param['num']]['curr_y']+$HTML2PDF_TABLEAU[$param['num']]['marge']['t'];

					// si la hauteur de cette partie a bien été calculée, on trace le cadre
					if (isset($HTML2PDF_TABLEAU[$param['num']]['height'][$HTML2PDF_TABLEAU[$param['num']]['page']]))
					{
						$old = $this->style->value;
						$this->style->value = $HTML2PDF_TABLEAU[$param['num']]['style_value'];

						// initialisation du style des bordures de la premiere partie de tableau
						$this->Rectangle(
								$HTML2PDF_TABLEAU[$param['num']]['curr_x'],
								$HTML2PDF_TABLEAU[$param['num']]['curr_y'],
								$HTML2PDF_TABLEAU[$param['num']]['width'],
								$HTML2PDF_TABLEAU[$param['num']]['height'][$HTML2PDF_TABLEAU[$param['num']]['page']],
								$this->style->value['border'],
								$this->style->value['padding'],
								$HTML2PDF_TABLEAU[$param['num']]['cellspacing']*0.5,
								$this->style->value['background']
							);
											 
						$this->style->value = $old;
					}
				}
				
				if ($HTML2PDF_TABLEAU[$param['num']]['new_page'] && count($HTML2PDF_TABLEAU[$param['num']]['thead']['code']))
				{
					$HTML2PDF_TABLEAU[$param['num']]['new_page'] = false;
					$tmp_tr = $HTML2PDF_TABLEAU[$param['num']]['tr_curr'];
					$tmp_td = $HTML2PDF_TABLEAU[$param['num']]['td_curr'];
					$OLD_parse_pos = $this->parse_pos;
					$OLD_parse_code = $this->parsing->code;
					
					$HTML2PDF_TABLEAU[$param['num']]['tr_curr'] = $HTML2PDF_TABLEAU[$param['num']]['thead']['tr'][0];
					$HTML2PDF_TABLEAU[$param['num']]['td_curr'] = 0;
					$this->parse_pos = 0;
					$this->parsing->code = $HTML2PDF_TABLEAU[$param['num']]['thead']['code'];
					$this->MakeHTMLcode();
					
					$this->parse_pos = 	$OLD_parse_pos;
					$this->parsing->code = $OLD_parse_code;
					$HTML2PDF_TABLEAU[$param['num']]['tr_curr'] = $tmp_tr;
					$HTML2PDF_TABLEAU[$param['num']]['td_curr'] = $tmp_td;
					$HTML2PDF_TABLEAU[$param['num']]['new_page'] = true;
				}
			}
			else
			{
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1] = array();
			}							
			
			return true;
		}

		/**
		* balise : TR
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_TR($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			global $HTML2PDF_TABLEAU; 

			// restauration du style
			$this->style->load();
			$this->style->FontSet();			

			// si on est pas dans un sub_html
			if (!$this->sub_part)
			{
				// Y courant apres la ligne
				$ty=null;
				for($ii=0; $ii<count($HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1]); $ii++)
					if ($HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$ii]['rowspan']==1)
						$ty = $HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$ii]['h'];	

				// mise à jour des coordonnées courantes
				$HTML2PDF_TABLEAU[$param['num']]['td_x'] = $HTML2PDF_TABLEAU[$param['num']]['curr_x']+$HTML2PDF_TABLEAU[$param['num']]['marge']['l'];
				$HTML2PDF_TABLEAU[$param['num']]['td_y']+= $ty;
				$HTML2PDF_TABLEAU[$param['num']]['new_page'] = false;
			}
			
			return true;
		}

		/**
		* balise : TD
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_TD($param, $other = 'td')
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			global $HTML2PDF_TABLEAU; 

			$param['cellpadding'] = $HTML2PDF_TABLEAU[$param['num']]['cellpadding'].'mm';
			$param['cellspacing'] = $HTML2PDF_TABLEAU[$param['num']]['cellspacing'].'mm';
			
			if ($other=='li')
			{
				$special_li = true;
			}
			else
			{
				$special_li = false;
				if ($other=='li_sub')
				{
					$param['style']['border'] = 'none';
					$param['style']['background-color']		= 'transparent';
					$param['style']['background-image']		= 'none';
					$param['style']['background-position']	= '';
					$param['style']['background-repeat']	= '';
					$other = 'li';
				}
			}

			// est-on en collapse
			$collapse = false;
			if (in_array($other, array('td', 'th')))
				$collapse = isset($this->style->value['border']['collapse']) ? $this->style->value['border']['collapse'] : false;


			// analyse du style
			$this->style->save();
			$this->style->analyse($other, $param);

			if ($special_li)
			{
				$this->style->value['width']-= $this->style->ConvertToMM($this->listeGetWidth());
				$this->style->value['width']-= $this->style->ConvertToMM($this->listeGetPadding());
			}
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			// si on est en collapse : modification du style
			if ($collapse)
			{
				if (!$this->sub_part)
				{
					if ($HTML2PDF_TABLEAU[$param['num']]['tr_curr']>1 && !$HTML2PDF_TABLEAU[$param['num']]['new_page'])
						$this->style->value['border']['t'] = $this->style->readBorder('none');				
				}
			
				if ($HTML2PDF_TABLEAU[$param['num']]['td_curr']>0)
					$this->style->value['border']['l'] = $this->style->readBorder('none');	
			}	
			
			$marge = array();
			$marge['t'] = $this->style->value['padding']['t']+0.5*$HTML2PDF_TABLEAU[$param['num']]['cellspacing']+$this->style->value['border']['t']['width'];
			$marge['r'] = $this->style->value['padding']['r']+0.5*$HTML2PDF_TABLEAU[$param['num']]['cellspacing']+$this->style->value['border']['r']['width'];
			$marge['b'] = $this->style->value['padding']['b']+0.5*$HTML2PDF_TABLEAU[$param['num']]['cellspacing']+$this->style->value['border']['b']['width'];
			$marge['l'] = $this->style->value['padding']['l']+0.5*$HTML2PDF_TABLEAU[$param['num']]['cellspacing']+$this->style->value['border']['l']['width'];

			// si on est dans un sub_html
			if ($this->sub_part)
			{
				// on se positionne dans le tableau
				$HTML2PDF_TABLEAU[$param['num']]['td_curr']++;
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1] = array();
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['w'] = 0;
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['h'] = 0;
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['dw'] = 0;
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['colspan'] = isset($param['colspan']) ? $param['colspan'] : 1;
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['rowspan'] = isset($param['rowspan']) ? $param['rowspan'] : 1;

				// on extrait tout ce qui est contenu dans le TD				
				$res = $this->parsing->getLevel($this->temp_pos);
				
				// on en créé un sous HTML que l'on transforme en PDF
				// pour analyse les dimensions
				// et les récupérer dans le tableau global.
				$this->CreateSubHTML($this->sub_html);
				$this->sub_html->writeHTML($res[1]);
				$this->temp_pos = $res[0]-2;
			}
			else
			{
				// on se positionne dans le tableau
				$HTML2PDF_TABLEAU[$param['num']]['td_curr']++;
				$HTML2PDF_TABLEAU[$param['num']]['td_x']+= $HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['dw'];
				
				// initialisation du style des bordures de la premiere partie de tableau
				$this->Rectangle(
						$HTML2PDF_TABLEAU[$param['num']]['td_x'],
						$HTML2PDF_TABLEAU[$param['num']]['td_y'],
						$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['w'],
						$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['h'],
						$this->style->value['border'],
						$this->style->value['padding'],
						$HTML2PDF_TABLEAU[$param['num']]['cellspacing']*0.5,
						$this->style->value['background']
					);
				

				$this->style->value['width'] = $HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['w'] - $marge['l'] - $marge['r'];

				// limitation des marges aux dimensions de la case
				$mL = $HTML2PDF_TABLEAU[$param['num']]['td_x']+$marge['l'];
				$mR = $this->pdf->w - $mL - $this->style->value['width'];
				$this->saveMargin($mL, 0, $mR);
				
				// positionnement en fonction
				$h_corr = $HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['h'];
				$h_reel = $HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['real_h'];
				switch($this->style->value['vertical-align'])
				{
					case 'bottom':
						$y_corr = $h_corr-$h_reel;
						break;
						
					case 'middle':
						$y_corr = ($h_corr-$h_reel)*0.5;
						break;
						
					case 'top':
					default:
						$y_corr = 0;
						break;	
				}

				$this->pdf->setX($HTML2PDF_TABLEAU[$param['num']]['td_x']+$marge['l']);
				$this->pdf->setY($HTML2PDF_TABLEAU[$param['num']]['td_y']+$marge['t']+$y_corr);
				$this->setNewPositionForNewLine();
			}
			
			return true;
		}

		/**
		* balise : TD
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_TD($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			global $HTML2PDF_TABLEAU; 

			// récupération de la marge
			$marge = array();
			$marge['t'] = $this->style->value['padding']['t']+0.5*$HTML2PDF_TABLEAU[$param['num']]['cellspacing']+$this->style->value['border']['t']['width'];
			$marge['r'] = $this->style->value['padding']['r']+0.5*$HTML2PDF_TABLEAU[$param['num']]['cellspacing']+$this->style->value['border']['r']['width'];
			$marge['b'] = $this->style->value['padding']['b']+0.5*$HTML2PDF_TABLEAU[$param['num']]['cellspacing']+$this->style->value['border']['b']['width'];
			$marge['l'] = $this->style->value['padding']['l']+0.5*$HTML2PDF_TABLEAU[$param['num']]['cellspacing']+$this->style->value['border']['l']['width'];
			$marge['t']+= 0.01;
			$marge['r']+= 0.01;
			$marge['b']+= 0.01;
			$marge['l']+= 0.01;

			// si on est dans un sub_html
			if ($this->sub_part)
			{
				if ($this->testTDin1page && $this->sub_html->pdf->page>1) @HTML2PDF::makeError(7, __FILE__, __LINE__); 
				
				// dimentions de cette case
				$w0 = $this->sub_html->maxX + $marge['l'] + $marge['r'];
				$h0 = $this->sub_html->maxY + $marge['t'] + $marge['b'];
	
				// dimensions imposées par le style
				$w2 = $this->style->value['width'] + $marge['l'] + $marge['r'];
				$h2 = $this->style->value['height'] + $marge['t'] + $marge['b'];
	
				// dimension finale de la case = max des 2 ci-dessus
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['w'] = max(array($w0, $w2));
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['h'] = max(array($h0, $h2));

				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['real_w'] = $w0;
				$HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['real_h'] = $h0;

				// suppresion du sous_html
				$this->DestroySubHTML();
			}
			else
			{
				$this->loadMargin();
				//positionnement
				$HTML2PDF_TABLEAU[$param['num']]['td_x']+= $HTML2PDF_TABLEAU[$param['num']]['cases'][$HTML2PDF_TABLEAU[$param['num']]['tr_curr']-1][$HTML2PDF_TABLEAU[$param['num']]['td_curr']-1]['w'];
			}

			// restauration du style
			$this->style->load();
			$this->style->FontSet();	
			
			return true;
		}
		
		function calculTailleCases(&$cases)
		{
			// construction d'un tableau de correlation
			$corr = array();

			// on fait correspondre chaque case d'un tableau normé aux cases réelles, en prennant en compte les colspan et rowspan
			$Yr=0;
			for($y=0; $y<count($cases); $y++)
			{
				$Xr=0; 	while(isset($corr[$Yr][$Xr])) $Xr++;
				
				for($x=0; $x<count($cases[$y]); $x++)
				{
					for($j=0; $j<$cases[$y][$x]['rowspan']; $j++)
					{
						for($i=0; $i<$cases[$y][$x]['colspan']; $i++)
						{
							$corr[$Yr+$j][$Xr+$i] = ($i+$j>0) ? '' : array($x, $y, $cases[$y][$x]['colspan'], $cases[$y][$x]['rowspan']);
						}
					}
					$Xr+= $cases[$y][$x]['colspan'];
					while(isset($corr[$Yr][$Xr])) $Xr++;
				}
				$Yr++;
			}
			
			if (!isset($corr[0])) return true;
			
			// on détermine, pour les cases sans colspan, la largeur maximale de chaque colone
			$sw = array();
			for($x=0; $x<count($corr[0]); $x++)
			{
				$m=0;
				for($y=0; $y<count($corr); $y++)
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]) && $corr[$y][$x][2]==1)
						$m = max($m, $cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w']);				
				$sw[$x] = $m;	
			}

			// on vérifie que cette taille est valide avec les colones en colspan
			for($x=0; $x<count($corr[0]); $x++)
			{
				for($y=0; $y<count($corr); $y++)
				{
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]) && $corr[$y][$x][2]>1)
					{
						// somme des colonnes correspondant au colspan
						$s = 0; for($i=0; $i<$corr[$y][$x][2]; $i++) $s+= $sw[$x+$i];
						
						// si la somme est inférieure à la taille necessaire => règle de 3 pour adapter
						if ($s>0 && $s<$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'])
							for($i=0; $i<$corr[$y][$x][2]; $i++)
								$sw[$x+$i] = $sw[$x+$i]/$s*$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'];
					}
				}
			}

			// on applique les nouvelles largeurs
			for($x=0; $x<count($corr[0]); $x++)
			{
				for($y=0; $y<count($corr); $y++)
				{
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]))
					{
						if ($corr[$y][$x][2]==1)
						{
							$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'] = $sw[$x];
						}
						else
						{
							// somme des colonnes correspondant au colspan
							$s = 0; for($i=0; $i<$corr[$y][$x][2]; $i++) $s+= $sw[$x+$i];
							$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'] = $s;
						}
					}
				}
			}

			// on détermine, pour les cases sans rowspan, la hauteur maximale de chaque colone
			$sh = array();
			for($y=0; $y<count($corr); $y++)
			{
				$m=0;
				for($x=0; $x<count($corr[0]); $x++)
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]) && $corr[$y][$x][3]==1)
						$m = max($m, $cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h']);
				$sh[$y] = $m;	
			}


			// on vérifie que cette taille est valide avec les lignes en rowspan
			for($y=0; $y<count($corr); $y++)
			{
				for($x=0; $x<count($corr[0]); $x++)
				{
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]) && $corr[$y][$x][3]>1)
					{
						// somme des colonnes correspondant au colspan
						$s = 0; for($i=0; $i<$corr[$y][$x][3]; $i++) $s+= $sh[$y+$i];
						
						// si la somme est inférieure à la taille necessaire => règle de 3 pour adapter
						if ($s>0 && $s<$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h'])
							for($i=0; $i<$corr[$y][$x][3]; $i++)
								$sh[$y+$i] = $sh[$y+$i]/$s*$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h'];
					}
				}
			}
			

			// on applique les nouvelles hauteurs
			for($y=0; $y<count($corr); $y++)
			{
				for($x=0; $x<count($corr[0]); $x++)
				{
					if (isset($corr[$y][$x]) && is_array($corr[$y][$x]))
					{
						if ($corr[$y][$x][3]==1)
						{
							$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h'] = $sh[$y];
						}
						else
						{
							// somme des lignes correspondant au rowspan
							$s = 0; for($i=0; $i<$corr[$y][$x][3]; $i++) $s+= $sh[$y+$i];
							$cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['h'] = $s;
							
							for($j=1; $j<$corr[$y][$x][3]; $j++)
							{
								$tx = $x+1;
								$ty = $y+$j;
								for(true; isset($corr[$ty][$tx]) && !is_array($corr[$ty][$tx]); $tx++);
								if (isset($corr[$ty][$tx])) $cases[$corr[$ty][$tx][1]][$corr[$ty][$tx][0]]['dw']+= $cases[$corr[$y][$x][1]][$corr[$y][$x][0]]['w'];
																	
							}
						}
					}
				}
			}		
		}

		/**
		* balise : TH
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_TH($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			// identique à TD mais en gras
			if (!isset($param['style']['font-weight'])) $param['style']['font-weight'] = 'bold';
			$this->o_TD($param, 'th');
			
			return true;
		}	

		/**
		* balise : TH
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_TH($param)
		{
			if ($this->forOneLine) return false;

			$this->maxH = 0;
			// identique à TD
			$this->c_TD($param);			
			
			return true;
		}

		/**
		* balise : IMG
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_IMG($param)
		{
			// analyse du style
			$src	= str_replace('&amp;', '&', $param['src']);	

			$this->style->save();
			$this->style->value['width']	= 0;
			$this->style->value['height']	= 0;
			$this->style->value['border']	= array(
													'type'	=> 'none',
													'width'	=> 0,
													'color'	=> array(0, 0, 0),
												);
			$this->style->value['background'] = array(
													'color'		=> null,
													'image'		=> null,
													'position'	=> null,
													'repeat'	=> null
												);
			$this->style->analyse('img', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();

			// affichage de l'image			
			$this->Image($src, isset($param['sub_li']));

			// restauration du style
			$this->style->load();
			$this->style->FontSet();	
			
			return true;
		}
		
		/**
		* balise : SELECT
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_SELECT($param)
		{
			// preparation du champs
			if (!isset($param['name']))		$param['name']	= 'champs_pdf_'.(count($this->lstChamps)+1);
			
			$param['name'] = strtolower($param['name']);
			
			if (isset($this->champs[$param['name']]))
				$this->champs[$param['name']]++;
			else
				$this->champs[$param['name']] = 1;
				
			$this->style->save();
			$this->style->analyse('select', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			$this->lstSelect = array();
			$this->lstSelect['name']	= $param['name'];
			$this->lstSelect['multi']	= isset($param['multiple']) ? true : false;
			$this->lstSelect['size']	= isset($param['size']) ? $param['size'] : 1;
			$this->lstSelect['options']	= array();

			if ($this->lstSelect['multi'] && $this->lstSelect['size']<3) $this->lstSelect['size'] = 3;
			
			return true;
		}
		
		/**
		* balise : OPTION
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_OPTION($param)
		{
			// on extrait tout ce qui est contenu dans l'option
			$res = $this->parsing->getLevel($this->parse_pos);
			$this->parse_pos = $res[0]-2;
			$texte = $res[1];
			$value = isset($param['value']) ? $param['value'] : 'auto_opt_'.(count($this->lstSelect)+1);
			
			$this->lstSelect['options'][$value] = $texte;
			
			return true;
		}
		
		/**
		* balise : OPTION
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_OPTION($param) { return true; }
				
		/**
		* balise : SELECT
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_SELECT()
		{
			// position d'affichage
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			$f = 1.08*$this->style->value['font-size'];

			$w = $this->style->value['width']; if (!$w) $w = 50; 
			$h = ($f*1.07*$this->lstSelect['size'] + 1);
			$prop = array();
			if ($this->lstSelect['multi']) $prop['multipleSelection'] = true;
			$this->pdf->form_Select($this->lstSelect['name'], $x, $y, $w, $h, $this->lstSelect['options'], $this->lstSelect['size']>1, $prop);
							
			$this->maxX = max($this->maxX, $x+$w);
			$this->maxY = max($this->maxY, $y+$h);
 			$this->maxH = max($this->maxH, $h);
			$this->pdf->setX($x+$w);
			
			$this->style->load();
			$this->style->FontSet();
			
			$this->lstSelect = array();
			
			return true;
		}

		/**
		* balise : TEXTAREA
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_TEXTAREA($param)
		{
			// preparation du champs
			if (!isset($param['name']))		$param['name']	= 'champs_pdf_'.(count($this->lstChamps)+1);
			
			$param['name'] = strtolower($param['name']);
			
			if (isset($this->champs[$param['name']]))
				$this->champs[$param['name']]++;
			else
				$this->champs[$param['name']] = 1;
				
			$this->style->save();
			$this->style->analyse('textarea', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();

			// position d'affichage
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			$fx = 0.65*$this->style->value['font-size'];
			$fy = 1.08*$this->style->value['font-size'];

			// on extrait tout ce qui est contenu dans le textarea
			$res = $this->parsing->getLevel($this->parse_pos);
			$this->parse_pos = $res[0]-2;
			$texte = $res[1];

			$w = $fx*(isset($param['cols']) ? $param['cols'] : 22)+1; 
			$h = $fy*1.07*(isset($param['rows']) ? $param['rows'] : 3)+3;
			
//			if ($this->style->value['width']) $w = $this->style->value['width'];
//			if ($this->style->value['height']) $h = $this->style->value['height'];
			
			$prop = array();
			$prop['value'] = $texte;
			$prop['multiline'] = true;
			
			$this->pdf->form_InputText($param['name'], $x, $y, $w, $h, $prop);
					
			$this->maxX = max($this->maxX, $x+$w);
			$this->maxY = max($this->maxY, $y+$h);
 			$this->maxH = max($this->maxH, $h);
			$this->pdf->setX($x+$w);
					
			return true;
		}
		
		/**
		* balise : TEXTAREA
		* mode : FERMETURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function c_TEXTAREA()
		{
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
						
		/**
		* balise : INPUT
		* mode : OUVERTURE
		* 
		* @param	array	paramètres de l'élément de parsing
		* @return	null
		*/	
		function o_INPUT($param)
		{
			// preparation du champs
			if (!isset($param['name']))		$param['name']	= 'champs_pdf_'.(count($this->lstChamps)+1);
			if (!isset($param['value']))	$param['value']	= '';
			if (!isset($param['type']))		$param['type']	= 'text';
			
			$param['name'] = strtolower($param['name']);
			$param['type'] = strtolower($param['type']);

			if (!in_array($param['type'], array('text', 'checkbox', 'radio', 'hidden', 'submit', 'reset', 'button'))) $param['type'] = 'text';

			if (isset($this->champs[$param['name']]))
				$this->champs[$param['name']]++;
			else
				$this->champs[$param['name']] = 1;

			$this->style->save();
			$this->style->analyse('input', $param);
			$this->style->setPosition($this->pdf->x, $this->pdf->y);
			$this->style->FontSet();
			
			$name = $param['name'];
		
			// position d'affichage
			$x = $this->pdf->getX();
			$y = $this->pdf->getY();
			$f = 1.08*$this->style->value['font-size'];
			
			switch($param['type'])
			{
				case 'checkbox':
					$w = 3;
					$h = $w;
					if ($h<$f) $y+= ($f-$h)*0.5;
					$this->pdf->form_InputCheckBox($name, $x, $y, $w, isset($param['checked']));
					break;
				
				case 'radio':
					$w = 3;
					$h = $w;
					if ($h<$f) $y+= ($f-$h)*0.5;
					$this->pdf->form_InputRadio($name, $x, $y, $w);
					break;
					
				case 'hidden':
					$w = 0;
					$h = 0;
					$this->pdf->form_InputHidden($name, $param['value']);
					break;
					
				case 'text':
					$w = $this->style->value['width']; if (!$w) $w = 40; 
					$h = $f*1.3;
					$prop = array();
					$prop['value'] = $param['value'];
					$this->pdf->form_InputText($name, $x, $y, $w, $h, $prop);
					break;

				case 'submit':
				case 'reset':
				case 'button':
					$action = isset($param['onclick']) ? $param['onclick'] : '';
					$w = $this->style->value['width'];	if (!$w) $w = 40; 
					$h = $this->style->value['height'];	if (!$h) $h = $f*1.3;
					$prop = array();
					$this->pdf->form_InputButton($name, $x, $y, $w, $h, $param['value'], $action, $prop);
					break;
					
				default:
					$w = 0;
					$h = 0;
					break;
			}
			
			$this->maxX = max($this->maxX, $x+$w);
			$this->maxY = max($this->maxY, $y+$h);
 			$this->maxH = max($this->maxH, $h);
			$this->pdf->setX($x+$w);
			
			$this->style->load();
			$this->style->FontSet();
			
			return true;
		}
		
		function CreateIndex($titre = 'Index', $size_title = 20, $size_bookmark = 15, $bookmark_title = true, $display_page = true)
		{
			$this->pdf->CreateIndex($this, $titre, $size_title, $size_bookmark, $bookmark_title, $display_page);
		}
		
		function textLOAD($langue)
		{
			if (!preg_match('/^([a-z0-9]+)$/isU', $langue))
			{
				echo 'ERROR : language code <b>'.$langue.'</b> incorrect.';
				exit;
			}
			
			$file = dirname(__FILE__).'/langues/'.strtolower($langue).'.txt';
			if (!is_file($file))
			{
				echo 'ERROR : language code <b>'.$langue.'</b> unknown.<br>';
				echo 'You can create the translation file <b>'.$file.'</b> and send it to me in order to integrate it into a future version.';
				exit;				
			}
			
			$texte = array();
			$infos = file($file);
			foreach($infos as $val)
			{
				$val = trim($val);
				$val = explode("\t", $val);
				if (count($val)<2) continue;
				
				$t_k = trim($val[0]); unset($val[0]);
				$t_v = trim(implode(' ', $val));
				if ($t_k && $t_v) $texte[$t_k] = $t_v;
			}
			global $HTML2PDF_TEXTE_FILE;
			$HTML2PDF_TEXTE_FILE = $texte;	
		}
		
		function textGET($key)
		{
			global $HTML2PDF_TEXTE_FILE;
			if (!isset($HTML2PDF_TEXTE_FILE[$key])) return '######';
			
			return $HTML2PDF_TEXTE_FILE[$key];
		}
		
		function makeError($err, $file, $line, $other = null)
		{
			$msg = '';
			switch($err)
			{
				case 1:
					$msg = (@HTML2PDF::textGET('err01'));
					$msg = str_replace('[[OTHER]]', $other, $msg); 
					break;
					
				case 2:
					$msg = (@HTML2PDF::textGET('err02'));
					$msg = str_replace('[[OTHER_0]]', $other[0], $msg); 
					$msg = str_replace('[[OTHER_1]]', $other[1], $msg); 
					$msg = str_replace('[[OTHER_2]]', $other[2], $msg); 
					break;
					
				case 3:
					$msg = (@HTML2PDF::textGET('err03'));
					$msg = str_replace('[[OTHER]]', $other, $msg); 
					break;
					
				case 4:
					$msg = (@HTML2PDF::textGET('err04'));
					$msg = str_replace('[[OTHER]]', print_r($other, true), $msg); 
					break;
					
				case 5:
					$msg = (@HTML2PDF::textGET('err05'));
					$msg = str_replace('[[OTHER]]', print_r($other, true), $msg); 
					break;
					
				case 6:
					$msg = (@HTML2PDF::textGET('err06'));
					$msg = str_replace('[[OTHER]]', $other, $msg); 
					break;	
					
				case 7:
					$msg = (@HTML2PDF::textGET('err07'));
					break;	
			}
			
			$Dokeosmsg = '<div style="position: relative; margin-top: 10px; margin-bottom: 10px; border-width: 1px; border-style: solid; padding: 5px; border: 1px solid #FF0000; color: #000; background-color: #FFD1D1; min-height: 30px;"><b>'.get_lang("ConversionFailled").'</b></br>'.get_lang("CommentErrorExportDocument").'</div>';
			echo $Dokeosmsg;
			/*
			////uncomment for debug
			echo '<span style="color: #AA0000; font-weight: bold;">'.(@HTML2PDF::textGET('txt01')).$err.'</span><br>';
			echo (@HTML2PDF::textGET('txt02')).' '.$file.'<br>';
			echo (@HTML2PDF::textGET('txt03')).' '.$line.'<br>';
			echo '<br>';
			echo $msg;
			*/
			exit;	
		}
	}
}

