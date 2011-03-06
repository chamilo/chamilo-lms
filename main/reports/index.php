<?php
/* For licensing terms, see /license.txt */

/**
* Reports
* @author Arnaud Ligot <arnaud@cblue.be>
* @copyrights CBLUE SPRL 2011
* @package chamilo.reports
*/

// name of the language file that needs to be included
$language_file = array ('index', 'tracking', 'userInfo', 'admin', 'gradebook'); // FIXME
$cidReset = true;


// including files 
require_once '../inc/global.inc.php';
require_once 'reports.lib.php';

// protect script
api_block_anonymous_users();

// defining constants

// current section
$this_section = SECTION_REPORTS;

// setting the name of the tool
$tool_name=get_lang('Reports');

// Displaying the header
foreach (array('jquery.js','jquery-ui-1.8.5.custom.min.js', 'jquery.ba-bbq.min.js', 'jquery.validate.js', 'jquery.form.js', 'jquery.form.wizard.js') as $js)
	$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/'.$js.'" type="text/javascript" language="javascript"></script>'."\n";


// FIXME
$htmlHeadXtra[] = '    <style type="text/css">
			#reportsBuilderWizard {
				padding : 1em;
				width : 500px;
				border-style: solid;
			}

			#fieldWrapper {
			}

			#demoNavigation {
				margin-top : 0.5em;
				margin-right : 1em;
				text-align: right;
			}
			
			#data {
				font-size : 0.7em;
			}

			input {
				margin-right: 0.1em;
				margin-bottom: 0.5em;
			}

			.input_field_25em {
				width: 2.5em;
			}

			.input_field_3em {
				width: 3em;
			}

			.input_field_35em {
				width: 3.5em;
			}

			.input_field_12em {
				width: 12em;
			}

			label {
				margin-bottom: 0.2em;
				font-weight: bold;
				font-size: 0.8em;
			}

			label.error {
				color: red;
				font-size: 0.8em;
				margin-left : 0.5em;
			}

			.step span {
				float: right;
				font-weight: bold;
				padding-right: 0.8em;
			}

			.navigation_button {
				width : 70px;
			}
			
			#data {
					overflow : auto;
			}
		</style>';

reports_loadTemplates();

Display::display_header($tool_name);

?>
		<div id="reportsBuilderWizard">
			<h3>Reports Builder</h3>
			<div id='wizardContent'>
			<p>Please fill the following from to build your report.
			<hr />
			<h5 id="status"></h5>
			<form id="reportsBuilderWizardForm" method="post" action="reports.php" class="bbq">
				<div id="fieldWrapper">
				<span class="step" id="first">
					<span class="font_normal_07em_black">Please choose between the different type of reports</span><br />
					<label for="type">Report Type</label><br />
					<select class="input_field_12em link required" name="type" id="type">
<?php
foreach($reports_template as $key => $value)
	echo '<option value="'.$key.'">'.$value['description'].'</option>';
?>
					</select><br />
				</span>
<?php
foreach($reports_template as $key => $value)
	echo $value['wizard'];
?>
				<span id="format" class="step submit_step">
					<span class="font_normal_07em_black">Format</span><br />

					<select name="format" id="format">
						<option value="html">html</option>
						<option value="csv">csv</option>
						<option value="sql">sql</option>
					</select><br />
				</span>
				</div>
				<div id="demoNavigation"> 							
					<input class="navigation_button" id="back" value="Back" type="reset" />
					<input class="navigation_button" id="next" value="Next" type="submit" />
				</div>
			</form>
			<hr />
			</div>
			<div id="wizardShowButton">
				Show Wizard
			</div>		
			<p id="data"></p>

		</div>


		<div id="result">
		</div>
    <script type="text/javascript">
			$(function(){
				$("#wizardShowButton").hide();
				$("#wizardShowButton").click(function() {
					$("#wizardContent").show();
					$("#wizardShowButton").hide();
				});
				$("#reportsBuilderWizardForm").formwizard({ 
				 	formPluginEnabled: true,
				 	validationEnabled: true,
				 	focusFirstInput : true,
				 	formOptions :{
						success: function(data){
		                                        $("#wizardContent").hide();
                		                        $("#wizardShowButton").show();
							$("#result").html(data); 
						},
						beforeSubmit: function(data){$("#data").html("data sent to the server: " + $.param(data));},
						resetForm: false
				 	}	
				 }
				);
  		});
    </script>

<?

// Footer
Display::display_footer();

?>
