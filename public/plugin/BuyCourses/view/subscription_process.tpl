{% autoescape false %}
<div class="buycourses-subscription-process mx-auto w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <style>
        .buycourses-subscription-process .buycourses-form-card form,
        .buycourses-subscription-process .buycourses-form-card .form-inline,
        .buycourses-subscription-process .buycourses-form-card .row,
        .buycourses-subscription-process .buycourses-form-card .button-group {
            width: 100%;
        }

        .buycourses-subscription-process .buycourses-form-card .form-group {
            margin-bottom: 1rem;
        }

        .buycourses-subscription-process .buycourses-form-card input[type="text"],
        .buycourses-subscription-process .buycourses-form-card input[type="number"],
        .buycourses-subscription-process .buycourses-form-card select,
        .buycourses-subscription-process .buycourses-form-card textarea {
            width: 100%;
            border: 1px solid #d7e0e8;
            border-radius: 0.95rem;
            min-height: 46px;
            padding: 0.7rem 0.95rem;
            box-sizing: border-box;
            background: #ffffff;
            color: #223046;
        }

        .buycourses-subscription-process .buycourses-form-card .radio,
        .buycourses-subscription-process .buycourses-form-card .checkbox {
            margin: 0 0 0.9rem 0;
        }

        .buycourses-subscription-process .buycourses-form-card .radio label,
        .buycourses-subscription-process .buycourses-form-card .checkbox label {
            display: flex;
            gap: 0.8rem;
            align-items: flex-start;
            border: 1px solid #e4e9ed;
            border-radius: 1rem;
            background: #ffffff;
            padding: 0.95rem 1rem;
            width: 100%;
            font-weight: 600;
            color: #223046;
            margin: 0;
        }

        .buycourses-subscription-process .buycourses-form-card .radio input,
        .buycourses-subscription-process .buycourses-form-card .checkbox input {
            margin-top: 0.2rem;
        }

        .buycourses-subscription-process .buycourses-form-card .alert {
            border-radius: 1rem;
            margin-bottom: 1rem;
        }

        .buycourses-subscription-process .buycourses-form-card .help-block {
            margin-top: 0.45rem;
            font-size: 0.85rem;
            color: #6b7a90;
        }

        .buycourses-subscription-process .buycourses-form-card label[for="submit"],
        .buycourses-subscription-process .buycourses-form-card .form_required,
        .buycourses-subscription-process .buycourses-form-card small {
            display: none !important;
        }

        .buycourses-subscription-process .buycourses-description :last-child {
            margin-bottom: 0;
        }

        .buycourses-subscription-process .buycourses-price-list > div + div {
            margin-top: 0.75rem;
        }

        .buycourses-subscription-process .buycourses-form-card .btn,
        .buycourses-subscription-process .buycourses-form-card button,
        .buycourses-subscription-process .buycourses-form-card input[type="submit"],
        .buycourses-subscription-process .buycourses-form-card input[type="button"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: auto;
            min-width: 170px;
            min-height: 44px;
            border-radius: 0.95rem;
            border: 1px solid transparent;
            padding: 0.75rem 1.1rem;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: none;
            transition: all 0.2s ease-in-out;
        }

        .buycourses-subscription-process .buycourses-form-card .btn:hover,
        .buycourses-subscription-process .buycourses-form-card button:hover,
        .buycourses-subscription-process .buycourses-form-card input[type="submit"]:hover,
        .buycourses-subscription-process .buycourses-form-card input[type="button"]:hover {
            text-decoration: none;
            transform: translateY(-1px);
        }

        .buycourses-subscription-process .buycourses-form-card .btn-success,
        .buycourses-subscription-process .buycourses-form-card .btn-primary,
        .buycourses-subscription-process .buycourses-form-card .btn--primary,
        .buycourses-subscription-process .buycourses-form-card input.btn-success,
        .buycourses-subscription-process .buycourses-form-card input.btn-primary {
            background: #2f7db3;
            border-color: #2f7db3;
            color: #ffffff !important;
        }

        .buycourses-subscription-process .buycourses-form-card .btn-default,
        .buycourses-subscription-process .buycourses-form-card .btn-secondary,
        .buycourses-subscription-process .buycourses-form-card input.btn-default {
            background: #ffffff;
            border-color: #d7e0e8;
            color: #27364b !important;
        }

        .buycourses-subscription-process .buycourses-payment-card .btn,
        .buycourses-subscription-process .buycourses-payment-card button,
        .buycourses-subscription-process .buycourses-payment-card input[type="submit"],
        .buycourses-subscription-process .buycourses-payment-card input[type="button"] {
            min-width: 220px;
        }

        .buycourses-subscription-process .buycourses-payment-card .form-group:last-child,
        .buycourses-subscription-process .buycourses-coupon-card .form-group:last-child,
        .buycourses-subscription-process .buycourses-plan-card .form-group:last-child {
            margin-bottom: 0;
        }

        .buycourses-subscription-process .buycourses-cta-row,
        .buycourses-subscription-process .buycourses-payment-card .button-group,
        .buycourses-subscription-process .buycourses-payment-card .form-inline,
        .buycourses-subscription-process .buycourses-payment-card .row:last-child,
        .buycourses-subscription-process .buycourses-coupon-card .button-group,
        .buycourses-subscription-process .buycourses-coupon-card .form-inline,
        .buycourses-subscription-process .buycourses-coupon-card .row:last-child {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
        }

        .buycourses-subscription-process .buycourses-summary-card {
            position: sticky;
            top: 1.5rem;
        }

        .buycourses-subscription-process .buycourses-payment-card .radio label span,
        .buycourses-subscription-process .buycourses-plan-card .radio label span {
            display: inline-block;
            line-height: 1.45;
        }

        .buycourses-subscription-process .buycourses-product-card {
            overflow: hidden;
        }

        .buycourses-subscription-process .buycourses-product-image {
            height: 100%;
            min-height: 320px;
        }

        .buycourses-subscription-process .buycourses-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .buycourses-subscription-process .buycourses-price-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: #2f7db3;
            color: #ffffff;
            padding: 0.45rem 0.9rem;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .buycourses-subscription-process .buycourses-secondary-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 1200px) {
            .buycourses-subscription-process .buycourses-secondary-grid {
                grid-template-columns: minmax(0, 1fr) 340px;
                align-items: start;
            }
        }

        @media (max-width: 991px) {
            .buycourses-subscription-process .buycourses-summary-card {
                position: static;
            }

            .buycourses-subscription-process .buycourses-product-image {
                min-height: 240px;
            }
        }
    </style>

    {% if item_type == 1 %}
    {% set back_url = url('index') ~ 'plugin/BuyCourses/src/subscription_course_catalog.php' %}
    {% elseif item_type == 2 %}
    {% set back_url = url('index') ~ 'plugin/BuyCourses/src/subscription_session_catalog.php' %}
    {% else %}
    {% set back_url = url('index') ~ 'plugin/BuyCourses/index.php' %}
    {% endif %}

    {% set has_course_description = buying_course and course.description is defined and course.description|striptags|trim is not empty %}
    {% set course_description_url = url('index') ~ 'plugin/BuyCourses/src/course_information.php?course_id=' ~ (course.id|default(0)) %}
    {% set has_coupon = subscription.has_coupon is defined and subscription.has_coupon %}
    {% set tax_percent = subscription.tax_perc_show|default(subscription.item.tax_perc_show|default(null)) %}

    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}
                </span>

                <div>
                    <h1 class="text-3xl font-semibold text-gray-90">
                        {{ 'PurchaseData'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        {{ buying_course ? 'Review the selected subscription, choose the billing frequency, apply a coupon if needed, and then confirm the payment method.' : 'Review the selected session subscription, choose the billing frequency, apply a coupon if needed, and then confirm the payment method.' }}
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
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_360px]">
        <section class="space-y-6">
            <article class="buycourses-product-card rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[360px_minmax(0,1fr)]">
                    <div class="buycourses-product-image bg-support-2 p-5">
                        <div class="h-full overflow-hidden rounded-3xl bg-white/40">
                            {% if buying_course %}
                            {% if has_course_description %}
                            <a
                                    class="ajax block h-full"
                                    href="{{ course_description_url }}"
                                    data-title="{{ course.title }}"
                            >
                                <img
                                        alt="{{ course.title }}"
                                        src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}"
                                >
                            </a>
                            {% else %}
                            <img
                                    alt="{{ course.title }}"
                                    src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}"
                            >
                            {% endif %}
                            {% elseif buying_session %}
                            <img
                                    alt="{{ session.title }}"
                                    src="{{ session.image ? session.image : 'session_default.png'|icon() }}"
                            >
                            {% endif %}
                        </div>
                    </div>

                    <div class="space-y-5 p-6 lg:p-7">
                        {% if buying_course %}
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                {% if course.code is defined and course.code %}
                                <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                                            {{ course.code }}
                                        </span>
                                {% endif %}

                                <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
                                        {{ selected_duration_name }}
                                    </span>
                            </div>

                            <h2 class="text-2xl font-semibold text-gray-90">
                                {% if has_course_description %}
                                <a
                                        class="ajax transition hover:text-primary"
                                        href="{{ course_description_url }}"
                                        data-title="{{ course.title }}"
                                >
                                    {{ course.title }}
                                </a>
                                {% else %}
                                {{ course.title }}
                                {% endif %}
                            </h2>

                            {% if subscription.total_price_formatted is defined %}
                            <span class="buycourses-price-badge">
                                        {{ subscription.total_price_formatted }}
                                    </span>
                            {% endif %}
                        </div>

                        {% if course.description %}
                        <div class="buycourses-description rounded-2xl border border-gray-25 bg-support-2 px-4 py-4 text-sm leading-6 text-gray-90">
                            {{ course.description }}
                        </div>
                        {% endif %}

                        {% if course.teachers %}
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Teachers'|get_plugin_lang('BuyCoursesPlugin') }}
                            </h3>

                            <ul class="space-y-2">
                                {% for teacher in course.teachers %}
                                <li class="flex items-center gap-2 text-sm text-gray-90">
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-support-2 text-primary">
                                                    <em class="fa fa-user" aria-hidden="true"></em>
                                                </span>
                                    <a
                                            href="{{ url('index') ~ 'main/social/profile.php?u=' ~ teacher.id }}"
                                            class="transition hover:text-primary"
                                    >
                                        {{ teacher.name }}
                                    </a>
                                </li>
                                {% endfor %}
                            </ul>
                        </div>
                        {% endif %}
                        {% elseif buying_session %}
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
                                        {{ selected_duration_name }}
                                    </span>
                            </div>

                            <h2 class="text-2xl font-semibold text-gray-90">
                                {{ session.title }}
                            </h2>

                            {% if subscription.total_price_formatted is defined %}
                            <span class="buycourses-price-badge">
                                        {{ subscription.total_price_formatted }}
                                    </span>
                            {% endif %}

                            {% if session.dates is defined and session.dates.display %}
                            <p class="text-sm text-gray-50">
                                <em class="fa fa-calendar fa-fw text-primary"></em>
                                {{ session.dates.display }}
                            </p>
                            {% endif %}
                        </div>

                        {% if session.description %}
                        <div class="buycourses-description rounded-2xl border border-gray-25 bg-support-2 px-4 py-4 text-sm leading-6 text-gray-90">
                            {{ session.description }}
                        </div>
                        {% endif %}

                        {% if session.courses %}
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Courses'|get_lang }}
                            </h3>

                            <div class="space-y-3">
                                {% for itemCourse in session.courses %}
                                <div class="rounded-2xl border border-gray-25 bg-white px-4 py-3">
                                    <p class="font-semibold text-gray-90">
                                        <em class="fa fa-book text-primary" aria-hidden="true"></em>
                                        {{ itemCourse.title }}
                                    </p>

                                    {% if itemCourse.coaches|length %}
                                    <ul class="mt-2 space-y-1">
                                        {% for coach in itemCourse.coaches %}
                                        <li class="flex items-center gap-2 text-sm text-gray-50">
                                            <em class="fa fa-user text-primary" aria-hidden="true"></em>
                                            <a
                                                    href="{{ url('index') ~ 'main/social/profile.php?u=' ~ coach.id }}"
                                                    class="transition hover:text-primary"
                                            >
                                                {{ coach.name }}
                                            </a>
                                        </li>
                                        {% endfor %}
                                    </ul>
                                    {% endif %}
                                </div>
                                {% endfor %}
                            </div>
                        </div>
                        {% endif %}
                        {% endif %}
                    </div>
                </div>
            </article>

            <div class="buycourses-secondary-grid">
                <article class="buycourses-payment-card buycourses-form-card rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-semibold text-gray-90">
                        {{ 'PaymentMethods'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h3>
                    <p class="mt-2 text-sm leading-6 text-gray-50">
                        {{ 'PleaseSelectThePaymentMethodBeforeConfirmYourOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>

                    <div class="mt-5">
                        {% if message_payment %}
                        {{ message_payment }}
                        {% else %}
                        {{ form }}
                        {% endif %}
                    </div>
                </article>

                <div class="space-y-6">
                    <article class="buycourses-plan-card buycourses-form-card rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-90">
                            {{ 'SelecSubscription'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-50">
                            Choose the frequency that best fits this subscription.
                        </p>

                        <div class="mt-5">
                            {{ form_subscription }}
                        </div>
                    </article>

                    <article class="buycourses-coupon-card buycourses-form-card rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-90">
                            {{ 'DoYouHaveACoupon'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-50">
                            Enter your coupon code to update the subscription total before paying.
                        </p>

                        <div class="mt-5">
                            {{ form_coupon }}
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <aside>
            <div class="buycourses-summary-card">
                <article class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-semibold text-gray-90">
                        {{ 'OrderSummary'|get_lang }}
                    </h3>

                    <div class="buycourses-price-list mt-5">
                        <div class="rounded-2xl border border-gray-25 bg-support-2 px-4 py-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'SelectedPlan'|get_lang }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ selected_duration_name }}
                            </div>
                        </div>

                        {% if buying_course %}
                        <div class="rounded-2xl border border-gray-25 bg-support-2 px-4 py-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Course'|get_lang }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ course.title }}
                            </div>
                        </div>
                        {% elseif buying_session %}
                        <div class="rounded-2xl border border-gray-25 bg-support-2 px-4 py-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'SessionName'|get_lang }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ session.title }}
                            </div>
                        </div>
                        {% endif %}

                        {% if subscription.price_formatted is defined %}
                        <div class="rounded-2xl border border-gray-25 bg-white px-4 py-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-1 text-lg font-semibold text-gray-90">
                                {{ subscription.price_formatted }}
                            </div>
                        </div>
                        {% endif %}

                        {% if tax_percent is not null and subscription.tax_amount_formatted is defined %}
                        <div class="rounded-2xl border border-gray-25 bg-white px-4 py-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ buying_course ? course.tax_name|default('Tax') : session.tax_name|default('Tax') }} ({{ tax_percent }}%)
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ subscription.tax_amount_formatted }}
                            </div>
                        </div>
                        {% endif %}

                        {% if has_coupon and subscription.discount_amount_formatted is defined %}
                        <div class="rounded-2xl border border-success/20 bg-success/10 px-4 py-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'DiscountAmount'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-1 text-base font-semibold text-gray-90">
                                {{ subscription.discount_amount_formatted }}
                            </div>

                            {% if subscription.price_without_discount_formatted is defined %}
                            <div class="mt-2 text-sm text-gray-50">
                                {{ 'OriginalPrice'|get_lang }}: {{ subscription.price_without_discount_formatted }}
                            </div>
                            {% endif %}
                        </div>
                        {% endif %}

                        <div class="rounded-2xl border border-primary/15 bg-support-2 px-4 py-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                                {{ 'Total'|get_plugin_lang('BuyCoursesPlugin') }}
                            </div>
                            <div class="mt-1 text-2xl font-semibold text-gray-90">
                                {{ subscription.total_price_formatted }}
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </aside>
    </div>
</div>

<script>
  $(function () {
    $(".buycourses-subscription-process .btn-primary, .buycourses-subscription-process .btn-success, .buycourses-subscription-process .btn--primary").css("color", "#ffffff");

    $(".buycourses-payment-card form, .buycourses-plan-card form, .buycourses-coupon-card form").each(function () {
      $(this).css({
        width: "100%",
        maxWidth: "none"
      });
    });

    $(".buycourses-payment-card .row:last-child, .buycourses-payment-card .form-group:last-child, .buycourses-coupon-card .row:last-child, .buycourses-coupon-card .form-group:last-child").css({
      marginBottom: 0
    });

    $(".buycourses-payment-card .btn, .buycourses-payment-card input[type='submit'], .buycourses-payment-card input[type='button']").css({
      width: "auto",
      minWidth: "220px"
    });

    $(".buycourses-coupon-card .btn, .buycourses-coupon-card input[type='submit'], .buycourses-coupon-card input[type='button'], .buycourses-plan-card .btn, .buycourses-plan-card input[type='submit'], .buycourses-plan-card input[type='button']").css({
      width: "auto",
      minWidth: "160px"
    });

    $("input[name=duration]").on("click", function () {
      var selected = $("input[name=duration]:checked").val();

      if (selected !== null) {
        $("form[name=confirm_subscription]").submit();
      }
    });
  });
</script>
{% endautoescape %}
