$(function () {
    $('.btn-portfolio-delete').on('click', function (e) {
        e.preventDefault();

        var self = this;

        $.get(self.href, function (response) {
            $(self).parent().html(response);
        });
    });
});