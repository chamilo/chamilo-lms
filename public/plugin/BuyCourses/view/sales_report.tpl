{% autoescape false %}
<style>
  /* Local jqGrid styling for the BuyCourses course/session sales report. */
  #gh2626-sales-grid .ui-jqgrid {
    width: 100% !important;
    border: 0;
    font-size: 0.875rem;
  }

  #gh2626-sales-grid .ui-jqgrid-view,
  #gh2626-sales-grid .ui-jqgrid-hdiv,
  #gh2626-sales-grid .ui-jqgrid-bdiv,
  #gh2626-sales-grid .ui-jqgrid-pager {
    width: 100% !important;
    max-width: 100% !important;
  }

  #gh2626-sales-grid .ui-jqgrid-hbox {
    float: none;
    padding-right: 0;
  }

  #gh2626-sales-grid .ui-jqgrid-hdiv,
  #gh2626-sales-grid .ui-jqgrid-bdiv {
    overflow-x: auto !important;
  }

  #gh2626-sales-grid .ui-jqgrid-htable th {
    background: #f8fafc;
    border-color: #e5e7eb;
    color: #374151;
    font-size: 0.75rem;
    font-weight: 600;
    text-align: start;
  }

  #gh2626-sales-grid .ui-jqgrid-htable th div {
    height: auto;
    min-height: 44px;
    padding: 0.625rem;
    line-height: 1.25;
    white-space: normal;
  }

  #gh2626-sales-grid .ui-jqgrid-btable td {
    padding: 0.75rem 0.625rem;
    border-color: #e5e7eb;
    color: #374151;
    line-height: 1.4;
    vertical-align: middle;
    white-space: normal;
    overflow-wrap: anywhere;
  }

  #gh2626-sales-grid .ui-jqgrid-btable tr:hover td {
    background: #f8fafc;
  }

  #gh2626-sales-grid .ui-jqgrid-pager {
    min-height: 52px;
    border-color: #e5e7eb;
    background: #fff;
  }

  #gh2626-sales-grid .ui-jqgrid-btable .btn-group {
    display: inline-flex;
    gap: 0.5rem;
  }

  #gh2626-sales-grid .ui-jqgrid-btable .btn-group a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.375rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fff;
  }
</style>

