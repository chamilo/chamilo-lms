
{# "UpdatedBy"|get_lang|format(comment.username) #}

{% if work_comment_enabled %}
    <hr>
    <h4>
        {{ 'Comments' | get_lang }}
    </h4>
    <hr>
    <ul>
        {% for comment in comments %}
            <li>
                <div class="page-header">
                    <a href="{{ _p.web_code }}">
                        <img height="24" src="{{ comment.picture }}"/> {{ comment.username }}
                    </a>- {{ comment.sent_at | api_get_local_time }}
                </div>
                <p>
                    {% if comment.comment is not empty %}
                        {{ comment.comment }}
                    {% else %}
                        {{ 'HereIsYourFeedback' | get_lang }}
                    {% endif %}
                </p>
                {% if comment.file_url is not empty %}
                    <p>
                        <a href="{{ comment.file_url }}">
                            <img src="{{ "attachment.gif"|icon(32) }}">
                            {{ comment.file_name_to_show }}
                        </a>
                        {% if is_allowed_to_edit %}
                            <a href="{{ comment.delete_file_url }}">
                                <img src="{{ "delete.png"|icon(22) }}">
                            </a>
                        {% endif %}
                    </p>
                {% endif %}
            </li>
        {% endfor %}
    </ul>
    <br />
    <hr>
    {{ form }}
{% endif %}
