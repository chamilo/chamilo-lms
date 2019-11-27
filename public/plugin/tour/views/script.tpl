{% if tour.show_tour %}
    <script type="text/javascript">
        var chamiloTour = (function() {
            var intro = null;
            var $btnStart = null;

            var setSteps = function (stepsData) {
                var steps = new Array();

                $.each(stepsData, function () {
                    var step = this;

                    if (step.element) {
                        if ($(step.element).length > 0) {
                            steps.push(step);
                        }
                    } else {
                        steps.push(step);
                    }
                });

                return steps;
            };

            return {
                init: function(pageClass) {
                    $.getJSON('{{ tour.web_path.steps_ajax }}', {
                        'page_class': pageClass
                    }, function(response) {
                        intro = introJs();
                        intro.setOptions({
                            steps: setSteps(response),
                            nextLabel: '{{ 'Next' | get_lang }}',
                            prevLabel: '{{ 'Prev' | get_lang }}',
                            skipLabel: '{{ 'Skip' | get_lang }}',
                            doneLabel: '{{ 'Done' | get_lang }}'
                        });
                        intro.oncomplete(function () {
                            $.post('{{ tour.web_path.save_ajax }}', {
                                page_class: pageClass
                            }, function () {
                                $btnStart.remove();
                            });
                        });

                        $btnStart = $('<button>', {
                            class: 'tour-warning',
                            html: '<img src="{{ _p.web }}/plugin/tour/resources/tour-chamilo.png">{{ 'StartButtonText' | get_lang }}',
                            click: function(e) {
                                e.preventDefault();

                                intro.start();
                            }
                        }).appendTo('#tour-button-container');
                    });
                }
            };
        })();

        $(function () {
            var pages = {{ tour.pages }};

            $.each(pages, function(index, page) {
                var thereIsSelectedPage = $(page.pageClass).length > 0;

                if (thereIsSelectedPage && page.show) {
                    $('<link>', {
                        href: '{{ tour.web_path.intro_css }}',
                        rel: 'stylesheet'
                    }).appendTo('head');

                    {% if tour.web_path.intro_theme_css is not null %}
                        $('<link>', {
                            href: '{{ tour.web_path.intro_theme_css }}',
                            rel: 'stylesheet'
                        }).appendTo('head');
                    {% endif %}

                    $.getScript('{{ tour.web_path.intro_js }}', function() {
                        chamiloTour.init(page.pageClass);
                    });
                }
            });
        });
    </script>

    <div id="tour-button-container"></div>
{% endif %}
