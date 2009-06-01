<?php // $Id: configure_homepage.php 9246 2006-09-25 13:24:53 +0000 (lun., 25 sept. 2006) bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

// name of the language file that needs to be included 
$language_file='admin';

$cidReset=true;

include('../inc/global.inc.php');


$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// Database Table Definitions
$tbl_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

$message = '';

require api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';



if(isset($_POST['activeExtension'])){

	switch ($_POST['extension_code']){		
		
		case 'visio' :
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="true"
					WHERE variable="service_visio"
					AND subkey="active"';
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			if(Database::affected_rows()>0)
			{
				
				// select all the courses and insert the tool inside
				$sql = 'SELECT db_name FROM '.Database::get_main_table(TABLE_MAIN_COURSE);
				$rs = api_sql_query($sql, __FILE__, __LINE__);
				while($row = Database::fetch_array($rs)){
					if(!empty($_POST['visio_host']))
					{
						$tool_table = Database::get_course_table(TABLE_TOOL_LIST,$row['db_name']);
						$select = "SELECT id FROM $tool_table WHERE name='".TOOL_VISIO_CONFERENCE."'";
						$selectres = api_sql_query($select,__FILE__, __LINE__);
						if(Database::num_rows($selectres)<1)
						{
							$sql = 'INSERT INTO '.$tool_table.' SET 
									name="'.TOOL_VISIO_CONFERENCE.'",
									link="conference/index.php?type=conference",
									image="visio.gif",
									visibility="1",
									admin="0",
									address="squaregrey.gif",
									target="_self",
									category="interaction"';
							api_sql_query($sql, __FILE__, __LINE__);		
						}
						$select = "SELECT id FROM $tool_table WHERE name='".TOOL_VISIO_CLASSROOM."'";
						$selectres = api_sql_query($select,__FILE__, __LINE__);
						if(Database::num_rows($selectres)<1)
						{
							$sql = 'INSERT INTO '.$tool_table.' SET 
									name="'.TOOL_VISIO_CLASSROOM.'",
									link="conference/index.php?type=classroom",
									image="visio.gif",
									visibility="1",
									admin="0",
									address="squaregrey.gif",
									target="_self",
									category="authoring"';
							api_sql_query($sql, __FILE__, __LINE__);
						}
					}					
				}
				$message = get_lang('ServiceActivated');
				
			}
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.Database::escape_string($_POST['visio_host']).'"
					WHERE variable="service_visio"
					AND subkey="visio_host"';
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.Database::escape_string($_POST['visio_port']).'"
					WHERE variable="service_visio"
					AND subkey="visio_port"';
			$rs = api_sql_query($sql, __FILE__, __LINE__);	

			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.Database::escape_string($_POST['visio_pass']).'"
					WHERE variable="service_visio"
					AND subkey="visio_pass"';
			$rs = api_sql_query($sql, __FILE__, __LINE__);	

			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.($_POST['visio_use_rtmpt']=='true'?'true':'false').'"
					WHERE variable="service_visio"
					AND subkey="visio_use_rtmpt"';
			$rs = api_sql_query($sql, __FILE__, __LINE__);	
			
			if(empty($message))
			{
				$message = get_lang('ServiceReconfigured');
			}
			
			
			
			
			break;	
			
		case 'ppt2lp' :
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="true"
					WHERE variable="service_ppt2lp"
					AND subkey="active"';
					
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			
			if(Database::affected_rows()>0){
				$message = get_lang('ServiceActivated');
			}
			
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.addslashes($_POST['host']).'"
					WHERE variable="service_ppt2lp"
					AND subkey="host"';
			api_sql_query($sql, __FILE__, __LINE__);
			
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.addslashes($_POST['port']).'"
					WHERE variable="service_ppt2lp"
					AND subkey="port"';
			api_sql_query($sql, __FILE__, __LINE__);
			
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.addslashes($_POST['ftp_password']).'"
					WHERE variable="service_ppt2lp"
					AND subkey="ftp_password"';
			api_sql_query($sql, __FILE__, __LINE__);
			
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.addslashes($_POST['user']).'"
					WHERE variable="service_ppt2lp"
					AND subkey="user"';
			api_sql_query($sql, __FILE__, __LINE__);
			
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.addslashes($_POST['path_to_lzx']).'"
					WHERE variable="service_ppt2lp"
					AND subkey="path_to_lzx"';
			api_sql_query($sql, __FILE__, __LINE__);
			
			$sql = 'UPDATE '.$tbl_settings_current.' SET
					selected_value="'.addslashes($_POST['size']).'"
					WHERE variable="service_ppt2lp"
					AND subkey="size"';
			api_sql_query($sql, __FILE__, __LINE__);
				
			break;		
	}
	
}


