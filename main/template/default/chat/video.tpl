<p class="lead">{{ "ChatWithXUser"|get_lang|format(chat_user.complete_name) }}</p>
<div class="row" id="chat-video-panel">
    <div class="col-sm-3">
        <div class="thumbnail">
            <video id="chat-local-video" class="skip"></video>
        </div>
    </div>
    <div class="col-sm-9">
        <div class="thumbnail" id="chat-remote-video"></div>
    </div>
</div>

<script>
    (function() {
        var VideoChat = {
            init: function() {
                var isCompatible = !!Modernizr.prefixed('RTCPeerConnection', window);

                var notifyNotSupport = function() {
                    $.get('{{ _p.web_ajax }}chat.ajax.php', {
                        action: 'notify_not_support',
                        to: {{ chat_user.id }}
                    });
                };

                var startVideoChat = function() {
                    var webRTC = new SimpleWebRTC({
                        localVideoEl: 'chat-local-video',
                        remoteVideosEl: 'chat-remote-video',
                        autoRequestMedia: true
                    });

                    webRTC.on('readyToCall', function() {
                        webRTC.joinRoom('{{ room_name }}');
                    });
                    webRTC.on('videoAdded', function (video, peer) {
                        $(video).addClass('skip');
                    });
                };

                if (!isCompatible) {
                    notifyNotSupport();

                    $('#chat-video-panel').remove();
                    return;
                }

                $('#messages').remove();

                startVideoChat();
            }
        };

        $(document).on('ready', function() {
            VideoChat.init();
        });
    })();
</script>
