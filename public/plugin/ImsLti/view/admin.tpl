{% autoescape 'html' %}
{% set imsLtiDescription = 'ImsLtiDescription'|get_plugin_lang('ImsLtiPlugin') %}

<div class="space-y-4">
    <div class="card p-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <h2 class="text-h4 mb-2">{{ 'IMS/LTI client'|get_plugin_lang('ImsLtiPlugin') }}</h2>
                <div class="text-body-2 text-gray-90 leading-relaxed">
                    {{ imsLtiDescription|raw }}
                </div>
            </div>

            <div class="flex flex-wrap gap-2 lg:justify-end lg:self-start">
                <a
                        href="{{ url('index') }}plugin/ImsLti/platform.php"
                        class="btn btn--plain"
                >
                    <i class="mdi mdi-key-variant"></i>
                    {{ 'PlatformKeys'|get_plugin_lang('ImsLtiPlugin') }}
                </a>

                <a
                        href="{{ url('index') }}plugin/ImsLti/create.php"
                        class="btn btn--primary"
                >
                    <i class="mdi mdi-plus"></i>
                    {{ 'AddExternalTool'|get_plugin_lang('ImsLtiPlugin') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-h5 mb-1">{{ 'External tools'|get_plugin_lang('ImsLtiPlugin') }}</h3>
                <p class="text-body-2 text-gray-50 mb-0">
                    {{ tools|length }} {{ tools|length == 1 ? 'tool' : 'tools' }}
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover admin-tools">
                <thead>
                <tr>
                    <th>{{ 'Name'|get_lang }}</th>
                    <th class="w-24">{{ 'ID'|get_lang }}</th>
                    <th>{{ 'ClientId'|get_plugin_lang('ImsLtiPlugin') }}</th>
                    <th>{{ 'LaunchUrl'|get_plugin_lang('ImsLtiPlugin') }}</th>
                    <th class="text-right w-72">{{ 'Actions'|get_lang }}</th>
                </tr>
                </thead>
                <tbody>
                {% if tools|length > 0 %}
                {% for tool in tools %}
                <tr>
                    <td>
                        <div class="flex flex-col gap-1">
                            <span class="font-semibold">{{ tool.title }}</span>

                            {% if tool.version %}
                            <span class="badge badge--secondary inline-flex w-fit">
                                            {{ tool.version|upper }}
                                        </span>
                            {% endif %}

                            {% if not tool.is_ready_for_courses %}
                            <div class="text-body-2 text-danger mt-1">
                                {{ tool.incomplete_message }}
                            </div>
                            {% endif %}
                        </div>
                    </td>
                    <td>{{ tool.id }}</td>
                    <td>
                        <code class="whitespace-normal break-all">
                            {{ tool.client_id ?: '—' }}
                        </code>
                    </td>
                    <td>
                        <code class="whitespace-normal break-all">
                            {{ tool.launch_url ?: '—' }}
                        </code>
                    </td>
                    <td class="text-right">
                        <div class="flex flex-wrap justify-end gap-2">
                            <a
                                    href="{{ url('index') }}plugin/ImsLti/tool_settings.php?id={{ tool.id }}"
                                    class="btn btn--plain js-imslti-open-modal"
                                    data-title="{{ 'ConfigSettingsForTool'|get_plugin_lang('ImsLtiPlugin') }}"
                                    title="{{ 'ConfigSettingsForTool'|get_plugin_lang('ImsLtiPlugin') }}"
                            >
                                <i class="mdi mdi-cog-outline"></i>
                                {{ 'ToolSettings'|get_plugin_lang('ImsLtiPlugin') }}
                            </a>

                            {% if tool.is_ready_for_courses %}
                            <a
                                    href="{{ url('index') }}plugin/ImsLti/multiply.php?id={{ tool.id }}"
                                    class="btn btn--plain js-imslti-open-modal"
                                    data-title="{{ 'AddInCourses'|get_plugin_lang('ImsLtiPlugin') }}"
                                    data-ajax="1"
                                    title="{{ 'AddInCourses'|get_plugin_lang('ImsLtiPlugin') }}"
                            >
                                <i class="mdi mdi-book-plus-outline"></i>
                                {{ 'AddInCourses'|get_plugin_lang('ImsLtiPlugin') }}
                            </a>
                            {% else %}
                            <button
                                    type="button"
                                    class="btn btn--plain opacity-50 cursor-not-allowed"
                                    disabled="disabled"
                                    title="{{ tool.incomplete_message }}"
                            >
                                <i class="mdi mdi-book-plus-outline"></i>
                                {{ 'AddInCourses'|get_plugin_lang('ImsLtiPlugin') }}
                            </button>
                            {% endif %}

                            <a
                                    href="{{ url('index') }}plugin/ImsLti/edit.php?id={{ tool.id }}"
                                    class="btn btn--plain"
                                    title="{{ 'Edit'|get_lang }}"
                            >
                                <i class="mdi mdi-pencil-outline"></i>
                                {{ 'Edit'|get_lang }}
                            </a>

                            <a
                                    href="{{ url('index') }}plugin/ImsLti/delete.php?id={{ tool.id }}"
                                    class="btn btn--danger"
                                    title="{{ 'Delete'|get_lang }}"
                                    onclick="return confirm('{{ 'Please confirm your choice'|get_lang|e('js') }}');"
                            >
                                <i class="mdi mdi-trash-can-outline"></i>
                                {{ 'Delete'|get_lang }}
                            </a>
                        </div>
                    </td>
                </tr>
                {% endfor %}
                {% else %}
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div class="text-body-2 text-gray-50">
                            {{ 'NoData'|get_lang }}
                        </div>
                    </td>
                </tr>
                {% endif %}
                </tbody>
            </table>
        </div>
    </div>
</div>

<div
        id="imslti-admin-modal"
        class="hidden fixed inset-0 z-50"
        aria-hidden="true"
>
    <div class="absolute inset-0 bg-black/40 imslti-modal__backdrop"></div>

    <div class="relative flex min-h-screen items-start justify-center p-4 md:p-6">
        <div
                class="relative flex w-full max-w-4xl flex-col overflow-visible rounded-xl bg-white shadow-xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="imslti-admin-modal-title"
        >
            <div class="flex items-center justify-between gap-3 border-b border-gray-20 px-5 py-4">
                <h4 id="imslti-admin-modal-title" class="text-h5 mb-0">
                    {{ 'ToolSettings'|get_plugin_lang('ImsLtiPlugin') }}
                </h4>

                <button
                        type="button"
                        class="js-imslti-close-modal inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-50 transition hover:bg-gray-10 hover:text-gray-90"
                        aria-label="{{ 'Close'|get_lang }}"
                        title="{{ 'Close'|get_lang }}"
                >
                    <i class="mdi mdi-close"></i>
                </button>
            </div>

            <div class="imslti-modal__body relative overflow-y-auto overflow-x-visible p-5 max-h-[calc(100vh-8rem)]"></div>
        </div>
    </div>
</div>
{% endautoescape %}

{% autoescape false %}
<script>
  $(function () {
    var $modal = $('#imslti-admin-modal');
    var $modalBody = $modal.find('.imslti-modal__body');
    var $modalTitle = $('#imslti-admin-modal-title');
    var adminUrl = '{{ url('index') }}plugin/ImsLti/admin.php';

    function openModal(title) {
      $modalTitle.text(title || '{{ 'ToolSettings'|get_plugin_lang('ImsLtiPlugin')|e('js') }}');
      $modal.removeClass('hidden').attr('aria-hidden', 'false');
      $('body').addClass('overflow-hidden');
    }

    function closeModal() {
      $modal.addClass('hidden').attr('aria-hidden', 'true');
      $modalBody.empty();
      $('body').removeClass('overflow-hidden');
    }

    function showLoading(title) {
      openModal(title);
      $modalBody.html(
        '<div class="flex flex-col items-center justify-center gap-3 py-8 text-gray-50">' +
        '<i class="mdi mdi-loading mdi-spin text-3xl"></i>' +
        '<div>{{ 'Loading'|get_lang|e('js') }}…</div>' +
        '</div>'
      );
    }

    function showError(message) {
      $modalBody.html('<div class="alert alert-danger mb-0">' + message + '</div>');
    }

    function fixAsyncDropdowns() {
      window.setTimeout(function () {
        $('.select2-container').css('z-index', 100002);
        $('.select2-container--open').css('z-index', 100003);
        $('.select2-dropdown').css('z-index', 100004);
        $('.ui-autocomplete').css('z-index', 100004);
        $('.autocomplete-suggestions').css('z-index', 100004);
        $('.search_course_results').css('z-index', 100004);
        $('.select_ajax_results').css('z-index', 100004);
      }, 0);
    }

    function enhanceMultiplyFormLayout($form) {
      $form.find('label').addClass('mb-2 inline-block');
      $form.find('.form_required').addClass('mb-2 inline-block');
      $form.find('.select2-container').addClass('mt-2');
      $form.find('select[name="courses[]"], select[name="courses"]').addClass('mt-2');
    }

    function applyAssignedCourses($form) {
      var $holder = $form.closest('.space-y-4').find('.js-imslti-assigned-courses').first();
      if (!$holder.length) {
        return;
      }

      var raw = $holder.attr('data-assigned') || '[]';
      var assigned = [];

      try {
        assigned = JSON.parse(raw);
      } catch (e) {
        assigned = [];
      }

      if (!assigned.length) {
        return;
      }

      var $select = $form.find('select[name="courses[]"], select[name="courses"]').first();
      if (!$select.length) {
        return;
      }

      assigned.forEach(function (item) {
        if (!item || !item.id) {
          return;
        }

        var value = String(item.id);
        var text = item.text || value;
        var $existingOption = $select.find('option[value="' + value.replace(/"/g, '\\"') + '"]');

        if (!$existingOption.length) {
          var option = new Option(text, value, true, true);
          $select.append(option);
        } else {
          $existingOption.prop('selected', true);
        }
      });

      $select.trigger('change');
    }

    function bindMultiplyForm() {
      var $form = $modalBody.find('form#frm_multiply');

      if (!$form.length) {
        return;
      }

      enhanceMultiplyFormLayout($form);
      applyAssignedCourses($form);
      fixAsyncDropdowns();

      $(document)
        .off('select2:open.imsltiFix')
        .on('select2:open.imsltiFix', function () {
          fixAsyncDropdowns();
        });

      $form.off('submit.imsltiMultiply').on('submit.imsltiMultiply', function (e) {
        e.preventDefault();

        var form = this;
        var formData = new FormData(form);
        formData.set('ajax', '1');

        var $submitButtons = $form.find('button[type="submit"], input[type="submit"]');
        $submitButtons.prop('disabled', true);

        $.ajax({
          url: form.action,
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          success: function (response, textStatus, xhr) {
            var contentType = xhr.getResponseHeader('Content-Type') || '';
            var payload = response;

            if (contentType.indexOf('application/json') !== -1) {
              if (typeof payload === 'string') {
                try {
                  payload = JSON.parse(payload);
                } catch (e) {
                  payload = {success: false, message: 'Invalid server response.'};
                }
              }

              if (payload.success) {
                closeModal();
                window.location.href = adminUrl;
                return;
              }

              showError(payload.message || 'Unexpected server response.');
              return;
            }

            $modalBody.html(response);
            bindMultiplyForm();
          },
          error: function (xhr) {
            var responseText = xhr.responseText || 'Unable to submit the form.';
            showError(responseText);
          },
          complete: function () {
            $submitButtons.prop('disabled', false);
          }
        });
      });
    }

    $(document).off('click.imsltiOpenModal').on('click.imsltiOpenModal', '.js-imslti-open-modal', function (e) {
      e.preventDefault();

      var $link = $(this);
      var title = $link.data('title') || $link.attr('title') || '{{ 'ToolSettings'|get_plugin_lang('ImsLtiPlugin')|e('js') }}';
      var url = $link.attr('href');
      var requiresAjax = String($link.data('ajax') || '') === '1';

      if (!url) {
        return;
      }

      if (requiresAjax) {
        url += (url.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';
      }

      showLoading(title);

      $.ajax({
        url: url,
        type: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
          $modalBody.html(html);
          bindMultiplyForm();
        },
        error: function (xhr) {
          var responseText = xhr.responseText || 'Unable to load the content.';
          showError(responseText);
        }
      });
    });

    $(document).off('click.imsltiCloseModal').on('click.imsltiCloseModal', '.js-imslti-close-modal', function (e) {
      e.preventDefault();
      closeModal();
    });

    $modal.find('.imslti-modal__backdrop').off('click.imsltiBackdrop').on('click.imsltiBackdrop', function () {
      closeModal();
    });

    $(document).off('keydown.imsltiModal').on('keydown.imsltiModal', function (e) {
      if (e.key === 'Escape' && !$modal.hasClass('hidden')) {
        closeModal();
      }
    });
  });
</script>
{% endautoescape %}
