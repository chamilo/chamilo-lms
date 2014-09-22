jQuery(document).ready(function() {
    SonataCore.setup_select2(document);
});

var SonataCore = {

    setup_select2: function(subject) {
        jQuery('select:not([data-sonata-select2="false"])', subject).each(function() {
            var select = $(this);

            var allowClearEnabled = false;

            if (select.find('option[value=""]').length) {
                allowClearEnabled = true;
            }

            if (select.attr('data-sonata-select2-allow-clear')==='true') {
                allowClearEnabled = true;
            } else if (select.attr('data-sonata-select2-allow-clear')==='false') {
                allowClearEnabled = false;
            }

            select.select2({
                width: 'resolve',
                minimumResultsForSearch: 10,
                allowClear: allowClearEnabled
            });

            var popover = select.data('popover');

            if (undefined !== popover) {
                select
                    .select2('container')
                    .popover(popover.options)
                ;
            }
        });
    }

};
