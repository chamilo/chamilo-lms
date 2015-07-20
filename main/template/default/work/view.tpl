<div class="page-header">
    <h2>
        {{ work.title }}
        {% if work.contains_file %}
            <a href="{{ work.download_url }}">
                <img src="{{ "save.png"|icon(22) }}" width="22" height="22">
            </a>
        {% endif %}
        </h2>
</div>

{% if work.url_correction %}
    <h3>{{ "Correction" |get_lang }}</h3>
    <p>
        <a class="btn btn-default" href="{{ work.download_url }}&correction=1">
            {{ "Download" |get_lang }}
        </a>
    </p>
    <hr />
{% endif %}

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

{% include template ~ '/work/comments.tpl' %}
