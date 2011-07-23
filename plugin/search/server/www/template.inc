<?php
/**
 * This is a library of functions to act on the template HTML file.
 * The functions declared here mainly read the template and write the
 * result output using this template.
 * Modified version - the PHP extension must be higher than 3.2.32 and PHP must be higher than 4.2
 * @package chamilo.plugin.search
 */
/**
 * Initialise arrays
 */
$templates=array();

/**
 * Reads the template file given and returns a string. 
 * Similar to the file_get_contents() function but using a loop to read by chunks
 * of 1024 bytes.
 * @param	string	Filename
 * @return	string	File contents 
 */
function load_file($file){
    if (!($fp=fopen($file, 'r'))){
		echo "Can't open template file $template_file";
		exit_local(1);
    } 
    
    while(!feof($fp)) {
		$str.=fgets($fp, 1024);
    }
    
    fclose($fp);

    return $str;
}

/**
 * Build the navigation bar
 * @param	string	Original query ? - not used anyway
 * @return	string	Navigation bar string
 */
function make_nav($query_orig=''){
   global $found,$np,$isnext,$ps,$tag,$ul,$ul_arr,$self,$o,$m,$cat,$cat_arr;
   global $dt, $dp, $dx, $dm, $dy, $dd, $db, $de, $lang, $wm, $wf, $site, $sp, $sy;
   global $q_local,$db_local,$de_local,$lang_arr, $tag, $tag_arr, $type, $type_arr;
   
   $temp_ul='';
   for($i=0; $i<count($ul_arr); $i++) {
	$temp_ul.="&ul=".urlencode($ul_arr[$i]);
   }	
   $temp_tag='';
   for($i=0; $i<count($tag_arr); $i++) {
	$temp_tag.="&tag=".urlencode($tag_arr[$i]);
   }	
   $temp_lang='';
   for($i=0; $i<count($lang_arr); $i++) {
	$temp_lang.="&lang=".urlencode($lang_arr[$i]);
   }	
   $temp_cat='';
   for($i=0; $i<count($cat_arr); $i++) {
	$temp_cat.="&cat=".urlencode($cat_arr[$i]);
   }	
   $temp_type='';
   for($i=0; $i<count($type_arr); $i++) {
	$temp_type.="&type=".urlencode($type_arr[$i]);
   }	

   if($np>0){
     $prevp=$np-1;
     $prev_href="$self?q=$q_local&np=$prevp&m=$m".
                ($ps==20?'':"&ps=$ps").
                $temp_tag.
                $temp_ul.
                $temp_cat.
                $temp_lang.
                ($site==''?'':"&site=$site").
		($wm==''?'':"&wm=$wm").
		($wf==''?'':"&wf=$wf").
                (!$o?'':"&o=$o").
                ($dt=='back'?'':"&dt=$dt").
                (!$dp?'':"&dp=$dp").
                (!$dx?'':"&dx=$dx").
                ($dd=='01'?'':"&dd=$dd").
                (!$dm?'':"&dm=$dm").
                ($dy=='1970'?'':"&dy=$dy").
                ($db=='01/01/1970'?'':"&db=$db_local").
                ($de=='31/12/2020'?'':"&de=$de_local").
                ($sp==''?'':"&sp=$sp").
                ($sy==''?'':"&sy=$sy");

     $nav_left=print_template('navleft',0);
     $nav_left=ereg_replace('\$NH',"$prev_href",$nav_left);
   } elseif ($np==0) {
     $nav_left=print_template('navleft_nop',0);
   }

   if($isnext==1) {
     $nextp=$np+1;
     $next_href="$self?q=$q_local&np=$nextp&m=$m".
     		($ps==20?'':"&ps=$ps").
                $temp_tag.
		$temp_ul.
                $temp_cat.
                $temp_lang.
                ($site==''?'':"&site=$site").
		($wm==''?'':"&wm=$wm").
		($wf==''?'':"&wf=$wf").
                (!$o?'':"&o=$o").
                ($dt=='back'?'':"&dt=$dt").
                (!$dp?'':"&dp=$dp").
                (!$dx?'':"&dx=$dx").
                ($dd=='01'?'':"&dd=$dd").
                (!$dm?'':"&dm=$dm").
                ($dy=='1970'?'':"&dy=$dy").
                ($db=='01/01/1970'?'':"&db=$db_local").
                ($de=='31/12/2020'?'':"&de=$de_local").
                ($sp==''?'':"&sp=$sp").
                ($sy==''?'':"&sy=$sy");

     $nav_right=print_template('navright',0);
     $nav_right=ereg_replace('\$NH',"$next_href",$nav_right);
   } else {
     $nav_right=print_template('navright_nop',0);
   }

   $nav_bar0=print_template('navbar0',0);
   $nav_bar1=print_template('navbar1',0);

   $tp=ceil($found/$ps);

   $cp=$np+1;

   if ($cp>5) {
      $lp=$cp-5;
   } else {
      $lp=1;
   }

   $rp=$lp+10-1;
   if ($rp>$tp) {
      $rp=$tp;
      $lp=$rp-10+1;
      if ($lp<1) $lp=1;
   }


   if ($lp!=$rp) {
      for ($i=$lp; $i<=$rp;$i++) {
         $realp=$i-1;
   
         if ($i==$cp) {
            $nav_bar=$nav_bar.$nav_bar0;
         } else {
            $nav_bar=$nav_bar.$nav_bar1;
         }
         
         $href="$self?q=$q_local&np=$realp&m=$m".
               	($ps==20?'':"&ps=$ps").
                $temp_tag.
		$temp_ul.
                $temp_cat.
                $temp_lang.
                ($site==''?'':"&site=$site").
		($wm==''?'':"&wm=$wm").
		($wf==''?'':"&wf=$wf").
                (!$o?'':"&o=$o").
                ($dt=='back'?'':"&dt=$dt").
                (!$dp?'':"&dp=$dp").
                (!$dx?'':"&dx=$dx").
                ($dd=='01'?'':"&dd=$dd").
                (!$dm?'':"&dm=$dm").
                ($dy=='1970'?'':"&dy=$dy").
                ($db=='01/01/1970'?'':"&db=$db_local").
                ($de=='31/12/2020'?'':"&de=$de_local").
                ($sp==''?'':"&sp=$sp").
                ($sy==''?'':"&sy=$sy");

         $nav_bar=ereg_replace('\$NP',"$i",$nav_bar);
         $nav_bar=ereg_replace('\$NH',"$href",$nav_bar);
      }
   
      $nav=print_template('navigator',0);
      $nav=ereg_replace('\$NL',"$nav_left",$nav);
      $nav=ereg_replace('\$NR',"$nav_right",$nav);
      $nav=ereg_replace('\$NB',"$nav_bar",$nav);

   } elseif ($found) {
      $nav=print_template('navigator',0);
      $nav=ereg_replace('\$NL',"$nav_left",$nav);
      $nav=ereg_replace('\$NR',"$nav_right",$nav);
      $nav=ereg_replace('\$NB',"",$nav);
   }

   return $nav;
}
/**
 * Prints the template on screen after substituting a lot of variables
 * from the global scope
 * @param	string	Index of the part of the template we want to use (storedoc_top, storedoc, ...)
 * @param	boolean	Whether to echo the results on screen or just return the string
 * @return	mixed	Nothing if $echo=1, the resulting string if $echo=false
 */
