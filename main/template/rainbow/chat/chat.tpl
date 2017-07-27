<div class="page-chat">
    <div class="row">
        <div class="col-sm-4 col-md-5 col-lg-4">
            <ul class="row list-unstyled" id="chat-users"></ul>
        </div>
        <div class="col-sm-8 col-md-7 col-lg-8">
            <div id="chat-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#all" aria-controls="all" role="tab" data-toggle="tab">{{ 'All'|get_lang }}</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="all">
                        <div class="course-chat chat-history" id="chat-history"></div>
                    </div>
                </div>
            </div>
            <div class="profile row">
                <div class="col-xs-12">
                    <div class="message-form-chat">
                        <div class="tabbable">
                            <ul class="nav nav-tabs">
                                <li class="active">
                                    <a href="#tab1" data-toggle="tab">{{ 'Write'|get_lang }}</a>
                                </li>
                                <li>
                                    <a href="#tab2" id="preview" data-toggle="tab">{{ 'Preview'|get_lang }}</a>
                                </li>
                                <li>
                                    <button id="emojis" class="btn btn-link" type="button">
                                        <span class="sr-only">{{ 'Emoji'|get_lang }}</span>{{ emoji_smile }}
                                    </button>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane active" id="tab1">
                                    <div class="row">
                                        <div class="col-sm-9">
                                            <span class="sr-only">{{ 'Message'|get_lang }}</span>
                                            <textarea id="chat-writer" name="message"></textarea>
                                        </div>
                                        <div class="col-sm-3">
                                            <button id="chat-send-message" type="button" class="btn btn-primary">{{ 'Send'|get_lang }}</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="tab2">
                                    <div id="html-preview" class="emoji-wysiwyg-editor-preview"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<audio id="chat-alert">
    <source src="{{ _p.web_main }}/chat/sound/notification.wav" type="audio/wav"></source>
    <source src="{{ _p.web_main }}chat/sound/notification.ogg" type="audio/ogg"></source>
    <source src="{{ _p.web_main }}chat/sound/notification.mp3" type="audio/mpeg"></source>
