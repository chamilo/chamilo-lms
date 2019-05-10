{% if is_allowed_to_edit %}
    {% if external_url is defined %}
        <div class="alert alert-info">
            <p>
                {{ 'YouNeedCreateContent'|get_plugin_lang('EmbedRegistryPlugin') }}
                <a href="{{ external_url }}" class="btn btn-info" target="_blank">{{ 'CreateContent'|get_plugin_lang('EmbedRegistryPlugin') }}</a>
            </p>
        </div>
    {% endif %}

    {% if form is defined %}
        {{ form }}
    {% endif %}
{% endif %}

{% if current_embed is defined %}
    {% set start_date %}
        <time datetime="{{ current_embed.displayStartDate.format(constant('\DateTime::W3C')) }}">
            {{ current_embed.displayStartDate|api_convert_and_format_date }}
        </time>
    {% endset %}
    {% set end_date %}
        <time datetime="{{ current_embed.displayEndDate.format(constant('\DateTime::W3C')) }}">
            {{ current_embed.displayEndDate|api_convert_and_format_date }}
        </time>
    {% endset %}

    <div class="well well-sm text-center">
        <p class="lead">{{ current_embed.title }}</p>
        <p>
            <small>{{ 'FromDateXToDateY'|get_lang|format(start_date, end_date) }}</small>
        </p>
        <p>{{ current_link }}</p>
    </div>
{% endif %}

{{ table }}
