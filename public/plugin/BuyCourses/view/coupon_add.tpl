{% autoescape false %}
<div class="buycourses-coupon-form mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <style>
        .buycourses-coupon-form form,
        .buycourses-coupon-form .row,
        .buycourses-coupon-form .form-group,
        .buycourses-coupon-form .element,
        .buycourses-coupon-form .form_element {
            width: 100% !important;
            max-width: none !important;
        }

        .buycourses-coupon-form .form-group {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .buycourses-coupon-form .form-group > [class*="col-"],
        .buycourses-coupon-form .form-group > .control-label,
        .buycourses-coupon-form .form-group > label {
            width: 100% !important;
            max-width: none !important;
            float: none !important;
            flex: 0 0 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            text-align: left !important;
        }

        .buycourses-coupon-form .form-group > .control-label,
        .buycourses-coupon-form .form-group > label,
        .buycourses-coupon-form .form-group > [class*="col-"]:first-child {
            margin-bottom: 0.75rem !important;
        }

        .buycourses-coupon-form .advmultiselect,
        .buycourses-coupon-form .advmultiselect > div,
        .buycourses-coupon-form .buycourses-advmultiselect-grid,
        .buycourses-coupon-form .buycourses-advmultiselect-column {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
        }

        .buycourses-coupon-form .buycourses-advmultiselect-grid {
            display: grid !important;
            grid-template-columns: minmax(340px, 1fr) 72px minmax(340px, 1fr);
            gap: 1.25rem;
            align-items: start;
            width: 100% !important;
        }

        .buycourses-coupon-form .buycourses-advmultiselect-column {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .buycourses-coupon-form .buycourses-advmultiselect-actions {
            width: 72px;
            min-width: 72px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding-top: 3.25rem;
        }

        .buycourses-coupon-form .buycourses-advmultiselect-column input[type="text"],
        .buycourses-coupon-form .buycourses-advmultiselect-column select[multiple] {
            width: 100% !important;
            max-width: none !important;
            box-sizing: border-box !important;
        }

        .buycourses-coupon-form .buycourses-advmultiselect-column select[multiple] {
            min-height: 260px !important;
            height: 260px !important;
        }

        .buycourses-coupon-form .buycourses-advmultiselect-actions button,
        .buycourses-coupon-form .buycourses-advmultiselect-actions input[type="button"],
        .buycourses-coupon-form .buycourses-advmultiselect-actions input[type="submit"] {
            width: 48px !important;
            min-width: 48px !important;
            height: 48px !important;
        }

        @media (max-width: 1200px) {
            .buycourses-coupon-form .buycourses-advmultiselect-grid {
                grid-template-columns: minmax(280px, 1fr) 64px minmax(280px, 1fr);
            }

            .buycourses-coupon-form .buycourses-advmultiselect-actions {
                width: 64px;
                min-width: 64px;
            }
        }

        @media (max-width: 992px) {
            .buycourses-coupon-form .buycourses-advmultiselect-grid {
                grid-template-columns: 1fr;
            }

            .buycourses-coupon-form .buycourses-advmultiselect-actions {
                width: 100%;
                min-width: 100%;
                flex-direction: row;
                padding-top: 0;
            }
        }
    </style>

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
                        {{ page_description|default('Create a coupon, define the discount type and validity period, and assign it to courses, sessions or services.') }}
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

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'CouponDiscountType'|get_plugin_lang('BuyCoursesPlugin') }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ discount_type_help|default('Percentage or fixed amount.') }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Date'|get_lang }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ date_help|default('Define the validity period for the coupon.') }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Scope'|get_lang }}
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    {{ scope_help|default('Assign the coupon to one or more courses, sessions or services.') }}
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-gray-25 bg-white shadow-sm">
        <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-gray-90">
                    {{ form_section_title|default(page_title) }}
                </h2>
                <p class="text-sm text-gray-50">
                    {{ form_section_help|default('Complete the form below and save the coupon configuration.') }}
                </p>
            </div>
        </div>

        <div class="p-6">
            {{ form }}
        </div>
    </section>
</div>

