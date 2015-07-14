<div class="row">
    <div class="col-md-3">
        <div class="well">
            {{ user_avatar }}
            <p>{{ user.getCompleteName() }}</p>
            <p>{{ 'Stars'|get_lang ~ ' ' ~ gamification_stars }}</p>
            <p>{{ 'XPoints'|get_lang|format(gamification_points) }}</p>
            <p>{{ 'GamificationProgress'|get_lang ~ ' ' ~ gamification_progress }}</p>
        </div>

        <h4>{{ 'ShowProgress'|get_lang }}</h4>
        <div class="list-group">
            {% for session in sessions %}
                <a href="{{ _p.self ~ '?' ~ {"session_id": session.getId}|url_encode() }}" class="list-group-item {{ current_session and session.getId == current_session.getId ? 'active' }}">{{ session.getName }}</a>
            {% endfor %}
        </div>
    </div>

    <div class="col-md-9">
        {% if current_session %}
            <h2 class="page-header">{{ current_session.getName() }}</h2>

            {% for course_id, course in session_data %}
                <h3>{{ course.title }}</h3>

                <div class="panel-group" id="course-accordion" role="tablist" aria-multiselectable="true">
                    {% for stats_url in course.stats %}
                        {% set panel_id = course_id ~ '-' ~ loop.index %}
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="heading-{{ panel_id }}">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" data-parent="#course-accordion" href="#collapse-{{ panel_id }}" aria-expanded="true" aria-controls="collapse-{{ panel_id }}">
                                        {{ stats_url.0 }}
                                    </a>
                                </h4>
                            </div>
                            <div id="collapse-{{ panel_id }}" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading-{{ panel_id }}">
                                <div class="panel-body">
                                    <div class="embed-responsive embed-responsive-4by3">
                                        <iframe src="{{ _p.web_main ~ stats_url.1 }}"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {% endfor %}
                </div>
            {% endfor %}
        {% endif %}
    </div>
</div>
