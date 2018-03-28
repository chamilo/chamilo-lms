{% if blocklist == '' %}
<div id="columns">
    <div class="row">
        {% if columns|length > 0 %}
            {% for key, column in columns %}
                <div id="{{ key }}" class="col-md-6">
                    {% for item in column %}
                        {{ item }}
                    {% endfor %}
                </div>
            {% endfor %}
        {% else %}
         <div class="alert alert-info" role="alert">
             {{ 'YouHaveNotEnabledBlocks'| get_lang }}
         </div>
        {% endif %}
    </div>
</div>
{% else %}
    {{ blocklist }}
{% endif %}