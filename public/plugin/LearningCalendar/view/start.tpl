<div class="space-y-6">
    <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-90">{{ page_title }}</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-50">{{ page_subtitle }}</p>
    </section>

    {% if form %}
        <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <span class="mdi mdi-calendar-edit ch-tool-icon"></span>
                <h2 class="text-xl font-semibold text-gray-90">
                    {% if is_form_view %}
                        {{ 'Settings'|get_lang }}
                    {% else %}
                        {{ page_title }}
                    {% endif %}
                </h2>
            </div>

            <div class="learning-calendar-form">
                {{ form|raw }}
            </div>
        </section>
    {% else %}
        {% if calendars is empty %}
            <section class="rounded-2xl border border-gray-25 bg-white p-8 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-15">
                    <span class="mdi mdi-calendar-blank ch-tool-icon text-3xl"></span>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gray-90">{{ empty_message }}</h2>
                <p class="mt-2 text-sm text-gray-50">{{ page_subtitle }}</p>
            </section>
        {% else %}
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {% for calendar in calendars %}
                    <article class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <a class="text-lg font-semibold text-primary hover:underline" href="{{ calendar.view_url }}">
                                    {{ calendar.title }}
                                </a>
                                {% if calendar.description %}
                                    <div class="mt-2 line-clamp-3 text-sm text-gray-50">
                                        {{ calendar.description|striptags }}
                                    </div>
                                {% endif %}
                            </div>
                            <span class="mdi mdi-calendar-clock ch-tool-icon text-2xl"></span>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-gray-15 p-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                    {{ 'Total hours'|get_lang }}
                                </div>
                                <div class="mt-1 text-xl font-bold text-gray-90">{{ calendar.total_hours }}</div>
                            </div>
                            <div class="rounded-xl bg-gray-15 p-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                    {{ 'Minutes per day'|get_lang }}
                                </div>
                                <div class="mt-1 text-xl font-bold text-gray-90">{{ calendar.minutes_per_day }}</div>
                            </div>
                            <div class="rounded-xl bg-gray-15 p-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                    {{ 'Events'|get_lang }}
                                </div>
                                <div class="mt-1 text-xl font-bold text-gray-90">{{ calendar.event_count }}</div>
                            </div>
                            <div class="rounded-xl bg-gray-15 p-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                    {{ 'Users'|get_lang }}
                                </div>
                                <div class="mt-1 text-xl font-bold text-gray-90">{{ calendar.user_count }}</div>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <a
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-25 bg-white text-primary shadow-sm transition hover:bg-primary/10 focus:outline-none focus:ring-2 focus:ring-primary/40"
                                href="{{ calendar.view_url }}"
                                title="{{ 'View'|get_lang }}"
                                aria-label="{{ 'View'|get_lang }}: {{ calendar.title|striptags }}"
                            >
                                <span class="mdi mdi-eye ch-tool-icon" aria-hidden="true"></span>
                                <span class="sr-only">{{ 'View'|get_lang }}</span>
                            </a>
                            <a
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-25 bg-white text-primary shadow-sm transition hover:bg-primary/10 focus:outline-none focus:ring-2 focus:ring-primary/40"
                                href="{{ calendar.users_url }}"
                                title="{{ 'Users'|get_lang }}"
                                aria-label="{{ 'Users'|get_lang }}: {{ calendar.title|striptags }}"
                            >
                                <span class="mdi mdi-account-group ch-tool-icon" aria-hidden="true"></span>
                                <span class="sr-only">{{ 'Users'|get_lang }}</span>
                            </a>
                            <a
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-secondary/30 bg-white text-secondary shadow-sm transition hover:bg-secondary/10 focus:outline-none focus:ring-2 focus:ring-secondary/40"
                                href="{{ calendar.edit_url }}"
                                title="{{ 'Edit'|get_lang }}"
                                aria-label="{{ 'Edit'|get_lang }}: {{ calendar.title|striptags }}"
                            >
                                <span class="mdi mdi-pencil ch-tool-icon text-secondary" aria-hidden="true"></span>
                                <span class="sr-only">{{ 'Edit'|get_lang }}</span>
                            </a>
                            <a
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-25 bg-white text-primary shadow-sm transition hover:bg-primary/10 focus:outline-none focus:ring-2 focus:ring-primary/40"
                                href="{{ calendar.copy_url }}"
                                title="{{ 'Copy'|get_lang }}"
                                aria-label="{{ 'Copy'|get_lang }}: {{ calendar.title|striptags }}"
                            >
                                <span class="mdi mdi-content-copy ch-tool-icon" aria-hidden="true"></span>
                                <span class="sr-only">{{ 'Copy'|get_lang }}</span>
                            </a>
                            <a
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-danger/30 bg-white text-danger shadow-sm transition hover:bg-danger/10 focus:outline-none focus:ring-2 focus:ring-danger/40"
                                href="{{ calendar.delete_url }}"
                                title="{{ 'Delete'|get_lang }}"
                                aria-label="{{ 'Delete'|get_lang }}: {{ calendar.title|striptags }}"
                                onclick="return confirm('{{ delete_confirm|e('js') }}');"
                            >
                                <span class="mdi mdi-delete ch-tool-icon text-danger" aria-hidden="true"></span>
                                <span class="sr-only">{{ 'Delete'|get_lang }}</span>
                            </a>
                        </div>
                    </article>
                {% endfor %}
            </section>
        {% endif %}
    {% endif %}
</div>