$listActiveServices = array();

// get the list of active services
$sql = 'SELECT variable FROM '.$tbl_settings_current.' WHERE variable LIKE "service_%" AND subkey="active" and selected_value="true"';

$rs = api_sql_query($sql, __FILE__, __LINE__);
while($row = Database::fetch_array($rs)){
	$listActiveServices[] = $row['variable'];
}

$javascript_service_displayed = '';
if(isset($_GET['display'])){
	$javascript_service_displayed = 'document.getElementById("extension_content_'.$_GET['display'].'").style.display = "block"';
}

// javascript to handle accordion behaviour
$javascript_message = '';
if(!empty($message)){
	$javascript_message = 
	'
	document.getElementById("message").style.display = "block";
	var timer = setTimeout(hideMessage,5000);
	';
}
$htmlHeadXtra[]= '
<script type="text/javascript">
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
'.$javascript_service_displayed.'
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
	if(!empty($message))
		Display::display_normal_message($message) 
	?>
</div>

<div id="content" align="center">



<!-- INSTRUCTIONS TO ADD AN EXTENSION HERE
- copy paste a "main_*" div 
- set the names of the subdiv to extension_header_yourextension and extension_content_yourextension
- extension_content_yourextension is the hidden div where you have to put your form / activation process
- extension_header_yourextension is the name of your extension
- you do not need to add javascript to display / hide your divs
- please fill free to improve the global display of the document
-->
	<!-- VISIOCONFERENCE -->
	<div id="main_visio">
		<div id="extension_header_visio" class="accordion_header">
			<a href="#"><?php echo get_lang('Visioconf') ?></a>
		</div>
		<div id="extension_content_visio" style="display:none" class="accordion_content">		
			<?php echo get_lang('VisioconfDescription') ?><br /><br />
			<table width="100%">
				<tr>
					<td align="center">
						<?php Display::display_icon('screenshot_conf.jpg', get_lang('Visioconf')); ?>
					</td>
					<!--td align="center" width="50%">
						<?php 
						$form = new FormValidator('visio');						
						$form -> addElement('text', 'visio_host', get_lang('VisioHost'));
						$form -> addElement('html','<br /><br />');
						$form -> addElement('text', 'visio_port', get_lang('VisioPort'));
						$form -> addElement('html','<br /><br />');
						$form -> addElement('text', 'visio_pass', get_lang('VisioPassword'));
						$form -> addElement('html','<br /><br />');
						$group = array();
						$group[] =& HTML_QuickForm::createElement('radio', 'visio_use_rtmpt',null,get_lang('Yes'),'true');
						$group[] =& HTML_QuickForm::createElement('radio', 'visio_use_rtmpt',null,get_lang('No'),'false');
						$form->addGroup($group, '', get_lang('VisioUseRtmptTitle').' ('.get_lang('VisioUseRtmptComment').')', '&nbsp;');
						//$form -> addElement('text', 'visio_is_web_rtmp', get_lang('VisioRTMPIsWeb'));
						//$form -> addElement('html','<br /><br />');
						$form -> addElement('hidden', 'extension_code', 'visio');
						$defaults = array();
						$renderer = $form -> defaultRenderer();
						$renderer -> setElementTemplate('<div style="text-align:left">{label}</div><div style="text-align:left">{element}</div>');
						$form -> addElement('html','<br /><br />');
						if(in_array('service_visio',$listActiveServices))
						{
							$sql = 'SELECT subkey, selected_value FROM '.$tbl_settings_current.' 
									WHERE variable = "service_visio"';
							$rs = api_sql_query($sql, __FILE__, __LINE__);
							while($row = Database::fetch_array($rs,'ASSOC'))
							{
								$defaults[$row['subkey']] = $row['selected_value'];
							}							
							$form -> addElement('style_submit_button', 'activeExtension', get_lang('ReconfigureExtension'),'class="save"');
						}
						else {
							$form -> addElement('style_submit_button', 'activeExtension', get_lang('ActivateExtension'),'class="save"');
						}
						$form -> setDefaults($defaults);
						$form -> display();
						?>
					</td-->
				</tr>
			</table>
		</div>
	</div>
	
	<!-- PPT2LP -->
	<div id="main_ppt2lp">
		<div id="extension_header_ppt2lp" class="accordion_header">
			<a href="#"><?php echo get_lang('Ppt2lp') ?></a>
		</div>
		<div id="extension_content_ppt2lp" style="display:none" class="accordion_content">		
			<?php echo get_lang('Ppt2lpDescription').' '.get_lang('Ppt2lpVoiceRecordingNeedsRed5') ?><br /><br />
			<table width="100%">
				<tr>
					<td valign="top" align="center">
						<?php Display::display_icon('screenshot_ppt2lp.jpg', get_lang('Ppt2lp')); ?>
					</td>
					<!--td align="center" width="50%">
						<form method="POST" action="<?php echo api_get_self(); ?>">
						<?php 
						
						$form = new FormValidator('ppt2lp');						
						$form -> addElement('text', 'host', get_lang('Host'));
						$form -> addElement('html','<br /><br />');
						$form -> addElement('text', 'port', get_lang('Port'));
						$form -> addElement('html','<br /><br />');
						$form -> addElement('text', 'user', get_lang('UserOnHost'));
						$form -> addElement('html','<br /><br />');
						$form -> addElement('text', 'ftp_password', get_lang('FtpPassword'));
						$form -> addElement('html','<br /><br />');
						$form -> addElement('text', 'path_to_lzx', get_lang('PathToLzx'));
						$form -> addElement('html','<br /><br />');
						$options = array(
									'540x405'=>'540x405',
									'640x480'=>'640x480',
									'720x540'=>'720x540',
									'800x600'=>'800x600',
									'1000x750'=>'1000x750'
									);
						$form -> addElement('select', 'size', get_lang('SlideSize'), $options);
						
						
						$form -> addElement('hidden', 'extension_code', 'ppt2lp');
						
						$defaults = array();
						$renderer = $form -> defaultRenderer();
						$renderer -> setElementTemplate('<div style="text-align:left">{label}</div><div style="text-align:left">{element}</div>');
						$form -> addElement('html','<br /><br />');
						if(in_array('service_ppt2lp',$listActiveServices))
						{
							$sql = 'SELECT subkey, selected_value FROM '.$tbl_settings_current.' 
									WHERE variable = "service_ppt2lp"
									AND subkey <> "active"';
							$rs = api_sql_query($sql, __FILE__, __LINE__);
							while($row = Database::fetch_array($rs,'ASSOC'))
							{
								$defaults[$row['subkey']] = $row['selected_value'];
							}							
							$form -> addElement('style_submit_button', 'activeExtension', get_lang('ReconfigureExtension'),'class="save"');
						}
						else {
							$defaults['host'] = 'localhost';
							$defaults['port'] = '2002';
							$defaults['size'] = '720x540';
							$form -> addElement('style_submit_button', 'activeExtension', get_lang('ActivateExtension'),'class="save"');
						}
						
						$form -> setDefaults($defaults);
						$form -> display();						
						echo '<br />';
						?>
						</form>
					</td-->
				</tr>
			</table>
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
	?>
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
	
</div><!-- /content -->
	

<?php

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
