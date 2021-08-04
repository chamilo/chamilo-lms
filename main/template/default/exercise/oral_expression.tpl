
<div id="record-audio-recordrtc-{{ question_id }}" class="row">
    <div class="col-sm-4 col-sm-offset-4">
        <div class="form-group text-center">
            <span class="fa fa-microphone fa-5x fa-fw" aria-hidden="true"></span>
            <span class="sr-only">{{ 'RecordAudio'|get_lang }}</span>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group text-center">
            <div id="timer" style="display: none">
                <h2>
                    <div class="label label-danger">
                        <span id="hour">00</span>
                        <span class="divider">:</span>
                        <span id="minute">00</span>
                        <span class="divider">:</span>
                        <span id="second">00</span>
                    </div>
                </h2>
                <br />
            </div>

            <button class="btn btn-primary" type="button" id="btn-start-record-{{ question_id }}">
                <span class="fa fa-circle fa-fw" aria-hidden="true"></span> {{ 'StartRecordingAudio'|get_lang }}
            </button>
            <button class="btn btn-primary hidden" type="button" id="btn-pause-record-{{ question_id }}" disabled>
                <span class="fa fa-pause fa-fw" aria-hidden="true"></span> {{ 'PauseRecordingAudio'|get_lang }}
            </button>
            <button class="btn btn-primary hidden" type="button" id="btn-play-record-{{ question_id }}" disabled>
                <span class="fa fa-play fa-fw" aria-hidden="true"></span> {{ 'PlayRecordingAudio'|get_lang }}
            </button>
            <button class="btn btn-danger hidden" type="button" id="btn-stop-record-{{ question_id }}" disabled>
                <span class="fa fa-square fa-fw" aria-hidden="true"></span> {{ 'StopRecordingAudio'|get_lang }}
            </button>
            <button class="btn btn-success hidden" type="button" id="btn-save-record-{{ question_id }}"
                data-loadingtext="{{ 'Uploading'|get_lang }}"
                disabled>
                <span class="fa fa-send fa-fw" aria-hidden="true"></span> {{ 'SaveRecordedAudio'|get_lang }}
            </button>
            <button id="hide_description_{{ question_id }}" type="button" class="btn btn-default advanced_options" data-toggle="button" aria-pressed="false" autocomplete="off">
                <em class="fa fa-bars"></em> {{ 'AddText' | get_lang }}
            </button>
        </div>
        <div class="form-group text-center">
            <audio class="skip hidden center-block" controls id="record-preview-{{ question_id }}"></audio>
        </div>
        <div class="well">
            {{ 'OralExpressionHelpText' | get_lang }}
        </div>
        <div class="record-message"></div>
    </div>
</div>

<div class="row" id="record-audio-wami-{{ question_id }}">
    <div class="col-sm-4 col-sm-offset-4 text-center">
        <div id="record-audio-wami-container-{{ question_id }}" class="wami-container"></div>
    </div>
</div>

<script>
$(document).on('ready', function () {
    RecordAudio.init({
        blockId: '#record-audio-recordrtc-{{ question_id }}',
        btnStartId: '#btn-start-record-{{ question_id }}',
        btnPauseId: '#btn-pause-record-{{ question_id }}',
        btnPlayId: '#btn-play-record-{{ question_id }}',
        btnStopId: '#btn-stop-record-{{ question_id }}',
        plyrPreviewId: '#record-preview-{{ question_id }}',
        directory: '{{ directory }}',
        type: 'document'
    }, {
        blockId: '#record-audio-wami-{{ question_id }}',
        containerId: 'record-audio-wami-container-{{ question_id }}',
        directory: '{{ directory }}',
        userId: {{ user_id }},
        type: 'document'
    }, '{{ file_name }}');

    if (0 === $('#hide_description_{{ question_id }}_options').length) {
        $('#hide_description_{{ question_id }}').remove();
    }
});
</script>
