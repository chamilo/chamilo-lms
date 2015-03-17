<div class="modal fade" id="send-message-modal" tabindex="-1" role="dialog" aria-labelledby="send-message-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ 'Close' | get_lang }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="send-message-modal-title">{{ 'SendMessage' | get_lang }}</h4>
            </div>
            <div class="modal-body">
                <div id="send-message-alert"></div>
                {{ messageForm }}
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-send-message" class="btn btn-primary">{{ 'Send' | get_lang }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).on('ready', function() {
        var $sendMessageModal = $('#send-message-modal');

        $('#btn-to-send-message').on('click', function(e) {
            e.preventDefault();

            $sendMessageModal.modal('show');
        });

        $('#btn-send-message').on('click', function(e) {
            e.preventDefault();

            var $frmSendMessage = $sendMessageModal.find('.modal-body form'),
                url = '{{ _p.web_ajax }}message.ajax.php?a=send_message&user_id={{ friendId }}';

            $.get(url, $frmSendMessage.serialize(), function(response) {
                $('#send-message-alert').html(response);

                $frmSendMessage[0].reset();
            });
        });
    });
</script>
