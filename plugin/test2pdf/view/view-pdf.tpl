<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>

<div class="row">
    <div class="col-xs-12 col-md-12">
    {% if infoExercise|length > 0 %}
    	<input type="hidden" id="course_id" value="{{course_id}}" />
        <table id="courses_table" class="table table-hover table-striped data_table table">
            <tr class="row_odd">
                <th class="th-header">{{ 'Exercise'|get_plugin_lang('Test2pdfPlugin') }}</th>
                <th class="th-header text-center">{{ 'DownloadOnlyQuestion'|get_plugin_lang('Test2pdfPlugin') }}</th>
                <th class="th-header text-center">{{ 'DownloadOnlyAnswer'|get_plugin_lang('Test2pdfPlugin') }}</th>
                <th class="th-header text-center">{{ 'DownloadAll'|get_plugin_lang('Test2pdfPlugin') }}</th>
            </tr>
            {% set i = 0 %}

            {% for item in infoExercise %}
                {{ i%2 == 0 ? '<tr class="row_even">' : '<tr class="row_odd">' }}
                    
                    <td class="valign-middle">
                    &nbsp;&nbsp;&nbsp;<img src="{{ 'quiz.png'|icon(32) }}">
                    <strong>{{ item.title }}</strong>
                    </td>
                    <td class="text-center">
                    	<a target="_blank" href="download-pdf.php?type=question&c_id={{course_id}}&id_quiz={{item.iid}}" title="{{ 'DownloadOnlyQuestion'|get_plugin_lang('Test2pdfPlugin') }}"><img src="{{ 'pdf.png'|icon(32) }}" /></a>
                    </td>
                    <td class="text-center">
                    	<a target="_blank" href="download-pdf.php?type=answer&c_id={{course_id}}&id_quiz={{item.iid}}" title="{{ 'DownloadOnlyAnswer'|get_plugin_lang('Test2pdfPlugin') }}"><img src="{{ 'pdf.png'|icon(32) }}" /></a>
                    </td>
                    <td class="text-center">
                    	<a target="_blank" href="download-pdf.php?type=all&c_id={{course_id}}&id_quiz={{item.iid}}" title="{{ 'DownloadAll'|get_plugin_lang('Test2pdfPlugin') }}"><img src="{{ 'pdf.png'|icon(32) }}" /></a>
                    </td>
                </tr>
                {% set i = i + 1 %}
            {% endfor %}
        </table>
    
    {% else %}
        <div class="alert alert-warning">
            {{ 'NoExercise'|get_plugin_lang('Test2pdfPlugin') }}
        </div>
    {% endif %}
	</div>
	<div class="cleared"></div>
</div>
