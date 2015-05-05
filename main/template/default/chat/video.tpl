<p class="lead">{{ "Chat with %s"|get_lang|format(chat_user.complete_name) }}</p>
<div class="row">
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
    })();
</script>
