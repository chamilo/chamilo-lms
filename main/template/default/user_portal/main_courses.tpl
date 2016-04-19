
{% for course in courses %}
<div class="grid-courses">
<div class="row">
    {% for item in course %}
    <div class="col-md-4 col-sm-4 col-xs-6">
        <div class="items">
            <div class="image">
                <img src="{{ item.image }}" class="img-responsive">
                {% if item.category != '' %}
                <span class="category">{{ item.category }}</span>
                <div class="cribbon"></div>
                {% endif %}
                <div class="black_shadow">
                    <div class="author-card">  
                    {% for teacher in item.teachers %}
                        {% set counter = counter + 1 %}
                        {% if counter <= 5 %}
                        <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                            <img src="{{ teacher.avatar }}"/>
                        </a>
                        <div class="teachers-details">
                             <h5>
                                <a href="{{ teacher.url }}" class="ajax" data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                    {{ teacher.firstname }} {{ teacher.lastname }}
                                </a>
                             </h5>
                        </div>       
                        {% endif %}
                    {% endfor %}
                    </div>
                </div>
                {% if item.edit_actions != '' %}
                    <div class="admin-actions"><a class="btn btn-default btn-sm" href="{{ item.edit_actions }}"><i class="fa fa-pencil" aria-hidden="true"></i></a></div>
                {% endif %}
            </div>
            <div class="description">
                <h4 class="title"><a href="{{ item.link }}">{{ item.title }}</a></h4>
                <div class="notifications">{{ item.notifications }}</div>
                
            </div>
        </div>
    </div>
    {% endfor %}
    </div>
</div>
{% endfor %}

