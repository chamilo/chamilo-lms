{% autoescape false %}
<div class="mx-auto w-full space-y-6 px-4 py-6 sm:px-6">
<script type="text/javascript" src="../resources/js/modals.js"></script>

<div id="buy-courses-tabs" class="space-y-6">
    <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
        <li id="buy-courses-tab" class="" role="presentation">
            <a href="course_panel.php" aria-controls="buy-courses" role="tab">{{ 'MyCourses'| get_lang }}</a>
        </li>
        {% if sessions_are_included %}
            <li id="buy-sessions-tab" class="" role="presentation">
                <a href="session_panel.php" aria-controls="buy-sessions" role="tab">{{ 'MySessions'| get_lang }}</a>
            </li>
        {% endif %}
        {% if services_are_included %}
            <li id="buy-services-tab" class="active" role="presentation">
                <a href="service_panel.php" aria-controls="buy-services" role="tab">{{ 'MyServices'| get_plugin_lang('BuyCoursesPlugin') }}</a>
            </li>
        {% endif %}
        <li id="buy-courses-tab" class="" role="presentation">
            <a href="payout_panel.php" aria-controls="buy-courses" role="tab">{{ 'MyPayouts'| get_plugin_lang('BuyCoursesPlugin') }}</a>
        </li>
    </ul>

    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-90">{{ 'ActiveServices'|get_plugin_lang('BuyCoursesPlugin') }}</h2>
                <p class="mt-1 text-body-2 text-gray-50">{{ 'ActiveServicesDescription'|get_plugin_lang('BuyCoursesPlugin') }}</p>
            </div>
            <a
                href="service_catalog.php"
                class="inline-flex items-center justify-center rounded-2xl bg-primary px-5 py-3 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-90"
            >
                {{ 'ListOfServicesOnSale'| get_plugin_lang('BuyCoursesPlugin') }}
            </a>
        </div>

        {% if active_service_list is empty %}
            <div class="rounded-2xl border border-dashed border-gray-25 bg-support-2 px-5 py-6 text-body-2 text-gray-50">
                {{ 'NoActiveServicesYet'|get_plugin_lang('BuyCoursesPlugin') }}
            </div>
        {% else %}
            <div class="grid gap-5 lg:grid-cols-2">
                {% for sale in active_service_list %}
                    <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                        <div class="aspect-[16/9] overflow-hidden bg-support-2">
                            <img src="{{ sale.image }}" alt="{{ sale.name }}" class="h-full w-full object-cover">
                        </div>
                        <div class="space-y-4 p-5">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-caption font-semibold uppercase tracking-wide text-primary">
                                        {{ sale.service_type }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-success/10 px-3 py-1 text-caption font-semibold uppercase tracking-wide text-success">
                                        {{ 'Active'|get_lang }}
                                    </span>
                                </div>
                                <h3 class="text-body-1 font-semibold text-gray-90">{{ sale.name }}</h3>
                                <p class="text-body-2 text-gray-50">
                                    {{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}: <span class="font-semibold text-gray-90">{{ sale.reference }}</span>
                                </p>
                            </div>

                            <dl class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-3">
                                    <dt class="text-tiny font-semibold uppercase tracking-wide text-primary">{{ 'StartDate'|get_lang }}</dt>
                                    <dd class="mt-1 text-body-2 font-semibold text-gray-90">{{ sale.date_start }}</dd>
                                </div>
                                <div class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-3">
                                    <dt class="text-tiny font-semibold uppercase tracking-wide text-primary">{{ 'EndDate'|get_lang }}</dt>
                                    <dd class="mt-1 text-body-2 font-semibold text-gray-90">{{ sale.date_end }}</dd>
                                </div>
                            </dl>

                            {% if sale.benefit_summaries is not empty %}
                                <div class="space-y-2">
                                    <h4 class="text-body-2 font-semibold text-gray-90">{{ 'GrantedBenefits'|get_plugin_lang('BuyCoursesPlugin') }}</h4>
                                    <ul class="space-y-2">
                                        {% for benefit in sale.benefit_summaries %}
                                            <li class="rounded-2xl border border-primary/10 bg-support-1 px-4 py-3 text-body-2 text-gray-90">{{ benefit }}</li>
                                        {% endfor %}
                                    </ul>
                                </div>
                            {% endif %}

                            <div class="flex flex-wrap gap-3 pt-2">
                                <a
                                    id="service_sale_info"
                                    tag="{{ sale.id }}"
                                    name="s_{{ sale.id }}"
                                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-info px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                                >
                                    {{ 'Info'|get_lang }}
                                </a>
                                {% if sale.receipt_url %}
                                    <a
                                        href="{{ sale.receipt_url }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:bg-support-1 hover:text-primary"
                                    >
                                        {{ 'Receipt'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </a>
                                {% endif %}
                            </div>
                        </div>
                    </article>
                {% endfor %}
            </div>
        {% endif %}
    </section>

    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-semibold text-gray-90">{{ 'PurchaseHistory'|get_plugin_lang('BuyCoursesPlugin') }}</h2>
            <p class="mt-1 text-body-2 text-gray-50">{{ 'PurchaseHistoryDescription'|get_plugin_lang('BuyCoursesPlugin') }}</p>
        </div>

        {% if purchase_history is empty %}
            <div class="rounded-2xl border border-dashed border-gray-25 bg-support-2 px-5 py-6 text-body-2 text-gray-50">
                {{ 'NoPurchasesYet'|get_plugin_lang('BuyCoursesPlugin') }}
            </div>
        {% else %}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-25">
                    <thead>
                    <tr>
                        <th class="px-4 py-3 text-left">{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="px-4 py-3 text-left">{{ 'ProductType'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="px-4 py-3 text-left">{{ 'ProductName'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="px-4 py-3 text-left">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="px-4 py-3 text-left">{{ 'SalePrice'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                        <th class="px-4 py-3 text-left">{{ 'Receipt'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {% for purchase in purchase_history %}
                        <tr>
                            <td class="px-4 py-4">{{ purchase.date }}</td>
                            <td class="px-4 py-4">{{ purchase.type }}</td>
                            <td class="px-4 py-4">{{ purchase.product_name }}</td>
                            <td class="px-4 py-4">{{ purchase.reference }}</td>
                            <td class="px-4 py-4">{{ purchase.amount }}</td>
                            <td class="px-4 py-4">
                                {% if purchase.receipt_url %}
                                    <a href="{{ purchase.receipt_url }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:bg-support-1 hover:text-primary">
                                        {{ 'Receipt'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </a>
                                {% else %}
                                    <span class="text-body-2 text-gray-50">—</span>
                                {% endif %}
                            </td>
                        </tr>
                    {% endfor %}
                    </tbody>
                </table>
            </div>
        {% endif %}
    </section>
</div>
<script>
    $(function () {
        $("a#service_sale_info").click(function () {
            var id = $(this).attr('tag');
            var action = $(this).attr('id');
            $.ajax({
                data: 'id=' + id,
                url: '{{ url('index') }}plugin/BuyCourses/src/buycourses.ajax.php?a=' + action,
                type: 'POST',
                beforeSend: function () {
                    $('a[name=s_' + id + ']').html('<em class="fa fa-spinner fa-pulse"></em> {{ 'Loading'|get_lang }}');
                },
                success: function (response) {
                    $('a[name=s_' + id + ']').html('{{ 'Info'|get_lang }}');
                    bootbox.dialog({
                        message: response,
                        title: "{{ 'ServiceSaleInfo'|get_plugin_lang('BuyCoursesPlugin') }}",
                        buttons: {
                            main: {
                                label: "{{ 'Close'|get_lang }}",
                                className: "btn--plain"
                            }
                        }
                    });
                }
            })
        });
    });
</script>
</div>
{% endautoescape %}
