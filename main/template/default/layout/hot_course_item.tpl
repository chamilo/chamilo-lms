{% for item in hot_courses %}
    {% if item.title %}
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="items items-hotcourse">
                <div class="image">
                    {% if item.is_registered %}
                        <a title="{{ item.title}}" href="{{ item.course_public_url }}">
                            <img src="{{ item.course_image_large }}" class="img-responsive" alt="{{ item.title }}">
                        </a>
                    {% else %}
                        <img src="{{ item.course_image_large }}" class="img-responsive" alt="{{ item.title }}">
                    {% endif %}
                    {% if item.categoryName != '' %}
                        <span class="category">{{ item.categoryName }}</span>
                        <div class="cribbon"></div>
                    {% endif %}
                    <div class="user-actions">{{ item.description_button }}</div>
                </div>
                <div class="description">
                    <div class="block-title">
                        {% if item.is_registered or _u.is_admin %}
                            <h5 class="title">
                                <a alt="{{ item.title }}" title="{{ item.title }}" href="{{ item.course_public_url }}">
                                    {{ item.title_cut}}
                                </a>
                            </h5>
                        {% else %}
                            <h5 class="title" title="{{ item.title }}">
                                {{ item.title_cut}}
                            </h5>
                        {% endif %}
                    </div>
                    <div class="ranking">
                        {{ item.rating_html }}
                    </div>
                    <div class="toolbar row">
                        <div class="col-sm-4">
                            {% if item.price %}
                                {{ item.price }}
                            {% endif %}
                        </div>
                        <div class="col-sm-8">
                            <div class="btn-group" role="group">
                                {{ item.register_button }}
                                {{ item.unsubscribe_button }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {% endif %}
{% endfor %}
