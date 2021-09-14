<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Edition of extensions configuration.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
// Database Table Definitions
$tbl_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
$message = '';

if (isset($_POST['activeExtension'])) {
    switch ($_POST['extension_code']) {
        case 'ppt2lp':
            $sql = 'UPDATE '.$tbl_settings_current.' SET
                    selected_value="true"
                    WHERE variable="service_ppt2lp"
                    AND subkey="active"';

            $rs = Database::query($sql);

            if (Database::affected_rows($rs) > 0) {
                $message = get_lang('Service activated');
            }

            $sql = 'UPDATE '.$tbl_settings_current.' SET
                    selected_value="'.addslashes($_POST['host']).'"
                    WHERE variable="service_ppt2lp"
                    AND subkey="host"';
            Database::query($sql);

            $sql = 'UPDATE '.$tbl_settings_current.' SET
                    selected_value="'.addslashes($_POST['port']).'"
                    WHERE variable="service_ppt2lp"
                    AND subkey="port"';
            Database::query($sql);

            $sql = 'UPDATE '.$tbl_settings_current.' SET
                    selected_value="'.addslashes($_POST['ftp_password']).'"
                    WHERE variable="service_ppt2lp"
                    AND subkey="ftp_password"';
            Database::query($sql);

            $sql = 'UPDATE '.$tbl_settings_current.' SET
                    selected_value="'.addslashes($_POST['user']).'"
                    WHERE variable="service_ppt2lp"
                    AND subkey="user"';
            Database::query($sql);

            $sql = 'UPDATE '.$tbl_settings_current.' SET
                    selected_value="'.addslashes($_POST['path_to_lzx']).'"
                    WHERE variable="service_ppt2lp"
                    AND subkey="path_to_lzx"';
            Database::query($sql);

            $sql = 'UPDATE '.$tbl_settings_current.' SET
                    selected_value="'.addslashes($_POST['size']).'"
                    WHERE variable="service_ppt2lp"
                    AND subkey="size"';
            Database::query($sql);

            break;
    }
}

$listActiveServices = [];

// get the list of active services
$sql = 'SELECT variable FROM '.$tbl_settings_current.'
		WHERE variable LIKE "service_%" AND subkey="active" and selected_value="true"';

$rs = Database::query($sql);
while ($row = Database::fetch_array($rs)) {
    $listActiveServices[] = $row['variable'];
}

