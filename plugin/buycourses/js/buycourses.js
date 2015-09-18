/* For licensing terms, see /license.txt */
/**
 * JS library for the Chamilo buy-courses plugin
 * @package chamilo.plugin.buycourses
 */
$(document).ready(function () {
    $(".bc-button-save").click(function () {
        var currentRow = $(this).closest("tr");
        var courseOrSessionObject = {
            tab: "save_mod",
            visible: currentRow.find("[name='visible']").is(':checked') ? 1 : 0,
            price: currentRow.find("[name='price']").val()
        };

        var itemField = currentRow.data('type') + '_id';

        courseOrSessionObject[itemField] = currentRow.data('item') || 0;

        $.post(
            "function.php",
            courseOrSessionObject,
            function (data) {
                if (!data.status) {
                    return;
                }

                currentRow.addClass('success');

                window.setTimeout(function () {
                    currentRow.removeClass('success');
                }, 3000);
            },
            "json"
        );
    });
});
