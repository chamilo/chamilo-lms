{% include 'zoom/view/meeting_details.tpl' %}

{% if is_conference_manager and meeting.isSignAttendance %}
    <p class="text-info">
        <span class="fa fa-list-alt"></span>
        {{ 'ConferenceWithAttendance'|get_plugin_lang('ZoomPlugin') }}
    </p>
{% endif %}

<hr>

{% set btn_start = '' %}

{% if start_url %}
    {% set btn_start %}
        <a href="{{ start_url }}" class="btn btn-primary">
            {{ 'EnterMeeting'|get_plugin_lang('ZoomPlugin') }}
        </a>
    {% endset %}
{% endif %}

{% if not is_conference_manager %}
    {% if meeting.isSignAttendance %}
        <div class="row">
            <div class="col-md-offset-3 col-md-6">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <span class="fa fa-pencil-square-o fa-fw" aria-hidden="true"></span>
                            {{ 'Attendance'|get_lang }}
                        </h3>
                    </div>
                    <div class="panel-body">
                        <p>{{ meeting.reasonToSignAttendance }}</p>

                        {% if signature %}
                            <div class="thumbnail">
                                <img src="{{ signature.file }}"
                                     alt="{{ 'SignatureDone'|get_plugin_lang('ZoomPlugin') }}">
                                <div class="caption text-center">
                                    {{ signature.registeredAt|api_convert_and_format_date(constant('DATE_TIME_FORMAT_LONG')) }}
                                </div>
                            </div>
                        {% else %}
                            {% set btn_start = '' %}

                            {% if 'started' == meeting.meetingInfoGet.status %}
                                <button class="btn btn-info" id="btn-sign" data-toggle="modal"
                                        data-target="#signature-modal">
                                    <i class="fa fa-pencil fa-fw" aria-hidden="true"></i>
                                    {{ 'Sign'|get_plugin_lang('ZoomPlugin') }}
                                </button>

                                <div class="modal fade" tabindex="-1" role="dialog" id="signature-modal"
                                     data-backdrop="static">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                                <h4 class="modal-title">{{ 'SignAttendance'|get_plugin_lang('ZoomPlugin') }}</h4>
                                            </div>
                                            <div class="modal-body">
                                                <div id="signature-modal--signature-area" class="well">
                                                    <canvas></canvas>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <span id="signature-modal--loader" aria-hidden="true"
                                                      class="fa fa-refresh fa-spin"
                                                      aria-label="{{ 'Loading'|get_lang }}" style="display: none;">
                                                </span>
                                                <span id="signature-modal--save-controls">
                                                    <button id="signature-modal--btn-save" class="btn btn-primary">
                                                        <em class="fa fa-save" aria-hidden="true"></em>
                                                        {{ 'Save'|get_lang }}
                                                    </button>
                                                    <button id="signature-modal--btn-clean" class="btn btn-default">
                                                        <em class="fa fa-eraser" aria-hidden="true"></em>
                                                        {{ 'Clean'|get_lang }}
                                                    </button>
                                                </span>
                                                <div id="signature-modal--close-controls" style="display: none;">
                                                    <span id="signature-modal--results"></span>
                                                    <button class="btn btn-default"
                                                            data-dismiss="modal">{{ 'Close'|get_lang }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    $(function () {
                                        var $signatureArea = $('#signature-modal--signature-area')
                                        var $loader = $('#signature-modal--loader')
                                        var $saveControls = $('#signature-modal--save-controls')
                                        var $btnSave = $('#signature-modal--btn-save')
                                        var $btnClean = $('#signature-modal--btn-clean')
                                        var $closeControls = $('#signature-modal--close-controls')
                                        var $txtResults = $('#signature-modal--results')

                                        var imageFormat = 'image/png'
                                        var canvas = document.querySelector('#signature-modal--signature-area canvas')
                                        var signaturePad = new SignaturePad(canvas)

                                        $('#signature-modal')
                                            .on('shown.bs.modal', function (e) {
                                                var parentWidth = $signatureArea.width()
                                                var parentHeight = $signatureArea.height()

                                                canvas.setAttribute('width', parentWidth + 'px')
                                                canvas.setAttribute('height', parentHeight + 'px')

                                                signaturePad = new SignaturePad(canvas)
                                            })
                                            .on('hide.bs.modal', function (e) {
                                                $loader.hide()
                                                $saveControls.show()
                                                $closeControls.hide()
                                                $signatureArea.show()
                                                $btnSave.prop('disabled', false)
                                                $btnClean.prop('disabled', false)
                                            })

                                        $btnClean.on('click', function () {
                                            signaturePad.clear()
                                        })

                                        $btnSave.on('click', function () {
                                            if (signaturePad.isEmpty()) {
                                                alert('{{ 'ProvideASignatureFirst'|get_plugin_lang('ZoomPlugin')|e('js') }}')

                                                return false
                                            }

                                            var dataURL = signaturePad.toDataURL(imageFormat)

                                            $.ajax({
                                                beforeSend: function () {
                                                    $loader.show()
                                                    $btnSave.prop('disabled', true)
                                                    $btnClean.prop('disabled', true)
                                                },
                                                type: 'POST',
                                                url: 'meeting.ajax.php?{{ _p.web_cid_query }}',
                                                data: {
                                                    a: 'sign_attempt',
                                                    meetingId: {{ meeting.meetingId }},
                                                    file: dataURL
                                                },
                                                success: function (data) {
                                                    $btnSave.prop('disabled', false)
                                                    $btnClean.prop('disabled', false)
                                                    $loader.hide()
                                                    $saveControls.hide()
                                                    $signatureArea.hide()

                                                    signaturePad.clear()

                                                    if ('1' === data) {
                                                        $txtResults.html('{{ 'Saved'|get_lang }}')

                                                        window.location.reload()
                                                    } else {
                                                        $txtResults.html('{{ 'Error'|get_lang }}')
                                                    }

                                                    $closeControls.show()
                                                },
                                            })
                                        })
                                    })
                                </script>
                            {% endif %}
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    {% endif %}
{% endif %}

{{ btn_start }}

{% if details_url %}
    <a href="{{ details_url }}" class="btn btn-default">
        {{ 'Details'|get_lang }}
    </a>
{% endif %}

{% if btn_announcement %}
    {{ btn_announcement }}
{% endif %}
