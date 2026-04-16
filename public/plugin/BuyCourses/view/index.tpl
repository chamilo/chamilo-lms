{% autoescape false %}
<div class="w-full px-4 py-8 sm:px-6 lg:px-8 2xl:px-8">
    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-xl">
        {% if is_granted('ROLE_ADMIN') %}
            <div class="grid gap-8 p-6 lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)] lg:p-8">
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-gray-25 bg-support-2">
                            <img
                                class="h-10 w-10 object-contain"
                                src="resources/img/64/buycourses.png"
                                alt="{{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}"
                            >
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                                {{ 'TitlePlugin'|get_plugin_lang('BuyCoursesPlugin') }}
                            </h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-50 sm:text-base">
                                {{ 'PluginPresentation'|get_plugin_lang('BuyCoursesPlugin') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            href="src/paymentsetup.php"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                        >
                            {{ 'PaymentsConfiguration'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>

                        <a
                            href="src/list.php"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                        >
                            {{ 'ConfigurationOfCoursesAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}
                        </a>
                    </div>
                </div>

                <aside class="rounded-2xl border border-gray-25 bg-support-2 p-5">
                    <h2 class="text-base font-semibold text-gray-90">
                        {{ 'Instructions'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h2>

                    <div class="mt-4 space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">
                                1
                            </span>
                            <p class="text-sm leading-6 text-gray-50">
                                {{ 'InstructionsStepOne'|get_plugin_lang('BuyCoursesPlugin') }}
                            </p>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">
                                2
                            </span>
                            <p class="text-sm leading-6 text-gray-50">
                                {{ 'InstructionsStepTwo'|get_plugin_lang('BuyCoursesPlugin') }}
                            </p>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">
                                3
                            </span>
                            <p class="text-sm leading-6 text-gray-50">
                                {{ 'InstructionsStepThree'|get_plugin_lang('BuyCoursesPlugin') }}
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        {% else %}
            <div class="p-6 lg:p-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-gray-25 bg-support-2">
                        <img
                            class="h-10 w-10 object-contain"
                            src="resources/img/64/buycourses.png"
                            alt="{{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}"
                        >
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                            {{ 'LandingChooseWhatToBuy'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h1>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-50 sm:text-base">
                            {{ 'LandingBrowseIntro'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>
                </div>
            </div>
        {% endif %}
    </section>

    <section class="mt-8">
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            {% if services_are_included %}
                <a
                    href="{% if is_granted('ROLE_ADMIN') %}src/list_service.php{% else %}src/service_catalog.php{% endif %}"
                    class="group flex h-full flex-col rounded-2xl border border-gray-25 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-25 bg-support-2 transition group-hover:bg-primary/10">
                            <img
                                class="h-9 w-9 object-contain"
                                src="resources/img/64/settings.png"
                                alt="{{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}"
                            >
                        </div>
                        <span class="text-xl font-semibold text-primary transition group-hover:translate-x-1">&rarr;</span>
                    </div>

                    <div class="mt-5 space-y-2">
                        <h2 class="text-lg font-semibold text-gray-90">
                            {{ 'Services'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {% if is_granted('ROLE_ADMIN') %}
                                Manage premium services and granted benefits
                            {% else %}
                                Browse premium services and granted benefits
                            {% endif %}
                        </p>
                    </div>
                </a>
            {% endif %}

            <a
                href="src/course_catalog.php"
                class="group flex h-full flex-col rounded-2xl border border-gray-25 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
            >
                <div class="flex items-center justify-between gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-25 bg-support-2 transition group-hover:bg-primary/10">
                        <img
                            class="h-9 w-9 object-contain"
                            src="resources/img/64/buycourses.png"
                            alt="{{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}"
                        >
                    </div>
                    <span class="text-xl font-semibold text-primary transition group-hover:translate-x-1">&rarr;</span>
                </div>

                <div class="mt-5 space-y-2">
                    <h2 class="text-lg font-semibold text-gray-90">
                        {{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h2>
                    <p class="text-sm leading-6 text-gray-50">
                        {{ 'CourseListOnSale'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </a>

            <a
                href="src/subscription_course_catalog.php"
                class="group flex h-full flex-col rounded-2xl border border-gray-25 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
            >
                <div class="flex items-center justify-between gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-25 bg-support-2 transition group-hover:bg-primary/10">
                        <img
                            class="h-9 w-9 object-contain"
                            src="resources/img/64/buysubscriptions.png"
                            alt="{{ 'BuySubscriptions'|get_plugin_lang('BuyCoursesPlugin') }}"
                        >
                    </div>
                    <span class="text-xl font-semibold text-primary transition group-hover:translate-x-1">&rarr;</span>
                </div>

                <div class="mt-5 space-y-2">
                    <h2 class="text-lg font-semibold text-gray-90">
                        {{ 'BuySubscriptions'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h2>
                    <p class="text-sm leading-6 text-gray-50">
                        {{ 'SubscriptionListOnSale'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </a>

            {% if is_granted('ROLE_ADMIN') %}
                <a
                    href="src/list.php"
                    class="group flex h-full flex-col rounded-2xl border border-gray-25 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-25 bg-support-2 transition group-hover:bg-primary/10">
                            <img
                                class="h-9 w-9 object-contain"
                                src="resources/img/64/settings.png"
                                alt="{{ 'ConfigurationOfCoursesAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}"
                            >
                        </div>
                        <span class="text-xl font-semibold text-primary transition group-hover:translate-x-1">&rarr;</span>
                    </div>

                    <div class="mt-5 space-y-2">
                        <h2 class="text-lg font-semibold text-gray-90">
                            {{ 'ConfigurationOfCoursesAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {{ 'AvailableCoursesConfiguration'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>
                </a>

                <a
                    href="src/subscriptions_courses.php"
                    class="group flex h-full flex-col rounded-2xl border border-gray-25 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-25 bg-support-2 transition group-hover:bg-primary/10">
                            <img
                                class="h-9 w-9 object-contain"
                                src="resources/img/64/subscriptionssettings.png"
                                alt="{{ 'ConfigurationOfSubscriptionsAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}"
                            >
                        </div>
                        <span class="text-xl font-semibold text-primary transition group-hover:translate-x-1">&rarr;</span>
                    </div>

                    <div class="mt-5 space-y-2">
                        <h2 class="text-lg font-semibold text-gray-90">
                            {{ 'ConfigurationOfSubscriptionsAndPrices'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {{ 'Subscriptions'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>
                </a>

                <a
                    href="src/coupons.php"
                    class="group flex h-full flex-col rounded-2xl border border-gray-25 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-25 bg-support-2 transition group-hover:bg-primary/10">
                            <img
                                class="h-9 w-9 object-contain"
                                src="resources/img/64/discount.png"
                                alt="{{ 'CouponsConfiguration'|get_plugin_lang('BuyCoursesPlugin') }}"
                            >
                        </div>
                        <span class="text-xl font-semibold text-primary transition group-hover:translate-x-1">&rarr;</span>
                    </div>

                    <div class="mt-5 space-y-2">
                        <h2 class="text-lg font-semibold text-gray-90">
                            {{ 'CouponsConfiguration'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {{ 'CouponList'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>
                </a>

                <a
                    href="src/paymentsetup.php"
                    class="group flex h-full flex-col rounded-2xl border border-gray-25 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-25 bg-support-2 transition group-hover:bg-primary/10">
                            <img
                                class="h-9 w-9 object-contain"
                                src="resources/img/64/paymentsettings.png"
                                alt="{{ 'PaymentsConfiguration'|get_plugin_lang('BuyCoursesPlugin') }}"
                            >
                        </div>
                        <span class="text-xl font-semibold text-primary transition group-hover:translate-x-1">&rarr;</span>
                    </div>

                    <div class="mt-5 space-y-2">
                        <h2 class="text-lg font-semibold text-gray-90">
                            {{ 'PaymentsConfiguration'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {{ 'PluginInstruction'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>
                </a>

                <a
                    href="src/sales_report.php"
                    class="group flex h-full flex-col rounded-2xl border border-gray-25 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-25 bg-support-2 transition group-hover:bg-primary/10">
                            <img
                                class="h-9 w-9 object-contain"
                                src="resources/img/64/backlogs.png"
                                alt="{{ 'SalesReport'|get_plugin_lang('BuyCoursesPlugin') }}"
                            >
                        </div>
                        <span class="text-xl font-semibold text-primary transition group-hover:translate-x-1">&rarr;</span>
                    </div>

                    <div class="mt-5 space-y-2">
                        <h2 class="text-lg font-semibold text-gray-90">
                            {{ 'SalesReport'|get_plugin_lang('BuyCoursesPlugin') }}
                        </h2>
                        <p class="text-sm leading-6 text-gray-50">
                            {{ 'Stats'|get_plugin_lang('BuyCoursesPlugin') }}
                        </p>
                    </div>
                </a>
            {% endif %}
        </div>
    </section>
</div>
{% endautoescape %}
