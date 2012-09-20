<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating audio files from a text.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 8/January/2011
 * TODO:clean all file
*/
/**
 * Code
 */

/*	INIT SECTION */
$language_file = array('document');

require_once '../inc/global.inc.php';
$_SESSION['whereami'] = 'document/createaudio';
$this_section = SECTION_COURSES;

require_once 'document.inc.php';

$nameTools = get_lang('CreateAudio');

api_protect_course_script();
api_block_anonymous_users();
if (api_get_setting('enabled_text2audio') == 'false'){
	api_not_allowed(true);
}

$document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id());
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties   = GroupManager::get_group_properties(api_get_group_id());        
        $document_id        = DocumentManager::get_document_id(api_get_course_info(), $group_properties['directory']);
        $document_data      = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());
    }
}
$document_id = $document_data['id'];
$dir = $document_data['path'];
//jquery textareaCounter
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/textareacounter/jquery.textareaCounter.plugin.js" type="text/javascript"></script>';

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

// Please, do not modify this dirname formatting

if (strstr($dir, '..')) {
	$dir = '/';
}

if ($dir[0] == '.') {
	$dir = substr($dir, 1);
}

if ($dir[0] != '/') {
	$dir = '/'.$dir;
}

if ($dir[strlen($dir) - 1] != '/') {
	$dir .= '/';
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
	$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
	$dir = '/';
}

//groups //TODO: clean
if (isset ($_SESSION['_gid']) && $_SESSION['_gid'] != 0) {
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[] = array ("url" => "../group/group_space.php?gidReq=".$_SESSION['_gid'], "name" => get_lang('GroupSpace'));
	$noPHP_SELF = true;
	$to_group_id = $_SESSION['_gid'];
	$group = GroupManager :: get_group_properties($to_group_id);
	$path = explode('/', $dir);
	if ('/'.$path[1] != $group['directory']) {
		api_not_allowed(true);
	}
}

$interbreadcrumb[] = array ("url" => "./document.php?curdirpath=".urlencode($dir).$req_gid, "name" => get_lang('Documents'));

if (!$is_allowed_in_course) {
	api_not_allowed(true);
}


if (!($is_allowed_to_edit || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder(api_get_user_id(), Security::remove_XSS($dir),api_get_session_id()))) {
	api_not_allowed(true);
}


/*	Header */
event_access_tool(TOOL_DOCUMENT);

$display_dir = $dir;
if (isset ($group)) {
	$display_dir = explode('/', $dir);
	unset ($display_dir[0]);
	unset ($display_dir[1]);
	$display_dir = implode('/', $display_dir);
}

// Interbreadcrumb for the current directory root path
	// Copied from document.php
	$dir_array = explode('/', $dir);
	$array_len = count($dir_array);
	
	$dir_acum = '';
	for ($i = 0; $i < $array_len; $i++) {
		$url_dir = 'document.php?&curdirpath='.$dir_acum.$dir_array[$i];
		//Max char 80
		$url_to_who = cut($dir_array[$i],80);
		if ($is_certificate_mode) {
			$interbreadcrumb[] = array('url' => $url_dir.'&selectcat='.Security::remove_XSS($_GET['selectcat']), 'name' => $url_to_who);
		} else {
			$interbreadcrumb[] = array('url' => $url_dir, 'name' => $url_to_who);
		}
		$dir_acum .= $dir_array[$i].'/';
	}

Display :: display_header($nameTools, 'Doc');

echo '<div class="actions">';
		echo '<a href="document.php?id='.$document_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';
		
		echo '<a href="create_audio.php?'.api_get_cidreq().'&amp;id='.$document_id.'&amp;dt2a=google">'.Display::return_icon('google.png',get_lang('GoogleAudio'),'',ICON_SIZE_MEDIUM).'</a>';
		
		echo '<a href="create_audio.php?'.api_get_cidreq().'&amp;id='.$document_id.'&amp;dt2a=pediaphon">'.Display::return_icon('pediaphon.png', get_lang('Pediaphon'),'',ICON_SIZE_MEDIUM).'</a>';

		
