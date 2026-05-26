{% autoescape false %}
<div class="mx-auto w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ plugin_title|default('BuyCourses') }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ page_title }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        {{ 'ExportReportIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a
                        href="{{ back_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    {{ 'Back'|get_lang }}
                </a>

                <a
                        href="{{ sales_report_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="fa fa-bar-chart fa-fw"></em>
                    {{ 'SalesReport'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Format'|get_lang }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ 'ExcelFormat'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'RequiredFields'|get_lang }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ 'DateStart'|get_lang }} / {{ 'DateEnd'|get_lang }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Usage'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ 'ExportReportUsageHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'Export'|get_lang }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ 'ExportReportFormHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
        </div>

        <div class="p-6">
            {{ form }}
        </div>
    </section>
</div>
{% endautoescape %}
