$(function () {
    $('.check-meeting-video').on('click', function (e) {
        e.preventDefault();

        var $self = $(this),
            meetingId = $self.data('id') || 0;

        if (!meetingId) {
            return;
        }

        var $loader = $('<span>', {
            'aria-hidden': 'true'
        }).addClass('fa fa-spinner fa-spin fa-fw');

        $self.replaceWith($loader);

        $.get(_p.web_plugin + 'bbb/ajax.php', {
            a: 'check_m4v',
            meeting: meetingId
        }, function (response) {
            $loader.replaceWith(response.link);

            window.open(response.url);
        });
    });
});
