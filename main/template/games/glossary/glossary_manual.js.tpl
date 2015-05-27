var ajaxRequestUrl = "{{ _p.web }}main/glossary/glossary_ajax_request.php";
var imageSource = "{{ _p.web_main }}" + "inc/lib/javascript/indicator.gif";
var indicatorImage ='<img src="' + imageSource + '" />';

$(document).ready(function() {

    $('html').on('mouseup', function(e) {
        if(!$(e.target).closest('.popover').length) {
            $('.popover').each(function(){
                $(this.previousSibling).popover('hide');
            });
        }
    });

    $(".glossary").attr('data-toggle', 'popover');
    $(".glossary").popover({
        'content': '{{ 'Loading' | get_lang }}',
        'html' : true
    });

    $("body").on("click", ".glossary", function() {
        is_glossary_name = $(this).html();

        var thisLink = $(this);

        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            type: "POST",
            url: ajaxRequestUrl,
            data: "glossary_name="+is_glossary_name,
            success: function(data) {
                thisLink.attr('data-title', is_glossary_name).data('bs.popover');
                var popover = thisLink.attr('data-content',data).data('bs.popover');
                popover.setContent();
                popover.$tip.addClass(popover.options.placement);
            }
        });
    });
});
