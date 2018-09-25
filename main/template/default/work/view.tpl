<div class="page-header">
    <h2>
        {{ work.title }}
    </h2>
</div>

{% if work.description %}
<h3>
    {{ 'Description' | get_lang }}
</h3>
<p>
    {{ work.description }}
</p>
{% endif %}

{{ form }}

{% if work.contains_file and work.show_content %}
<h3>
    {{ 'Content' | get_lang }}
</h3>
<p>
    {{ work.show_content }}
</p>
{% endif %}

{% include 'work/comments.tpl'|get_template %}
