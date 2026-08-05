{% if is_allowed_to_edit %}
    {% if external_url is defined %}
        <div class="mb-4 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <p class="m-0">
                    {{ 'YouNeedCreateContent'|get_plugin_lang('EmbedRegistryPlugin') }}
                </p>
                <a
                    href="{{ external_url }}"
                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="mdi mdi-open-in-new mr-2" aria-hidden="true"></span>
                    {{ 'CreateContent'|get_plugin_lang('EmbedRegistryPlugin') }}
                </a>
            </div>
        </div>
    {% endif %}

    {% if form is defined %}
        <section class="mb-6 rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
            {{ form|raw }}
        </section>
    {% endif %}
{% endif %}

{% if current_embed is defined %}
    {% set start_date %}
        <time datetime="{{ current_embed.displayStartDate.format(constant('DateTime::W3C')) }}">
            {{ current_embed.displayStartDate|api_convert_and_format_date }}
        </time>
    {% endset %}
    {% set end_date %}
        <time datetime="{{ current_embed.displayEndDate.format(constant('DateTime::W3C')) }}">
            {{ current_embed.displayEndDate|api_convert_and_format_date }}
        </time>
    {% endset %}

    <section class="mb-6 rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-blue-700">
                    {{ 'Current'|get_lang }}
                </p>
                <h2 class="m-0 text-xl font-semibold text-gray-90">
                    {{ current_embed.title }}
                </h2>
                <p class="mt-2 text-sm text-gray-50">
                    {{ 'From %s to %s'|get_lang|format(start_date|raw, end_date|raw)|raw }}
                </p>
            </div>
            {% if current_link is defined %}
                <div class="flex shrink-0 items-center gap-2">
                    {{ current_link|raw }}
                </div>
            {% endif %}
        </div>
    </section>
{% endif %}

{% if table is defined and table is not empty %}
    <section class="rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-blue-700">
                    {{ 'EmbedRegistry'|get_plugin_lang('EmbedRegistryPlugin') }}
                </p>
                <h2 class="m-0 text-xl font-semibold text-gray-90">
                    {{ 'Embeddable content'|get_plugin_lang('EmbedRegistryPlugin') }}
                </h2>
            </div>
        </div>

        <div class="overflow-x-auto">
            {{ table|raw }}
        </div>
    </section>
{% endif %}
