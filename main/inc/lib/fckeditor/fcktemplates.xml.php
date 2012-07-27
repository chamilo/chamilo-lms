<?php
/**
 *	Chamilo LMS
 *
 *	For a full list of contributors, see "credits.txt".
 *	The full license can be read in "license.txt".
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	See the GNU General Public License for more details.
 */

// Setting the encoding to UTF-8.
header('Content-Type: text/xml; charset=utf-8');

// Name of the language file that needs to be included.
$language_file = 'document';

// Including the global initialization file.
require_once '../../global.inc.php';

// Outputting the opening tag of the xml file.
echo '<?xml version="1.0" encoding="utf-8" ?>';

// Load a stylesheet.
$css = loadCSS(api_get_setting('stylesheets'));

// Setting some paths.
$img_dir = api_get_path(REL_CODE_PATH).'img/';
$default_course_dir = api_get_path(REL_CODE_PATH).'default_course_document/';

// Setting templates for teachers or for students
$is_allowed_to_edit = api_is_allowed_to_edit(false,true);

// Start the templates node.
echo '<Templates imagesBasePath="">';

// Load empty template.
load_empty_template();

if($is_allowed_to_edit){
    // Load the templates that were defined by the platform admin.
    load_platform_templates();
}
else{
    // Load student templates.
    load_student_templates();
}
// Load the personal templates.
    load_personal_templates(api_get_user_id());

// End the templates node.
echo '</Templates>';

exit;


/**
 * Loads a given css style (default.css).
 *
 * @param string $css_name the folder name of the style
 * @return html code for adding a css style <style ...
 */
function loadCSS($css_name) {
    $template_css = file_get_contents(api_get_path(SYS_PATH).'main/css/'.$css_name.'/default.css');
    $template_css = str_replace('../../img/', api_get_path(REL_CODE_PATH).'img/', $template_css);
    $template_css = str_replace('images/', api_get_path(REL_CODE_PATH).'css/'.$css_name.'/images/', $template_css);

    // Reseting the body's background color to be in white, see Task #1885 and http://www.chamilo.org/en/node/713
    $template_css .= "\n".'body { background: #fff; } /* Resetting the background. */'."\n";

    // Removing system-specific styles and cleaning, see task #1282.
    $regex1 = array(
        '/\/\*(.+?)\*\//sm' => '',          // Removing comments.
        '/\r\n/m' => "\n",                  // New lines in Unix style.
        '/\r/m' => "\n"                     // New lines in Unix style.
    );
    $template_css = preg_replace(array_keys($regex1), $regex1, $template_css);
    $template_css = preg_replace('/behavior[^;\{\}]*;/ism', '', $template_css);   // Removing behavior-definition, it is IE-specific.
    $template_css_array = explode('}', $template_css);
    if (!empty($template_css_array)) {
        $deleters = array(
            '/.*\#.*\{[^\}]*\}/sm',         // Removing css definitions bound to system secific elements (identified by id).
            '/.*\..*\{[^\}]*\}/sm',         // Removing css definitions bound to classes, we assume them as system secific.
            // Removing css definitions bound to intractive types of elements that teachers most probably don't need.
            '/.*input.*\{[^\}]*\}/ism',
            '/.*textarea.*\{[^\}]*\}/ism',
            '/.*select.*\{[^\}]*\}/ism',
            '/.*form.*\{[^\}]*\}/ism',
            '/.*button.*\{[^\}]*\}/ism'
        );
        foreach ($template_css_array as $key => & $css_definition) {
            if (trim($css_definition) == '') {
                unset($template_css_array[$key]);
                continue;
            }
            $css_definition = trim($css_definition.'}');
            foreach ($deleters as & $deleter) {
                if (preg_match($deleter, $css_definition)) {
                    unset($template_css_array[$key]);
                }
            }
        }
        $template_css = implode("\n\n", $template_css_array);
    }
    $regex2 = array(
        '/[ \t]*\n/m' => "\n",              // Removing trailing whitespace.
        '/\n{3,}/m' => "\n\n"               // Removing extra empty lines.
    );
    $template_css = preg_replace(array_keys($regex2), $regex2, $template_css);

    if (trim($template_css) == '') {
        return '';
    }

    return "\n".'<style type="text/css">'."\n".$template_css."\n".'</style>'."\n";
}

/**
 * Transforms a language variable into XML-usable code
 *
 * @param unknown_type $var
 * @return unknown
 */
function s($var) {
    static $search = array('&', '<', '>');
    static $replace = array('&amp;',' &amp;lt;', '&amp;gt;');
    return str_replace($search, $replace, api_utf8_encode(get_lang($var, '')));
}

/**
 * Transforms some text into XML-usable code
 *
 * @param unknown_type $var
 * @return unknown
 */
function s2($var) {
    static $search = array('&', '<', '>');
    static $replace = array('&amp;', '&amp;lt;', '&amp;gt;');
    return str_replace($search, $replace, api_utf8_encode($var));
}

/**
 * Loads the platform templates as defined by the platform administrator in
 * "Administration > Configuration settings > Templates"
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version March 2009
 * @since Dokeos 1.8.6
 */
