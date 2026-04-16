{% autoescape false %}
<div class="buycourses-process-confirm mx-auto w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <style>
        .buycourses-process-confirm .buycourses-confirm-actions form,
        .buycourses-process-confirm .buycourses-confirm-actions .form-inline,
        .buycourses-process-confirm .buycourses-confirm-actions .row,
        .buycourses-process-confirm .buycourses-confirm-actions .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .buycourses-process-confirm .buycourses-confirm-actions .form-group,
        .buycourses-process-confirm .buycourses-confirm-actions .col-sm-12,
        .buycourses-process-confirm .buycourses-confirm-actions .col-md-12,
        .buycourses-process-confirm .buycourses-confirm-actions .col-xs-12 {
            margin: 0;
            padding: 0;
            width: auto;
            float: none;
        }

        .buycourses-process-confirm .buycourses-confirm-actions .btn,
        .buycourses-process-confirm .buycourses-confirm-actions button,
        .buycourses-process-confirm .buycourses-confirm-actions input[type="submit"],
        .buycourses-process-confirm .buycourses-confirm-actions input[type="button"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.95rem;
            padding: 0.85rem 1.35rem;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s ease-in-out;
            box-shadow: none;
            min-height: 46px;
        }

        .buycourses-process-confirm .buycourses-confirm-actions .btn:hover,
        .buycourses-process-confirm .buycourses-confirm-actions button:hover,
        .buycourses-process-confirm .buycourses-confirm-actions input[type="submit"]:hover,
        .buycourses-process-confirm .buycourses-confirm-actions input[type="button"]:hover {
            text-decoration: none;
            transform: translateY(-1px);
        }

        .buycourses-process-confirm .buycourses-confirm-actions .btn-success,
        .buycourses-process-confirm .buycourses-confirm-actions .btn-primary,
        .buycourses-process-confirm .buycourses-confirm-actions .btn--primary,
        .buycourses-process-confirm .buycourses-confirm-actions input.btn-success,
        .buycourses-process-confirm .buycourses-confirm-actions input.btn-primary {
            background: #2f7db3;
            border-color: #2f7db3;
            color: #ffffff !important;
        }

        .buycourses-process-confirm .buycourses-confirm-actions .btn-danger,
        .buycourses-process-confirm .buycourses-confirm-actions .btn-default,
        .buycourses-process-confirm .buycourses-confirm-actions .btn-secondary,
        .buycourses-process-confirm .buycourses-confirm-actions input.btn-danger,
        .buycourses-process-confirm .buycourses-confirm-actions input.btn-default {
            background: #ffffff;
            border-color: #d7e0e8;
            color: #27364b !important;
        }

        .buycourses-process-confirm textarea.form-control {
            width: 100%;
            min-height: 20rem;
            resize: vertical;
            border-radius: 1rem;
            border: 1px solid #d7e0e8;
            padding: 1rem;
            box-sizing: border-box;
            background: #ffffff;
        }

        .buycourses-process-confirm table {
            width: 100%;
        }

        .buycourses-process-confirm th,
        .buycourses-process-confirm td {
            padding: 0.9rem 1rem;
            vertical-align: top;
        }

        .buycourses-process-confirm thead th {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7a90;
        }

        .buycourses-process-confirm tbody tr + tr {
            border-top: 1px solid #e4e9ed;
        }
    </style>

    {% set has_course_description = buying_course and course.description is defined and course.description|striptags|trim is not empty %}
    {% set course_description_url = url('index') ~ 'plugin/BuyCourses/src/course_information.php?course_id=' ~ (course.id|default(0)) %}

    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    {{ 'BuyCourses'|get_plugin_lang('BuyCoursesPlugin') }}
                </span>

                <div>
                    <h1 class="text-3xl font-semibold text-gray-90">
                        {{ title }}
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        Review the order details and confirm the operation to continue with the selected payment method.
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-primary/15 bg-support-2 px-5 py-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    {{ 'Total'|get_lang }}
                </div>
                <div class="mt-1 text-2xl font-semibold text-gray-90">
                    {% if buying_course and course.item is defined and course.item.total_price_formatted is defined %}
                    {{ course.item.total_price_formatted }}
                    {% elseif buying_session and session.item is defined and session.item.total_price_formatted is defined %}
                    {{ session.item.total_price_formatted }}
                    {% elseif buying_service and service_item is defined and service_item.total_price_formatted is defined %}
                    {{ service_item.total_price_formatted }}
                    {% else %}
                    {{ price }}
                    {% endif %}
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 {% if terms %}xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]{% else %}xl:grid-cols-[minmax(0,1fr)_360px]{% endif %}">
        <section class="space-y-6">
            <article class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[320px_minmax(0,1fr)]">
                    <div class="bg-support-2 p-5">
                        {% if buying_course %}
                        {% if has_course_description %}
                        <a class="ajax block" href="{{ course_description_url }}" data-title="{{ course.title }}">
                            <img
                                    alt="{{ course.title }}"
                                    class="h-80 w-full object-cover rounded-3xl"
                                    src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}"
                            >
                        </a>
                        {% else %}
                        <img
                                alt="{{ course.title }}"
                                class="h-80 w-full object-cover rounded-3xl"
                                src="{{ course.course_img ? course.course_img : 'session_default.png'|icon() }}"
                        >
                        {% endif %}
                        {% elseif buying_session %}
                        <img
                                alt="{{ session.title }}"
                                class="h-80 w-full object-cover rounded-3xl"
                                src="{{ session.image ? session.image : 'session_default.png'|icon() }}"
                        >
                        {% elseif buying_service %}
                        <a href="{{ url('index') ~ 'service/' ~ service.id }}">
                            <img
                                    alt="{{ service.name }}"
                                    class="h-80 w-full object-cover rounded-3xl"
                                    src="{{ service.image ? service.image : 'session_default.png'|icon }}"
                            >
                        </a>
                        {% endif %}
                    </div>

                    <div class="space-y-5 p-6">
                        {% if buying_course %}
                        <div class="space-y-3">
                            <h2 class="text-2xl font-semibold text-gray-90">
                                {% if has_course_description %}
                                <a class="ajax transition hover:text-primary" href="{{ course_description_url }}" data-title="{{ course.title }}">
                                    {{ course.title }}
                                </a>
                                {% else %}
                                {{ course.title }}
                                {% endif %}
                            </h2>

                            {% if course.teachers %}
                            <ul class="space-y-2">
                                {% for teacher in course.teachers %}
                                <li class="flex items-center gap-2 text-sm text-gray-90">
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-support-2 text-primary">
                                                    <em class="fa fa-user" aria-hidden="true"></em>
                                                </span>
                                    <span>{{ teacher.name }}</span>
                                </li>
                                {% endfor %}
                            </ul>
                            {% endif %}
                        </div>

                        {% if course.description %}
                        <div class="rounded-2xl border border-gray-25 bg-support-2 px-4 py-4 text-sm leading-6 text-gray-90">
                            {{ course.description }}
                        </div>
                        {% endif %}
                        {% elseif buying_session %}
                        <div class="space-y-3">
                            <h2 class="text-2xl font-semibold text-gray-90">
                                {{ session.title }}
                            </h2>

                            {% if session.dates is defined and session.dates.display %}
                            <p class="text-sm text-gray-50">
                                <em class="fa fa-calendar fa-fw text-primary"></em>
                                {{ session.dates.display }}
                            </p>
                            {% endif %}
                        </div>

                        {% if session.courses %}
                        <div class="space-y-3">
                            {% for itemCourse in session.courses %}
                            <div class="rounded-2xl border border-gray-25 bg-support-2 px-4 py-3">
                                <p class="font-semibold text-gray-90">
                                    {{ itemCourse.title }}
                                </p>

                                {% if itemCourse.coaches|length %}
                                <ul class="mt-2 space-y-1">
                                    {% for coach in itemCourse.coaches %}
                                    <li class="flex items-center gap-2 text-sm text-gray-50">
                                        <em class="fa fa-user text-primary" aria-hidden="true"></em>
                                        <span>{{ coach }}</span>
                                    </li>
                                    {% endfor %}
                                </ul>
                                {% endif %}
                            </div>
                            {% endfor %}
                        </div>
                        {% endif %}
                        {% elseif buying_service %}
                        <div class="space-y-3">
                            <h2 class="text-2xl font-semibold text-gray-90">
                                <a href="{{ url('index') ~ 'service/' ~ service.id }}" class="transition hover:text-primary">
                                    {{ service.name }}
                                </a>
                            </h2>

                            <ul class="space-y-2 text-sm text-gray-90">
                                {% if service.applies_to == 0 %}
                                <li><em class="fa fa-hand-o-right text-primary"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'None'|get_lang }}</li>
                                {% elseif service.applies_to == 1 %}
                                <li><em class="fa fa-hand-o-right text-primary"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'User'|get_lang }}</li>
                                {% elseif service.applies_to == 2 %}
                                <li><em class="fa fa-hand-o-right text-primary"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'Course'|get_lang }}</li>
                                {% elseif service.applies_to == 3 %}
                                <li><em class="fa fa-hand-o-right text-primary"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'Session'|get_lang }}</li>
                                {% elseif service.applies_to == 4 %}
                                <li><em class="fa fa-hand-o-right text-primary"></em> {{ 'AppliesTo'|get_plugin_lang('BuyCoursesPlugin') }} {{ 'TemplateTitleCertificate'|get_lang }}</li>
                                {% endif %}

                                <li>
                                    <em class="fa fa-money text-primary"></em>
                                    {{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}:
                                    {{ service_item.total_price_formatted }}
                                </li>

                                {% if service.owner is defined %}
                                <li>
                                    <em class="fa fa-user text-primary"></em>
                                    {{ service.owner.name }}
                                </li>
                                {% endif %}
                            </ul>

                            {% if service.description %}
                            <div class="rounded-2xl border border-gray-25 bg-support-2 px-4 py-4 text-sm leading-6 text-gray-90">
                                {{ service.description }}
                            </div>
                            {% endif %}
                        </div>
                        {% endif %}
                    </div>
                </div>
            </article>

            {% if terms %}
            <article class="rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-90">
                        {{ 'TermsAndConditions'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h3>
                </div>

                <div class="space-y-4 p-6">
                    <textarea class="form-control" readonly>{{ terms }}</textarea>

                    <label for="confirmTermsAndConditons" class="flex items-start gap-3 text-sm font-medium text-gray-90">
                        <input type="checkbox" id="confirmTermsAndConditons" name="confirmTermsAndConditons">
                        <span>{{ 'IConfirmIReadAndAcceptTermsAndCondition'|get_plugin_lang('BuyCoursesPlugin') }}</span>
                    </label>
                </div>
            </article>
            {% endif %}

            {% if is_bank_transfer %}
            <article class="rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="border-b border-gray-25 bg-gray-15 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-90">
                        {{ 'BankAccountInformation'|get_plugin_lang('BuyCoursesPlugin') }}
                    </h3>
                </div>

                <div class="space-y-4 p-6">
                    <div class="overflow-hidden rounded-2xl border border-gray-25">
                        <table>
                            <thead class="bg-support-2">
                            <tr>
                                <th>{{ 'Name'|get_lang }}</th>
                                <th class="text-center">{{ 'BankAccount'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                                <th class="text-center">{{ 'SWIFT'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white">
                            {% for account in transfer_accounts %}
                            <tr>
                                <td>{{ account.name }}</td>
                                <td class="text-center">{{ account.account }}</td>
                                <td class="text-center">{{ account.swift }}</td>
                            </tr>
                            {% endfor %}
                            </tbody>
                        </table>
                    </div>

                    <p class="text-sm leading-6 text-gray-50">
                        {{ 'OnceItIsConfirmedYouWillReceiveAnEmailWithTheBankInformationAndAnOrderReference'|get_plugin_lang('BuyCoursesPlugin') }}
                    </p>
                </div>
            </article>
            {% endif %}
        </section>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-90">
                    {{ 'BuyerInformation'|get_lang }}
                </h3>

                <dl class="mt-5 grid gap-4">
                    <div class="rounded-2xl bg-support-2 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Name'|get_lang }}
                        </dt>
                        <dd class="mt-1 text-sm font-medium text-gray-90">
                            {{ user.complete_name }}
                        </dd>
                    </div>

                    <div class="rounded-2xl bg-support-2 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'Username'|get_lang }}
                        </dt>
                        <dd class="mt-1 text-sm font-medium text-gray-90">
                            {{ user.username }}
                        </dd>
                    </div>

                    <div class="rounded-2xl bg-support-2 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                            {{ 'EmailAddress'|get_lang }}
                        </dt>
                        <dd class="mt-1 break-all text-sm font-medium text-gray-90">
                            {{ user.email }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-90">
                    {{ 'ConfirmOrder'|get_plugin_lang('BuyCoursesPlugin') }}
                </h3>
                <p class="mt-2 text-sm leading-6 text-gray-50">
                    Confirm the order to continue with the selected payment flow.
                </p>

                <div class="mt-5 rounded-2xl border border-primary/15 bg-support-2 px-4 py-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Total'|get_lang }}
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-gray-90">
                        {% if buying_course and course.item is defined and course.item.total_price_formatted is defined %}
                        {{ course.item.total_price_formatted }}
                        {% elseif buying_session and session.item is defined and session.item.total_price_formatted is defined %}
                        {{ session.item.total_price_formatted }}
                        {% elseif buying_service and service_item is defined and service_item.total_price_formatted is defined %}
                        {{ service_item.total_price_formatted }}
                        {% else %}
                        {{ price }}
                        {% endif %}
                    </div>
                </div>

                <div class="buycourses-confirm-actions mt-6">
                    {{ form }}
                </div>
            </section>
        </aside>
    </div>

    <div id="message-alert"></div>
</div>

<script>
  $(function () {
      {% if terms %}
    $("#confirm").prop("disabled", true);

    $("#confirmTermsAndConditons").on("click", function () {
      $("#confirm").prop("disabled", !$("#confirmTermsAndConditons").is(":checked"));
    });
      {% endif %}

      {% if is_culqi_payment %}
    var price = {{ price }} * 100;

  Culqi.codigoComercio = '{{ culqi_params.commerce_code }}';
  Culqi.configurar({
    nombre: '{{ _s.institution }}',
    orden: '{{ sale.reference ? sale.reference : buying_service.reference }}',
    moneda: '{{ currency.iso_code }}',
    descripcion: '{{ title }}',
    monto: price
  });

  $("#confirm").click(function (e) {
    Culqi.abrir();
    e.preventDefault();

    $(".culqi_checkout").watch("style", function () {
      if (Culqi.error) {
        $("#message-alert").html('<div class="alert alert-danger">{{ 'ErrorOccurred'|get_plugin_lang('BuyCoursesPlugin')|format("'+Culqi.error.codigo+'", "'+Culqi.error.mensaje+'") }}</div>');
      } else if (Culqi.token) {
          {% if buying_service %}
        var url = '{{ url('index') }}plugin/BuyCourses/src/buycourses.ajax.php?a=culqi_cargo_service&token_id=' + Culqi.token.id + '&service_sale_id=' + {{ buying_service.id }};
        {% else %}
      var url = '{{ url('index') }}plugin/BuyCourses/src/buycourses.ajax.php?a=culqi_cargo&token_id=' + Culqi.token.id + '&sale_id=' + {{ sale.id }};
      {% endif %}

    $.ajax({
      type: "POST",
      url: url,
      beforeSend: function () {
        $("#confirm").html('<em class="fa fa-spinner fa-pulse fa-fw"></em> {{ 'Loading'|get_lang }}');
        $("#confirm").prop("disabled", true);
        $("#cancel").prop("disabled", true);
      },
      success: function () {
        window.location = "{{ url('index') }}plugin/BuyCourses/index.php";
      }
    });
  }

  $(".culqi_checkout").unwatch("style");
  });

  return false;
  });

  $.fn.watch = function (property, callback) {
    return $(this).each(function () {
      var oldPropertyValue = this[property];
      var timer;

      function watch() {
        var self = $(".culqi_checkout");
        self = self[0];

        if ($(self).data(property + "-watch-abort") === true) {
          timer = clearInterval(timer);
          $(self).data(property + "-watch-abort", null);
          return;
        }

        if (self[property] !== oldPropertyValue) {
          oldPropertyValue = self[property];
          callback.call(self);
        }
      }

      timer = setInterval(watch, 700);
    });
  };

  $.fn.unwatch = function (property) {
    return $(this).each(function () {
      $(this).data(property + "-watch-abort", true);
    });
  };
  {% endif %}
  });
</script>
{% endautoescape %}
