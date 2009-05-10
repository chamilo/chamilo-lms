<?php 
// setting the character set to UTF-8
header('Content-Type: text/xml; charset=utf-8');

// name of the language file that needs to be included
$language_file = 'document';

// including the global dokeos file
require_once('../../global.inc.php');

// outputting the opening tag of the xml file
echo '<?xml version="1.0" encoding="utf-8" ?>';

// is this needed? 
$IMConfig['base_url'] = $_configuration['root_web'].'main/img/gallery/';

// load a stylesheet
$css = loadCSS(api_get_setting('stylesheets'));

// setting some paths
$img_dir = api_get_path(REL_CODE_PATH).'img/';
$default_course_dir = api_get_path(REL_CODE_PATH).'default_course_document/';

// start the templates node
echo '<Templates imagesBasePath="">';

// load empty template
load_empty_template();

// load the templates that were defined by the platform admin
load_platform_templates();

// load the personal templates
load_personal_templates(api_get_user_id());

// end the templates node
echo '</Templates>';
exit;


/**
 * load a given css style (default.css)
 *
 * @param string $css_name the folder name of the style
 * @return html code for adding a css style <style ...
 */
function loadCSS($css_name)
{
	$template_css = ' <style type="text/css">'.str_replace('../../img/',api_get_path(REL_CODE_PATH).'img/',file_get_contents(api_get_path(SYS_PATH).'main/css/'.$css_name.'/default.css')).'</style>';
	$template_css = str_replace('images/',api_get_path(REL_CODE_PATH).'css/'.$css_name.'/images/',$template_css);
	return $template_css;
}

/**
 * Transforms a language variable into XML-usable code
 *
 * @param unknown_type $var
 * @return unknown
 */
function s($var)
{
	global $charset;
    $search = array('&','<','>');
    $replace = array ('&amp;','&amp;lt;','&amp;gt;');
    return str_replace($search,$replace,api_convert_encoding(get_lang($var),'UTF-8',$charset));
}

/**
 * Transforms a language variable into XML-usable code
 *
 * @param unknown_type $var
 * @return unknown
 */
function s2($var)
{
	global $charset;
    $search = array('&','<','>');
    $replace = array ('&amp;','&amp;lt;','&amp;gt;');
    return str_replace($search,$replace,api_convert_encoding($var,'UTF-8',$charset));
}

/**
 * Load the platform templates as defined by the platform administrator in "Platform administration > Dokeos configuration settings > templates"
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version March 2009
 * @since Dokeos 1.8.6
 */
function load_platform_templates() {
	// Database table definition
	$table_template = Database::get_main_table('system_template');	
	global $css, $img_dir, $default_course_dir;
	$sql = "SELECT title, image, comment, content FROM $table_template";
	
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result)) {
        if (!empty($row['image'])) {
            $image = api_get_path(WEB_PATH).'home/default_platform_document/'.$row['image'];
        } else {
            $image = api_get_path(WEB_PATH).'home/default_platform_document/empty.gif';
        }        
      	$row['content'] =  str_replace('{CSS}',$css, $row['content']);
      	$row['content'] =  str_replace('{IMG_DIR}',$img_dir, $row['content']);
      	$row['content'] =  str_replace('{REL_PATH}', api_get_path(REL_PATH), $row['content']);
      	$row['content'] =  str_replace('{COURSE_DIR}',$default_course_dir, $row['content']);

		echo '	<Template title="'.s(get_lang($row['title'])).'" image="'.$image.'">
					<Description>'.s(get_lang($row['comment'])).'</Description>
					<Html>
						<![CDATA[
							    '.$row['content'].'
						]]>
					</Html>
				</Template>';			
	}
}

/**
 * Load all the personal templates of the user when 
 *
 * @param integer $user_id the id of the user
 * @return xml node
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version March 2009
 * @since Dokeos 1.8.6 The code already existed but not in a function and a lot less performant. 
 */
function load_personal_templates($user_id=0) {
	global $_course; 

	// the templates that the user has defined are only available inside the course itself
	if (empty($_course))
	{
		return false;
	}
	
	// For which user are we getting the templates? 
	if ($user_id==0)
	{
		$user_id = api_get_user_id();
	}

	// Database table definition
	$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);	
	$table_document = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);	
	
	
	// The sql statement for getting all the user defined templates
	$sql = "SELECT template.id, template.title, template.description, template.image, template.ref_doc, document.path 
			FROM ".$table_template." template, ".$table_document." document 
			WHERE user_id='".Database::escape_string($user_id)."'
			AND course_code='".Database::escape_string(api_get_course_id())."'
			AND document.id = template.ref_doc"; 
	$result_template = api_sql_query($sql,__FILE__,__LINE__);
	while ($row = Database::fetch_array($result_template))
	{
		$row['content'] = file_get_contents(api_get_path('SYS_COURSE_PATH').$_course['path'].'/document'.$row['path']);
		//$row['content'] = api_get_path('SYS_COURSE_PATH').$_course['path'].'/document'.$row['path'];
		
		if (!empty($row['image']))
		{
			$image = api_get_path(WEB_CODE_PATH).'upload/template_thumbnails/'.$row['image'];
		} else {			
			$image = api_get_path(WEB_PATH).'home/default_platform_document/noimage.gif';
		}
		
		
		echo '	<Template title="'.s2($row['title']).'" image="'.$image.'">
					<Description>'.s2($row['Description']).'</Description>
					<Html>
						<![CDATA[
							    '.$row['content'].'
						]]>
					</Html>
				</Template>';		
	}	
}

function load_empty_template()
{
	global $css;			
	?>
<Template title="<?php echo s2('Empty'); ?>" image="<?php echo api_get_path(WEB_PATH).'home/default_platform_document/empty.gif'; ?>">
    <Description></Description>
    <Html>
	    <![CDATA[
		   <html>
		   <head>
			<?php echo $css ?>   
		   <body></body>
		   </head>
		   </html>
	   ]]>
    </Html>
</Template>
<?php
}
?>
