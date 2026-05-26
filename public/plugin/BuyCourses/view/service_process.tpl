{% autoescape false %}
{% set appliesToLabel = '' %}
{% if service.applies_to == 0 %}
    {% set appliesToLabel = 'None'|get_lang %}
{% elseif service.applies_to == 1 %}
    {% set appliesToLabel = 'User'|get_lang %}
{% elseif service.applies_to == 2 %}
    {% set appliesToLabel = 'Course'|get_lang %}
{% elseif service.applies_to == 3 %}
    {% set appliesToLabel = 'Session'|get_lang %}
{% elseif service.applies_to == 4 %}
    {% set appliesToLabel = 'TemplateTitleCertificate'|get_lang %}
{% endif %}

{% set durationLabel = service.duration_days == 0 ? 'NoLimit'|get_lang : service.duration_days ~ ' ' ~ 'Days'|get_lang %}

<div class="mx-auto w-full max-w-screen-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h1>
                    <p class="max-w-3xl text-sm leading-6 text-gray-50">
                        {{ 'ServiceProcessIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0">
                <a
                    href="service_information.php?service_id={{ service.id }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    {{ 'Back'|get_lang }}
                </a>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="space-y-6 xl:col-span-2">
            <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[320px_minmax(0,1fr)]">
                    <div class="bg-support-2">
                        <div class="aspect-[16/11] overflow-hidden">
                            <img
                                alt="{{ service.name }}"
                                class="h-full w-full object-cover"
                                src="{{ service.image ? service.image : 'session_default.png'|icon() }}"
                            >
                        </div>
                    </div>

                    <div class="p-6 lg:p-8">
                        <div class="space-y-5">
                            <div class="space-y-2">
                                <h2 class="text-2xl font-semibold text-gray-90">
                                    {{ service.name }}
                                </h2>

                                {% if service.description %}
                                    <div class="text-sm leading-7 text-gray-50">
                                        {{ service.description|raw }}
                                    </div>
                                {% endif %}
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <div class="rounded-2xl bg-support-2 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                        {{ 'Total'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </div>
                                    <div class="mt-2 text-xl font-semibold text-gray-90">
                                        {{ service.total_price_formatted }}
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-support-2 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                        {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </div>
                                    <div class="mt-2 text-base font-semibold text-gray-90">
                                        {{ appliesToLabel }}
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-support-2 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                        {{ 'Duration'|get_lang }}
                                    </div>
                                    <div class="mt-2 text-base font-semibold text-gray-90">
                                        {{ durationLabel }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 text-sm text-gray-50">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-support-1 text-support-4">
                                        <em class="fa fa-user"></em>
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-90">
                                            {{ 'Owner'|get_lang }}
                                        </div>
                                        <div>{{ service.owner_name }}</div>
                                    </div>
                                </div>

                                {% if service.tax_enable %}
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-support-1 text-support-4">
                                            <em class="fa fa-percent"></em>
                                        </span>
                                        <div>
                                            <div class="font-semibold text-gray-90">
                                                {{ service.tax_name }} ({{ service.tax_perc_show }}%)
                                            </div>
                                            <div>{{ service.tax_amount_formatted }}</div>
                                        </div>
                                    </div>
                                {% endif %}

                                {% if service.has_coupon %}
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-support-1 text-support-4">
                                            <em class="fa fa-tag"></em>
                                        </span>
                                        <div>
                                            <div class="font-semibold text-gray-90">
                                                {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}
                                            </div>
                                            <div>{{ service.discount_amount_formatted }}</div>
                                        </div>
                                    </div>
                                {% endif %}
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
                <div class="space-y-5">
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-gray-90">
                            {{ 'DoYouHaveACoupon'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {{ 'ServiceCouponCodeHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>

                    <div class="buycourses-generated-form buycourses-coupon-form">
                        {{ form_coupon }}
                    </div>
                </div>
            </article>

            <article class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
                <div class="space-y-5">
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-gray-90">
                            {{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {{ 'ServicePaymentMethodHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>

                    <div class="buycourses-generated-form buycourses-payment-form">
                        {{ form }}
                    </div>
                </div>
            </article>
        </section>

        <aside class="space-y-6 xl:col-span-1">
            <div class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <div class="space-y-5">
                    <div class="space-y-2">
                        <h2 class="text-lg font-semibold text-gray-90">
                            {{ 'ServiceProcessSummaryTitle'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {{ 'ServiceProcessSummaryHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-support-2 p-4">
                            <span class="text-sm font-semibold text-gray-90">
                                {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                            </span>
                            <span class="text-sm font-semibold text-gray-90">
                                {{ service.price_formatted }}
                            </span>
                        </div>

                        {% if service.tax_enable %}
                            <div class="flex items-center justify-between gap-4 rounded-2xl bg-support-2 p-4">
                                <span class="text-sm font-semibold text-gray-90">
                                    {{ service.tax_name }} ({{ service.tax_perc_show }}%)
                                </span>
                                <span class="text-sm font-semibold text-gray-90">
                                    {{ service.tax_amount_formatted }}
                                </span>
                            </div>
                        {% endif %}

                        {% if service.has_coupon %}
                            <div class="flex items-center justify-between gap-4 rounded-2xl bg-support-2 p-4">
                                <span class="text-sm font-semibold text-gray-90">
                                    {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}
                                </span>
                                <span class="text-sm font-semibold text-gray-90">
                                    {{ service.discount_amount_formatted }}
                                </span>
                            </div>
                        {% endif %}

                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-primary p-4">
                            <span class="text-sm font-semibold text-white">
                                {{ 'Total'|get_plugin_lang('BuyCoursesPlugin') }}
                            </span>
                            <span class="text-lg font-semibold text-white">
                                {{ service.total_price_formatted }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <div class="rounded-2xl border border-gray-25 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-90">
                                {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-2 text-sm text-gray-50">
                                {{ appliesToLabel }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-25 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-90">
                                {{ 'Duration'|get_lang }}
                            </div>
                            <div class="mt-2 text-sm text-gray-50">
                                {{ durationLabel }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-25 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-90">
                                {{ 'User'|get_lang }}
                            </div>
                            <div class="mt-2 text-sm text-gray-50">
                                {{ service.owner_name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    $(function () {
        const inputClasses = 'block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary';
        const primaryButtonClasses = 'inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2';
        const secondaryButtonClasses = 'inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2';
        const alertBaseClasses = 'rounded-2xl border px-4 py-3 text-sm';
        const alertInfoClasses = 'border-info/20 bg-support-2 text-gray-90';
        const alertErrorClasses = 'border-danger/20 bg-danger/10 text-gray-90';
        const alertSuccessClasses = 'border-success/20 bg-success/10 text-gray-90';

        $('.buycourses-generated-form form').each(function () {
            const $form = $(this);

            $form.addClass('space-y-5');
            $form.removeClass('form-horizontal');

            $form.find('.row').each(function () {
                $(this).removeClass('row').addClass('grid gap-4');
            });

            $form.find('[class*="col-"]').each(function () {
                const currentClass = $(this).attr('class') || '';
                const cleanedClass = currentClass.replace(/\bcol-(xs|sm|md|lg)-\d+\b/g, '').replace(/\s+/g, ' ').trim();
                $(this).attr('class', cleanedClass).addClass('w-full');
            });

            $form.find('.form-group').addClass('space-y-2');
            $form.find('label').removeClass('control-label').addClass('block text-sm font-semibold text-gray-90');
            $form.find('.form_required').remove();
            $form.find('small').remove();
            $form.find('label[for=submit]').remove();

            $form.find('input[type="text"], input[type="email"], input[type="number"], input[type="password"], select, textarea').each(function () {
                $(this).addClass(inputClasses);
            });

            $form.find('textarea').addClass('min-h-[120px]');

            $form.find('input[type="radio"], input[type="checkbox"]').addClass('h-4 w-4 border-gray-25 text-primary focus:ring-primary');

            $form.find('.radio, .checkbox').each(function () {
                $(this).addClass('rounded-2xl border border-gray-25 bg-white px-4 py-3');
                $(this).find('label').addClass('!mb-0 inline-flex items-center gap-3 text-sm font-medium text-gray-90');
            });

            $form.find('.alert').each(function () {
                const $alert = $(this);
                $alert.addClass(alertBaseClasses);

                if ($alert.hasClass('alert-info')) {
                    $alert.addClass(alertInfoClasses);
                } else if ($alert.hasClass('alert-danger') || $alert.hasClass('alert-error')) {
                    $alert.addClass(alertErrorClasses);
                } else if ($alert.hasClass('alert-success')) {
                    $alert.addClass(alertSuccessClasses);
                } else {
                    $alert.addClass(alertInfoClasses);
                }
            });
        });

        $('.buycourses-coupon-form').find('.btn, button, input[type="submit"]').each(function () {
            $(this)
                .removeClass('btn btn-primary btn-success btn-default btn-lg pull-right')
                .addClass(secondaryButtonClasses);
        });

        $('.buycourses-payment-form').find('.btn, button, input[type="submit"]').each(function () {
            $(this)
                .removeClass('btn btn-primary btn-success btn-default btn-lg pull-right')
                .addClass(primaryButtonClasses);
        });
    });
</script>
{% endautoescape %}
