{% if _u.status == '5' %}
<!-- list cuourses in sessions users -->
<div class="grid-courses">
<div class="row">
    {% for item in all_courses %}
    <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="items">
            <div class='session'>
                <h6 class='session-name'><i class="fa fa-book" aria-hidden="true"></i> {{ item.session.title }}</h6>
            </div>
            <div class="image">
                <img src="{{ item.image }}" class="img-responsive">
                {% if item.category != '' %}
                <span class="category">{{ item.session.category_id }}</span>
                <div class="cribbon"></div>
                {% endif %}
                <div class="black-shadow">
                    <div class="author-card">  
                    {% for teacher in item.teachers %}
                        {% set counter = counter + 1 %}
                        {% if counter <= 3 %}
                        <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                            <img src="{{ teacher.avatar }}"/>
                        </a>
                        {% endif %}
                    {% endfor %}
                    </div>
                    <div class="session-date">
                        <i class="fa fa-calendar" aria-hidden="true"></i> {{ item.session.date }}
                    </div>
                </div>
            </div>
            <div class="description">
                <h4 class="title">
                    {{ item.title }}    
                </h4>
                <div class="notifications">{{ item.notifications }}</div>
            </div>
        </div>
    </div>
    {% endfor %}
    </div>
</div>
{% else %}
    {{ session | var_dump }}
{% endif %}