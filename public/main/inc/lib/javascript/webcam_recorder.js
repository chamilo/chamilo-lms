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
            maxClip: 25
        },
        callbacks: {
            save: null,
            auto: null
        }
    };

    function snap() {
        var deferred = $.Deferred();

        Webcam.snap(function (dataUri) {
            Webcam.upload(dataUri, config.urlReceiver, function (code, response) {
                $(config.selectors.results).html(
                    '<h3>' + response + '</h3>'
                    + '<div>'
                    + '<img src="' + dataUri + '" class="webcamclip_bg">'
                    + '</div>'
                );

                deferred.resolve();
            });
        });

        return deferred.promise();
    };

    var videoInterval = 0,
        videoDeferred = $.Deferred();

    function startVideo() {
        var counter = 0;

        videoDeferred = $.Deferred();
        videoInterval = window.setInterval(function () {
            counter++;

            if (config.video.maxClip >= counter) {
                snap();
            } else {
                stopVideo();
            }
        }, config.video.fps);

        return videoDeferred.promise();
    }

    function stopVideo() {
        if (!videoInterval) {
            return;
        }

        window.clearTimeout(videoInterval);
        videoDeferred.resolve();
    }

    window.RecordWebcam = {
        init: function (params) {
            config = $.extend(true, config, params);

            $(document).ready(function () {
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

                    snap()
                        .always(function () {
                            if (config.callbacks.save) {
                                config.callbacks.save.apply(null, []);
                            }
                        });
                });

                $(config.selectors.btnAuto).on('click', function (e) {
                    e.preventDefault();

                    startVideo()
                        .always(function () {
                            if (config.callbacks.auto) {
                                config.callbacks.auto.apply(null, []);
                            }
                        });
                });

                $(config.selectors.btnStop).on('click', function (e) {
                    e.preventDefault();

                    stopVideo();
                });
            })
        }
    };
})();