<?php
/* For licensing terms, see /license.txt */
/**
* Reports
* @author Arnaud Ligot <arnaud@cblue.be>
* @copyrights CBLUE SPRL 2011
* @package chamilo.reports
*/

// name of the language file that needs to be included
$language_file = array('reportlib');
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
foreach (array('jquery.ba-bbq.min.js', 'jquery.validate.js', 'jquery.form.js', 'jquery.form.wizard.js', 'jquery.dataTables.min.js') as $js)
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/'.$js.'" type="text/javascript" language="javascript"></script>'."\n";

// FIXME
$htmlHeadXtra[] = '    <style type="text/css">
			#reportsBuilderWizard {
				padding : 1em;
				width : 500px;
				border-style: solid;
				border-color: #0daee4;
			}

			#fieldWrapper {
			}

			#wizardNavigation {
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
				display: none;
			}
			.result {
				padding-top: 15px;
			}
			#result2 {
				margin: 50px;
			}
			#result3 {
				margin: 100px;
			}
			#result4 {
				margin: 150px;
			}
			select.link, select.link:hover {
				color: black;
			}
		</style>';

$htmlCSSXtra[] = 'dataTable.css';

reports_loadTemplates();

Display::display_header($tool_name);

?>
    <div id="reportsBuilderWizard">
        <h3><?php get_lang('Reports'); ?></h3>
            <div id='wizardContent'>
                <p><?php echo get_lang('PleaseFillFormToBuildReport'); ?></p>
                <h5 id="status"></h5>
                <form id="reportsBuilderWizardForm" method="post" action="reports.php" class="bbq">
                <div id="fieldWrapper">
                    <span class="step" id="first">
                        <span class="font_normal_07em_black"><?php echo get_lang('PleaseChooseReportType'); ?></span><br />
                        <label for="type"><?php echo get_lang('ReportType'); ?></label><br />
                        <select class="input_field_12em link required" name="type" id="type">
<?php
foreach ($reports_template as $key => $value) {
	echo '<option value="'.$key.'">'.$value['description'].'</option>';
}
?>
                        </select><br />
                    </span>
<?php
foreach ($reports_template as $key => $value) {
	echo $value['wizard'];
}
?>
                    <span id="format" class="step submit_step">
                        <span class="font_normal_07em_black"><?php echo get_lang('ReportFormat'); ?></span><br />
                        <select name="format" id="format">
                            <option value="html">HTML</option>
                            <option value="csv">CSV</option>
                            <option value="sql">SQL</option>
                            <option value="link"><?php echo get_lang('ReportTypeLink'); ?></option>
                        </select><br />
                    </span>
                </div>
                <div id="wizardNavigation">
                    <input class="navigation_button" id="back" value="<?php echo get_lang('Back'); ?>" type="reset" />
                    <input class="navigation_button" id="next" value="<?php echo get_lang('Next'); ?>" type="submit" />
                </div>
            </form>
        <hr />
    </div>
    <div id="wizardShowButton">
        <?php echo get_lang('ShowWizard'); ?>
    </div>		
    <p id="data"></p>
</div>
<div id="result" class="result">
</div>
<div id="result2" class="result">
</div>
<div id="result3" class="result">
</div>
<div id="result4" class="result">
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
							$(".result").html('');
							$("#result").html(data);
						},
						beforeSubmit: function(data){$("#data").html("data sent to the server: " + $.param(data));},
						resetForm: false
				 	}	
				 }
				);
  		});
		function setSubDataUri(elem, uri) {
			$.ajax({
				url: uri,
				success: function(data) {
					$(elem).closest('.result').nextAll().html('');
					$(elem).closest('.result').next().html(data);
				}
			});
		}
</script>

<?
// Footer
Display::display_footer();
