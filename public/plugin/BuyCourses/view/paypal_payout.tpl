{% autoescape false %}
<div class="mx-auto w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ plugin_title|default('plugin_title'|get_plugin_lang('BuyCoursesPlugin')) }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ page_title }}
                    </h1>
                    <p class="mt-2 text-sm leading-6 text-gray-50">
                        {{ 'PaypalPayoutCommissions'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <a
                    href="{{ back_url }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    {{ 'Back'|get_lang }}
                </a>

                <button
                    id="continuePayout"
                    type="button"
                    data-toggle="modal"
                    data-target="#startPayout"
                    data-backdrop="static"
                    data-keyboard="false"
                    {% if not has_eligible_payouts %}disabled="disabled"{% endif %}
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2 {% if not has_eligible_payouts %}cursor-not-allowed opacity-50{% endif %}"
                >
                    <em class="fa fa-paypal fa-fw"></em>
                    {{ 'ContinuePayout'|get_plugin_lang('BuyCoursesPlugin') }}
                </button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Results'|get_lang }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ payouts_count }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'PayPalAccount'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ eligible_payouts_count }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'NoPayPalAccountDetected'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ missing_paypal_account_count }}
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ 'PaypalPayoutCommissions'|get_plugin_lang('BuyCoursesPlugin') }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}, {{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}, {{ 'Commission'|get_plugin_lang('BuyCoursesPlugin') }}, {{ 'PayPalAccount'|get_plugin_lang('BuyCoursesPlugin') }}
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-25">
                <thead class="bg-gray-15">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-50">
                            <input
                                type="checkbox"
                                id="checkAll"
                                class="h-4 w-4 rounded border-gray-25 text-primary focus:ring-primary"
                            >
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Commission'|get_plugin_lang('BuyCoursesPlugin') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'PayPalAccount'|get_plugin_lang('BuyCoursesPlugin') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Options'|get_lang }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-25 bg-white">
                    {% for payout in payout_list %}
                        <tr class="transition {{ payout.has_paypal_account ? 'hover:bg-support-2' : 'bg-danger/5 hover:bg-danger/10' }}">
                            <td class="px-4 py-4 text-center">
                                {% if payout.has_paypal_account %}
                                    <input
                                        id="{{ payout.id }}"
                                        type="checkbox"
                                        name="data[]"
                                        value="{{ payout.commission }}"
                                        class="h-4 w-4 rounded border-gray-25 text-primary focus:ring-primary"
                                    >
                                {% else %}
                                    <span class="inline-flex items-center rounded-full bg-gray-20 px-2 py-1 text-xs font-semibold text-gray-50">
                                        —
                                    </span>
                                {% endif %}
                            </td>

                            <td class="px-4 py-4 text-sm font-medium text-gray-90">
                                {{ payout.reference }}
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-90">
                                {{ payout.date }}
                            </td>

                            <td class="px-4 py-4 text-right text-sm font-semibold text-gray-90">
                                {{ payout.commission_formatted }}
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-90">
                                {% if payout.has_paypal_account %}
                                    {{ payout.paypal_account }}
                                {% else %}
                                    <span class="inline-flex items-center rounded-full bg-danger px-3 py-1 text-xs font-semibold text-white">
                                        {{ 'NoPayPalAccountDetected'|get_plugin_lang('BuyCoursesPlugin') }}
                                    </span>
                                {% endif %}
                            </td>

                            <td class="px-4 py-4 text-right">
                                <button
                                    id="{{ payout.id }}"
                                    type="button"
                                    class="cancelPayout inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2"
                                >
                                    <em class="fa fa-ban fa-fw"></em>
                                    {{ 'CancelPayout'|get_plugin_lang('BuyCoursesPlugin') }}
                                </button>
                            </td>
                        </tr>
                    {% else %}
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center">
                                <div class="mx-auto max-w-md space-y-2">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-support-2 text-primary">
                                        <em class="fa fa-paypal text-xl"></em>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-90">
                                        {{ 'NoResults'|get_lang }}
                                    </h3>
                                </div>
                            </td>
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    </section>

    <div id="startPayout" class="modal fade" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-xl">
                <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
                    <h4 class="text-lg font-semibold text-gray-90">
                        {{ 'PaypalPayoutCommissions'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h4>
                </div>

                <div class="space-y-4 px-6 py-6">
                    <div id="content" class="text-sm text-gray-90"></div>
                    <div id="spinner" class="text-sm text-gray-50"></div>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-25 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        id="proceedPayout"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-success px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success/30 focus:ring-offset-2"
                    >
                        <em class="fa fa-paypal fa-fw"></em>
                        {{ 'ProceedPayout'|get_plugin_lang('BuyCoursesPlugin') }}
                    </button>

                    <button
                        id="cancelPayout"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2"
                        data-dismiss="modal"
                    >
                        {{ 'Cancel'|get_lang }}
                    </button>

                    <button
                        id="responseButton"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                    >
                        {{ 'Confirm'|get_lang }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function () {
            $("#responseButton").hide()

            $("#checkAll").on("click", function () {
                $('input[name="data[]"]').prop("checked", this.checked)
            })

            $("#continuePayout").on("click", function () {
                var val = []

                $('input[name="data[]"]:checked').each(function (i) {
                    val[i] = $(this).attr("id")
                })

                $("#content").html("")
                $("#spinner").html("")
                $("#responseButton").hide()
                $("#proceedPayout").show()
                $("#cancelPayout").show()

                $.ajax({
                    data: { payouts: val },
                    url: '{{ url('index') ~ 'plugin/BuyCourses/src/buycourses.ajax.php?' ~ {'a': 'processPayout'}|url_encode }}',
                    type: 'POST',
                    success: function (response) {
                        $("#content").html(response)
                        $("#proceedPayout").prop("disabled", 0 === val.length)
                    }
                })
            })

            $("#proceedPayout").on("click", function () {
                var val = []

                $('input[name="data[]"]:checked').each(function (i) {
                    val[i] = $(this).attr("id")
                })

                $.ajax({
                    data: { payouts: val },
                    url: '{{ url('index') ~ 'plugin/BuyCourses/src/buycourses.ajax.php?' ~ {'a': 'proceedPayout'}|url_encode }}',
                    type: 'POST',
                    beforeSend: function () {
                        $("#proceedPayout").hide()
                        $("#cancelPayout").hide()
                        $("#spinner").html('<div class="rounded-2xl border border-gray-25 bg-support-2 px-4 py-4 text-sm text-gray-90">{{ 'ProcessingPayoutsDontCloseThisWindow'|get_plugin_lang('BuyCoursesPlugin') }}</div>')
                    },
                    success: function (response) {
                        $("#spinner").html("")
                        $("#content").html(response)
                        $("#responseButton").show()
                    }
                })
            })

            $(".cancelPayout").on("click", function () {
                var id = this.id

                $.ajax({
                    data: { id: id },
                    url: '{{ url('index') ~ 'plugin/BuyCourses/src/buycourses.ajax.php?' ~ {'a': 'cancelPayout'}|url_encode }}',
                    type: 'POST',
                    success: function () {
                        window.location.reload()
                    }
                })
            })

            $("#responseButton").on("click", function () {
                window.location.reload()
            })
        })
    </script>
</div>
{% endautoescape %}