{% set formShell = 'rounded-3xl border border-gray-25 bg-white p-5 shadow-sm [&_form]:space-y-6 [&_.form-group]:mb-0 [&_.form-group]:rounded-2xl [&_.form-group]:border [&_.form-group]:border-gray-25 [&_.form-group]:bg-white [&_.form-group]:p-5 [&_.form-group]:shadow-sm [&_label]:mb-2 [&_label]:block [&_label]:text-sm [&_label]:font-semibold [&_label]:text-gray-90 [&_input:not([type=radio]):not([type=checkbox])]:block [&_input:not([type=radio]):not([type=checkbox])]:w-full [&_input:not([type=radio]):not([type=checkbox])]:rounded-xl [&_input:not([type=radio]):not([type=checkbox])]:border-gray-25 [&_input:not([type=radio]):not([type=checkbox])]:bg-white [&_input:not([type=radio]):not([type=checkbox])]:text-sm [&_input:not([type=radio]):not([type=checkbox])]:text-gray-90 [&_input:not([type=radio]):not([type=checkbox])]:shadow-sm [&_input:not([type=radio]):not([type=checkbox])]:placeholder:text-gray-50 [&_input:not([type=radio]):not([type=checkbox])]:focus:border-primary [&_input:not([type=radio]):not([type=checkbox])]:focus:ring-primary [&_select]:block [&_select]:w-full [&_select]:rounded-xl [&_select]:border-gray-25 [&_select]:bg-white [&_select]:text-sm [&_select]:text-gray-90 [&_select]:shadow-sm [&_select]:focus:border-primary [&_select]:focus:ring-primary [&_.radio]:space-y-2 [&_.radio]:rounded-2xl [&_.radio]:border [&_.radio]:border-gray-25 [&_.radio]:bg-support-2 [&_.radio]:p-4 [&_.radio]:text-sm [&_.radio]:text-gray-90 [&_.radio_label]:text-sm [&_.radio_label]:font-semibold [&_.btn]:inline-flex [&_.btn]:items-center [&_.btn]:justify-center [&_.btn]:gap-2 [&_.btn]:rounded-xl [&_.btn]:px-4 [&_.btn]:py-2.5 [&_.btn]:text-sm [&_.btn]:font-semibold [&_.btn]:shadow-sm [&_.btn]:transition [&_.btn]:hover:opacity-90 [&_.btn]:focus:outline-none [&_.btn]:focus:ring-2 [&_.btn]:focus:ring-offset-2 [&_.btn-primary]:bg-primary [&_.btn-primary]:text-white [&_.btn-primary]:focus:ring-primary/30 [&_.btn-success]:bg-success [&_.btn-success]:text-white [&_.btn-success]:focus:ring-success/30 [&_.btn-default]:border [&_.btn-default]:border-gray-25 [&_.btn-default]:bg-white [&_.btn-default]:text-gray-90 [&_.help-block]:mt-2 [&_.help-block]:block [&_.help-block]:text-sm [&_.help-block]:text-gray-50 [&_.input-group]:flex [&_.input-group]:items-center [&_.input-group]:gap-3 [&_.col-sm-2]:w-full [&_.col-sm-3]:w-full [&_.col-sm-7]:w-full [&_.col-sm-8]:w-full [&_.col-sm-10]:w-full [&_.col-sm-11]:w-full' %}

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
                        {{ 'SalesReportIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <a
                        href="{{ back_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="mdi mdi-arrow-left rtl:rotate-180"></em>
                    {{ 'Back'|get_lang }}
                </a>

                <a
                        href="{{ export_report_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="mdi mdi-file-excel fa-fw"></em>
                    {{ 'GenerateReport'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>

                {% if paypal_enable and commissions_enable %}
                <a
                        href="{{ paypal_payout_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-secondary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:ring-offset-2"
                >
                    <em class="mdi mdi-bank-transfer"></em>
                    {{ 'PaypalPayoutCommissions'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
                {% endif %}

                {% if commissions_enable %}
                <a
                        href="{{ payout_report_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-info px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-info/30 focus:ring-offset-2"
                >
                    <em class="mdi mdi-cash"></em>
                    {{ 'PayoutReport'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
                {% endif %}
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Results'|get_lang }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ sales_count }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Filter'|get_lang }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ selected_filter_label ?: '—' }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'OrderStatus'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ selected_filter_type == '0' ? (selected_status_label ?: '—') : '—' }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Invoicing'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ invoicing_enable ? 'Enabled'|get_lang : 'Disabled'|get_lang }}
                </div>
            </div>
        </div>
    </section>

    <nav class="overflow-x-auto">
        <div class="inline-flex min-w-full rounded-2xl border border-gray-25 bg-white p-1 shadow-sm sm:min-w-0">
            <a
                    href="sales_report.php"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
            >
                {{ 'CourseSessionBlock'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>

            <a
                    href="service_sales_report.php"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary"
            >
                {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>

            <a
                    href="subscription_sales_report.php"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary"
            >
                {{ 'Subscriptions'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>

            <a
                    href="{{ export_report_url }}"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:bg-support-2 hover:text-primary"
            >
                {{ 'Export'|get_plugin_lang('BuyCoursesPlugin') }}
            </a>
        </div>
    </nav>

    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'Search'|get_lang }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ 'SalesReportSearchHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
        </div>

        <div class="p-6">
            <div class="{{ formShell }}">
                {{ form }}
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'SalesReport'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ 'SalesReportTableIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
        </div>

        <div class="p-6">
            <div id="gh2626-sales-grid" class="w-full overflow-x-auto rounded-2xl border border-gray-25 bg-white">
                <table id="table_report"></table>
            </div>

            <div id="tblGridPager" class="mt-4 text-center"></div>
        </div>
    </section>
</div>

<script>
  $(function () {
    function toggleReportFilters(value) {
      $("#report-by-status").toggle(value === "0");
      $("#report-by-user").toggle(value === "1");
      $("#report-by-date").toggle(value === "2");
      $("#report-by-email").toggle(value === "3");
    }

    const $filterType = $('[name="filter_type"]');
    let currentValue = $filterType.filter(":checked").val();

    if (typeof currentValue === "undefined") {
      currentValue = "{{ selected_filter_type|e('js') }}";
    }

    toggleReportFilters(currentValue);

    $filterType.on("change", function () {
      toggleReportFilters($(this).val());
    });
  });
</script>
{% endautoescape %}
