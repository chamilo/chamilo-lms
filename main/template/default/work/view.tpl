<div class="page-header">
    <h2>{{ work.title }}</h2>
</div>
<p>
    {{ work.description }}
</p>
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
                <a href="{{ _p.web_code }}"><img height="24" src="{{ comment.picture }}"/> {{ comment.username }} </a>- {{ comment.sent_at | api_get_local_time }}
            </div>

        <p>
            {{ comment.comment }}
        </p>
        </li>
    {% endfor %}
    </ul>
    <br />
    <hr>
    {{ form }}
{% endif %}
