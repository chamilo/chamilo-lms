<div class="mx-auto w-full max-w-screen-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    {% set flash_messages = messages|default([]) %}
    {% set mode = form_mode|default('create') %}
    {% set action = action_url|default('') %}
    {% set data = form_data|default({}) %}
    {% set readOnlyCode = read_only_code|default(false) %}
    {% set readOnlyDiscountType = read_only_discount_type|default(false) %}
    {% set readOnlyDiscountAmount = read_only_discount_amount|default(false) %}
    {% set submitLabel = submit_label|default('Save'|get_lang) %}
    {% set pageDescription = page_description|default('Create a coupon, define the discount type and validity period, and assign it to courses, sessions or services.') %}
    {% set formHelp = form_section_help|default('Complete the form below and save the coupon configuration.') %}
    {% set formTitle = form_section_title|default(page_title) %}
    {% set discountTypeHelp = discount_type_help|default('Percentage or fixed amount.') %}
    {% set dateHelp = date_help|default('Define the validity period for the coupon.') %}
    {% set scopeHelp = scope_help|default('Assign the coupon to one or more courses, sessions or services.') %}

    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
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
                        {{ pageDescription }}
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

        <div class="mt-6 grid gap-4 xl:grid-cols-3">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'CouponDiscountType'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ discountTypeHelp }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Date'|get_lang }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ dateHelp }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Scope'|get_lang }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ scopeHelp }}
                </div>
            </div>
        </div>
    </section>

    {% if flash_messages %}
        <section class="space-y-3">
            {% for message in flash_messages %}
                {{ message|raw }}
            {% endfor %}
        </section>
    {% endif %}

    <section class="rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ formTitle }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ formHelp }}
                </p>
            </div>
        </div>

        <div class="p-6 lg:p-8">
            <form method="post" action="{{ action }}" class="space-y-8" id="buycourses-coupon-form">
                <input type="hidden" name="csrf_token" value="{{ csrf_token|default('')|e('html_attr') }}">

                {% set couponIdValue = data.id|default(0) %}
                {% if mode == 'configure' or couponIdValue > 0 %}
                    <input type="hidden" name="id" value="{{ couponIdValue|e('html_attr') }}">
                {% endif %}

                <div class="space-y-6">
                    <div class="w-full max-w-lg space-y-2">
                        <label for="coupon-code" class="block text-sm font-semibold text-gray-90">
                            * {{ 'CouponCode'|get_plugin_lang('BuyCoursesPlugin') }}
                        </label>
                        <input
                            id="coupon-code"
                            name="code"
                            type="text"
                            value="{{ data.code|default('')|e('html_attr') }}"
                            class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary {{ readOnlyCode ? 'bg-gray-15 text-gray-50' : '' }}"
                            {{ readOnlyCode ? 'readonly' : '' }}
                        >
                    </div>

                    <div class="space-y-3">
                        <div class="block text-sm font-semibold text-gray-90">
                            {{ 'CouponDiscountType'|get_plugin_lang('BuyCoursesPlugin') }}
                        </div>

                        {% if readOnlyDiscountType %}
                            <div class="w-full max-w-lg">
                                <input
                                    type="text"
                                    value="{{ data.discount_type_label|default('')|e('html_attr') }}"
                                    class="block w-full rounded-xl border-gray-25 bg-gray-15 text-sm text-gray-50 shadow-sm focus:border-primary focus:ring-primary"
                                    readonly
                                >
                            </div>
                        {% else %}
                            <div class="space-y-3">
                                {% for discountTypeId, discountTypeLabel in discount_types %}
                                    <label class="flex max-w-lg items-center gap-3 rounded-2xl border border-gray-25 bg-white px-4 py-3">
                                        <input
                                            type="radio"
                                            name="discount_type"
                                            value="{{ discountTypeId }}"
                                            class="h-4 w-4 border-gray-25 text-primary focus:ring-primary"
                                            {{ data.discount_type|default('') == discountTypeId ? 'checked' : '' }}
                                        >
                                        <span class="text-sm font-medium text-gray-90">{{ discountTypeLabel }}</span>
                                    </label>
                                {% endfor %}
                            </div>
                        {% endif %}
                    </div>

                    <div class="w-full max-w-lg space-y-2">
                        <label for="coupon-discount-amount" class="block text-sm font-semibold text-gray-90">
                            {{ 'CouponDiscount'|get_plugin_lang('BuyCoursesPlugin') }}
                        </label>
                        <input
                            id="coupon-discount-amount"
                            name="discount_amount"
                            type="number"
                            min="0"
                            step="1"
                            value="{{ data.discount_amount|default('0')|e('html_attr') }}"
                            class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary {{ readOnlyDiscountAmount ? 'bg-gray-15 text-gray-50' : '' }}"
                            {{ readOnlyDiscountAmount ? 'readonly' : '' }}
                        >
                        {% if currency_iso %}
                            <div class="text-sm text-gray-50">{{ currency_iso }}</div>
                        {% endif %}
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="w-full max-w-lg space-y-2">
                            <label for="coupon-date-start" class="block text-sm font-semibold text-gray-90">
                                * Start date
                            </label>
                            <input
                                id="coupon-date-start"
                                name="date_start"
                                type="datetime-local"
                                value="{{ data.date_start_input|default('')|e('html_attr') }}"
                                class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                            >
                        </div>

                        <div class="w-full max-w-lg space-y-2">
                            <label for="coupon-date-end" class="block text-sm font-semibold text-gray-90">
                                * End date
                            </label>
                            <input
                                id="coupon-date-end"
                                name="date_end"
                                type="datetime-local"
                                value="{{ data.date_end_input|default('')|e('html_attr') }}"
                                class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                            >
                        </div>
                    </div>

                    <label class="flex max-w-lg items-center gap-3 rounded-2xl border border-gray-25 bg-white px-4 py-3">
                        <input
                            type="checkbox"
                            name="active"
                            value="1"
                            class="h-4 w-4 border-gray-25 text-primary focus:ring-primary"
                            {{ data.active|default(false) ? 'checked' : '' }}
                        >
                        <span class="text-sm font-medium text-gray-90">{{ 'Active'|get_lang }}</span>
                    </label>
                </div>

                <div class="space-y-8">
                    <section class="space-y-3">
                        <div class="text-base font-semibold text-gray-90">{{ 'Courses'|get_lang }}</div>

                        <div class="w-full max-w-5xl">
                            <div class="flex w-full flex-col gap-6 xl:flex-row xl:items-center">
                                <div class="min-w-0 flex-1 space-y-3">
                                    <div class="text-sm font-semibold text-gray-90">{{ 'AvailableItems'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                    <input
                                        type="text"
                                        id="courses_available_search"
                                        placeholder="Search"
                                        class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                                    >
                                    <select
                                        id="courses_available"
                                        multiple
                                        class="block h-80 w-full rounded-2xl border border-gray-25 bg-white p-3 text-sm text-gray-90 shadow-sm"
                                    >
                                        {% for id, label in courses_options %}
                                            {% if data.courses_lookup[id] is not defined %}
                                                <option value="{{ id }}">{{ label }}</option>
                                            {% endif %}
                                        {% endfor %}
                                    </select>
                                </div>

                                <div class="flex shrink-0 self-center gap-4">
                                    <button
                                        type="button"
                                        id="courses_add"
                                        class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-2xl font-bold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                                    >›</button>
                                    <button
                                        type="button"
                                        id="courses_remove"
                                        class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-2xl font-bold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                                    >‹</button>
                                </div>

                                <div class="min-w-0 flex-1 space-y-3">
                                    <div class="text-sm font-semibold text-gray-90">{{ 'SelectedItems'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                    <input
                                        type="text"
                                        id="courses_selected_search"
                                        placeholder="Search"
                                        class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                                    >
                                    <select
                                        id="courses_selected"
                                        name="courses[]"
                                        multiple
                                        class="block h-80 w-full rounded-2xl border border-gray-25 bg-white p-3 text-sm text-gray-90 shadow-sm"
                                    >
                                        {% for id, label in courses_options %}
                                            {% if data.courses_lookup[id] is defined %}
                                                <option value="{{ id }}" selected>{{ label }}</option>
                                            {% endif %}
                                        {% endfor %}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </section>

                    {% if include_sessions %}
                        <section class="space-y-3">
                            <div class="text-base font-semibold text-gray-90">{{ 'Sessions'|get_lang }}</div>

                            <div class="w-full max-w-5xl">
                                <div class="flex w-full flex-col gap-6 xl:flex-row xl:items-center">
                                    <div class="min-w-0 flex-1 space-y-3">
                                        <div class="text-sm font-semibold text-gray-90">{{ 'AvailableItems'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                        <input
                                            type="text"
                                            id="sessions_available_search"
                                            placeholder="Search"
                                            class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                                        >
                                        <select
                                            id="sessions_available"
                                            multiple
                                            class="block h-80 w-full rounded-2xl border border-gray-25 bg-white p-3 text-sm text-gray-90 shadow-sm"
                                        >
                                            {% for id, label in sessions_options %}
                                                {% if data.sessions_lookup[id] is not defined %}
                                                    <option value="{{ id }}">{{ label }}</option>
                                                {% endif %}
                                            {% endfor %}
                                        </select>
                                    </div>

                                    <div class="flex shrink-0 self-center gap-4">
                                        <button
                                            type="button"
                                            id="sessions_add"
                                            class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-2xl font-bold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                                        >›</button>
                                        <button
                                            type="button"
                                            id="sessions_remove"
                                            class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-2xl font-bold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                                        >‹</button>
                                    </div>

                                    <div class="min-w-0 flex-1 space-y-3">
                                        <div class="text-sm font-semibold text-gray-90">{{ 'SelectedItems'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                        <input
                                            type="text"
                                            id="sessions_selected_search"
                                            placeholder="Search"
                                            class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                                        >
                                        <select
                                            id="sessions_selected"
                                            name="sessions[]"
                                            multiple
                                            class="block h-80 w-full rounded-2xl border border-gray-25 bg-white p-3 text-sm text-gray-90 shadow-sm"
                                        >
                                            {% for id, label in sessions_options %}
                                                {% if data.sessions_lookup[id] is defined %}
                                                    <option value="{{ id }}" selected>{{ label }}</option>
                                                {% endif %}
                                            {% endfor %}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </section>
                    {% endif %}

                    {% if include_services %}
                        <section class="space-y-3">
                            <div class="text-base font-semibold text-gray-90">{{ 'Services'|get_lang }}</div>

                            <div class="w-full max-w-5xl">
                                <div class="flex w-full flex-col gap-6 xl:flex-row xl:items-center">
                                    <div class="min-w-0 flex-1 space-y-3">
                                        <div class="text-sm font-semibold text-gray-90">{{ 'AvailableItems'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                        <input
                                            type="text"
                                            id="services_available_search"
                                            placeholder="Search"
                                            class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                                        >
                                        <select
                                            id="services_available"
                                            multiple
                                            class="block h-80 w-full rounded-2xl border border-gray-25 bg-white p-3 text-sm text-gray-90 shadow-sm"
                                        >
                                            {% for id, label in services_options %}
                                                {% if data.services_lookup[id] is not defined %}
                                                    <option value="{{ id }}">{{ label }}</option>
                                                {% endif %}
                                            {% endfor %}
                                        </select>
                                    </div>

                                    <div class="flex shrink-0 self-center gap-4">
                                        <button
                                            type="button"
                                            id="services_add"
                                            class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-2xl font-bold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                                        >›</button>
                                        <button
                                            type="button"
                                            id="services_remove"
                                            class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-2xl font-bold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                                        >‹</button>
                                    </div>

                                    <div class="min-w-0 flex-1 space-y-3">
                                        <div class="text-sm font-semibold text-gray-90">{{ 'SelectedItems'|get_plugin_lang('BuyCoursesPlugin') }}</div>
                                        <input
                                            type="text"
                                            id="services_selected_search"
                                            placeholder="Search"
                                            class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                                        >
                                        <select
                                            id="services_selected"
                                            name="services[]"
                                            multiple
                                            class="block h-80 w-full rounded-2xl border border-gray-25 bg-white p-3 text-sm text-gray-90 shadow-sm"
                                        >
                                            {% for id, label in services_options %}
                                                {% if data.services_lookup[id] is defined %}
                                                    <option value="{{ id }}" selected>{{ label }}</option>
                                                {% endif %}
                                            {% endfor %}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </section>
                    {% endif %}
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-success px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2 {{ submit_disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ submit_disabled ? 'disabled' : '' }}
                    >
                        <em class="fa fa-check fa-fw"></em>
                        {{ submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
    (function () {
        function filterSelectOptions(searchInput, selectElement) {
            const query = searchInput.value.trim().toLowerCase();

            Array.from(selectElement.options).forEach(function (option) {
                option.hidden = query !== '' && !option.text.toLowerCase().includes(query);
            });
        }

        function moveSelectedOptions(sourceSelect, targetSelect) {
            const selectedOptions = Array.from(sourceSelect.options).filter(function (option) {
                return option.selected;
            });

            selectedOptions.forEach(function (option) {
                option.selected = false;
                option.hidden = false;
                targetSelect.appendChild(option);
            });
        }

        function selectAllOptions(selectElement) {
            Array.from(selectElement.options).forEach(function (option) {
                option.selected = true;
            });
        }

        function bindDualList(baseName) {
            const availableSearch = document.getElementById(baseName + '_available_search');
            const selectedSearch = document.getElementById(baseName + '_selected_search');
            const availableSelect = document.getElementById(baseName + '_available');
            const selectedSelect = document.getElementById(baseName + '_selected');
            const addButton = document.getElementById(baseName + '_add');
            const removeButton = document.getElementById(baseName + '_remove');

            if (!availableSelect || !selectedSelect || !addButton || !removeButton) {
                return;
            }

            if (availableSearch) {
                availableSearch.addEventListener('input', function () {
                    filterSelectOptions(availableSearch, availableSelect);
                });
            }

            if (selectedSearch) {
                selectedSearch.addEventListener('input', function () {
                    filterSelectOptions(selectedSearch, selectedSelect);
                });
            }

            addButton.addEventListener('click', function () {
                moveSelectedOptions(availableSelect, selectedSelect);
                if (availableSearch) {
                    filterSelectOptions(availableSearch, availableSelect);
                }
                if (selectedSearch) {
                    filterSelectOptions(selectedSearch, selectedSelect);
                }
            });

            removeButton.addEventListener('click', function () {
                moveSelectedOptions(selectedSelect, availableSelect);
                if (availableSearch) {
                    filterSelectOptions(availableSearch, availableSelect);
                }
                if (selectedSearch) {
                    filterSelectOptions(selectedSearch, selectedSelect);
                }
            });

            availableSelect.addEventListener('dblclick', function () {
                moveSelectedOptions(availableSelect, selectedSelect);
            });

            selectedSelect.addEventListener('dblclick', function () {
                moveSelectedOptions(selectedSelect, availableSelect);
            });
        }

        bindDualList('courses');
        bindDualList('sessions');
        bindDualList('services');

        const form = document.getElementById('buycourses-coupon-form');
        if (form) {
            form.addEventListener('submit', function () {
                ['courses_selected', 'sessions_selected', 'services_selected'].forEach(function (id) {
                    const selectElement = document.getElementById(id);
                    if (selectElement) {
                        selectAllOptions(selectElement);
                    }
                });
            });
        }
    })();
</script>
