<script type="text/javascript">
    var chamiloTour = (function() {
        var intro = null;

        return {
            init: function() {
                intro = introJs();
            },
            showStartButton: function(pageClassName) {
                $('<button>', {
                    class: 'btn btn-primary btn-large',
                    text: '{{ tour.text.start_button }}',
                    click: function(e) {
                        e.preventDefault();

                        var promise = chamiloTour.setSteps(pageClassName);

                        $.when(promise).done(function (data) {
                            intro.start();
                        });
                    }
                }).appendTo('#tour-button-cotainer');
            },
            setSteps: function(pageClassName) {
                return $.getJSON('{{ tour.web_path.steps_ajax }}', {
                    'page_class': pageClassName
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
        $('<link>', {
            href: '{{ tour.web_path.intro_css }}',
            rel: 'stylesheet'
        }).appendTo('head');

        $('<link>', {
            href: '{{ tour.web_path.intro_theme_css }}',
            rel: 'stylesheet'
        }).appendTo('head');

        $.getScript('{{ tour.web_path.intro_js }}', function() {
            chamiloTour.init();
        });

        var pagesClassName = {{ tour.pagesClassName }};

        $.each(pagesClassName, function(index, className) {
            var thereIsSelectedPage = $(className).length > 0;

            if (thereIsSelectedPage) {
                chamiloTour.showStartButton(className);
            }
        });
    });
</script>

<div id="tour-button-cotainer"></div>