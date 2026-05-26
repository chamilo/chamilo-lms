{% autoescape false %}
<div class="space-y-6">
    <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
        <div class="grid gap-0 lg:grid-cols-[minmax(260px,340px)_minmax(0,1fr)]">
            <div class="h-full bg-support-2">
                <img
                        alt="{{ course.title }}"
                        class="h-full min-h-[220px] w-full object-cover"
                        src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}"
                >
            </div>

            <div class="space-y-5 p-6">
                <div class="space-y-3">
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-gray-90">
                            {{ course.title }}
                        </h2>

                        <div class="mt-3 flex flex-wrap gap-2">
                            {% if course.code %}
                            <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                                {{ course.code }}
                            </span>
                            {% endif %}

                            {% if course.visual_code %}
                            <span class="inline-flex items-center rounded-full border border-gray-25 bg-white px-3 py-1 text-xs font-semibold text-gray-90">
                                {{ course.visual_code }}
                            </span>
                            {% endif %}
                        </div>
                    </div>

                    {% if course.teachers is defined and course.teachers %}
                    <div class="space-y-2">
                        <div class="text-sm font-semibold text-gray-90">
                            {{ 'Teachers'|get_lang }}
                        </div>

                        <div class="flex flex-wrap gap-2">
                            {% for teacher in course.teachers %}
                            <span class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-white px-3 py-1 text-sm text-gray-50">
                                <em class="fa fa-user text-primary"></em>
                                <span>{{ teacher.name }}</span>
                            </span>
                            {% endfor %}
                        </div>
                    </div>
                    {% endif %}
                </div>

                <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1">
                            <div class="text-sm font-semibold text-gray-90">
                                {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-sm font-semibold text-white">
                                {{ course.item.total_price_formatted }}
                            </div>
                        </div>

                        <a
                                href="{{ url('index') ~ 'plugin/BuyCourses/src/process.php?' ~ {'i': course.id, 't': 1}|url_encode }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                        >
                            <em class="fa fa-shopping-cart"></em>
                            {{ 'Buy'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-90">
                        {{ 'Description'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h3>

                    <div class="rounded-2xl border border-gray-25 bg-white p-4 text-sm leading-6 text-gray-50">
                        {% if course.description %}
                        {{ course.description }}
                        {% else %}
                        <p>{{ 'CourseDescriptionUnavailable'|get_plugin_lang('BuyCoursesPlugin') }}</p>
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{% endautoescape %}