echo '</div>';

?>
<!-- javascript and styles for textareaCounter-->
<script type="text/javascript">
var info;
$(document).ready(function(){
	var options = {
		'maxCharacterSize': 100,
		'originalStyle': 'originalTextareaInfo',
		'warningStyle' : 'warningTextareaInfo',
		'warningNumber': 20,
		'displayFormat' : '#input/#max'
	};
	$('#textarea_google').textareaCount(options, function(data){
		$('#textareaCallBack').html(data);				
	});    
});

</script>		        
<style>
.overview {
	background: #FFEC9D;
	padding: 10px;
	width: 90%;
	border: 1px solid #CCCCCC;
}

.originalTextareaInfo {
	font-size: 12px;
	color: #000000;
	text-align: right;
}

.warningTextareaInfo {
	color: #FF0000;
	font-weight:bold;
	text-align: right;	
}

#showData {
	height: 70px;
	width: 200px;
	border: 1px solid #CCCCCC;
	padding: 10px;
	margin: 10px;
}
</style>
<div id="textareaCallBack"></div>
<?php
	if(Security::remove_XSS($_POST['text2voice_mode'])=='google'){  
    	downloadMP3_google($filepath, $dir);
	}
	elseif(Security::remove_XSS($_POST['text2voice_mode'])=='pediaphon'){   
    	downloadMP3_pediaphon($filepath, $dir);
	}
    
    $tbl_admin_languages    = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
    $sql_select = "SELECT * FROM $tbl_admin_languages";
    $result_select = Database::query($sql_select);              

    $options = $options_pedia = array();
    $selected_language = null;
	$options_pedia['defaultmessage'] = get_lang('FirstSelectALanguage');//need read before platform languages
	
    while ($row = Database::fetch_array($result_select)) {       
        if (api_get_setting('platformLanguage')==$row['english_name']) {        
            //$selected_language = $row['isocode'];//lang default is the default platform language
        }
		
        $options[$row['isocode']] =$row['original_name'].' ('.$row['english_name'].')';

        if (in_array($row['isocode'], array('de', 'en', 'es', 'fr'))){		
            $options_pedia[$row['isocode']] =$row['original_name'].' ('.$row['english_name'].')';
        }        
    }
	
	$icon = Display::return_icon('text2audio.png', get_lang('HelpText2Audio'),'',ICON_SIZE_MEDIUM);
	echo '<div class="page-header"><h2>'.$icon.get_lang('HelpText2Audio').'</h2></div>'; 
	
  
    if(Security::remove_XSS($_GET['dt2a'])=='google'){
		$selected_language = api_get_language_isocode();//lang default is the course language
		echo '<div>';
		$form = new FormValidator('form1', 'post', null, '', array('id' => 'form1'));
		$form->addElement('hidden', 'text2voice_mode', 'google');
		$form->addElement('hidden', 'document_id', $document_id);
		$form->addElement('text', 'title', get_lang('Title'));
		$form->addElement('select', 'lang', get_lang('Language'), $options);    
		$form->addElement('textarea', 'text', get_lang('InsertText2Audio'), array('id' => 'textarea_google', 'class' =>'span6' ));
		//echo Display :: return_icon('info3.gif', get_lang('HelpGoogleAudio'), array('align' => 'absmiddle', 'hspace' => '3px'), false);
		$form->addElement('style_submit_button', 'submit', get_lang('SaveMP3'), 'class="save"');
		$defaults = array();
		$defaults['lang'] = $selected_language;   
		$form->setDefaults($defaults);        
		$form->display();

		echo '</div>';
	}
	
	if(Security::remove_XSS($_GET['dt2a'])=='pediaphon'){
		//lang default is a default message
		$selected_language = "defaultmessage";
		
		$options['defaultmessage'] = get_lang('FirstSelectALanguage'); 
		echo '<div>';
		
		$form = new FormValidator('form2', 'post', null, '', array('id' => 'form2'));
		$form->addElement('hidden', 'text2voice_mode','pediaphon');
		$form->addElement('hidden', 'document_id', $document_id);
		$form->addElement('text', 'title', get_lang('Title'));
		$form->addElement('select', 'lang', get_lang('Language'), $options_pedia, array('onclick' => 'update_voices(this.selectedIndex);'));
		$form->addElement('select', 'voices', get_lang('Voice'), array(get_lang('FirstSelectALanguage')), array());            
		$speed_options = array();
		$speed_options['1']     = get_lang('Normal');
		$speed_options['0.75']  = get_lang('GoFaster');
		$speed_options['0.8']   = get_lang('Fast');
		$speed_options['1.2']   = get_lang('Slow');
		$speed_options['1.6']   = get_lang('SlowDown');
		
		$form->addElement('select', 'speed', get_lang('Speed'), $speed_options, array());        
		$form->addElement('textarea', 'text', get_lang('InsertText2Audio'), array('id' => 'textarea_pediaphon', 'class' =>'span6'));
		//echo Display :: return_icon('info3.gif', get_lang('HelpPediaphon'), array('align' => 'absmiddle', 'hspace' => '3px'), false);
		    
		$form->addElement('style_submit_button', 'submit', get_lang('SaveMP3'), 'class="save"');
		$defaults = array();
		$defaults['lang'] = $selected_language;    
		$form->setDefaults($defaults);        
		$form->display();
		echo '</div>';
		
		?>
		
		<!-- javascript form name form2 update voices -->
		<script type="text/javascript">      
		var langslist=document.form2.lang
		var voiceslist=document.form2.voices     
		var voices=new Array()
		
		<!--Default message -->
		voices[0]=["<?php echo get_lang('FirstSelectALanguage'); ?>"]
		
		<!--German -->
		voices[1]=["<?php echo get_lang('Female').' (de1)'; ?>|de1", "<?php echo get_lang('Male').' (de2)'; ?>|de2", "<?php echo get_lang('Female').' (de3)'; ?>|de3", "<?php echo get_lang('Male').' (de4)'; ?>|de4", "<?php echo get_lang('Female').' (de5)'; ?>|de5", "<?php echo get_lang('Male').' (de6)'; ?>|de6", "<?php echo get_lang('Female').' (de7)'; ?>|de7", "<?php echo get_lang('Female').' (de8 HQ)'; ?>|de8"]
		
		<!--English -->
		voices[2]=["<?php echo get_lang('Male').' (en1)'; ?>|en1", "<?php echo get_lang('Male').' (en2 HQ)'; ?>|en2", "<?php echo get_lang('Female').' (us1)'; ?>| us1", "<?php echo get_lang('Male').' (us2)'; ?>|us2", "<?php echo get_lang('Male').' (us3)'; ?>|us3", "<?php echo get_lang('Female').'(us4 HQ)'; ?>|us4"]	
		
		<!--Spanish -->
		voices[3]=["<?php echo get_lang('Male').' (es5 HQ)'; ?>|es5"]
		
		<!--French -->
		voices[4]=["<?php echo get_lang('Female').' (fr8 HQ)'; ?>|fr8"]
			
			 
		function update_voices(selectedvoicegroup){
		voiceslist.options.length=0
		for (i=0; i<voices[selectedvoicegroup].length; i++)
			voiceslist.options[voiceslist.options.length]=new Option(voices[selectedvoicegroup][i].split("|")[0], voices[selectedvoicegroup][i].split("|")[1])
		}
		</script>    

     
    <?php
	}//end pediaphon
	
	//vozMe services
		//disabled for a time
	/*
	echo '<div>&nbsp;</div><input type="checkbox" value="1" name="checktext2voice" onclick="javascript: if(this.checked){document.getElementById(\'option3\').style.display=\'block\';}else{document.getElementById(\'option3\').style.display=\'none\';}"/>&nbsp;<img src="../img/file_sound.gif" title="'.get_lang('HelpvozMe').'" alt="'.get_lang('vozMe').'"/>&nbsp;'.get_lang('vozMe').'';
	echo '&nbsp;&nbsp;&nbsp;<span id="msg_error3" style="display:none;color:red"></span>';
	echo '<div id="option3" style="padding:4px; margin:5px; border:1px dotted; display:none;">';
	
	echo '<form id="form3" name="form3" method="post" action="http://vozme.com/text2voice.php" target="mymp3" class="formw">';
		echo '<br/>';
		echo '<label>'.get_lang('Language').': ';
		$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
		$sql_select = "SELECT * FROM $tbl_admin_languages";
		$result_select = Database::query($sql_select);		
		echo '<select name="lang" id="select">';
		while ($row = Database::fetch_array($result_select)) {
			
			if (in_array($row['isocode'], array('ca', 'en', 'es', 'hi', 'it', 'pt'))){
			
				if (api_get_setting('platformLanguage')==$row['english_name']){
					echo '<option value="'.$row['isocode'].'" selected="selected">'.$row['original_name'].' ('.$row['english_name'].')</option>';
				}
				else{					
					echo '<option value="'.$row['isocode'].'">'.$row['original_name'].' ('.$row['english_name'].')</option>';
				}
			}
		}
		echo '</select>';
		echo '</label>';
		echo '<label>&nbsp;&nbsp;'.get_lang('Voice').': ';
		echo '<select name="gn" id="select1">';
		echo '<option value="ml">'.get_lang('Male').'</option>';
		echo '<option value="fm">'.get_lang('Female').'</option>';
		echo '</select>';
		echo '</label>';
		echo '<br/><br/>';
		echo '<div>'.get_lang('InsertText2Audio').'</div>';
		echo '<br/>';
		echo '<label>';
		echo '<textarea name="text" id="textarea" cols="70" rows="10"></textarea>';
		echo '</label>';  
		echo '<br/><br/>';  
		echo '<button class="save" type="submit" name="SendText2Audio">'.get_lang('BuildMP3').'</button>';
		echo '<br/>';
	echo '</form>';
	echo '</div>';
	*/
