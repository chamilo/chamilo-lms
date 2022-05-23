/**
 * Plugin created by BeezNest Latino S.A.C
 *
 * For licensing terms, see /license.txt
 *
 * This plugin allows set quizzes markers in video with mediaelement.
 */

CKEDITOR.dialog.add('qMarkersrollsDialog', function (editor) {
    var lang = editor.lang.qmarkersrolls,
        player = null,
        pgbProgress = null,
        fakeImage = null,
        videoNode = null,
        quizzesList = [],
        currentMarkers = [],
        colorDialog = editor.plugins.colordialog;

    function initPlayer(dialog) {
        fakeImage = dialog.getSelectedElement();

        if (!fakeImage ||
            !fakeImage.data( 'cke-real-element-type' ) ||
            'video' !== fakeImage.data('cke-real-element-type')
        ) {
            return;
        }

        videoNode = editor.restoreRealElement(fakeImage);

        var sourcesList = videoNode.getElementsByTag('source', '');

        if (sourcesList.count() === 0) {
            sourcesList = videoNode.getElementsByTag('source', 'cke');

            if (sourcesList.count() === 0) {
                return;
            }
        }

        var sourceNode = sourcesList.getItem(0);

        if (!sourceNode) {
            return;
        }

        pgbProgress = CKEDITOR.document.getById('ck-qmr-progress');
        pgbProgress.setAttributes({value: 0});
        pgbProgress.on('change', function () {
            var value = pgbProgress.getValue()

            player.$.currentTime = value;
            dialog.setValueOf('tab-markers', 'txt-hms', encodeTime(value));
        });
        pgbProgress.hide();

        var playerContainer = CKEDITOR.document.getById('ck-qmr-player-container');
        playerContainer.setHtml('');
        playerContainer.hide();

        var sourceType = sourceNode.getAttribute('type');

        var embedVideoTypes = [
            'video/dailymotion',
            'video/facebook',
            'video/twitch',
            'video/vimeo',
            'video/youtube',
        ];

        var isEmbedVideo = -1 !== CKEDITOR.tools.indexOf(embedVideoTypes, sourceType);

        if (isEmbedVideo) {
            playerContainer.setText(lang.embedVideoSource + ' ' + sourceNode.getAttribute('src'));
            playerContainer.show();
        } else {
            player = new CKEDITOR.dom.element('video');
            player.setAttributes({
                'class': 'skip',
                src: sourceNode.getAttribute('src')
            });
            player.removeAttribute('controls');
            player.setStyles({ maxWidth: '100%', maxHeight: '100%' });
            player.on('loadedmetadata', function () {
                pgbProgress.setAttribute('max', Math.floor(player.$.duration));
                pgbProgress.show();

                playerContainer.append(player);
                playerContainer.show();
            });
        }
    }

    function decodeTime(tms) {
        var parts = tms.split(':');

        if (3 !== parts.length) {
            return 0;
        }

        var hours = parseInt(parts[0]),
            minutes = parseInt(parts[1]),
            seconds = parseInt(parts[2]);

        if (seconds > 59 || minutes > 59) {
            return 0;
        }

        hours *= 60 * 60;
        minutes *= 60;

        return hours + minutes + seconds;
    }

    function encodeTime(time) {
        if (time < 60) {
            if (time < 10) {
                time = '0' + time;
            }

            return '00:00:' + time;
        }

        var hours = 0,
            minutes = Math.floor(time / 60),
            seconds = Math.floor(time % 60);

        if (minutes > 60) {
            hours = Math.floor(minutes / 60);
            minutes = minutes - (hours * 60);
        }

        return (hours < 10 ? '0' + hours : hours) + ':'
            + (minutes < 10 ? '0' + minutes : minutes) + ':'
            + (seconds < 10 ? '0' + seconds : seconds);
    }

    function displayQuizzes() {
        var container = document.getElementById('ck-qmr-quizzes-container');
        container.innerHTML = '';

        quizzesList.forEach(function (quiz) {
            var alreadyAdded = false;

            currentMarkers.forEach(function (markerRoll) {
                if (quiz.id == markerRoll[1]) {
                    alreadyAdded = true;
                }
            });

            if (alreadyAdded) {
                return;
            }

            var label = document.createElement('label');
            label.textContent = quiz.title;
            label.htmlFor = 'ck-qmr-quiz-' + quiz.id;
            label.style.verticalAlign = 'top';
            label.style.whiteSpace = 'normal';
            label.style.display = 'inline-block';

            var radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'ck_qmr_quiz';
            radio.id = 'ck-qmr-quiz-' + quiz.id;
            radio.value = quiz.id;

            var row = document.createElement('li');
            row.appendChild(radio);
            row.appendChild(label);

            container.appendChild(row);
        });
    }

    function displayCurrentMarkersList() {
        var quizzesAddedContainer = document.getElementById('ck-qmr-quizzes-added-container');
        quizzesAddedContainer.innerHTML = '';

        currentMarkers.forEach(function (markerRoll) {
            var makerForQuiz = null;

            quizzesList.forEach(function (quiz) {
                if (markerRoll[1] == quiz.id) {
                    makerForQuiz = quiz;
                }
            });

            if (!makerForQuiz) {
                return;
            }

            var btnRemove = document.createElement('a');
            btnRemove.className = 'cke_dialog_ui_button';
            btnRemove.type = 'button';
            btnRemove.innerHTML = '<span class="cke_dialog_ui_button">' + lang.delete + '</span>';
            btnRemove.setAttribute('role', 'button');
            btnRemove.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                for (var i = 0; i < currentMarkers.length; i++) {
                    if (currentMarkers[i][1] == markerRoll[1]) {
                        currentMarkers.splice(i, 1);
                        i--;
                    }
                }

                displayQuizzes();
                displayCurrentMarkersList();
            }, false);

            var divMarker = document.createElement('span');
            divMarker.style.whiteSpace = 'normal';
            divMarker.innerHTML = ' <strong>' + encodeTime(markerRoll[0]) + '</strong> &mdash; '
                + makerForQuiz.title;

            var pMarker = document.createElement('p');
            pMarker.appendChild(btnRemove);
            pMarker.appendChild(divMarker);

            quizzesAddedContainer.appendChild(pMarker);
        });
    }

    return {
        title: lang.dialogTitle,
        minWidth: 400,
        minHeight: 500,
        resizable: CKEDITOR.DIALOG_RESIZE_NONE,
        contents: [
            {
                id: 'tab-markers',
                label: lang.markers,
                elements: [
                    {
                        type: 'vbox',
                        width: '100%',
                        children: [
                            {
                                type: 'html',
                                id: 'html',
                                html: '<div id="ck-qmr-player-container"></div>'
                            },
                            {
                                type: 'html',
                                html: '<input type="range" min="0" step="1" id="ck-qmr-progress">'
                            },
                            {
                                type: 'hbox',
                                widths: ['100%', '200px'],
                                children: [
                                    {
                                        type: 'html',
                                        html: lang.embeddableQuizzes + ' '
                                            + '<ul id="ck-qmr-quizzes-container" '
                                            + 'style="max-height: 110px; overflow: hidden auto; list-style: none;"></ul>'
                                    },
                                    {
                                        type: 'vbox',
                                        children: [
                                            {
                                                type: 'text',
                                                id: 'txt-hms',
                                                label: lang.time,
                                                'default': '00:00:00'
                                            },
                                            {
                                                type: 'button',
                                                id: 'btn-assign',
                                                label: lang.assignQuiz,
                                                title: lang.assignQuiz,
                                                onClick: function () {
                                                    var radioQuizzes = document.getElementsByName('ck_qmr_quiz'),
                                                        selected = null;

                                                    radioQuizzes.forEach(function (radio) {
                                                        if (!radio.checked) {
                                                            return;
                                                        }

                                                        selected = radio;
                                                    });

                                                    if (!selected) {
                                                        return;
                                                    }

                                                    var tms = this.getDialog()
                                                        .getContentElement('tab-markers', 'txt-hms')
                                                        .getValue();

                                                    currentMarkers.push([
                                                        decodeTime(tms),
                                                        parseInt(selected.value)
                                                    ]);

                                                    displayCurrentMarkersList();

                                                    selected.parentElement.remove();
                                                }
                                            },
                                        ]
                                    }
                                ]
                            },
                            {
                                type: 'html',
                                html: lang.currentMarkers + ' '
                                    + '<div id="ck-qmr-quizzes-added-container" '
                                    + 'style="max-height: 140px; overflow: hidden auto;"></div>'
                            }
                        ]
                    },
                ]
            },
            {
                id: 'tab-settings',
                label: lang.settings,
                elements: [
                    {
                        type: 'hbox',
                        widths: ['200px', '100%'],
                        children: [
                            {
                                type: 'text',
                                id: 'markerColor',
                                label: lang.markerColor,
                                'default': '',
                                setup: function (widget) {
                                    this.setValue(widget.getAttribute('data-q-markersrolls-color'));
                                },
                                commit: function (widget) {
                                    widget.setAttribute('data-q-markersrolls-color', this.getValue());
                                }
                            },
                            colorDialog ? {
                                type: 'button',
                                id: 'markerColorChoose',
                                'class': 'colorChooser',
                                label: lang.choose,
                                onLoad: function() {
                                    // Stick the element to the bottom
                                    this.getElement()
                                        .getParent()
                                        .setStyle('vertical-align', 'bottom');
                                },
                                onClick: function () {
                                    editor.getColorFromDialog(function (color) {
                                        if (color) {
                                            this.getDialog()
                                                .getContentElement('tab-settings', 'markerColor')
                                                .setValue(color);
                                        }

                                        this.focus();
                                    }, this)
                                }
                            } : {
                                type: 'html',
                                html: '&nbsp;'
                            }
                        ]
                    },
                ]
            },
        ],
        onShow: function () {
            var dialog = this;

            document.getElementById('ck-qmr-quizzes-container').innerHTML = '';

            initPlayer(dialog);

            currentMarkers = JSON.parse(
                videoNode.getAttribute('data-q-markersrolls') || '[]'
            );

            CKEDITOR.ajax.load(
                editor.config.qMarkersRollsUrl,
                function (response) {
                    quizzesList = JSON.parse(response);

                    displayQuizzes();

                    displayCurrentMarkersList();

                    dialog.setupContent(videoNode);
                }
            );
        },
        onHide: function () {
            player = null;
            pgbProgress = null;
        },
        onOk: function () {
            if (!fakeImage) {
                return;
            }

            this.commitContent(videoNode);

            videoNode.setAttribute('data-q-markersrolls', JSON.stringify(currentMarkers));

            var newFakeImage = editor.createFakeElement(videoNode, 'cke_video', 'video', false);
            newFakeImage.setStyles({
                width: fakeImage.getStyle('width'),
                height: fakeImage.getStyle('height')
            });

            newFakeImage.replace(fakeImage);
            editor.getSelection().selectElement(newFakeImage);
        }
    };
});
