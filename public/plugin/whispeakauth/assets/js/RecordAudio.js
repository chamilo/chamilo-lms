/* For licensing terms, see /license.txt */

window.RecordAudio = (function () {
    function useRecordRTC(rtcInfo) {
        $(rtcInfo.blockId).show();

        var mediaConstraints = {audio: true},
            localStream = null,
            recordRTC = null,
            btnStart = $(rtcInfo.btnStartId),
            btnStop = $(rtcInfo.btnStopId),
            tagAudio = $(rtcInfo.plyrPreviewId);

        function saveAudio() {
            var recordedBlob = recordRTC.getBlob();

            if (!recordedBlob) {
                return;
            }

            var btnStopText = btnStop.html();
            var fileExtension = recordedBlob.type.split('/')[1];

            var formData = new FormData();
            formData.append('audio', recordedBlob, 'audio.' + fileExtension);

            for (var prop in rtcInfo.data) {
                if (!rtcInfo.data.hasOwnProperty(prop)) {
                    continue;
                }

                formData.append(prop, rtcInfo.data[prop]);
            }

            $.ajax({
                url: _p.web_plugin + 'whispeakauth/ajax/record_audio.php',
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                beforeSend: function () {
                    btnStart.prop('disabled', true);
                    btnStop.prop('disabled', true).text(btnStop.data('loadingtext'));
                }
            }).done(function (response) {
                $('#messages-deck').html(response);

                if ($('#messages-deck > .alert.alert-success').length > 0) {
                    tagAudio.parents('#audio-wrapper').addClass('hidden').removeClass('show');
                } else {
                    tagAudio.parents('#audio-wrapper').removeClass('hidden').addClass('show');
                }

                btnStop.prop('disabled', true).html(btnStopText).parent().addClass('hidden');

                if ($('#messages-deck > .alert.alert-success').length > 0 ||
                    $('#messages-deck > .alert.alert-warning [data-reach-attempts]').length > 0
                ) {
                    btnStart.prop('disabled', true);
                } else {
                    btnStart.prop('disabled', false);
                }

                btnStart.parent().removeClass('hidden');
            });
        }

        btnStart.on('click', function () {
            tagAudio.prop('src', '');

            function successCallback(stream) {
                localStream = stream;

                recordRTC = RecordRTC(stream, {
                    recorderType: StereoAudioRecorder,
                    numberOfAudioChannels: 1,
                    type: 'audio'
                });
                recordRTC.startRecording();

                btnStop.prop('disabled', false).parent().removeClass('hidden');
                btnStart.prop('disabled', true).parent().addClass('hidden');
                tagAudio.removeClass('show').parents('#audio-wrapper').addClass('hidden');
            }

            function errorCallback(error) {
                alert(error.message);
            }

            if (navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia(mediaConstraints)
                    .then(successCallback)
                    .catch(errorCallback);

                return;
            }

            navigator.getUserMedia = navigator.getUserMedia || navigator.mozGetUserMedia || navigator.webkitGetUserMedia;

            if (navigator.getUserMedia) {
                navigator.getUserMedia(mediaConstraints, successCallback, errorCallback);
            }
        });

        btnStop.on('click', function () {
            if (!recordRTC) {
                return;
            }

            recordRTC.stopRecording(function (audioURL) {
                tagAudio.prop('src', audioURL);

                localStream.getTracks()[0].stop();

                saveAudio();
            });
        });
    }

    return {
        init: function (rtcInfo) {
            $(rtcInfo.blockId).hide();

            var userMediaEnabled = (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) ||
                !!navigator.webkitGetUserMedia ||
                !!navigator.mozGetUserMedia ||
                !!navigator.getUserMedia;

            if (!userMediaEnabled) {
                return;
            }

            useRecordRTC(rtcInfo);
        }
    };
})();