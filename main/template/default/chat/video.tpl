<div id="chat-video-panel">
    <div class="alert alert-warning alert-dismissible fade in">
        <button type="button" class="close" data-dismiss="alert" aria-label="{{ 'Close'|get_lang }}">
            <span aria-hidden="true">&times;</span>
        </button>
        <h4>{{ 'Warning'|get_lang }}</h4>
        <div id="dlg-webrtc-help">
            <p>{{ 'WebRTCDialogHelp'|get_lang }}</p>
            <img src="{{ _p.web_lib ~ 'javascript/chat/img/webrtc_' ~ (navigator_is_firefox ? 'firefox' : 'chrome') }}.png"
                 alt="{{ 'Permissions'|get_lang }}" class="img-thumbnail img-responsive">
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-sm-7">
            <div class="thumbnail video-chat-user">
                <div id="chat-remote-video"></div>
                <div class="caption">
                    <p class="text-muted text-center">{{ "ChatWithXUser"|get_lang|format(chat_user.complete_name) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-5">
            <div class="thumbnail">
                <div id="chat-local-video"></div>
                <div class="caption">
                    <p class="text-muted text-center">{{ user_local.complete_name }}</p>
                </div>
            </div>
            <div id="connection-status"></div>
            <div class="chat-friends">
                <div class="panel-group" id="blocklistFriends" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#blocklistFriends" href="#listFriends" aria-expanded="true" aria-controls="listFriends">
                                    {{ "SocialFriend" | get_lang }}
                                </a>
                            </h4>
                        </div>
                        <div id="listFriends" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                            {{ block_friends }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var VideoChat = {
        init: function () {
            var isCompatible = !!Modernizr.prefixed('RTCPeerConnection', window);

            var notifyNotSupport = function () {
                $.get('{{ _p.web_ajax }}chat.ajax.php', {
                    action: 'notify_not_support',
                    to:{{ chat_user.id }}
                });
            };

            var startVideoChat = function () {
                var webRTC = new SimpleWebRTC({
                    localVideoEl: 'chat-local-video',
                    remoteVideosEl: '',
                    autoRequestMedia: true
                });

                webRTC.on('readyToCall', function () {
                    $('#dlg-webrtc-help').replaceWith("<p>" +
                        "<em class=\"fa fa-warning\"></em> {{ 'AvoidChangingPageAsThisWillCutYourCurrentVideoChatSession'|get_lang }}" +
                        "</p>");

                    webRTC.joinRoom('{{ room_name }}');
                });
                webRTC.on('videoAdded', function (video, peer) {
                    $(video).addClass('skip');
                    $('#chat-remote-video').html(video);

                    if (peer && peer.pc) {
                        peer.pc.on('iceConnectionStateChange', function () {
                            var alertDiv = $('<div>')
                                    .addClass('alert');

                            switch (peer.pc.iceConnectionState) {
                                case 'checking':
                                    alertDiv
                                        .addClass('alert-info')
                                        .html('<em class="fa fa-spinner fa-spin"></em> ' + "{{ 'ConnectingToPeer'|get_lang }}");
                                    break;
                                case 'connected':
                                    //no break
                                case 'completed':
                                    alertDiv
                                        .addClass('alert-success')
                                        .html('<em class="fa fa-commenting"></em> ' + "{{ 'ConnectionEstablished'|get_lang }}");
                                    break;
                                case 'disconnected':
                                    alertDiv
                                        .addClass('alert-info')
                                        .html('<em class="fa fa-frown-o"></em> ' + "{{ 'Disconnected'|get_lang }}");
                                    break;
                                case 'failed':
                                    alertDiv
                                        .addClass('alert-danger')
                                        .html('<em class="fa fa-times"></em> ' + "{{ 'ConnectionFailed'|get_lang }}");
                                    break;
                                case 'closed':
                                    alertDiv
                                        .addClass('alert-danger')
                                        .html('<em class="fa fa-close"></em> ' + "{{ 'ConnectionClosed'|get_lang }}");
                                    break;
                            }

                            $('#connection-status').html(alertDiv);
                        });
                    }
                });
                webRTC.on('videoRemoved', function (video, peer) {
                    video.src = '';
                });
                webRTC.on('iceFailed', function (peer) {
                    var alertDiv = $('<div>')
                        .addClass('alert-danger')
                        .html('<em class="fa fa-close"></em> ' + "{{ 'LocalConnectionFailed'|get_lang }}");

                    $('#connection-status').html(alertDiv);
                });
                webRTC.on('connectivityError', function (peer) {
                    var alertDiv = $('<div>')
                        .addClass('alert-danger')
                        .html('<em class="fa fa-close"></em> ' + "{{ 'RemoteConnectionFailed'|get_lang }}");

                    $('#connection-status').html(alertDiv);
                });
            };

            if (!isCompatible) {
                notifyNotSupport();

                $('#chat-video-panel').remove();
                return;
            }

            $('#messages').remove();

            startVideoChat();

            window.onbeforeunload = function () {
                return "{{ 'AvoidChangingPageAsThisWillCutYourCurrentVideoChatSession'|get_lang }}";
            };
        }
    };

    $(function () {
        VideoChat.init();
    });
})();
</script>
