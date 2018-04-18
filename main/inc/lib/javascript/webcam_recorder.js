(function () {
    var config = {
        selectors: {
            btnFreeze: '#btnCapture',
            btnUnFreeze: '#btnClean',
            btnSave: '#btnSave',
            btnAuto: '#btnAuto',
            btnStop: '#btnStop',
            camera: '#chamilo-camera',
            results: '#upload_results'
        },
        urlReceiver: '',
        video: {
            fps: 1000,
            maxTime: 60000,
            maxClip: 25
        }
    };

    function snap() {
        Webcam.snap(function (dataUri) {
            Webcam.upload(dataUri, config.urlReceiver, function (code, response) {
                $(config.selectors.results).html(
                    '<h3>' + response + '</h3>'
                    + '<div>'
                    + '<img src="' + dataUri + '" class="webcamclip_bg">'
                    + '</div>'
                );
            });
        });
    };

    var videoInterval = 0;

    function startVideo() {
        var counter = 0;
        videoInterval = window.setInterval(function () {
            counter++;

            window.setTimeout(function () {
                stopVideo();
            }, config.video.maxTime);

            if (config.video.maxClip >= counter) {
                snap();
            } else {
                stopVideo();
            }
        }, config.video.fps);
    }

    function stopVideo() {
        if (!videoInterval) {
            return;
        }

        window.clearTimeout(videoInterval);
    }

    window.RecordWebcam = {
        init: function (params) {
            config = $.extend(true, config, params);

            $(document).on('ready', function () {
                Webcam.set({
                    width: 320,
                    height: 240,
                    image_format: 'jpeg',
                    jpeg_quality: 90
                });
                Webcam.attach(config.selectors.camera);

                $('video').addClass('skip');

                $(config.selectors.btnFreeze).on('click', function (e) {
                    e.preventDefault();

                    Webcam.freeze();
                });

                $(config.selectors.btnUnFreeze).on('click', function (e) {
                    e.preventDefault();

                    Webcam.unfreeze();
                });

                $(config.selectors.btnSave).on('click', function (e) {
                    e.preventDefault();

                    snap();
                })

                $(config.selectors.btnAuto).on('click', function (e) {
                    e.preventDefault();

                    startVideo();
                });

                $(config.selectors.btnStop).on('click', function (e) {
                    e.preventDefault();

                    stopVideo();
                });
            })
        }
    };
})();