</audio>
<script>
    $(document).on('ready', function () {
        var ChChat = {
            _ajaxUrl: '{{ _p.web_ajax }}course_chat.ajax.php?{{ _p.web_cid_query }}',
            _historySize: -1,
            usersOnline: 0,
            currentFriend: 0,
            call: false,
            track: function () {
                return $
                        .get(ChChat._ajaxUrl, {
                            action: 'track',
                            size: ChChat._historySize,
                            users_online: ChChat.usersOnline,
                            friend: ChChat.currentFriend
                        })
                        .done(function (response) {
                            if (response.data.history) {
                                ChChat._historySize = response.data.oldFileSize;
                                ChChat.setHistory(response.data.history);
                            }

                            if (response.data.userList) {
                                ChChat.usersOnline = response.data.usersOnline;
                                ChChat.setConnectedUsers(response.data.userList);
                            }
                        });
            },
            setHistory: function (messageList) {
                var chatHistoryContainer = ChChat.currentFriend ? ('#chat-history-' + ChChat.currentFriend) : '#chat-history';

                $(chatHistoryContainer)
                        .html(messageList)
                        .prop('scrollTop', function () {
                            return this.scrollHeight;
                        });

                $('#chat-alert').get(0).play();
            },
            setConnectedUsers: function (userList) {
                var html = '';

                userList.forEach(function (user) {
                    var buttonStatus = user.isConnected ? 'success' : 'muted',
                            buttonTitle = user.isConnected ? '{{ 'StartAChat'|get_lang }}' : '{{ 'LeaveAMessage'|get_lang }}';

                    html += '<li class="col-xs-12 chat-user">' +
                            '   <div>' +
                            '       <img src="'+ user.image_url + '" alt="' + user.complete_name + '" class="img-circle user-image-chat"/>' +
                            '       <ul class="list-unstyled">' +
                            '           <li>' + user.complete_name;

                    if (user.id != {{ _u.user_id }}) {
                        html += '           <button type="button" class="btn btn-link btn-xs" title="' + buttonTitle + '" data-name="' + user.complete_name + '" data-user="' + user.id + '">' +
                                '               <i class="fa fa-comments text-' + buttonStatus + '"></i><span class="sr-only">' + buttonTitle + '</span>' +
                                '           </button>';
                    }

                    html += '           </li>' +
                            '           <li><small>' + user.username + '</small></li>' +
                            '       </ul>' +
                            '   </div>' +
                            '</li>';
                });

                $('#chat-users').html(html);
            },
            onPreviewListener: function () {
                $
                        .post(ChChat._ajaxUrl, {
                            action: 'preview',
                            'message': $('textarea#chat-writer').val()
                        })
                        .done(function (response) {
                            if (!response.status) {
                                return;
                            }

                            $('#html-preview').html(response.data.message);
                        });
            },
            onSendMessageListener: function (e) {
                e.preventDefault();

                if (!$('textarea#chat-writer').val().trim().length) {
                    return;
                }

                var self = this;
                self.disabled = true;

                $
                        .post(ChChat._ajaxUrl, {
                            action: 'write',
                            message: $('textarea#chat-writer').val(),
                            friend: ChChat.currentFriend
                        })
                        .done(function (response) {
                            self.disabled = false;

                            if (!response.status) {
                                return;
                            }

                            $('textarea#chat-writer').val('');
                            $(".emoji-wysiwyg-editor").html('');
                        });
            },
            onResetListener: function (e) {
                if (!confirm("{{ 'ConfirmReset'|get_lang }}")) {
                    e.preventDefault();

                    return;
                }

                $
                        .get(ChChat._ajaxUrl, {
                            action: 'reset',
                            friend: ChChat.currentFriend
                        })
                        .done(function (response) {
                            if (!response.status) {
                                return;
                            }

                            ChChat.setHistory(response.data);
                        });
            },
            init: function () {
                ChChat.track().done(function () {
                    ChChat.init();
                });
            }
        };

        hljs.initHighlightingOnLoad();

        emojione.ascii = true;
        emojione.imagePathPNG = '{{ _p.web_lib }}javascript/emojione/png/';
        emojione.imagePathSVG = '{{ _p.web_lib }}javascript/emojione/svg/';
        emojione.imagePathSVGSprites = '{{ _p.web_lib }}javascript/emojione/sprites/';

        var emojiStrategy = {{ emoji_strategy|json_encode }};

        $.emojiarea.path = '{{ _p.web_lib }}javascript/emojione/png/';
        $.emojiarea.icons = {{ icons|json_encode }};

        $('body').on('click', '#chat-reset', ChChat.onResetListener);

        $('#preview').on('click', ChChat.onPreviewListener);

        $('#emojis').on('click', function () {
            $('[data-toggle="tab"][href="#tab1"]')
                    .show()
                    .tab('show');
        });

        $('textarea#chat-writer').emojiarea({
            button: '#emojis'
        });

        $('body').delay(1500).find('.emoji-wysiwyg-editor').textcomplete([
            {
                match: /\B:([\-+\w]*)$/,
                search: function (term, callback) {
                    var results = [];
                    var results2 = [];
                    var results3 = [];
                    $.each(emojiStrategy, function (shortname, data) {
                        if (shortname.indexOf(term) > -1) {
                            results.push(shortname);
                        } else {
                            if ((data.aliases !== null) && (data.aliases.indexOf(term) > -1)) {
                                results2.push(shortname);
                            } else if ((data.keywords !== null) && (data.keywords.indexOf(term) > -1)) {
                                results3.push(shortname);
                            }
                        }
                    });

                    if (term.length >= 3) {
                        results.sort(function (a, b) {
                            return (a.length > b.length);
                        });
                        results2.sort(function (a, b) {
                            return (a.length > b.length);
                        });
                        results3.sort();
                    }

                    var newResults = results.concat(results2).concat(results3);

                    callback(newResults);
                },
                template: function (shortname) {
                    return '<img class="emojione" src="{{ _p.web_lib }}javascript/emojione/png/'
                            + emojiStrategy[shortname].unicode
                            + '.png"> :' + shortname + ':';
                },
                replace: function (shortname) {
                    return ':' + shortname + ': ';
                },
                index: 1,
                maxCount: 10
            }
        ], {});

        $('button#chat-send-message').on('click', ChChat.onSendMessageListener);

        $('#chat-users').on('click', 'button.btn', function (e) {
            e.preventDefault();

            var jSelf = $(this),
                    userId = parseInt(jSelf.data('user')) || 0;

            if (!userId) {
                return;
            }

            var exists = false;

            $('#chat-tabs ul.nav li').each(function (i, el) {
                if ($(el).data('user') == userId) {
                    exists = true;
                }
            });

            if (exists) {
                $('#chat-tab-' + userId).tab('show');

                return;
            }

            $('#chat-tabs ul.nav-tabs').append('\
                <li role="presentation" data-user="' + userId + '">\
                    <a id="chat-tab-' + userId + '" href="#chat-' + userId + '" aria-controls="chat-' + userId + '" role="tab" data-toggle="tab">' + jSelf.data('name') + '</a>\
                </li>\
            ');

            $('#chat-tabs .tab-content').append('\
                <div role="tabpanel" class="tab-pane" id="chat-' + userId + '">\
                    <div class="course-chat chat-history" id="chat-history-' + userId + '"></div>\
                </div>\
            ');

            $('#chat-tab-' + userId).tab('show');
        });

        $('#chat-tabs ul.nav-tabs').on('shown.bs.tab', 'li a', function (e) {
            var jSelf = $(this);

            var userId = parseInt(jSelf.parent().data('user')) || 0;

            if (!userId) {
                ChChat.currentFriend = 0;

                return;
            }

            ChChat.currentFriend = userId;

            $(this).tab('show');
        });

        $('.emoji-wysiwyg-editor').on('keyup', function (e) {
            if (e.ctrlKey && e.keyCode === 13) {
                $('button#chat-send-message').trigger('click');
            }
        });

        ChChat.init();
    });
</script>
