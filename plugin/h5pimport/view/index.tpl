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

{{ table }}
