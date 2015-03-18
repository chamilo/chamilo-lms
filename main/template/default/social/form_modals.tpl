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
                <button type="button" id="btn-send-message" class="btn btn-primary">
                    <i class="fa fa-send"></i> {{ 'Send' | get_lang }}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="send-invitation-modal" tabindex="-1" role="dialog" aria-labelledby="send-invitation-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ 'Close' | get_lang }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="send-invitation-modal-title">{{ 'SendInvitation' | get_lang }}</h4>
            </div>
            <div class="modal-body">
                <div id="send-invitation-alert"></div>
                {{ invitationForm }}
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-send-invitation" class="btn btn-primary">
                    <i class="fa fa-send"></i> {{ 'Send' | get_lang }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).on('ready', function() {
        var $sendMessageModal = $('#send-message-modal'),
            $sendInvitationModal = $('#send-invitation-modal');

        var sendToUser = 0;

        $('.btn-to-send-message').on('click', function(e) {
            e.preventDefault();

            sendToUser = $(this).data('send-to');

            $sendMessageModal.modal('show');
        });

        $('#btn-send-message').on('click', function(e) {
            e.preventDefault();

            var $frmSendMessage = $sendMessageModal.find('.modal-body form'),
                url = '{{ _p.web_ajax }}message.ajax.php?a=send_message&user_id=' + sendToUser;

            $.get(url, $frmSendMessage.serialize(), function(response) {
                $('#send-message-alert').html(response);

                $frmSendMessage[0].reset();
            });
        });

        $('.btn-to-send-invitation').on('click', function(e) {
            e.preventDefault();

            sendToUser = $(this).data('send-to');

            $sendInvitationModal.modal('show');
        });

        $('#btn-send-invitation').on('click', function(e) {
            e.preventDefault();

            var $frmSendInvitation = $sendInvitationModal.find('.modal-body form'),
                url = '{{ _p.web_ajax }}message.ajax.php?a=send_invitation&user_id=' + sendToUser;

            $.get(url, $frmSendInvitation.serialize(), function(response) {
                $('#send-invitation-alert').html(response);

                $frmSendInvitation[0].reset();
            });
        });
    });
</script>
