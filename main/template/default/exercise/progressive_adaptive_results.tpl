<div class="row">
    <div class="col-md-10 col-md-offset-1 col-lg-8 col-lg-offset-2">
        <p class="lead"><strong>{{ result.user.completeName }}</strong></p>
        <p class="lead">
            {% if session %}
                {{ session.name ~ '(' ~ course.title ~ ')' }}
            {% else %}
                {{ course.title }}
            {% endif %}
        </p>

        {% if not course_fields is empty %}
            <dl class="dl-horizontal">
                {% for display_text, value in course_fields %}
                    <dt>{{ display_text }}</dt>
                    <dd>{{ value }}</dd>
                {% endfor %}
            </dl>
        {% endif %}

        {% if not session_fields is empty %}
            <dl class="dl-horizontal">
                {% for display_text, value in session_fields %}
                    <dt>{{ display_text }}</dt>
                    <dd>{{ value }}</dd>
                {% endfor %}
            </dl>
            <br>
        {% endif %}
    </div>
</div>
<div class="row">
    <div class="col-xs-6 col-sm-4 col-md-3 col-md-offset-1 col-lg-2 col-lg-offset-2">
        <img src="{{ qr }}" alt="{{ result.hash }}" class="img-responsive">
    </div>
    <div class="col-xs-6 col-sm-8 col-md-7 col-lg-6">
        <p class="lead">{{ 'LevelReachedX'|get_lang|format(result.achievedLevel) }}</p>
        <dl class="dl-horizontal">
            <dt>{{ 'Username'|get_lang }}</dt>
            <dd>{{ result.user.username }}</dd>
            <dt>{{ 'StartDate'|get_lang }}</dt>
            <dd>{{ result.exe.exeDate|api_convert_and_format_date }}</dd>
            <dt>{{ 'Duration'|get_lang }}</dt>
            <dd>{{ exe_duration }}</dd>
            <dt>{{ 'IP'|get_lang }}</dt>
            <dd>{{ result.exe.userIp }}</dd>
        </dl>
        <p><span>{{ 'ResultHashX'|get_lang|format(result.hash) }}</span></p>
    </div>
</div>