<script>
  $(function () {
    function forcePrimaryButtonsTextWhite() {
      $(".btn-primary, .btn--primary, input.btn-primary, button.btn-primary").each(function () {
        $(this).css("color", "#ffffff");
        $(this).addClass("text-white");
      });
    }

    function expandLegacyFormColumns() {
      $(".buycourses-coupon-form .form-group").each(function () {
        $(this).css({
          width: "100%",
          maxWidth: "none",
          marginLeft: "0",
          marginRight: "0"
        });

        $(this)
          .find("> [class*='col-'], > .control-label, > label")
          .css({
            width: "100%",
            maxWidth: "none",
            float: "none",
            flex: "0 0 100%",
            paddingLeft: "0",
            paddingRight: "0",
            textAlign: "left"
          });

        $(this)
          .find("> .control-label, > label, > [class*='col-']:first-child")
          .css({
            marginBottom: "12px"
          });
      });

      $(".buycourses-coupon-form form, .buycourses-coupon-form .row, .buycourses-coupon-form .element, .buycourses-coupon-form .form_element").css({
        width: "100%",
        maxWidth: "none"
      });
    }

    function normalizeMoveButtonText($button, index) {
      let label = "";

      if ($button.is("input")) {
        label = ($button.val() || "").trim();
      } else {
        label = ($button.text() || "").trim();
      }

      if (/^(>|&gt;|>>|»|›|add|right)$/i.test(label)) {
        return "›";
      }

      if (/^(<|&lt;|<<|«|‹|remove|left)$/i.test(label)) {
        return "‹";
      }

      if (index === 0) {
        return "›";
      }

      if (index === 1) {
        return "‹";
      }

      return label || "›";
    }

    function styleMoveButton($button, index) {
      const label = normalizeMoveButtonText($button, index);

      if ($button.is("input")) {
        $button.val(label);
      } else {
        $button.text(label);
      }

      $button.css({
        width: "48px",
        minWidth: "48px",
        height: "48px",
        padding: "0",
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        borderRadius: "14px",
        border: "0",
        background: "rgb(var(--color-primary-base))",
        color: "#ffffff",
        fontSize: "22px",
        fontWeight: "700",
        lineHeight: "1",
        boxShadow: "0 1px 2px rgba(0,0,0,0.08)",
        cursor: "pointer"
      });

      $button.removeClass("btn btn-primary btn-default btn--primary btn--plain w-full text-white");
    }

    function styleSearchInput($input) {
      $input.css({
        width: "100%",
        maxWidth: "none",
        borderRadius: "12px",
        border: "1px solid #cbd5e1",
        background: "#ffffff",
        minHeight: "44px",
        padding: "10px 14px",
        fontSize: "14px",
        color: "#334155",
        boxSizing: "border-box"
      });
    }

    function styleMultiSelect($select) {
      $select.css({
        width: "100%",
        maxWidth: "none",
        minHeight: "260px",
        height: "260px",
        borderRadius: "16px",
        border: "1px solid #e4e9ed",
        background: "#ffffff",
        padding: "10px",
        fontSize: "14px",
        color: "#333333",
        boxSizing: "border-box",
        overflow: "auto"
      });
    }

    function rebuildAdvancedMultiSelect($widget) {
      const $searchInputs = $widget.find('input[type="text"]').filter(function () {
        return !$(this).closest(".daterangepicker").length;
      });

      const $selects = $widget.find("select[multiple]");
      const $buttons = $widget.find('button, input[type="button"], input[type="submit"]').filter(function () {
        return !$(this).closest(".daterangepicker").length;
      });

      if ($selects.length < 2) {
        return;
      }

      $widget.css({
        width: "100%",
        maxWidth: "none",
        minWidth: "0"
      });

      $widget.closest(".form-group, .element, .form_element, [class*='col-']").css({
        width: "100%",
        maxWidth: "none",
        minWidth: "0",
        float: "none",
        flex: "0 0 100%"
      });

      const $leftSearch = $searchInputs.eq(0);
      const $rightSearch = $searchInputs.eq(1);
      const $leftSelect = $selects.eq(0);
      const $rightSelect = $selects.eq(1);

      const $layout = $("<div></div>")
        .addClass("buycourses-advmultiselect-grid")
        .css({
          width: "100%",
          maxWidth: "none"
        });

      const $leftColumn = $("<div></div>").addClass("buycourses-advmultiselect-column");
      const $centerColumn = $("<div></div>").addClass("buycourses-advmultiselect-actions");
      const $rightColumn = $("<div></div>").addClass("buycourses-advmultiselect-column");

      if ($leftSearch.length) {
        styleSearchInput($leftSearch);
        $leftColumn.append($leftSearch);
      }

      styleMultiSelect($leftSelect);
      $leftColumn.append($leftSelect);

      $buttons.each(function (index) {
        const $button = $(this);
        styleMoveButton($button, index);
        $centerColumn.append($button);
      });

      if ($rightSearch.length) {
        styleSearchInput($rightSearch);
        $rightColumn.append($rightSearch);
      }

      styleMultiSelect($rightSelect);
      $rightColumn.append($rightSelect);

      $widget.empty().append($layout.append($leftColumn, $centerColumn, $rightColumn));
    }

    function fixAllAdvancedMultiSelects() {
      $(".buycourses-coupon-form .advmultiselect").each(function () {
        rebuildAdvancedMultiSelect($(this));
      });
    }

    forcePrimaryButtonsTextWhite();
    expandLegacyFormColumns();
    fixAllAdvancedMultiSelects();
  });
</script>
{% endautoescape %}
