{% autoescape 'html' %}
{% set pluginBaseUrl = url('index') ~ 'plugin/ImsLti/' %}

<div>
    <div class="mx-auto w-full max-w-7xl px-4 py-8 lg:px-6">
        <div class="mb-8 overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-xl">
            <div class="flex flex-col gap-6 border-b border-gray-25 bg-support-2 px-6 py-6 lg:flex-row lg:items-start lg:justify-between lg:px-8">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold text-gray-90">{{ page_title }}</h1>
                    <div class="mt-3 max-w-4xl text-body-2 leading-relaxed text-gray-90">
                        {{ page_description|raw }}
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-3 lg:justify-end">
                    <a
                        href="{{ pluginBaseUrl }}platform.php"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary"
                    >
                        <i class="mdi mdi-key-variant" aria-hidden="true"></i>
                        <span>{{ 'PlatformKeys'|get_plugin_lang('ImsLtiPlugin') }}</span>
                    </a>

                    <a
                        href="{{ pluginBaseUrl }}create.php"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-90"
                    >
                        <i class="mdi mdi-plus" aria-hidden="true"></i>
                        <span>{{ 'AddExternalTool'|get_plugin_lang('ImsLtiPlugin') }}</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 px-6 py-5 lg:grid-cols-3 lg:px-8">
                <div class="rounded-2xl border border-gray-25 bg-gray-10 p-4">
                    <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'External tools'|get_plugin_lang('ImsLtiPlugin') }}
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-gray-90">{{ tools|length }}</div>
                </div>

                <div class="rounded-2xl border border-gray-25 bg-gray-10 p-4">
                    <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Ready for courses'|trans }}
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-gray-90">
                        {{ tools|filter(tool => tool.is_ready_for_courses)|length }}
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-25 bg-gray-10 p-4">
                    <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                        {{ 'Incomplete'|trans }}
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-gray-90">
                        {{ tools|filter(tool => not tool.is_ready_for_courses)|length }}
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-xl">
            <div class="border-b border-gray-25 bg-support-2 px-6 py-4 lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-body-1 font-semibold text-gray-90">
                            {{ 'External tools'|get_plugin_lang('ImsLtiPlugin') }}
                        </h2>
                        <p class="mt-1 text-body-2 text-gray-50">
                            {{ tools|length }} {{ tools|length == 1 ? 'tool' : 'tools' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="hidden grid-cols-[minmax(0,1.2fr)_6rem_minmax(0,1fr)_minmax(0,1.4fr)_22rem] gap-4 border-b border-gray-25 bg-gray-10 px-6 py-4 lg:grid lg:px-8">
                <div class="text-body-2 font-semibold text-gray-90">{{ 'Name'|get_lang }}</div>
                <div class="text-body-2 font-semibold text-gray-90">{{ 'ID'|get_lang }}</div>
                <div class="text-body-2 font-semibold text-gray-90">{{ 'ClientId'|get_plugin_lang('ImsLtiPlugin') }}</div>
                <div class="text-body-2 font-semibold text-gray-90">{{ 'LaunchUrl'|get_plugin_lang('ImsLtiPlugin') }}</div>
                <div class="text-right text-body-2 font-semibold text-gray-90">{{ 'Actions'|get_lang }}</div>
            </div>

            <div class="divide-y divide-gray-20">
                {% if tools|length > 0 %}
                    {% for tool in tools %}
                        <div class="grid grid-cols-1 gap-5 px-6 py-5 lg:grid-cols-[minmax(0,1.2fr)_6rem_minmax(0,1fr)_minmax(0,1.4fr)_22rem] lg:items-start lg:px-8">
                            <div class="min-w-0">
                                <div class="text-caption font-semibold uppercase tracking-wide text-gray-50 lg:hidden">
                                    {{ 'Name'|get_lang }}
                                </div>

                                <div class="flex flex-col gap-2">
                                    <span class="break-words text-body-2 font-semibold text-gray-90">
                                        {{ tool.title }}
                                    </span>

                                    {% if tool.version %}
                                        <span class="inline-flex w-fit items-center rounded-full bg-support-1 px-3 py-1 text-caption font-semibold text-primary">
                                            {{ tool.version|upper }}
                                        </span>
                                    {% endif %}

                                    {% if not tool.is_ready_for_courses %}
                                        <div class="rounded-xl border border-danger bg-white p-3 text-body-2 text-danger">
                                            {{ tool.incomplete_message }}
                                        </div>
                                    {% endif %}
                                </div>
                            </div>

                            <div>
                                <div class="text-caption font-semibold uppercase tracking-wide text-gray-50 lg:hidden">
                                    {{ 'ID'|get_lang }}
                                </div>
                                <div class="text-body-2 text-gray-90">{{ tool.id }}</div>
                            </div>

                            <div class="min-w-0">
                                <div class="text-caption font-semibold uppercase tracking-wide text-gray-50 lg:hidden">
                                    {{ 'ClientId'|get_plugin_lang('ImsLtiPlugin') }}
                                </div>
                                <code class="block break-all rounded-xl border border-gray-25 bg-gray-10 px-3 py-2 text-caption text-gray-90">
                                    {{ tool.client_id ?: '—' }}
                                </code>
                            </div>

                            <div class="min-w-0">
                                <div class="text-caption font-semibold uppercase tracking-wide text-gray-50 lg:hidden">
                                    {{ 'LaunchUrl'|get_plugin_lang('ImsLtiPlugin') }}
                                </div>
                                <code class="block break-all rounded-xl border border-gray-25 bg-gray-10 px-3 py-2 text-caption text-gray-90">
                                    {{ tool.launch_url ?: '—' }}
                                </code>
                            </div>

                            <div>
                                <div class="text-caption font-semibold uppercase tracking-wide text-gray-50 lg:hidden">
                                    {{ 'Actions'|get_lang }}
                                </div>

                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    <a
                                        href="{{ pluginBaseUrl }}tool_settings.php?id={{ tool.id }}"
                                        class="js-imslti-open-modal inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary"
                                        data-title="{{ 'ConfigSettingsForTool'|get_plugin_lang('ImsLtiPlugin') }}"
                                        title="{{ 'ConfigSettingsForTool'|get_plugin_lang('ImsLtiPlugin') }}"
                                    >
                                        <i class="mdi mdi-cog-outline" aria-hidden="true"></i>
                                        <span>{{ 'ToolSettings'|get_plugin_lang('ImsLtiPlugin') }}</span>
                                    </a>

                                    {% if tool.is_ready_for_courses %}
                                        <a
                                            href="{{ pluginBaseUrl }}multiply.php?id={{ tool.id }}"
                                            class="js-imslti-open-modal inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary"
                                            data-title="{{ 'AddInCourses'|get_plugin_lang('ImsLtiPlugin') }}"
                                            data-ajax="1"
                                            title="{{ 'AddInCourses'|get_plugin_lang('ImsLtiPlugin') }}"
                                        >
                                            <i class="mdi mdi-book-plus-outline" aria-hidden="true"></i>
                                            <span>{{ 'AddInCourses'|get_plugin_lang('ImsLtiPlugin') }}</span>
                                        </a>
                                    {% else %}
                                        <button
                                            type="button"
                                            class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl border border-gray-25 bg-gray-10 px-4 py-2 text-body-2 font-semibold text-gray-50 opacity-70"
                                            disabled="disabled"
                                            title="{{ tool.incomplete_message }}"
                                        >
                                            <i class="mdi mdi-book-plus-outline" aria-hidden="true"></i>
                                            <span>{{ 'AddInCourses'|get_plugin_lang('ImsLtiPlugin') }}</span>
                                        </button>
                                    {% endif %}

                                    <a
                                        href="{{ pluginBaseUrl }}edit.php?id={{ tool.id }}"
                                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-90"
                                        title="{{ 'Edit'|get_lang }}"
                                    >
                                        <i class="mdi mdi-pencil-outline" aria-hidden="true"></i>
                                        <span>{{ 'Edit'|get_lang }}</span>
                                    </a>

                                    <a
                                        href="{{ pluginBaseUrl }}delete.php?id={{ tool.id }}"
                                        class="inline-flex items-center gap-2 rounded-xl bg-danger px-4 py-2 text-body-2 font-semibold text-danger-button-text shadow-sm transition hover:opacity-90"
                                        title="{{ 'Delete'|get_lang }}"
                                        onclick="return confirm('{{ 'Please confirm your choice'|get_lang|e('js') }}');"
                                    >
                                        <i class="mdi mdi-trash-can-outline" aria-hidden="true"></i>
                                        <span>{{ 'Delete'|get_lang }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    {% endfor %}
                {% else %}
                    <div class="px-6 py-10 text-center lg:px-8">
                        <div class="mx-auto flex max-w-md flex-col items-center gap-3">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-support-1 text-primary">
                                <i class="mdi mdi-puzzle-outline text-2xl" aria-hidden="true"></i>
                            </div>
                            <div class="text-body-1 font-semibold text-gray-90">
                                {{ 'NoData'|get_lang }}
                            </div>
                            <div class="text-body-2 text-gray-50">
                                {{ 'Create your first external tool to start using the IMS/LTI client plugin.'|trans }}
                            </div>
                            <a
                                href="{{ pluginBaseUrl }}create.php"
                                class="mt-2 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-90"
                            >
                                <i class="mdi mdi-plus" aria-hidden="true"></i>
                                <span>{{ 'AddExternalTool'|get_plugin_lang('ImsLtiPlugin') }}</span>
                            </a>
                        </div>
                    </div>
                {% endif %}
            </div>
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
            class="relative flex w-full max-w-4xl flex-col overflow-visible rounded-2xl bg-white shadow-xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="imslti-admin-modal-title"
        >
            <div class="flex items-center justify-between gap-3 border-b border-gray-25 px-5 py-4">
                <h4 id="imslti-admin-modal-title" class="mb-0 text-body-1 font-semibold text-gray-90">
                    {{ 'ToolSettings'|get_plugin_lang('ImsLtiPlugin') }}
                </h4>

                <button
                    type="button"
                    class="js-imslti-close-modal inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-50 transition hover:bg-gray-10 hover:text-gray-90"
                    aria-label="{{ 'Close'|get_lang }}"
                    title="{{ 'Close'|get_lang }}"
                >
                    <i class="mdi mdi-close"></i>
                </button>
            </div>

            <div class="imslti-modal__body relative max-h-[calc(100vh-8rem)] overflow-y-auto overflow-x-visible p-5"></div>
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
    var adminUrl = '{{ pluginBaseUrl }}admin.php';

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
        $modalBody.html(
            '<div class="rounded-xl border border-danger bg-white p-4 text-body-2 text-danger">' +
            message +
            '</div>'
        );
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

    function normalizeLegacyForm($scope) {
        $scope.find('form').addClass('space-y-6 w-full');
        $scope.find('.row, .form-group, .field, .p-field').addClass('mb-6 w-full max-w-none').removeAttr('style');

        $scope.find('.col-sm-2, .col-sm-3, .col-sm-4, .col-sm-6, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-12, .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col-md-8, .col-md-9, .col-md-10, .col-md-12')
            .addClass('w-full max-w-none px-0')
            .removeAttr('style');

        $scope.find('label, .control-label').each(function () {
            $(this)
                .addClass('mb-2 block w-full text-body-2 font-semibold text-gray-90')
                .css({
                    position: 'static',
                    transform: 'none',
                    background: 'transparent',
                    padding: '0',
                    marginBottom: '0.5rem',
                });
        });

        $scope.find('.p-float-label').each(function () {
            var $wrapper = $(this);
            var $label = $wrapper.find('> label');
            var $controls = $wrapper.find('> input, > textarea, > select, > .p-inputtext');

            $wrapper
                .removeClass('p-float-label')
                .addClass('flex w-full max-w-3xl flex-col gap-2')
                .removeAttr('style');

            $label
                .addClass('block w-full text-body-2 font-semibold text-gray-90')
                .css({
                    position: 'static',
                    top: 'auto',
                    left: 'auto',
                    right: 'auto',
                    bottom: 'auto',
                    transform: 'none',
                    background: 'transparent',
                    padding: '0',
                    margin: '0',
                    zIndex: 'auto',
                });

            $controls.addClass(
                'mt-0 block w-full rounded-xl border border-gray-25 bg-white px-4 py-3 text-body-2 text-gray-90 shadow-sm placeholder-gray-50 focus:border-primary focus:ring-2 focus:ring-primary'
            );
        });

        $scope.find('input[type="text"], input[type="url"], input[type="password"], input[type="email"], input[type="number"], textarea, select, .form-control, .p-inputtext')
            .addClass('mt-0 block w-full max-w-3xl rounded-xl border border-gray-25 bg-white px-4 py-3 text-body-2 text-gray-90 shadow-sm placeholder-gray-50 focus:border-primary focus:ring-2 focus:ring-primary');

        $scope.find('textarea').addClass('min-h-[120px]');

        $scope.find('input[type="radio"], input[type="checkbox"]')
            .addClass('h-4 w-4 rounded border-gray-25 text-primary focus:ring-primary');

        $scope.find('.radio, .checkbox').addClass('mb-3 flex items-start gap-3').removeAttr('style');

        $scope.find('.help-block, .form-text, small')
            .addClass('mt-2 block text-caption text-gray-50');

        $scope.find('.alert-info')
            .addClass('rounded-xl border border-support-3 bg-support-1 p-4 text-body-2 text-support-4');

        $scope.find('.alert-danger, .alert-error')
            .addClass('rounded-xl border border-danger bg-white p-4 text-body-2 text-danger');

        $scope.find('.btn-primary, .btn-success, button[type="submit"], input[type="submit"]')
            .addClass('inline-flex items-center gap-2 rounded-xl bg-success px-5 py-3 text-body-2 font-semibold text-success-button-text shadow-sm transition hover:opacity-90');

        $scope.find('.btn-danger')
            .addClass('inline-flex items-center gap-2 rounded-xl bg-danger px-4 py-2 text-body-2 font-semibold text-danger-button-text shadow-sm transition hover:opacity-90');

        $scope.find('.btn:not(.btn-primary):not(.btn-success):not(.btn-danger)')
            .addClass('inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary');

        $scope.find('.select2-container')
            .addClass('mt-2 w-full max-w-3xl');

        $scope.find('.select2-selection')
            .addClass('rounded-xl border border-gray-25 bg-white text-body-2 text-gray-90 shadow-sm');

        $scope.find('.select2-selection__rendered')
            .addClass('text-body-2 text-gray-90');

        $scope.find('.select2-selection__choice')
            .addClass('rounded-full bg-support-1 text-caption font-semibold text-primary');
    }

    function enhanceMultiplyFormLayout($form) {
        var $fieldWrapper = $form.find('select[name="courses[]"], select[name="courses"]').first().closest('.row, .form-group, .field, .p-field');
        var $submitButtons = $form.find('button[type="submit"], input[type="submit"], .btn[type="submit"], .btn[name="submit_save"], .btn');

        $form.find('label').removeClass('inline-block').addClass('mb-2 block text-body-2 font-semibold text-gray-90');
        $form.find('.form_required').removeClass('inline-block').addClass('mb-2 block text-danger');

        $form.find('.select2-container').addClass('mt-2 w-full max-w-3xl');
        $form.find('select[name="courses[]"], select[name="courses"]').addClass('mt-2 w-full max-w-3xl');

        if ($fieldWrapper.length) {
            var $label = $fieldWrapper.find('label').first();
            var $select = $fieldWrapper.find('select[name="courses[]"], select[name="courses"]').first();
            var $select2 = $fieldWrapper.find('.select2-container').first();

            $fieldWrapper.addClass('flex flex-col');

            if ($label.length) {
                $label.prependTo($fieldWrapper);
            }

            if ($select.length) {
                $select.insertAfter($label);
            }

            if ($select2.length) {
                $select2.insertAfter($label);
            }
        }

        $submitButtons.each(function () {
            var $button = $(this);

            $button
                .removeClass('btn-default btn-light btn-secondary btn--plain btn--default btn--light btn--secondary')
                .addClass('inline-flex items-center justify-center gap-2 rounded-xl bg-success px-5 py-3 text-body-2 font-semibold text-success-button-text shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success focus:ring-offset-2');

            if ($button.is('button') && $.trim($button.text()) === '') {
                $button.text('{{ 'Save'|get_lang|e('js') }}');
            }

            if ($button.is('input') && (!$button.val() || $.trim($button.val()) === '')) {
                $button.val('{{ 'Save'|get_lang|e('js') }}');
            }
        });

        $form.find('.form-actions, .button-toolbar, .toolbar, .btn-toolbar').addClass('mt-6 flex justify-end');
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