echo '</div>';

Display :: display_footer();

//Functions. TODO:all at one

/**
 * This function save a post into a file mp3 from google services
 *
 * @param $filepath
 * @param $dir
 * @author Juan Carlos Raña Trabado <herodoto@telefonica.net>
 * @version january 2011, chamilo 1.8.8
 */
function downloadMP3_google($filepath, $dir) {
	$location='create_audio.php?'.api_get_cidreq().'&id='.Security::remove_XSS($_POST['document_id']).'&dt2a=google';

	//security
	if (!isset($_POST['lang']) && !isset($_POST['text']) && !isset($_POST['title']) && !isset($filepath) && !isset($dir)) {
		echo '<script>window.location.href="'.$location.'"</script>';
		return;	
	}
    global $_course, $_user;
	$clean_title=trim($_POST['title']);
	$clean_text=trim($_POST['text']);
	if(empty($clean_title) || empty($clean_text)){ 
		echo '<script>window.location.href="'.$location.'"</script>';
		return;
	}
	$clean_title=Security::remove_XSS($clean_title);
	$clean_title=Database::escape_string($clean_title);	
	$clean_title=str_replace(' ','_', $clean_title);//compound file names
	
	$clean_text=Security::remove_XSS($clean_text);
	$clean_lang=Security::remove_XSS($_POST['lang']);	
	
	$extension='mp3';
	$audio_filename=$clean_title.'.'.$extension;	
	$audio_title = str_replace('_',' ',$clean_title);
	
	//prevent duplicates
	if (file_exists($filepath.'/'.$clean_title.'.'.$extension)){ 
		$i = 1;		
		while (file_exists($filepath.'/'.$clean_title.'_'.$i.'.'.$extension)) $i++;
		$audio_filename = $clean_title . '_' . $i . '.'.$extension;
		$audio_title = $clean_title . '_' . $i . '.'.$extension;
		$audio_title = str_replace('_',' ',$audio_title);
	}

	$documentPath = $filepath.'/'.$audio_filename;
	
	
	//prev for a fine unicode, borrowed from main api TODO:clean
	// Safe replacements for some non-letter characters (whitout blank spaces)
	$search  = array("\0", "\t", "\n", "\r", "\x0B", '/', "\\", '"', "'", '?', '*', '>', '<', '|', ':', '$', '(', ')', '^', '[', ']', '#', '+', '&', '%');
	$replace = array('',  '_',  '_',  '_',  '_',    '-', '-',  '-', '_', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-');	
	$filename=$clean_text;
	// Encoding detection.
	$encoding = api_detect_encoding($filename);
	// Converting html-entities into encoded characters.
	$filename = api_html_entity_decode($filename, ENT_QUOTES, $encoding);
	// Transliteration to ASCII letters, they are not dangerous for filesystems.
	$filename = api_transliterate($filename, 'x', $encoding);
    // Replacing remaining dangerous non-letter characters.
    $clean_text = str_replace($search, $replace, $filename);
	
	$returntext2voice=file_get_contents("http://translate.google.com/translate_tts?tl=".$clean_lang."&q=".urlencode($clean_text)."");
	
	//make a temporal file for get the file size
		$tmpfname = tempnam("/tmp", "CTF");
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $returntext2voice);
		fclose($handle);
		// Check if there is enough space in the course to save the file
		if (!DocumentManager::enough_space(filesize($tmpfname), DocumentManager::get_course_quota())) {
			unlink($tmpfname);
			die(get_lang('UplNotEnoughSpace'));
		}
		//erase temporal file
		unlink($tmpfname);
		
	
	//adding the file
		//add new file to disk
		file_put_contents($documentPath, $returntext2voice);
		//add document to database
		$current_session_id = api_get_session_id();
		$groupId=$_SESSION['_gid'];
		$file_size = filesize($documentPath);
		$relativeUrlPath=$dir;
		$doc_id = add_document($_course, $relativeUrlPath.$audio_filename, 'file', filesize($documentPath), $audio_title);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
        Display::display_confirmation_message(get_lang('DocumentCreated'));
	//return to location
	echo '<script>window.location.href="'.$location.'"</script>';
}

