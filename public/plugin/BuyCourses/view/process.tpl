{% autoescape false %}
{% if item_type == 1 %}
{% set back_url = url('index') ~ 'plugin/BuyCourses/src/course_catalog.php' %}
{% elseif item_type == 2 %}
{% set back_url = url('index') ~ 'plugin/BuyCourses/src/session_catalog.php' %}
{% else %}
{% set back_url = url('index') ~ 'plugin/BuyCourses/src/service_catalog.php' %}
{% endif %}

{% set couponFormShell = 'rounded-2xl border border-gray-25 bg-white p-5 shadow-sm [&_form]:space-y-4 [&_.form-group]:mb-0 [&_.form-group]:space-y-2 [&_label]:mb-2 [&_label]:block [&_label]:text-sm [&_label]:font-semibold [&_label]:text-gray-90 [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:block [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:w-full [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:rounded-xl [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:border-gray-25 [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:bg-white [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:text-sm [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:text-gray-90 [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:shadow-sm [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:placeholder:text-gray-50 [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:focus:border-primary [&_input:not([type=radio]):not([type=checkbox]):not([type=hidden])]:focus:ring-primary [&_button]:inline-flex [&_button]:items-center [&_button]:justify-center [&_button]:gap-2 [&_button]:rounded-xl [&_button]:bg-success [&_button]:px-4 [&_button]:py-2.5 [&_button]:text-sm [&_button]:font-semibold [&_button]:text-white [&_button]:shadow-sm [&_button]:transition [&_button]:hover:opacity-90 [&_button]:focus:outline-none [&_button]:focus:ring-2 [&_button]:focus:ring-success/30 [&_button]:focus:ring-offset-2 [&_input[type=submit]]:inline-flex [&_input[type=submit]]:items-center [&_input[type=submit]]:justify-center [&_input[type=submit]]:gap-2 [&_input[type=submit]]:rounded-xl [&_input[type=submit]]:bg-success [&_input[type=submit]]:px-4 [&_input[type=submit]]:py-2.5 [&_input[type=submit]]:text-sm [&_input[type=submit]]:font-semibold [&_input[type=submit]]:text-white [&_input[type=submit]]:shadow-sm [&_input[type=submit]]:transition [&_input[type=submit]]:hover:opacity-90 [&_input[type=submit]]:focus:outline-none [&_input[type=submit]]:focus:ring-2 [&_input[type=submit]]:focus:ring-success/30 [&_input[type=submit]]:focus:ring-offset-2 [&_.form_required]:hidden [&_small]:hidden [&_.help-block]:mt-2 [&_.help-block]:block [&_.help-block]:text-sm [&_.help-block]:text-gray-50 [&_.col-sm-2]:w-full [&_.col-sm-3]:w-full [&_.col-sm-7]:w-full [&_.col-sm-8]:w-full [&_.col-sm-10]:w-full [&_.col-sm-11]:w-full' %}
{% set paymentFormShell = 'rounded-2xl border border-gray-25 bg-white p-5 shadow-sm [&_form]:space-y-4 [&_.form-group]:mb-0 [&_.form-group]:space-y-3 [&_label]:mb-2 [&_label]:block [&_label]:text-sm [&_label]:font-semibold [&_label]:text-gray-90 [&_.radio]:space-y-3 [&_.radio]:rounded-2xl [&_.radio]:border [&_.radio]:border-gray-25 [&_.radio]:bg-support-2 [&_.radio]:p-4 [&_.radio]:text-sm [&_.radio]:text-gray-90 [&_input[type=radio]]:mr-2 [&_input[type=radio]]:h-4 [&_input[type=radio]]:w-4 [&_input[type=radio]]:border-gray-25 [&_input[type=radio]]:text-primary [&_input[type=radio]]:focus:ring-primary [&_.alert]:rounded-2xl [&_.alert]:border [&_.alert]:border-info/20 [&_.alert]:bg-support-2 [&_.alert]:px-4 [&_.alert]:py-3 [&_.alert]:text-sm [&_.alert]:text-gray-90 [&_button]:inline-flex [&_button]:items-center [&_button]:justify-center [&_button]:gap-2 [&_button]:rounded-xl [&_button]:bg-primary [&_button]:px-4 [&_button]:py-2.5 [&_button]:text-sm [&_button]:font-semibold [&_button]:text-white [&_button]:shadow-sm [&_button]:transition [&_button]:hover:opacity-90 [&_button]:focus:outline-none [&_button]:focus:ring-2 [&_button]:focus:ring-primary/30 [&_button]:focus:ring-offset-2 [&_input[type=submit]]:inline-flex [&_input[type=submit]]:items-center [&_input[type=submit]]:justify-center [&_input[type=submit]]:gap-2 [&_input[type=submit]]:rounded-xl [&_input[type=submit]]:bg-primary [&_input[type=submit]]:px-4 [&_input[type=submit]]:py-2.5 [&_input[type=submit]]:text-sm [&_input[type=submit]]:font-semibold [&_input[type=submit]]:text-white [&_input[type=submit]]:shadow-sm [&_input[type=submit]]:transition [&_input[type=submit]]:hover:opacity-90 [&_input[type=submit]]:focus:outline-none [&_input[type=submit]]:focus:ring-2 [&_input[type=submit]]:focus:ring-primary/30 [&_input[type=submit]]:focus:ring-offset-2 [&_.form_required]:hidden [&_small]:hidden [&_.help-block]:mt-2 [&_.help-block]:block [&_.help-block]:text-sm [&_.help-block]:text-gray-50 [&_.col-sm-2]:w-full [&_.col-sm-3]:w-full [&_.col-sm-7]:w-full [&_.col-sm-8]:w-full [&_.col-sm-10]:w-full [&_.col-sm-11]:w-full' %}

