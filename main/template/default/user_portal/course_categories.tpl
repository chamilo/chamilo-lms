<p class="lead">{{ 'MyCoursePageCategoryIntroduction'|get_lang }}</p>
<ul class="media-list">
    {% for category in course_categories %}
        {% if category %}
            <li class="media">
                <div class="media-left">
                    <a href="{{ _p.web_self ~ '?' ~ {'category':category.code}|url_encode }}">
                        <img src="{{ category.image ? _p.web_upload ~ category.image : 'session_default.png'|icon(128) }}"
                             alt="{{ category.name }}" width="200" class="media-object">
                    </a>
                </div>
                <div class="media-body">
                    <h4 class="media-heading">{{ category.name }}</h4>
                    <p>{{ category.code }}</p>
                    {% if category.description %}
                        <p>{{ category.description }}</p>
                    {% endif %}
                    <a href="{{ _p.web_self ~ '?' ~ {'category':category.code}|url_encode }}" class="btn btn-default">
                        {{ 'View'|get_lang }} <span class="fa fa-arrow-right" aria-hidden="true"></span>
                    </a>
                </div>
            </li>
        {% endif %}
    {% endfor %}
</ul>