function load_platform_templates() {

    global $css, $img_dir, $default_course_dir, $js;

    $table_template = Database::get_main_table('system_template');
    $sql = "SELECT title, image, comment, content FROM $table_template";
    $result = Database::query($sql);

    $search = array('{CSS}', '{IMG_DIR}', '{REL_PATH}', '{COURSE_DIR}');
    $replace = array($css.$js, $img_dir, api_get_path(REL_PATH), $default_course_dir);
    $template_thumb = api_get_path(WEB_PATH).'home/default_platform_document/template_thumb/';

    while ($row = Database::fetch_array($result)) {
        $image = empty($row['image']) ? $template_thumb.'empty.gif' : $template_thumb.$row['image'];
        $row['content'] = str_replace($search, $replace, $row['content']);

        echo '
                <Template title="'.s($row['title']).'" image="'.$image.'">
                    <Description>'.s($row['comment']).'</Description>
                    <Html>
                        <![CDATA[
                                '.$row['content'].'
                        ]]>
                    </Html>
                </Template>';
    }
}

/**
 * Loads all the personal templates of the user when
 *
 * @param integer $user_id the id of the user
 * @return xml node
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version March 2009
 * @since Dokeos 1.8.6 The code already existed but not in a function and a lot less performant.
 */
function load_personal_templates($user_id = 0) {
    global $_course;

    // the templates that the user has defined are only available inside the course itself
    if (empty($_course)) {
        return false;
    }

    // For which user are we getting the templates?
    if ($user_id == 0) {
        $user_id = api_get_user_id();
    }

    $table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
    $table_document = Database::get_course_table(TABLE_DOCUMENT);
    
    $course_id = api_get_course_int_id();

    // The sql statement for getting all the user defined templates
    $sql = "SELECT template.id, template.title, template.description, template.image, template.ref_doc, document.path
            FROM ".$table_template." template, ".$table_document." document
            WHERE 
                user_id='".Database::escape_string($user_id)."' AND 
                course_code='".Database::escape_string(api_get_course_id())."' AND
                document.c_id = $course_id AND 
                document.id = template.ref_doc";

    $result_template = Database::query($sql);

    while ($row = Database::fetch_array($result_template)) {

        $row['content'] = file_get_contents(api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$row['path']);
        //$row['content'] = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$row['path'];

        if (!empty($row['image'])) {
            $image = api_get_path(WEB_PATH).'courses/'.$_course['path'].'/upload/template_thumbnails/'.$row['image'];
        } else {
            $image = api_get_path(WEB_PATH).'home/default_platform_document/template_thumb/noimage.gif';
        }

        echo '
                <Template title="'.s2($row['title']).'" image="'.$image.'">
                    <Description>'.s2($row['Description']).'</Description>
                    <Html>
                        <![CDATA[
                                '.$row['content'].'
                        ]]>
                    </Html>
                </Template>';
    }
}

function load_empty_template() {
    global $css, $js;
    ?>
<Template title="<?php echo s2('Empty'); ?>" image="<?php echo api_get_path(WEB_PATH).'home/default_platform_document/template_thumb/empty.gif'; ?>">
    <Description></Description>
    <Html>
        <![CDATA[
           <html>
           <head>
            <meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
            <?php echo $css; ?>
            <?php echo $js; ?>
           </head>
           <body  dir="<?php echo api_get_text_direction(); ?>">
           </body>
           </html>
       ]]>
    </Html>
</Template>
<?php
}

/**
 * Loads the student templates
 */
function load_student_templates() {
    $fckeditor_template_path='/main/inc/lib/fckeditor/editor/dialog/fck_template/images/';
    ?>
    <Template title="Image and Title" image="<?php echo api_get_path(WEB_PATH).$fckeditor_template_path.'template1.gif';?>">
        <Description>One main image with a title and text that surround the image.</Description>
        <Html>
            <![CDATA[
                <img style="MARGIN-RIGHT: 10px" height="100" alt="" width="100" align="left"/>
                <h3>Type the title here</h3>
                Type the text here
            ]]>
        </Html>
    </Template>
    <Template title="Strange Template" image="<?php echo api_get_path(WEB_PATH).$fckeditor_template_path.'template2.gif';?>">
        <Description>A template that defines two colums, each one with a title, and some text.</Description>
        <Html>
            <![CDATA[
                <table cellspacing="0" cellpadding="0" width="100%" border="0">
                    <tbody>
                        <tr>
                            <td width="50%">
                            <h3>Title 1</h3>
                            </td>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </td>
                            <td width="50%">
                            <h3>Title 2</h3>
                            </td>
                        </tr>
                        <tr>
                            <td>Text 1</td>
                            <td>&nbsp;</td>
                            <td>Text 2</td>
                        </tr>
                    </tbody>
                </table>
                More text goes here.
            ]]>
        </Html>
    </Template>
    <Template title="Text and Table" image="<?php echo api_get_path(WEB_PATH).$fckeditor_template_path.'template3.gif';?>">
        <Description>A title with some text and a table.</Description>
        <Html>
            <![CDATA[
                <table align="left" width="80%" border="0" cellspacing="0" cellpadding="0"><tr><td>
                    <h3>Title goes here</h3>
                    <p>
                    <table style="FLOAT: right" cellspacing="0" cellpadding="0" width="150" border="1">
                        <tbody>
                            <tr>
                                <td align="center" colspan="3"><strong>Table title</strong></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                    Type the text here</p>
                </td></tr></table>
            ]]>
        </Html>
    </Template>
    <?php
}