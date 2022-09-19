(function ($) {
    $.fn.passwordChecker = function (options) {
        options = $.extend({
            rules: []
        }, options);

        this.each(function (i, el) {
            var $el = $(el);
            var $parent = $el.parent();

            var $ulHelp = $('<ul class="help-block fa-ul"></ul>');

            var helpTexts = [];

            $(options.rules).each(function (j, rule) {
                helpTexts.push(
                    $('<li>')
                );

                helpTexts[j].text(rule.helpText).appendTo($ulHelp).append('<span class="fa fa-fw fa-li ">');
            });

            $ulHelp.insertAfter($parent);

            $el
                .on('input', function () {
                    var tempPassword = this.value;

                    $(options.rules).each(function (j, rule) {
                        var match = tempPassword.match(
                            new RegExp(rule.pattern, 'g')
                        );

                        if (match && match.length >= rule.minChar) {
                            helpTexts[j].removeClass('text-danger').addClass('text-success')
                                .find('.fa-li').removeClass('fa-times').addClass('fa-check');
                        } else {
                            helpTexts[j].addClass('text-danger').removeClass('text-success')
                                .find('.fa-li').addClass('fa-times').removeClass('fa-check');
                        }
                    });
                })
                .trigger('input');
        });
    };
})(jQuery);