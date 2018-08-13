{% if hot_sessions %}
<section id="sessions-current" class="grid-courses">
    <div class="page-header">
        <h4>
            {{ "HottestSessions"|get_lang}}
        </h4>
    </div>
    <div class="row">
        {% for session in hot_sessions %}
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="items items-hotcourse">
                <div class="image">
                    <a href="{{ _p.web }}session/{{ session.id }}/about/" title="title-session">
                        <img class="img-responsive"  src="{{ session.image ? _p.web_upload ~ session.image : 'session_default.png'|icon() }}">
                    </a>
                    {% if session.category_name %}
                    <span class="category">{{ session.category_name }}</span>
                    <div class="cribbon"></div>
                    {% endif %}
                </div>
                <div class="description">
                    <div class="block-title">
                        <h5 class="title">
                            <a href="{{ _p.web }}session/{{ session.id }}/about/"
                               title="title-session">{{ session.name }}</a>
                        </h5>
                    </div>
                    <div class="block-info">
                        <i class="fa fa-user"></i> {{ session.users }} {{ "Users"|get_lang }}
                        <i class="fa fa-book"></i> {{ session.lessons }} {{ "Learnpaths"|get_lang }}
                    </div>
                    {% if session.on_sale is defined %}
                        {% if session.on_sale != false %}
                            <div class="toolbar">
                                <div class="buycourses-price">
                                    <span class="label label-primary label-price">
                                        <strong>{{ session.on_sale.iso_code }} {{ session.on_sale.price }}</strong>
                                    </span>
                                </div>
                            </div>
                        {% else %}
                            <div class="toolbar">
                                <div class="buycourses-price">
                                    <span class="label label-primary label-free">
                                        <strong>{{ 'Free'|get_plugin_lang('BuyCoursesPlugin') }}</strong>
                                    </span>
                                </div>
                            </div>
                        {% endif %}
                    {% endif %}
                </div>

            </div>
        </div>
        {% endfor %}
    </div>
</section>
{% endif %}