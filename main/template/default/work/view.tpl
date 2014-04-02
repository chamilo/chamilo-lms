<div class="page-header">
    <h2>
        {{ work.title }}
        {% if work.contains_file %}
            <a href="{{ work.download_url }}"><img src="{{ "save.png"|icon(22) }}"></a>
        {% endif %}
        </h2>
</div>

{% if work.description %}
<p>
    {{ work.description }}
</p>
{% endif %}

{% if work.contains_file and work.show_content %}
<p>
    {{ work.show_content }}
</p>
{% endif %}

{% include 'default/work/comments.tpl' %}
