<div class="question-result">
    <div class="panel panel-default">
        <div class="panel-body">
            {% if 'save_titles_as_html'|api_get_configuration_value %}
                {{ data.title | remove_xss }}
            {% else %}
                <h3>{{ data.title | remove_xss  }}</h3>
            {% endif %}

            <div class="row">
                <div class="col-md-3">
                    <div class="user-avatar">
                        <img src="{{ data.avatar }}">
                    </div>
                    <div class="user-info">
                        <strong>{{ data.name_url }}</strong>
                        <br />
                        {% if signature %}
                            <img src="{{ signature }}" />
                        {% endif %}
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="group-data">
                        <div class="list-data username">
                            <span class="item">{{ 'Username'|get_lang }}</span>
                            <i class="fa fa-fw fa-user" aria-hidden="true"></i> {{ data.username }}
                        </div>

                        {% if data.start_date %}
                            <div class="list-data start-date">
                                <span class="item">{{ 'StartDate'|get_lang }}</span>
                                <i class="fa fa-fw fa-calendar" aria-hidden="true"></i> {{ data.start_date }}
                            </div>
                        {% endif %}

                        {% if data.duration %}
                            <div class="list-data duration">
                                <span class="item">{{ 'Duration'|get_lang }}</span>
                                <i class="fa fa-fw fa-clock-o" aria-hidden="true"></i> {{ data.duration }}
                            </div>
                        {% endif %}

                        {% if data.ip %}
                            <div class="list-data ip">
                                <span class="item">{{ 'IP'|get_lang }}</span>
                                <i class="fa fa-fw fa-laptop" aria-hidden="true"></i> {{ data.ip }}
                            </div>
                        {% endif %}

                        {% if allow_signature %}
                            <div class="list-data">
                                <span class="item"></span>
                                <a id="sign" class="btn btn-primary" href="javascript:void(0)">
                                    <em class="fa fa-pencil"></em> {{ 'Sign'| get_plugin_lang('ExerciseSignaturePlugin') }}
                                </a>
                            </div>
                        {% endif %}

                        {% if allow_export_pdf %}
                            <br />
                            <div class="list-data">
                                <span class="item"></span>
                                <a href="{{ export_url }}" class="btn btn-default">
                                    <img src="{{ 'export_pdf.png'|icon(32) }}" /> {{ 'ExportResponseReport'| get_lang }}
                                </a>
                            </div>
                        {% endif %}
                    </div>
                    <hr />
                    <div id="quiz_saved_answers_container">
                    {% if data.number_of_answers_saved != data.number_of_answers %}
                        <span class="label label-warning">
                            <strong>{{ 'XAnswersSavedByUsersFromXTotal'|get_lang|format(data.number_of_answers_saved, data.number_of_answers) }}</strong>
                        </span>
                    {% else %}
                        <span class="label label-success">
                            <strong>{{ 'XAnswersSavedByUsersFromXTotal'|get_lang|format(data.number_of_answers_saved, data.number_of_answers) }}</strong>
                        </span>
                    {% endif %}

                        {% if 'quiz_confirm_saved_answers'|api_get_configuration_value %}
                            {% set enable_form = data.track_confirmation.updatedAt is empty and data.track_confirmation.userId == _u.id %}
                            <form class="form-horizontal" action="#" id="quiz_confirm_saved_answers_form">
                                <div class="form-group">
                                    <div class="col-sm-12">
                                        <div class="checkbox">
                                            <label>
                                                <input
                                                    type="checkbox"
                                                    name="quiz_confirm_saved_answers_check"
                                                    {% if not enable_form %}disabled{% endif %} {% if data.track_confirmation.confirmed %}checked{% endif %}
                                                >
                                                {{ 'QuizConfirmSavedAnswers'|get_lang }}
                                            </label>
                                        </div>
                                        {% if enable_form %}
                                            <span class="help-block">{{ 'QuizConfirmSavedAnswersHelp'|get_lang }}</span>
                                        {% endif %}
                                    </div>
                                </div>
                                {% if enable_form %}
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <input type="hidden" name="tc_id" value="{{ data.track_confirmation.id }}">
                                            <button type="submit" class="btn btn-primary" disabled>
                                                <span class="fa fa-save fa-fw" aria-hidden="true"></span> {{ 'Save'|get_lang }}
                                            </button>
                                        </div>
                                    </div>
                                {% endif %}
                            </form>
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{% if 'quiz_confirm_saved_answers'|api_get_configuration_value %}
    {% set enable_form = data.track_confirmation.updatedAt is empty and data.track_confirmation.userId == _u.id %}
    {% if enable_form %}
        <script>
            $(function () {
                var form = $('#quiz_confirm_saved_answers_form');
                var checkbox = form.find('[type="checkbox"]');
                var button = form.find(':submit');

                checkbox.on('change', function () {
                    button.prop('disabled', !this.checked);
                });

                form.on('submit', function (e) {
                    e.preventDefault();

                    if (!checkbox.is(':checked')) {
                        return;
                    }

                    var xhrData = form.serialize();

                    button.prop('disabled', true);
                    checkbox.prop('disabled', true);

                    $.post(
                        '{{ _p.web_ajax }}exercise.ajax.php?a=quiz_confirm_saved_answers',
                        xhrData
                    ).done(function () {
                        button.parents('.form-group').remove();

                        $('#quiz_end_message').show();
                    }).fail(function (response) {
                        button.replaceWith(response.responseText);
                    });
                })

                $('#quiz_end_message').hide();
            });
        </script>
    {% endif %}
{% endif %}
