<div class="exercise">
    {{ time_control }}
    {% if status is not empty %}
        <div class="alert alert-warning" role="alert">
            {{ status }}
        </div>
    {% endif %}
    {% if data.edit and data.session_id == api_get_session_id %}
        <div class="pull-right">
            <a class="btn btn-default" title="{{ "Edit"|get_lang }}" href="{{ _p.web_main }}exercise/admin.php?{{ _p.web_cid_query }}&id_session={{ _c.session_id }}&exerciseId={{ data.id }}">
                <i class="fa fa-pencil" aria-hidden="true"></i>
            </a>
        </div>
    {% endif %}
        <div class="page-header">
            <h2>{{ data.title }}</h2>
        </div>
        <div class="exercise_description">
            {{ data.description }}
        </div>
    {% if count == data.attempts %}
        <div class="alert alert-error" role="alert">
            {{ "Attempts" |get_lang }} {{ count }} / {{ data.attempts }}
        </div>
    {% else %}
        <div class="alert alert-info" role="alert">
            {{ "Attempts" |get_lang }} {{ count }} / {{ data.attempts }}
        </div>
    {% endif %}
    {% if data.url is not empty %}
        <div class="exercise-btn text-center">
            <a href="{{ data.url }}" class="btn btn-success btn-lg">
                {{ label }}
            </a>
        </div>
    {% endif %}
    
    {% if table_result is not empty %}
        <div class="table-responsive">
            {{ table_result }}
        </div>
    {% endif %}
</div>
