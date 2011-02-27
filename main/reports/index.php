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
foreach (array('jquery-1.4.2.min.js','jquery-ui-1.8.5.custom.min.js', 'jquery.ba-bbq.min.js', 'jquery.validate.js', 'jquery.form.js', 'jquery.form.wizard.js') as $js)
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


Display::display_header($tool_name);

?>
		<div id="reportsBuilderWizard">
			<h3>Reports Builder</h3>
			<p>Please fill the following from to build your report.
			<hr />
			<h5 id="status"></h5>
			<form id="reportsBuilderWizardForm" method="post" action="reports.php" class="bbq">
				<div id="fieldWrapper">
				<span class="step" id="first">
					<span class="font_normal_07em_black">Please choose between the different type of reports</span><br />
					<label for="type">Report Type</label><br />
					<select class="input_field_12em link required" name="type" id="type">
						<option value="exercicesMultiCourses">Result of each test per student</option>
						<option value="courseTime">Time spend by students within courses</option>
					</select><br />
				</span>
				<span id="exercicesMultiCourses" class="step">
					<span class="font_normal_07em_black">Result of each test per student</span><br />

					<label for="scoremin">Score min</label><br />
					<input class="input_field_25em" name="scoremin" id="scoremin" value="0"><br />
					<label for="scoremax">Score max</label><br />
					<input class="input_field_25em" name="scoremax" id="scoremax" value="0"><br />
					<label for="tattempt">How to treat Attempts</label><br />
					<select name="tattempt" id="tattempt">
						<option value="first">take only the first one</option>
						<option value="last">take only the last one</option>
						<option value="average">take the average value</option>
						<option value="min">take the minimum value</option>
						<option value="max">take the maximum value</option>
					</select><br />
					<label name="gcourses">Do you want to group quiz per courses</label><br />
					<select name="gcourses" id="gcourses">
						<option value="nogroup">Do not group</option>
						<option value="average">group and take the average value</option>
						<option value="min">group and take the minimum value</option>
						<option value="max">group and take the maximum value</option>
					</select></br>
					<input type="hidden" class="link" value="format" />
				</span>
				<span id="courseTime" class="step">
					FIXME<br />

					<input type="hidden" class="link" value="format" />
				</span>
				<span id="format" class="step submit_step">
					<span class="font_normal_07em_black">Format</span><br />

					<select name="format" id="format">
						<option value="html">html</option>
						<option value="csv">csv</option>
					</select><br />
				</span>
				</div>
				<div id="demoNavigation"> 							
					<input class="navigation_button" id="back" value="Back" type="reset" />
					<input class="navigation_button" id="next" value="Next" type="submit" />
				</div>
			</form>
			<hr />
			
			<p id="data"></p>

		</div>

    <script type="text/javascript">
			$(function(){
				$("#reportsBuilderWizardForm").formwizard({ 
				 	formPluginEnabled: true,
				 	validationEnabled: true,
				 	focusFirstInput : true,
				 	formOptions :{
						success: function(data){$("#status").fadeTo(500,1,function(){ $(this).html("You are now registered!").fadeTo(5000, 0); })},
						beforeSubmit: function(data){$("#data").html("data sent to the server: " + $.param(data));},
						dataType: 'json',
						resetForm: true
				 	}	
				 }
				);
  		});
    </script>

<?

// Footer
Display::display_footer();

?>