<div class="mx-auto w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        {{ 'ProcessIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a
                        href="{{ back_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                        title="{{ 'Back'|get_lang }}"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    {{ 'Back'|get_lang }}
                </a>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
        <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
            <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="aspect-[4/3] overflow-hidden bg-gray-15">
                    {% if buying_course %}
                    <a
                            class="ajax block h-full w-full"
                            data-title="{{ course.title }}"
                            href="{{ _p.web_ajax ~ 'course_home.ajax.php?' ~ {'a': 'show_course_information', 'code': course.code}|url_encode() }}"
                    >
                        <img
                                alt="{{ course.title }}"
                                class="h-full w-full object-cover"
                                src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}"
                        >
                    </a>
                    {% elseif buying_session %}
                    <img
                            alt="{{ session.title }}"
                            class="h-full w-full object-cover"
                            src="{{ session.image ? session.image : 'session_default.png'|icon() }}"
                    >
                    {% endif %}
                </div>

                <div class="space-y-4 p-6">
                    <div class="grid gap-3">
                        {% if buying_course %}
                        {% if course.tax_enable %}
                        <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ course.item.price_formatted }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ course.tax_name }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ course.item.tax_amount_formatted }} ({{ course.item.tax_perc_show }}%)
                            </div>
                        </div>
                        {% endif %}

                        {% if course.item.has_coupon %}
                        <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ course.item.discount_amount_formatted }}
                            </div>
                        </div>
                        {% endif %}

                        <div class="rounded-2xl border border-primary/20 bg-support-2 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Total'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-1 text-xl font-semibold text-gray-90">
                                {{ course.item.total_price_formatted }}
                            </div>
                        </div>
                        {% elseif buying_session %}
                        {% if session.tax_enable %}
                        <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ session.item.price_formatted }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ session.tax_name }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ session.item.tax_amount_formatted }} ({{ session.item.tax_perc_show }}%)
                            </div>
                        </div>
                        {% endif %}

                        {% if session.item.has_coupon %}
                        <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ session.item.discount_amount_formatted }}
                            </div>
                        </div>
                        {% endif %}

                        <div class="rounded-2xl border border-primary/20 bg-support-2 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Total'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-1 text-xl font-semibold text-gray-90">
                                {{ session.item.total_price_formatted }}
                            </div>
                        </div>
                        {% endif %}
                    </div>

                    <div class="space-y-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-90">
                                {{ 'DoYouHaveACoupon'|get_plugin_lang('BuyCoursesPlugin') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-50">
                                {{ 'CouponCodeHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                            </p>
                        </div>

                        <div class="{{ couponFormShell }}">
                            {{ form_coupon }}
                        </div>
                    </div>
                </div>
            </section>
        </aside>

        <div class="space-y-6">
            <section class="rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-90">
                        {{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h2>
                </div>

                <div class="space-y-6 p-6">
                    {% if buying_course %}
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-2">
                                <h3 class="text-2xl font-semibold tracking-tight text-gray-90">
                                    <a
                                            class="ajax transition hover:text-primary"
                                            data-title="{{ course.title }}"
                                            href="{{ _p.web_ajax ~ 'course_home.ajax.php?' ~ {'a': 'show_course_information', 'code': course.code}|url_encode() }}"
                                    >
                                        {{ course.title }}
                                    </a>
                                </h3>

                                <div class="flex flex-wrap gap-2">
                                    {% if course.code %}
                                    <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                                                {{ course.code }}
                                            </span>
                                    {% endif %}
                                    {% if course.visual_code %}
                                    <span class="inline-flex items-center rounded-full bg-gray-20 px-3 py-1 text-xs font-semibold text-gray-90">
                                                {{ course.visual_code }}
                                            </span>
                                    {% endif %}
                                </div>
                            </div>

                            <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
                                    {{ 'Course'|get_lang }}
                                </span>
                        </div>

                        {% if course.description %}
                        <div class="rounded-2xl border border-gray-25 bg-white p-5 text-sm leading-6 text-gray-90 shadow-sm">
                            {{ course.description }}
                        </div>
                        {% endif %}

                        {% if course.teachers %}
                        <div class="rounded-2xl border border-gray-25 bg-support-2 p-5">
                            <div class="mb-3 text-sm font-semibold text-gray-90">
                                {{ 'Teachers'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="flex flex-wrap gap-2">
                                {% for teacher in course.teachers %}
                                <a
                                        href="{{ url('index') ~ 'main/social/profile.php?u=' ~ teacher.id }}"
                                        class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-white px-3 py-2 text-sm font-medium text-gray-90 transition hover:border-primary/30 hover:text-primary"
                                >
                                    <em class="fa fa-user"></em>
                                    {{ teacher.name }}
                                </a>
                                {% endfor %}
                            </div>
                        </div>
                        {% endif %}
                    </div>
                    {% elseif buying_session %}
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-2">
                                <h3 class="text-2xl font-semibold tracking-tight text-gray-90">
                                    {{ session.title }}
                                </h3>

                                {% if session.dates.display %}
                                <div class="inline-flex items-center gap-2 rounded-full bg-support-1 px-3 py-1 text-sm font-medium text-support-4">
                                    <em class="fa fa-calendar"></em>
                                    {{ session.dates.display }}
                                </div>
                                {% endif %}
                            </div>

                            <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
                                    {{ 'Session'|get_lang }}
                                </span>
                        </div>

                        {% if session.description %}
                        <div class="rounded-2xl border border-gray-25 bg-white p-5 text-sm leading-6 text-gray-90 shadow-sm">
                            {{ session.description }}
                        </div>
                        {% endif %}

                        {% if session.courses %}
                        <div class="rounded-2xl border border-gray-25 bg-support-2 p-5">
                            <div class="mb-4 text-sm font-semibold text-gray-90">
                                {{ 'Courses'|get_lang }}
                            </div>

                            <div class="space-y-4">
                                {% for course in session.courses %}
                                <div class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1 text-primary">
                                            <em class="fa fa-book"></em>
                                        </div>
                                        <div class="min-w-0 flex-1 space-y-2">
                                            <div class="text-base font-semibold text-gray-90">
                                                {{ course.title }}
                                            </div>

                                            {% if course.coaches|length %}
                                            <div class="flex flex-wrap gap-2">
                                                {% for coach in course.coaches %}
                                                <a
                                                        href="{{ url('index') ~ 'main/social/profile.php?u=' ~ coach.id }}"
                                                        class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-support-2 px-3 py-2 text-sm font-medium text-gray-90 transition hover:border-primary/30 hover:text-primary"
                                                >
                                                    <em class="fa fa-user"></em>
                                                    {{ coach.name }}
                                                </a>
                                                {% endfor %}
                                            </div>
                                            {% endif %}
                                        </div>
                                    </div>
                                </div>
                                {% endfor %}
                            </div>
                        </div>
                        {% endif %}
                    </div>
                    {% endif %}
                </div>
            </section>

            <section class="rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-90">
                        {{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-50">
                        {{ 'PaymentMethodHelp'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>

                <div class="p-6">
                    <div class="{{ paymentFormShell }}">
                        {{ form }}
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
  $(function () {
    $(".form_required").remove();
    $("small").remove();
    $("label[for=submit]").remove();
    $(".coupon form label.control-label, .payment-methods form label.control-label").removeClass("control-label");
  });
</script>
{% endautoescape %}
