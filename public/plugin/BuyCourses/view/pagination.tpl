{% if pagination_pages_count is defined and pagination_pages_count > 1 %}
    <nav class="flex items-center justify-center gap-2 pt-2">
        {% set query = app.request.query.all %}
        {% for page in 1..pagination_pages_count %}
            {% set pageQuery = query|merge({'page': page}) %}
            <a
                href="{{ pagination_base_path }}?{{ pageQuery|url_encode }}"
                class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl border px-3 text-sm font-semibold transition {{ page == pagination_current_page ? 'border-primary bg-primary text-white shadow-sm' : 'border-gray-25 bg-white text-gray-90 hover:border-primary/30 hover:text-primary' }}"
            >
                {{ page }}
            </a>
        {% endfor %}
    </nav>
{% endif %}
