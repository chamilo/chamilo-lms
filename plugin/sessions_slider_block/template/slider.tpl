{% if sessions_slider_block.sessions|length > 0 %}
    <link href="{{ _p.web_plugin }}sessions_slider_block/resources/owl-carousel/owl.carousel.css" rel="stylesheet">
    <link href="{{ _p.web_plugin }}sessions_slider_block/resources/owl-carousel/owl.theme.css" rel="stylesheet">
    <link href="{{ _p.web_plugin }}sessions_slider_block/resources/style.css" rel="stylesheet">
    <div id="slider-sessions">
        <div class="row">
            <div class="col-md-12">
                <h4 class="title-section">{{ "RecommendedCourses"|get_lang }}</h4>
                <a href="{{ _p.web_main ~ 'auth/courses.php' }}" class="more">{{ 'SeeMore'|get_lang }}</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div id="top-session">
                    {% for session in sessions_slider_block.sessions %}
                        <div class="item">
                            <div class="slider-block">
                                <div class="caption">
                                    <h2 class="title-course">{{ session.name }}</h2>
                                </div>
                                <div class="card">
                                    <div class="front">
                                        <div class="thumbnail">
                                            <img src="{{ _p.web_upload ~ session.image_in_slider }}" alt="{{ session.name }}">
                                        </div>
                                    </div>
                                    <div class="back">
                                        <div class="session-description">
                                            {{ session.course_description }}
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-7">
                                                <p class="level">{{ "LevelX"|get_lang|format(session.course_level) }}</p>
                                            </div>
                                            <div class="col-xs-5 text-right">
                                                <a href="{{ session.url_in_slider }}" class="btn btn-primary">{{ "SeeCourse"|get_lang }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {% endfor %}
                </div>
                <script src="{{ _p.web_plugin }}sessions_slider_block/resources/owl-carousel/owl.carousel.min.js"></script>
                <script type="text/javascript">
                    $(document).ready(function () {
                        $("#top-session").owlCarousel({
                            autoPlay: 6000, //Set AutoPlay to 3 seconds
                            items: 3,
                            itemsDesktop: [1199, 3],
                            itemsDesktopSmall: [979, 3],
                            navigation: true,
                            pagination: false,
                            stopOnHover: true,
                            navigationText: ['<', ">"]
                        });
                    });
                </script>
            </div>
        </div>
    </div>
{% endif %}