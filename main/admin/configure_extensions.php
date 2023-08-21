<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Edition of extensions configuration.
 *
 * @package chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
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
                $message = get_lang('ServiceActivated');
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
    api_flush_settings_cache(api_get_current_access_url_id());
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

$nameTool = get_lang('ConfigureExtensions');
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
            <?php echo Display::panel(get_lang('Ppt2lpDescription').' '.get_lang('Ppt2lpVoiceRecordingNeedsRed5'), get_lang('Ppt2lp')); ?>
        </div>
    </div>
        <div class="row">
            <div class="col-md-5">
                <?php Display::display_icon('screenshot_ppt2lp.jpg', get_lang('Ppt2lp'), ['class' => 'img-responsive']); ?>
            </div>
            <div class="col-md-7">
                <form method="POST" class="form-horizontal" action="<?php echo api_get_self(); ?>">
                    <?php
                    $form = new FormValidator('ppt2lp');
                    if (api_get_configuration_value('webservice_remote_ppt2png_enable') == true) {
                        $form->addElement('text', 'host', get_lang('Host'));
                    } else {
                        $form->addElement('text', 'host', [get_lang('Host'), 'Remote host disabled - set webservice_remote_ppt2png_enable setting to true in configuration.php to enable']);
                    }
                    //$form -> addElement('html','<br /><br />');
                    $form->addElement('text', 'port', get_lang('Port'));
                    //$form -> addElement('html','<br /><br />');
                    $form->addElement('text', 'user', get_lang('UserOnHost'));
                    //$form -> addElement('html','<br /><br />');
                    $form->addElement('text', 'ftp_password', get_lang('FtpPassword'));
                    //$form -> addElement('html','<br /><br />');
                    $form->addElement('text', 'path_to_lzx', get_lang('PathToLzx'));
                    //$form -> addElement('html','<br /><br />');
                    $options = ChamiloApi::getDocumentConversionSizes();
                    $form->addElement('select', 'size', get_lang('SlideSize'), $options);
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
                        $form->addButtonSave(get_lang('ReconfigureExtension'), 'activeExtension');
                    } else {
                        $defaults['host'] = 'localhost';
                        $defaults['port'] = '2002';
                        $defaults['size'] = '720x540';
                        $form->addButtonSave(get_lang('ActivateExtension'), 'activeExtension');
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
    <!-- EPHORUS -->
    <div id="main_ephorus">
        <div id="extension_header_ephorus" class="accordion_header">
            <a href="#"><?php echo get_lang('EphorusPlagiarismPrevention') ?></a>
        </div>
        <div id="extension_content_ephorus" style="display:none;padding:0;width:780px;" class="accordion_content">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <TABLE style="WIDTH: 750px" cellSpacing="0" cellPadding="0" align="middle" border="0">
                        <TBODY>
                            <TR>
                                <TD>
                                    <TABLE style="WIDTH: 475px" cellSpacing="0" cellPadding="0" border="0">
                                    <TBODY>
                                        <TR>
                                            <TD>
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <a title="http://www.ephorus.com/dokeos_activate.html" href="http://www.ephorus.com/dokeos_activate.html" target="_blank">
                                                <?php Display::display_icon('ephorus.gif','Ephorus'); ?>
                                                </a>
                                            </TD>
                                            <TD>
                                                <P align=center>
                                                    <FONT color="#669966" size="3"><?php echo get_lang('EphorusLeadersInAntiPlagiarism') ?></FONT>
                                                </P>
                                            </TD>
                                        </TR>
                                    </TBODY>
                                    </TABLE>
                                </TD>
                            </TR>
                            <TR>
                                <TD>
                                    <P>
                                        <TABLE style="WIDTH: 85%" cellSpacing="0" cellPadding="0" border="0">
                                        <TBODY>
                                            <TR>
                                                <TD width="50">&nbsp;

                                                </TD>
                                                <TD>
                                                    <P>
                                                        <?php echo get_lang('EphorusDescription') ?>
                                                    </P>
                                                    <P>
                                                        <A title="http://www.ephorus.nl/demo_account_en.html" href="http://www.ephorus.nl/demo_account_en.html" target="_blank"><?php echo get_lang('EphorusClickHereForADemoAccount') ?></A>
                                                    </P>
                                                    <P>
                                                        <A title="http://www.ephorus.nl:80/dokeos_activate.html" href="http://www.ephorus.nl/dokeos_activate.html" target="_blank"><?php echo get_lang('EphorusClickHereForInformationsAndPrices') ?></A>.
                                                    </P>
                                                </TD>
                                            </TR>
                                        </TBODY>
                                        </TABLE>
                                    </P>
                                </TD>
                            </TR>
                        </TBODY>
                        </TABLE>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    */
    /*

    <!-- SEARCH -->
    <div id="main_search">
        <div id="extension_header_search" class="accordion_header">
            <a href="#"><?php echo get_lang('SearchEngine') ?></a>
        </div>
        <div id="extension_content_search" style="display:none" class="accordion_content">
            <?php echo get_lang('SearchEngineDescription') ?><br /><br />
            <table width="100%">
                <tr>
                    <td width="50%">
                        <?php Display::display_icon('screenshot_search.jpg', get_lang('SearchEngine')); ?>
                    </td>
                    <td align="center" width="50%">
                        <form method="POST" action="<?php echo api_get_self(); ?>">
                        <input type="hidden" name="extension_code" value="search" />
                        <button type="submit" class="save" name="activeExtension" value="<?php echo get_lang('ActivateExtension') ?>" ><?php echo get_lang('ActivateExtension') ?></button>
                        </form>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- SERVER STATS -->
    <div id="main_serverstats">
        <div id="extension_header_serverstats" class="accordion_header">
            <a href="#"><?php echo get_lang('ServerStatistics') ?></a>
        </div>
        <div id="extension_content_serverstats" style="display:none" class="accordion_content">
            <?php echo get_lang('ServerStatisticsDescription') ?><br /><br />
            <table width="100%">
                <tr>
                    <td width="50%">
                        <?php Display::display_icon('screenshot_serverstats.jpg', get_lang('ServerStatistics')); ?>
                    </td>
                    <td align="center" width="50%">
                        <form method="POST" action="<?php echo api_get_self(); ?>">
                        <input type="hidden" name="extension_code" value="serverstats" />
                        <button type="submit" class="save" name="activeExtension" value="<?php echo get_lang('ActivateExtension') ?>" ><?php echo get_lang('ActivateExtension') ?></button>
                        </form>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- BANDWIDTH STATS -->
    <div id="main_bandwidthstats">
        <div id="extension_header_bandwidthstats" class="accordion_header">
            <a href="#"><?php echo get_lang('BandWidthStatistics') ?></a>
        </div>
        <div id="extension_content_bandwidthstats" style="display:none" class="accordion_content">
            <?php echo get_lang('BandWidthStatisticsDescription') ?><br /><br />
            <table width="100%">
                <tr>
                    <td width="50%">
                        <?php Display::display_icon('screenshot_bandwidth.jpg', get_lang('BandWidthStatistics')); ?>
                    </td>
                    <td align="center" width="50%">
                        <form method="POST" action="<?php echo api_get_self(); ?>">
                        <input type="hidden" name="extension_code" value="bandwidthstats" />
                        <button type="submit" class="save" name="activeExtension" value="<?php echo get_lang('ActivateExtension') ?>" ><?php echo get_lang('ActivateExtension') ?></button>
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