// javascript to handle accordion behaviour
$javascript_message = '';
if (!empty($message)) {
    $javascript_message = '
    document.getElementById("message").style.display = "block";
    var timer = setTimeout(hideMessage, 5000);';
}
$htmlHeadXtra[] = '<script>
var listeDiv;
var extensionsHeader = new Array();
var extensionsContent = new Array();
window.onload = loadTables;
function loadTables(){
	'.$javascript_message.'
	var listeDiv = document.getElementsByTagName("div");

	// fill extensionsHeader and extensionsContent
	for(var i=0 ; i < listeDiv.length ; i++){
		if(listeDiv[i].id.indexOf(\'extension_header\')!=-1){
			listeDiv[i].onclick = afficheContent;
			extensionsHeader.push(listeDiv[i]);
		}
		if(listeDiv[i].id.indexOf("extension_content")!=-1){
			extensionsContent.push(listeDiv[i]);
		}
	}
}

function hideMessage(){
	document.getElementById("message").style.display = "none";
}

function afficheContent(event){
	var id = this.id.replace("header","content");
	switch(document.getElementById(id).style.display){
		case "block" :
			document.getElementById(id).style.display = "none";
			break;
		case "none" :
			document.getElementById(id).style.display = "block";
			for(var i=0 ; i < extensionsContent.length ; i++){
				if(extensionsContent[i].id != id)
					extensionsContent[i].style.display = "none";
			}
			break;
	}
}
</script>';

$nameTool = get_lang('Configure extensions');
Display::display_header($nameTool);

?>
<div id="message" style="display: none">
	<?php
    if (!empty($message)) {
        echo Display::return_message($message, 'normal');
    }
    ?>
</div>

<div id="content" align="center">
	<!-- PPT2LP -->
    <div class="chamilo-rapid">
    <div class="row">
        <div class="col-md-12">
            <?php echo Display::panel(get_lang('Chamilo RAPID is a Rapid Learning tool available in Chamilo Pro and Chamilo Medical. It allows you to convert Powerpoint or LibreOffice presentations to SCORM-compliant courses. After the conversion, you end up in the Courses authoring tool and are able to add audio comments on slides and pages, tests and activities between the slides or pages and interaction activities like forum discussions or assignment upload. Every step becomes an independent and removable learning object. And the whole course generates accurate SCORM reporting for further coaching.').' '.get_lang('The voice recording feature in the course editor relies on a Red5 streaming server. This server\'s parameters can be configured in the videoconference section on the current page.'), get_lang('Chamilo RAPID')); ?>
        </div>
    </div>
        <div class="row">
            <div class="col-md-5">
                <?php echo Display::return_icon('screenshot_ppt2lp.jpg', get_lang('Chamilo RAPID'), ['class' => 'img-responsive']); ?>
            </div>
            <div class="col-md-7">
                <form method="POST" class="form-horizontal" action="<?php echo api_get_self(); ?>">
                    <?php
                    $form = new FormValidator('ppt2lp');
                    $form->addElement('text', 'host', get_lang('Host'));
                    //$form -> addElement('html','<br /><br />');
                    $form->addElement('text', 'port', get_lang('Port'));
                    //$form -> addElement('html','<br /><br />');
                    $form->addElement('text', 'user', get_lang('Login'));
                    //$form -> addElement('html','<br /><br />');
                    $form->addElement('text', 'ftp_password', get_lang('FTP password'));
                    //$form -> addElement('html','<br /><br />');
                    $form->addElement('text', 'path_to_lzx', get_lang('Path to LZX files'));
                    //$form -> addElement('html','<br /><br />');
                    $options = ChamiloApi::getDocumentConversionSizes();
                    $form->addSelect('size', get_lang('Size of the slides'), $options);
                    $form->addElement('hidden', 'extension_code', 'ppt2lp');

                    $defaults = [];
                    $renderer = $form->defaultRenderer();
                    $renderer->setElementTemplate(
                        '<div style="text-align:left">{label}</div><div style="text-align:left">{element}</div>'
                    );
                    if (in_array('service_ppt2lp', $listActiveServices)) {
                        $sql = 'SELECT subkey, selected_value FROM '.$tbl_settings_current.'
                                WHERE variable = "service_ppt2lp"
                                AND subkey <> "active"';
                        $rs = Database::query($sql);
                        while ($row = Database::fetch_array($rs, 'ASSOC')) {
                            $defaults[$row['subkey']] = $row['selected_value'];
                        }
                        $form->addButtonSave(get_lang('Reconfigure extension'), 'activeExtension');
                    } else {
                        $defaults['host'] = 'localhost';
                        $defaults['port'] = '2002';
                        $defaults['size'] = '720x540';
                        $form->addButtonSave(get_lang('Activate service'), 'activeExtension');
                    }

                    $form->setDefaults($defaults);
                    $form->display();
                    echo '<br />';
                    ?>
                    </form>
            </div>
        </div>
    </div>
	<?php
    /*

    <!-- SEARCH -->
    <div id="main_search">
        <div id="extension_header_search" class="accordion_header">
            <a href="#"><?php echo get_lang('Chamilo LIBRARY') ?></a>
        </div>
        <div id="extension_content_search" style="display:none" class="accordion_content">
            <?php echo get_lang('Chamilo LIBRARYDescription') ?><br /><br />
            <table width="100%">
                <tr>
                    <td width="50%">
                        <?php Display::display_icon('screenshot_search.jpg', get_lang('Chamilo LIBRARY')); ?>
                    </td>
                    <td align="center" width="50%">
                        <form method="POST" action="<?php echo api_get_self(); ?>">
                        <input type="hidden" name="extension_code" value="search" />
                        <button type="submit" class="save" name="activeExtension" value="<?php echo get_lang('Activate service') ?>" ><?php echo get_lang('Activate service') ?></button>
                        </form>
                    </td>
                </tr>
            </table>
        </div>
    </div>

     */ ?>
</div><!-- /content -->

<?php
Display::display_footer();