function print_template($t,$echo=1){
    global $templates, $udm_agent;
    global $first_doc, $last_doc, $found, $query_orig, $error, $self;
    global $nav, $wordinfo, $ws, $doclang, $doccharset, $storedocurl;
    global $url, $ue, $o, $cat, $storedstr, $stored_href;
    global $clonestr, $searchtime, $persite;
    global $title, $rating, $desc, $contype, $lastmod, $docsize, $ndoc;
    global $keyw, $text, $category, $pop_rank;
    global $crc, $Randoms, $rec_id, $DEBUG, $id;
    global $lang_url_translation, $phpver, $sitelimit_href, $sitelimitstr;
    global $global_doc_res, $global_res_position;
    global $last_modified, $content, $charset, $length, $document, $url;
 
    $str=$templates["$t"][$o];
    if ($str == '') $str=$templates["$t"][0];
    
    //variables as returned by the udm_search() function and defined in search.php
    $str=ereg_replace('\$f', "$first_doc", $str);
    $str=ereg_replace('\$l', "$last_doc", $str);
    $str=ereg_replace('\$t', "$found", $str);
    $str=ereg_replace('\$A', $self, $str);

    $str=ereg_replace('\$Q', urlencode(stripslashes($query_orig)), $str);
    $str=ereg_replace('\$q', urlencode($query_orig), $str);
    $str=eregi_replace('\$UE', $ue, $str);

    $str=ereg_replace('\$E', $error, $str);
    $str=ereg_replace('\$W', htmlspecialchars($wordinfo), $str);
    $str=ereg_replace('\$\(WS\)', htmlspecialchars($ws), $str);

    $str=ereg_replace('\$V', $nav, $str);

    if ($lang_url_translation == 'yes') {
    	$nolangurl = ereg_replace("\.[a-z]{2}\.[a-z]{2,4}$", "", $url);
        $str=ereg_replace('\$DU', htmlspecialchars($nolangurl), $str);
    } else {
    	$str=ereg_replace('\$DU', htmlspecialchars($url), $str);
    }

    $str=ereg_replace('\$\(stored_href\)', htmlspecialchars($stored_href), $str);
    $str=ereg_replace('\$\(sitelimit_href\)', htmlspecialchars($sitelimit_href), $str);

    //require_once('filter_user.functions.php');
    //$title = subst_course_code(urldecode(html_entity_decode($title)));
    $str=ereg_replace('\$DT', htmlspecialchars($title), $str);
    $str=ereg_replace('\$DR', htmlspecialchars("$rating"), $str);
    //$text = subst_course_code(html_entity_decode($text));
    //Comment out temporarily because breaking XML file (excerpt of Word document with unknown character)
    $str=ereg_replace('\$DX', htmlspecialchars(urlencode($text)), $str);
    $str=ereg_replace('\$DE', ($desc != '')?htmlspecialchars(urlencode($desc)):htmlspecialchars(urlencode($text)), $str);
    $str=ereg_replace('\$DC', $contype, $str);
    $str=ereg_replace('\$DM', $lastmod, $str);
    $str=ereg_replace('\$DS', "$docsize", $str);
    $str=ereg_replace('\$DN', "$ndoc", $str);
    $str=ereg_replace('\$DD', htmlspecialchars($desc), $str);
    $str=ereg_replace('\$DK', htmlspecialchars($keyw), $str);
    $str=ereg_replace('\$SearchTime', "$searchtime", $str);
    $str=ereg_replace('\$\(STORED\)', htmlspecialchars("$storedstr"), $str);
    $str=ereg_replace('\$\(SITELIMIT\)', htmlspecialchars("$sitelimitstr"), $str);
    $str=ereg_replace('\$\(Pop_Rank\)', "$pop_rank", $str);
    $str=ereg_replace('\$CL', "$clonestr", $str);
    $str=ereg_replace('\$\(PerSite\)', "$persite", $str);
    
	//information on each link (header for one item)
    if ($t == 'storedoc_top') {
        $str=ereg_replace('\$\(ID\)', htmlspecialchars("$id"), $str);
        $str=ereg_replace('\$\(Last-Modified\)', htmlspecialchars("$last_modified"), $str);
        $str=ereg_replace('\$\(Content-Language\)', htmlspecialchars("$content"), $str);
		$str=ereg_replace('\$\(Charset\)', htmlspecialchars("$charset"), $str);
		$str=ereg_replace('\$\(Content-Length\)', "$length", $str);
		$str=ereg_replace('\$\(URL\)', htmlspecialchars("$url"), $str);
    }
    //link to the document
    if ($t == 'storedoc') {
		$str=ereg_replace('\$\(document\)', htmlspecialchars("$document"), $str);
    }

	//Categories	
	if (ereg('\$CP',$str)) {
		if ($temp_cp_arr=udm_cat_path($udm_agent,$cat)) {
    		reset($temp_cp_arr);
        	$temp_cp='';
        	for ($i=0; $i<count($temp_cp_arr); $i+=2) {
        		$cp_path=$temp_cp_arr[$i];
        		$cp_name=$temp_cp_arr[$i+1];
        		$temp_cp .= " &gt; <a href=\"$PHP_SELF?cat=$cp_path\">$cp_name</a> ";
        	}
        	$str=ereg_replace('\$CP', $temp_cp, $str);
	} else $str=ereg_replace('\$CP', '', $str);
    }    	

	//Categories
    if (ereg('\$CS',$str)) {
    	if ($temp_cp_arr=udm_cat_list($udm_agent,$cat)) {
        	reset($temp_cp_arr);
        	$temp_cp='';
            for ($i=0; $i<count($temp_cp_arr); $i+=2) {
        		$cp_path=$temp_cp_arr[$i];
        		$cp_name=$temp_cp_arr[$i+1];
        		$temp_cp .= "<a href=\"$PHP_SELF?cat=$cp_path\">$cp_name</a><br>";
        	}
                $str=ereg_replace('\$CS', $temp_cp, $str);
	} else $str=ereg_replace('\$CS', '', $str);
    }    	
	
	//Categories
    if (ereg('\$DY',$str)) {
    	if ($temp_cp_arr=udm_cat_path($udm_agent,$category)) {
        	reset($temp_cp_arr);
        	$temp_cp='';
            for ($i=0; $i<count($temp_cp_arr); $i+=2) {
        		$cp_path=$temp_cp_arr[$i];
        		$cp_name=$temp_cp_arr[$i+1];
        		$temp_cp .= " &gt; <a href=\"$PHP_SELF?cat=$cp_path\">$cp_name</a> ";
        	}
            $str=ereg_replace('\$DY', $temp_cp, $str);
		} else $str=ereg_replace('\$DY', '', $str);
    }

	//Number of documents
    if (eregi('\$ndocs*',$str)) {
    	$str=preg_replace('/\$ndocs?/', get_doc_count(), $str);
    }
	//document link?
    if ($global_doc_res) {
		$str=preg_replace_callback('/\$\(([\w\.\_\-\:]+)\)/', 'res_field_callback', $str);
    }
    
    //Random?
    $sub=$str;
    while($sub=strstr($sub,'$r')){
		$sub=substr($sub,2);
		$pos=0;
		$num='';
		
		while(($sub[$pos]>='0')&&($sub[$pos]<='9')){
			$num.=$sub[$pos++];
		}
		
		$str=ereg_replace('\$r'.$num, ''.$Randoms[$num], $str);
    }
    
    if($echo) echo $str; else return $str;
}

