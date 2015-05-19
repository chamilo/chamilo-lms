{% if sessions_slider_block.sessions|length > 0 %}
    <div class="row">
        {% for session in sessions_slider_block.sessions %}
            <div class="col-md-4">
                <div class="thumbnail">
                    <img src="{{ session.youtube_thumbnail }}">
                    <div class="caption">
                        <h3>
                            <a href="{{ _p.web_main }}session/index.php?session_id={{ session.id }}">{{ session.name }}</a>
                        </h3>
                    </div>
                </div>
            </div>
        {% endfor %}
    </div>
{% endif %}
