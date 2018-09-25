<div class="exercise-container">
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
        <div class="title">
            <h2>{{ data.title }}</h2>
        </div>
        <div class="description">
            {{ data.description }}
        </div>
    {% if data.url is not empty %}
        <div class="exercise-btn">
            <a href="{{ data.url }}" class="btn btn-success btn-exercise">
                {{ label }}
            </a>
        </div>
    {% endif %}
    {% if count == data.attempts %}
        <div class="alert alert-error" role="alert">
            {{ "Attempts" |get_lang }} {{ count }} / {{ data.attempts }}
        </div>
    {% else %}
        <div class="attempts">
            <ul class="attempts-status">
                {% if count != 0 %}
                    {% for i in 0..count - 1 %}
                        <li><img src="{{ _p.web_css_theme }}images/attempt-check.png"></li>
                    {% endfor %}
                {% endif %}
                {% if count != data.attempts %}
                    {% for i in 0..data.attempts - count - 1 %}
                        <li><img src="{{ _p.web_css_theme }}images/attempt-nocheck.png"></li>
                    {% endfor %}
                {% endif %}
            </ul>
            <div class="details">
                {{ "Attempts" |get_lang }} {{ count }} / {{ data.attempts }}
            </div>
        </div>
    {% endif %}

    
    {% if table_result is not empty %}
        <div class="table-responsive">
            {{ table_result }}
        </div>
    {% endif %}
</div>