/**
 * Reads the template file.
 * @param	none	All the parameters are taken as globals, which is very bad.
 * @return	void	No return value everything is left in global scope
 */
function read_template(){
    global $Randoms;
    global $template_file; //uri of the file to open
    global $templates,$dbaddr;
    global $ps, $np, $ul, $ul_arr, $o, $tag, $tag_arr, $m, $cat, $cat_arr, $wm, $wf;
    global $dt, $dp, $dx, $dm, $dy, $dd, $db, $de;
    global $clones,$lang,$lang_arr, $sp, $sy, $s, $resultslimit;
    global $affix_file, $spell_file, $spell_host;
    global $stopwordtable_arr, $stopwordfile_arr;
    global $synonym_arr, $searchd_arr;
    global $alias_arr,$type, $type_arr;
    
    $affix_file=array();
    $spell_file=array();
    $alias_arr=array();

	$dbaddr=array();

    $names=array('top', 'bottom', 'restop', 'resbot', 'navigator',
				 'res', 'ftpres', 'httpres', 'notfound', 'error', 'variables', 
				 'clone', 'navleft_nop', 'navright_nop', 'navleft', 
				 'navbar1', 'navbar0', 'navright', 'noquery', 'site_limit', 
				 'storedoc_top','storedoc_bottom','storedoc','stored');

    $where='';
    
    //First check that the template file is available
    if (!($fp=fopen($template_file, 'r'))){
		echo "Can't open template file $template_file";
		exit_local(1);
    } do {
    	//The template file is there, read it 
		$str=fgets($fp, 1024);

		$param = array();
        if (preg_match('/value="(.*)"[\s\t]+selected="\$ps"/i',$str,$param)) {
      		if (($param[1]) == $ps) {
          		$str=preg_replace('/selected="\$ps"/i',"selected",$str);
      		} else {
          		$str=preg_replace('/selected="\$ps"/i',"",$str);
      		}
  		} elseif (preg_match('/value="(.*)"[\s\t]+selected="\$ul"/i',$str,$param)) {
  			$found_param=0;
  			for($i=0; $i<count($ul_arr); $i++) {
				$temp_ul=$ul_arr[$i];
      			if (($param[1]) == $temp_ul) {
          			$str=preg_replace('/selected="\$ul"/i',"selected",$str);
          			$found_param=1;
          			break;
          		}
      		} 
      		if (!$found_param) $str=preg_replace('/selected="\$ul"/i',"",$str);
  		} elseif (preg_match('/value="(.*)"[\s\t]+selected="\$type"/i',$str,$param)) {
  			$found_param=0;
  			for($i=0; $i<count($type_arr); $i++) {
				$temp_type=$type_arr[$i];
      			if (($param[1]) == $temp_type) {
          			$str=preg_replace('/selected="\$type"/i',"selected",$str);
          			$found_param=1;
          			break;
          		}
      		} 
      		if (!$found_param) $str=preg_replace('/selected="\$type"/i',"",$str);
        } elseif (preg_match('/value="(.*)"[\s\t]+selected="\$lang"/i',$str,$param)) {
	  		$found_param=0;
	  		for($i=0; $i<count($lang_arr); $i++) {
				$temp_lang=$lang_arr[$i];
      			if (($param[1]) == $temp_lang) {
          			$str=preg_replace('/selected="\$lang"/i',"selected",$str);
          			$found_param=1;
          			break;
          		}
      		} 
      		if (!$found_param) $str=preg_replace('/selected="\$lang"/i',"",$str);
  		} elseif (preg_match('/value="(.*)"[\s\t]+selected="\$o"/i',$str,$param)) {
      		if (($param[1]) == $o) {
          		$str=preg_replace('/selected="\$o"/i',"selected",$str);
      		} else {
          		$str=preg_replace('/selected="\$o"/i',"",$str);
      		}
  		} elseif (preg_match('/value="(.*)"[\s\t]+selected="\$m"/i',$str,$param)) {
      		if (($param[1]) == $m) {
          		$str=preg_replace('/selected="\$m"/i',"selected",$str);
      		} else {
          		$str=preg_replace('/selected="\$m"/i',"",$str);
      		}
        } elseif (preg_match('/value="(.*)"[\s\t]+selected="\$wm"/i',$str,$param)) {
      		if (($param[1]) == $wm) {
          		$str=preg_replace('/selected="\$wm"/i',"selected",$str);
      		} else {
          		$str=preg_replace('/selected="\$wm"/i',"",$str);
      		}
        } elseif (preg_match('/value="(.*)"[\s\t]+selected="\$s"/i',$str,$param)) {
      		if ($param[1] == $s) {
          		$str=preg_replace('/selected="\$s"/i',"selected",$str);
      		} else {
          		$str=preg_replace('/selected="\$s"/i',"",$str);
      		}
        } elseif (preg_match('/value="(.*)"[\s\t]+selected="\$sp"/i',$str,$param)) {
      		if ($param[1] == $sp) {
          		$str=preg_replace('/selected="\$sp"/i',"selected",$str);
      		} else {
          		$str=preg_replace('/selected="\$sp"/i',"",$str);
      		}
        } elseif (preg_match('/value="(.*)"[\s\t]+selected="\$sy"/i',$str,$param)) {
      		if ($param[1] == $sy) {
          		$str=preg_replace('/selected="\$sy"/i',"selected",$str);
      		} else {
          		$str=preg_replace('/selected="\$sy"/i',"",$str);
      		}
        } elseif (preg_match('/value="(.*)"[\s\t]+selected="\$wf"/i',$str,$param)) {
      		if (($param[1]) == $wf) {
          		$str=preg_replace('/selected="\$wf"/i',"selected",$str);
      		} else {
          		$str=preg_replace('/selected="\$wf"/i',"",$str);
      		}
  		} elseif (preg_match('/value="(.*)"[\s\t]+selected="\$tag"/i',$str,$param)) {
      		$found_param=0;
			for($i=0; $i<count($tag_arr); $i++) {
				$temp_tag=$tag_arr[$i];
      			if (($param[1]) == $temp_tag) {
          			$str=preg_replace('/selected="\$tag"/i',"selected",$str);
          			$found_param=1;
          			break;
          		}
      		} 
      		if (!$found_param) $str=preg_replace('/selected="\$tag"/i',"",$str);
  			} elseif (preg_match('/type=radio[\s\t]+name="dt"[\s\t]+value="(.*)"/i',$str,$param)) {
      		if (($param[1]) == $dt) {
          		$str=preg_replace("/value=\"$dt\"/i","value=\"$dt\" checked",$str);
      		} else {                                
          		$str=preg_replace('/checked/i',"",$str);
      		}
 	 	} elseif (preg_match('/value="(.*)"[\s\t]+selected="\$dp"/i',$str,$param)) {
      		if (($param[1]) == $dp) {
          		$str=preg_replace('/selected="\$dp"/i',"selected",$str);
      		} else {                                
          		$str=preg_replace('/selected="\$dp"/i',"",$str);
      		}
  		} elseif (preg_match('/value="(.*)"[\s\t]+selected="\$dx"/i',$str,$param)) {
      		if (($param[1]) == $dx) {
          		$str=preg_replace('/selected="\$dx"/i',"selected",$str);
      		} else {                                
          		$str=preg_replace('/selected="\$dx"/i',"",$str);
      		}
  		} elseif (preg_match('/value="(.*)"[\s\t]+selected="\$dm"/i',$str,$param)) {
      		if (($param[1]) == $dm) {
          		$str=preg_replace('/selected="\$dm"/i',"selected",$str);
      		} else {                                
          		$str=preg_replace('/selected="\$dm"/i',"",$str);
      		}
  		} elseif (preg_match('/value="(.*)"[\s\t]+selected="\$dy"/i',$str,$param)) {
      		if (($param[1]) == $dy) {
          		$str=preg_replace('/selected="\$dy"/i',"selected",$str);
      		} else {                                
          		$str=preg_replace('/selected="\$dy"/i',"",$str);
      		}
  		}

		if (preg_match('/\$g\((\d*)\)/i',$str,$param)) {	    	    
		    $str=str_replace("\$g($param[1])",substr($tag,0,strlen($tag)-$param[1]),$str);
		}
	
		//Replace date attributes
        $str=str_replace("\$dd",$dd,$str);
        $str=str_replace("\$dy",$dy,$str);
        $str=str_replace("\$dm",$dm,$str);
        $str=str_replace("\$db",$db,$str);
        $str=str_replace("\$de",$de,$str);

		//Replace page numbering
        $str=str_replace("\$ps",$ps,$str);
		$str=str_replace("\$pn",$ps*$np,$str);
        $str=str_replace("\$cat",$cat,$str);

		
        if (preg_match('/\$IF\s?\((.+)\)/i',$str,$param)) {
		    $param[1]=trim($param[1]);
		    $str=preg_replace('/\$IF\s?\((.+)\)/i',load_file($param[1]),$str);	    
        }

		$comment_flag=0;
		
		//If found comment tag, add empty field to the $templates array for the corresponding tag 
		if (substr($str, 0, 4) == "<!--") {
			reset($names);
			while ($name=current($names)){
			    if (strstr($str, "<!--$name")){
					$where=$name;
		
					$templates["$where"][] = '';
					
					$comment_flag=1;
					break;
			    } elseif (strstr($str, "/$name-->")) {
					$where='';
					$comment_flag=1;
					break;
			    }
			    next($names);
			}
		}
	
		//if found a comment on this line, skip to the next line (do{...})
		if ($comment_flag) continue;

		//if the previous line was a comment and contained 'variables'
		//'variables' is the section of the template where the configuration is
		//Once this section is over, the HTML template really begins
		if ($where=='variables') {
			//if the line starts with a #, it's a comment so skip to next line
		    if (ereg("^#",$str)) continue;

			//look for some characters not space or tab starting a line, 
			//followed by spaces or tabs, followed by any chars (basically any var..value couple)
		    if (preg_match("/^([^\s\t]+)[\s\t]+(.+)/",$str,$param)) {
	            $var=$param[1];
			    $value=$param[2];
			    $var=strtolower($var);
	
			    $var=trim($var);
			    $value=trim($value);                 

			    //assign each value to the corresponding array, for reuse later
			    if ($var=='affix') {
			    	if ($sp=='') $sp=1;
			    	$GLOBALS["ispellmode"]="text";
		       		if (preg_match("/([^\s\t]+)[\s\t]+([^\s\t]+)[\s\t]+(.+)/",$value,$param)) {
		          		$t_lang=trim($param[1]);
                        $t_charset=trim($param[2]);
		          		$file=trim($param[3]);

		          		$affix_file[$t_lang]="$t_charset;$file";
		          	} else {
		          		print "Unsupported Affix command: Affix $value\n<br>";
		          		exit;
		        	}
			    } elseif ($var=='spell') {
                       if ($sp=='') $sp=1;
                       $GLOBALS["ispellmode"]="text";
       	           		if (preg_match("/([^\s\t]+)[\s\t]+([^\s\t]+)[\s\t]+(.+)/",$value,$param)) {
			          		$t_lang=trim($param[1]);
                            $t_charset=trim($param[2]);
			          		$file=trim($param[3]);
	
			          		$spell_file[$t_lang][]="$t_charset;$file";
			       		} else {
			          		print "Unsupported Spell command: Spell $value\n<br>";
			          		exit;
			       		}
				} elseif ($var=='ispellmode') {
                       if (preg_match("/^server[\s\t]+(.+)/i",$value,$param)) {
				          $spell_host=trim($param[1]);
                          $GLOBALS["ispellmode"]="server";
		    		   } else {
		   	 		   	  $GLOBALS["$var"]="$value";
		   			    }
		   		} elseif ($var=='alias') {
                       if (preg_match("/^([^\s]+)\s+(.+)$/i",$value,$param)) {
                       	  $alias_arr[trim($param[1])]=trim($param[2]);
		       			} else {
		       			  $GLOBALS["$var"]="$value";
		       			}
	            } elseif ($var=='stopwordtable') {
	               $stopwordtable_arr[]=$value;
	            } elseif ($var=='stopwordfile') {
	               $stopwordfile_arr[]=$value;
	            } elseif ($var=='synonym') {
	               if ($sy=='') $sy=1;
	               $synonym_arr[]=$value;
	            } elseif ($var=='searchdaddr') {
	                   $searchd_arr[]=$value;
			    } elseif ($var=='r') {
			       $rand=0+substr($var,1);
			       $frand=rand();
			       $frand=$frand/getrandmax()*$value;
			       $Randoms[$rand]=floor($frand);
			    } elseif ($var=='dbaddr') {
		    		$dbaddr[]=$value;
			    } else {
			       $GLOBALS["$var"]="$value";
			    }
	    }
	}
	else
	if ($where!=''){
	    $templates["$where"][count($templates["$where"])-1] .= $str;
	}
	
    } while (!feof($fp));//do this big loop until we reach the end of the file

    fclose($fp);
}
/**
 * Calls back the udm_get_res_field_ex() function whith the second element of
 * the given array...!?
 * @param	array	Array which second element is sent to udm_get_res_field_ex()
 * @return	string	A string
 */
function res_field_callback($expr) {
    global $global_doc_res, $global_res_position;

    return 	ParseDocText(htmlspecialchars(udm_get_res_field_ex($global_doc_res,$global_res_position,$expr[1])));
}


?>
