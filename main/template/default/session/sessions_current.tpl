{% if hot_sessions %}
<section id="sessions-current" class="grid-courses">
    <div class="page-header">
        <h4 class="hot-course-title">
            {{ "HottestSessions"|get_lang}}
        </h4>
    </div>
    <div class="row">
        <!-- Esto repite para mostar 8 sessiones recientes -->
        {% for session in hot_sessions %}
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="items items-hotcourse">
                <div class="image">
                    <a href="{{ _p.web }}session/{{ session.id }}/about/" title="title-session">
                        <img class="img-responsive"  src="{{ session.image ? _p.web_upload ~ session.image : 'session_default.png'|icon() }}">
                    </a>
                    <span class="category">{{ session.category_name }}</span>
                    <div class="cribbon"></div>
                </div>
                <div class="description">
                    <div class="block-title">
                        <h3 class="title">
                            <a href="{{ _p.web }}session/{{ session.id }}/about/"
                               title="title-session">{{ session.name }}</a>
                        </h3>
                    </div>
                    <div class="block-author">

                        <a href="{{ _p.web_main }}inc/ajax/user_manager.ajax.php?a=get_user_popup&user_id={{ session.id_coach }}"
                           class="ajax" data-title="{{ session.firstname }} {{ session.lastname }}">
                            <img src="{{ session.avatar }}"/>
                        </a>
                        <div class="teachers-details">
                            <h5>
                                <a href="#">{{ session.firstname }} {{ session.lastname }}</a>
                            </h5>
                            <p>{{ 'Teacher'|get_lang }}</p>
                        </div>

                    </div>
                    <div class="block-info">
                        <i class="fa fa-user"></i> {{ session.users }} {{ "Users"|get_lang }}
                        &nbsp;
                        &nbsp;
                        <i class="fa fa-book"></i> {{ session.lessons }} {{ "Learnpaths"|get_lang }}
                    </div>
                </div>
            </div>
        </div>
        {% endfor %}
        <!-- Fin de 8 sessiones recientes -->
    </div>
</section>
{% endif %}