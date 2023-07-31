<hr />
<h3>
    {{ 'Comments' | get_lang }}
</h3>
<hr>
<ul>
{% for comment in comments %}
    <li>
        <div class="page-header">
            <a href="{{ _p.web_code }}">
                <img height="24" src="{{ comment.picture }}"/> {{ comment.complete_name }}
            </a>- {{ comment.sent_at_with_label }}
        </div>
        <p>
            {% if comment.comment is not empty %}
                {{ comment.comment|remove_xss }}
            {% else %}
                {{ 'HereIsYourFeedback' | get_lang }}
            {% endif %}
        </p>
        {% if comment.file_url is not empty %}
            <ul class="fa-ul">
                <li>
                    <span class="fa-li fa fa-paperclip"></span>
                    <a href="{{ comment.file_url }}">
                        {{ comment.file_name_to_show }}
                    </a>
                    {% if is_allowed_to_edit %}
                        <a href="{{ comment.delete_file_url }}">
                            {{ 'delete.png'|img(22, 'Delete'|get_lang) }}
                        </a>
                    {% endif %}
                </li>
            </ul>
        {% endif %}
    </li>
{% endfor %}
</ul>
<br />
