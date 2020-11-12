<div id="record-audio-recordrtc" class="row text-center">
    <form>
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <div class="form-group">
                    <span class="fa fa-microphone fa-5x fa-fw" aria-hidden="true"></span>
                    <span class="sr-only">{{ 'RecordAudio'|get_lang }}</span>

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
                    </div>
                </div>
                <input type="hidden" name="audio_title" id="audio-title-rtc" value="{{ audio_title }}">
            </div>
        </div>
        <div class="text-center">
            <div class="form-group">
                <button class="btn btn-default" type="button" id="btn-start-record">
                    <span class="fa fa-circle fa-fw" aria-hidden="true"></span> {{ 'StartRecordingAudio'|get_lang }}
                </button>
                <button class="btn btn-danger hidden" type="button" id="btn-stop-record" disabled>
                    <span class="fa fa-square fa-fw" aria-hidden="true"></span> {{ 'StopRecordingAudio'|get_lang }}
                </button>
            </div>
            <div class="form-group">
                <audio class="skip hidden center-block" controls id="record-preview"></audio>
            </div>
        </div>
    </form>
</div>

<div class="row" id="record-audio-wami">
    <div class="col-sm-3 col-sm-offset-3">
        <br>
        <form>
            <div class="form-group">
                <input type="hidden" name="audio_title" id="audio-title-wami" value="{{ audio_title }}">
            </div>
            <div class="form-group text-center">
                <button class="btn btn-default" type="button" id="btn-activate-wami">
                    <span class="fa fa-check fa-fw" aria-hidden=""></span> {{ 'Activate'|get_lang }}
                </button>
            </div>
        </form>
    </div>
    <div class="col-sm-3">
        <div id="record-audio-wami-container" class="wami-container"></div>
    </div>
</div>

<script>
    $(document).on('ready', function () {
        RecordAudio.init(
            {
                blockId: '#record-audio-recordrtc',
                btnStartId: '#btn-start-record',
                btnPauseId: '#btn-pause-record',
                btnPlayId: '#btn-play-record',
                btnStopId: '#btn-stop-record',
                btnSaveId: '',
                plyrPreviewId: '#record-preview',
                directory: '{{ directory }}',
                reload_page: '{{ reload_page }}',
                type: 'message',
            },
            {
                blockId: '#record-audio-wami',
                containerId: 'record-audio-wami-container',
                directory: '{{ directory }}',
                userId: {{ user_id }},
                type: 'message'
            },
            null
        );
    });
</script>
