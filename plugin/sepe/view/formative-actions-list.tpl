<script type='text/javascript' src="../js/sepe.js"></script>
<link rel="stylesheet" type="text/css" href="../resources/plugin.css"/>

<div class="row">
    <div class="col-md-12">
        {% if message_info != "" %}
            <div class="alert alert-success">
                {{ message_info }}
            </div>
        {% endif %}
        {% if message_error != "" %}
            <div class="alert alert-danger">
                {{ message_error }}
            </div>
        {% endif %}
        <div class="page-header">
               <h2>{{ 'FormativesActionsList' | get_plugin_lang('SepePlugin') }}</h2>
        </div>
        
        <div class="report_section">
            {% if course_action_list|length > 0 %}
                <input type="hidden" id="confirmDeleteUnlinkAction" value="{{ 'confirmDeleteAction' | get_plugin_lang('SepePlugin') }}" />
                <table class="table table-hover table-striped table-bordered sepe-box-center" style="width:auto">
                {% for course in course_action_list %}
                    <tr>
                        <td class="sepe-vertical-align-middle">{{ 'Course' | get_lang }}: <strong>{{ course.title }}</strong> -> {{ 'ActionId' | get_plugin_lang('SepePlugin') | upper }}: <strong>{{ course.action_origin }} {{ course.action_code }}</strong></td>
                        <td class="text-center">
                            <a href="#" class="btn btn-danger btn-sm sepe-margin-side delete-action" id="delete-action-id{{ course.action_id }}">{{ 'Delete' | get_plugin_lang('SepePlugin') }}</a>
                            <a href="#" class="btn btn-warning btn-sm sepe-margin-side unlink-action" id="unlink-action-id{{ course.id }}">{{ 'Unlink' | get_plugin_lang('SepePlugin') }}</a>
                            <a href="formative-action.php?cid={{ course.course_id }}" class="btn btn-info btn-sm sepe-margin-side">{{ 'SeeOrEdit' | get_plugin_lang('SepePlugin') }}</a>
                        </td>
                       </tr>
                {% endfor %} 
                </table>
            {% else %}
                <div class="alert alert-warning">
                    {{ 'NoFormativeActionToCourse' | get_plugin_lang('SepePlugin') }}
                </div>
            {% endif %}
        </div>
        <hr />
        <div class="page-header">
            <h2>{{ 'CourseFreeOfFormativeAction' | get_plugin_lang('SepePlugin') }}</h2>
        </div>

        <div class="report_section">
            <input type="hidden" id="alertAssignAction" value="{{ 'alertAssignAction'| get_plugin_lang('SepePlugin') }}" />
            <table class="table table-striped"> 
            {% for course in course_free_list %}
                <tr>
                    <td class="sepe-vertical-align-middle">{{ 'Course' | get_lang }}: <strong>{{ course.title }}</strong></td>
                    <td class="text-center sepe-vertical-align-middle">
                        <select class="chzn-select" id="action_formative{{ course.id }}" style="width:250px">
                            <option value="">{{ 'SelectAction' | get_plugin_lang('SepePlugin') }}</option>
                            {% for action in action_free_list %}
                                <option value="{{ action.id }}">
                                    {{ action.action_origin }} {{ action.action_code }}
                                </option>
                            {% endfor %}  
                        </select>
                    </td>
                    <td class="text-center sepe-vertical-align-middle" style="min-width:240px">
                        <a href="#" class="btn btn-info btn-sm sepe-margin-side assign_action" id="course_id{{ course.id }}">{{ 'AssignAction' | get_plugin_lang('SepePlugin') }}</a>
                        <a href="formative-action-edit.php?new_action=1&cid={{ course.id }}" class="btn btn-success btn-sm sepe-margin-side">{{ 'CreateAction' | get_plugin_lang('SepePlugin') }}</a>
                    </td>
                   </tr>
            {% endfor %} 
            </table>
        </div> 
    </div>
</div>
