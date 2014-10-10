<script type="text/javascript">
    var chamiloTour = (function() {
        var intro = null;
        var currentPageClass = '';
        var $btnStart = null;

        return {
            init: function(pageClass) {
                currentPageClass = pageClass;

                intro = introJs();
                intro.oncomplete(function () {
                    $.post('{{ tour.web_path.save_ajax }}', {
                        page_class: currentPageClass
                    }, function () {
                        $btnStart.remove();
                    });
                });
            },
            showStartButton: function() {
                $btnStart = $('<button>', {
                    class: 'btn btn-primary btn-large',
                    text: '{{ tour.text.start_button }}',
                    click: function(e) {
                        e.preventDefault();

                        var promise = chamiloTour.setSteps(currentPageClass);

                        $.when(promise).done(function (data) {
                            intro.start();
                        });
                    }
                }).appendTo('#tour-button-cotainer');
            },
            setSteps: function() {
                return $.getJSON('{{ tour.web_path.steps_ajax }}', {
                    'page_class': currentPageClass
                }, function(response) {
                    intro.setOptions({
                        steps: response,
                        nextLabel: '{{ tour.text.next }}',
                        prevLabel: '{{ tour.text.prev }}',
                        skipLabel: '{{ tour.text.skip }}',
                        doneLabel: '{{ tour.text.done }}'
                    });
                });
            }
        };
    })();

    $(document).on('ready', function() {
        var pages = {{ tour.pages }};

        $.each(pages, function(index, page) {
            var thereIsSelectedPage = $(page.pageClass).length > 0;

            if (thereIsSelectedPage && page.show) {
                $('<link>', {
                    href: '{{ tour.web_path.intro_css }}',
                    rel: 'stylesheet'
                }).appendTo('head');

                $('<link>', {
                    href: '{{ tour.web_path.intro_theme_css }}',
                    rel: 'stylesheet'
                }).appendTo('head');

                $.getScript('{{ tour.web_path.intro_js }}', function() {
                    chamiloTour.init(page.pageClass);
                    chamiloTour.showStartButton(page.pageClass);
                });
            }
        });
    });
</script>

<div id="tour-button-cotainer"></div>