/**
 * This function save a post into a file mp3 from pediaphon services
 *
 * @param $filepath
 * @param $dir
 * @author Juan Carlos Raña Trabado <herodoto@telefonica.net>
 * @version january 2011, chamilo 1.8.8
 */
function downloadMP3_pediaphon($filepath, $dir){
	$location='create_audio.php?'.api_get_cidreq().'&id='.Security::remove_XSS($_POST['document_id']).'&dt2a=pediaphon';
	//security
	if(!isset($_POST['lang']) && !isset($_POST['text']) && !isset($_POST['title']) && !isset($filepath) && !isset($dir)) {
		echo '<script>window.location.href="'.$location.'"</script>';
		return;	
	}
    global $_course, $_user;
	$clean_title=trim($_POST['title']);
	$clean_title= Database::escape_string($clean_title);
	$clean_text=trim($_POST['text']);
	$clean_voices=Security::remove_XSS($_POST['voices']);
	if(empty($clean_title) || empty($clean_text) || empty($clean_voices)){
		echo '<script>window.location.href="'.$location.'"</script>';
		return;
	}
	$clean_title=Security::remove_XSS($clean_title);
	$clean_title=Database::escape_string($clean_title);	
	$clean_title=str_replace(' ','_', $clean_title);//compound file names
	$clean_text=Security::remove_XSS($clean_text);
	$clean_lang=Security::remove_XSS($_POST['lang']);
	$clean_speed=Security::remove_XSS($_POST['speed']);
	
	$extension='mp3';
	$audio_filename=$clean_title.'.'.$extension;
	$audio_title = str_replace('_',' ',$clean_title);
	
	//prevent duplicates
	if (file_exists($filepath.'/'.$clean_title.'.'.$extension)){ 
		$i = 1;		
		while (file_exists($filepath.'/'.$clean_title.'_'.$i.'.'.$extension)) $i++;
		$audio_filename = $clean_title . '_' . $i . '.'.$extension;
		$audio_title = $clean_title . '_' . $i . '.'.$extension;
		$audio_title = str_replace('_',' ',$audio_title);
	}

	$documentPath = $filepath.'/'.$audio_filename;
	



	//prev for a fine unicode, borrowed from main api TODO:clean
	// Safe replacements for some non-letter characters (whitout blank spaces)
	$search  = array("\0", "\t", "\n", "\r", "\x0B", '/', "\\", '"', "'", '?', '*', '>', '<', '|', ':', '$', '(', ')', '^', '[', ']', '#', '+', '&', '%');
	$replace = array('',  '_',  '_',  '_',  '_',    '-', '-',  '-', '_', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-');	
	$filename=$clean_text;
	// Encoding detection.
	$encoding = api_detect_encoding($filename);
	// Converting html-entities into encoded characters.
	$filename = api_html_entity_decode($filename, ENT_QUOTES, $encoding);
	// Transliteration to ASCII letters, they are not dangerous for filesystems.
	$filename = api_transliterate($filename, 'x', $encoding);
    // Replacing remaining dangerous non-letter characters.
    $clean_text = str_replace($search, $replace, $filename);
	
	//adding the file
	
		
		if($clean_lang=='de'){
			$url_pediaphon='http://www.pediaphon.org/~bischoff/radiopedia/sprich_multivoice.cgi';
			$find_t2v = '/http\:\/\/www\.pediaphon\.org\/\~bischoff\/radiopedia\/mp3\/(.*)\.mp3\"/';
		}		
		else{
			$url_pediaphon='http://www.pediaphon.org/~bischoff/radiopedia/sprich_multivoice_'.$clean_lang.'.cgi';//en, es, fr
			$find_t2v = '/http\:\/\/www\.pediaphon\.org\/\~bischoff\/radiopedia\/mp3\/'.$clean_lang.'\/(.*)\.mp3\"/';
		}

		$data="stimme=".$clean_voices."&inputtext=".$clean_text."&speed=".$clean_speed."&go=go";
		$opts = array('http' =>
			array(
			 'method'  => 'POST',
			 'header'  =>"Content-Type: application/x-www-form-urlencoded\r\n",
			 "Content-Length: " . strlen($data) . "\r\n",
			 'content' => $data
			)
		);                   
		$context  = stream_context_create($opts);
		$previous_returntext2voice = file_get_contents($url_pediaphon,false,$context);
		
		//clean file contents
		
		$search_source=preg_match($find_t2v, $previous_returntext2voice, $hits);
		$souce_end=substr($hits[0], 0,-1);
		$returntext2voice = file_get_contents($souce_end);
		
		//make a temporal file for get the file size
			$tmpfname = tempnam("/tmp", "CTF");
			$handle = fopen($tmpfname, "w");
			fwrite($handle, $returntext2voice);
			fclose($handle);
			// Check if there is enough space in the course to save the file
			if (!DocumentManager::enough_space(filesize($tmpfname), DocumentManager::get_course_quota())) {
				unlink($tmpfname);
				die(get_lang('UplNotEnoughSpace'));
			}
			//erase temporal file
			unlink($tmpfname);
		
		//save file
		file_put_contents($documentPath, $returntext2voice);
		
		//add document to database
		$current_session_id = api_get_session_id();
		$groupId=$_SESSION['_gid'];
		$file_size = filesize($documentPath);
		$relativeUrlPath=$dir;
		$doc_id = add_document($_course, $relativeUrlPath.$audio_filename, 'file', filesize($documentPath), $audio_title);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
        Display::display_confirmation_message(get_lang('DocumentCreated'));
	//return to location
	echo '<script>window.location.href="'.$location.'"</script>';
}