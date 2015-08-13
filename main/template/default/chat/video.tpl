<div id="chat-video-panel">
    <div class="row">
        <div class="col-md-12">
            <h3 class="title"><i class="fa fa-video-camera"></i> {{ room.room_name }} </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div id="chat-local-video"></div>
            <div class="username-local">
                {% if user_local.user_is_online_in_chat == 1 %}
                    <img src="{{ 'online.png' | icon(16) }}" />
                {% else %}
                <img src="{{ 'offline.png' | icon(16) }}" />
                {% endif %}
                {{ user_local.complete_name }} ( {{ user_local.username }} )
            </div>

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
                            <div class="panel-body">
                                {{ block_friends }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="user-from" id="chat-remote-video"></div>
            <div class="chat-user-remote">{{ "ChatWithXUser"|get_lang|format(chat_user.complete_name) }}</div>
        </div>
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
