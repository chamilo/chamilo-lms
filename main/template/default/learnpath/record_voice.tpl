<script type="text/javascript" src="{{ _p.web_lib }}javascript/rtc/RecordRTC.js"></script>

<audio id="audio" autoplay="" loop="" controls=""></audio>
<span id="progress-info"></span>
<br />
<button id="record" class="btn btn-danger" >Record Audio</button>
<button id="stop" class="btn" disabled="">Stop Recording Audio</button>

<script>

    $(document).ready(function() {


        var format = 'webm'; // or wav
        var record = document.getElementById('record');
        var stop = document.getElementById('stop');
        var preview = document.getElementById('audio');
        var progressInfo = document.getElementById('progress-info');
        var previewBlock = document.getElementById('preview');


        function xhr(url, data, callback) {
            var request = new XMLHttpRequest();
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    callback(location.href + request.responseText);
                }
            };
            request.open('POST', url);
            request.send(data);
        }

         function postBlob(blob, fileType, fileName) {
            // FormData
            var formData = new FormData();
            formData.append(fileType + '-filename', fileName);
            formData.append(fileType + '-blob', blob);

            // progress-bar
            var hr = document.createElement('hr');
            progressInfo.appendChild(hr);
            var strong = document.createElement('strong');
            strong.innerHTML = fileType + ' upload progress: ';
            progressInfo.appendChild(strong);
            var progress = document.createElement('progress');
            progressInfo.appendChild(progress);

            // POST the Blob

            $.ajax({
                url:'{{ _p.web_ajax }}lp.ajax.php?a=record_audio&lp_item_id={{ lp_item_id }}',
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                success:function(fileURL) {
                    progressInfo.appendChild(document.createElement('hr'));

                    var mediaElement = document.createElement(fileType);
                    mediaElement.src = fileURL;
                    mediaElement.controls = true;
                    var uniq = 'id' + (new Date()).getTime();
                    mediaElement.id = uniq;
                    $(previewBlock).html('');
                    previewBlock.appendChild(mediaElement);
                    mediaElement.play();
                    $(progressInfo).html('');
                }
            });
        }



        var recordAudio, recordVideo;
        record.onclick = function() {
            record.disabled = true;
            var video_constraints = {
                mandatory: { },
                optional: []
            };

            navigator.webkitGetUserMedia({
                    audio: true,
                    video: video_constraints
                }, function(stream) {
                    preview.src = (window.webkitURL || window.URL).createObjectURL(stream);
                    preview.play();

                    recordAudio = RecordRTC(stream);
                    recordAudio.startRecording();

                    /*recordVideo = RecordRTC(stream, {
                        type: 'video'
                    });*/
                    //recordVideo.startRecording();
                    stop.disabled = false;
                });
        };

        stop.onclick = function() {
            record.disabled = false;
            stop.disabled = true;

            var fileName = Math.round(Math.random() * 99999999) + 99999999;

            recordAudio.stopRecording();

            postBlob(recordAudio.getBlob(), 'audio', fileName + '.' + format);

            //recordVideo.stopRecording();
            //PostBlob(recordVideo.getBlob(), 'video', fileName + '.webm');

            preview.src = '';
        };
    });

</script>



