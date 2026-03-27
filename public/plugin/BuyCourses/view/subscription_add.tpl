{% autoescape false %}
<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        {{ page_title }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        Create a subscription for this product, define the optional tax rate, and add one or more subscription periods with their prices.
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
                        href="{{ frequency_url }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="fa fa-calendar-alt fa-fw"></em>
                    {{ 'ConfigureSubscriptionsFrequencies'|get_plugin_lang('BuyCoursesPlugin') }}
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'ProductType'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ product_label }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4 md:col-span-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Name'|get_lang }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ product_name }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Currency'|get_lang }}
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    {{ currency_label }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4 md:col-span-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Status'|get_lang }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {% if has_currency and has_frequencies %}
                    Ready to configure subscription periods.
                    {% elseif not has_currency %}
                    Currency must be configured before saving this subscription.
                    {% else %}
                    Subscription periods must be configured before saving this subscription.
                    {% endif %}
                </div>
            </div>
        </div>
    </section>

    {{ items_form }}
</div>

<script>
  $(function () {
    const $addButton = $("#subscription-add-frequency");
    const $duration = $("#duration");
    const $price = $("#price");
    const $tbody = $("#subscription-frequencies-body");
    const currencyIso = {{ currencyIso|json_encode|raw }};

  function renderEmptyState() {
    if ($tbody.find("tr.subscription-frequency-row").length === 0) {
      if ($("#subscription-empty-row").length === 0) {
        $tbody.append(
          '<tr id="subscription-empty-row">' +
          '<td colspan="3" class="px-4 py-8 text-center text-sm text-gray-50">' +
          'No subscription periods added yet.' +
          '</td>' +
          '</tr>'
        );
      }
    } else {
      $("#subscription-empty-row").remove();
    }
  }

  function getNextIndex() {
    return $tbody.find("tr.subscription-frequency-row").length + 1;
  }

  function frequencyAlreadyExists(value) {
    let exists = false;

    $tbody.find(".frequency-days").each(function () {
      if ($(this).val() === value) {
        exists = true;
        return false;
      }
    });

    return exists;
  }

  $addButton.on("click", function (event) {
    event.preventDefault();

    const selectedFrequency = $duration.val();
    const selectedFrequencyText = $("#duration option:selected").text();
    const selectedFrequencyPriceRaw = $price.val();

    if (!selectedFrequency) {
      return;
    }

    if (!selectedFrequencyPriceRaw || parseFloat(selectedFrequencyPriceRaw) <= 0) {
      return;
    }

    if (frequencyAlreadyExists(selectedFrequency)) {
      return;
    }

    const rowIndex = getNextIndex();
    const selectedFrequencyPrice = parseFloat(selectedFrequencyPriceRaw).toFixed(2);

    const rowHtml =
      '<tr class="subscription-frequency-row transition hover:bg-support-2">' +
      '<td class="px-4 py-4 text-sm text-gray-90">' +
      '<input class="frequency-days" type="hidden" name="frequencies[' + rowIndex + '][duration]" value="' + selectedFrequency + '">' +
      selectedFrequencyText +
      '</td>' +
      '<td class="px-4 py-4 text-sm font-semibold text-gray-90">' +
      '<input type="hidden" name="frequencies[' + rowIndex + '][price]" value="' + selectedFrequencyPrice + '">' +
      selectedFrequencyPrice + ' ' + currencyIso +
      '</td>' +
      '<td class="px-4 py-4 text-right">' +
      '<button type="button" class="subscription-delete-frequency inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2">' +
      '<em class="fa fa-remove fa-fw"></em>' +
      '{{ "Delete"|get_lang }}' +
      '</button>' +
      '</td>' +
      '</tr>';

    $tbody.append(rowHtml);
    $price.val("");
    renderEmptyState();
  });

  $tbody.on("click", ".subscription-delete-frequency", function (event) {
    event.preventDefault();
    $(this).closest("tr").remove();
    renderEmptyState();
  });

  renderEmptyState();
  });
</script>
{% endautoescape %}
