<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">

                {% if form_room %}
                    {% if is_admin %}
                        <div class="alert alert-info" role="alert">
                            {{ 'MessageMeetingAdmin'|get_plugin_lang('ZoomPlugin') }}
                        </div>
                    {% endif %}
                    {% if is_teacher %}
                        <div class="alert alert-success" role="alert">
                            {{ 'MessageMeetingTeacher'|get_plugin_lang('ZoomPlugin') }}
                        </div>
                    {% endif %}

                    {{ form_room }}

                {% endif %}

            </div>
        </div>
    </div>
</div>