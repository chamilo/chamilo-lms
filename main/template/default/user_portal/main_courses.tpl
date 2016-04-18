
{% for course in courses %}
    <div class="row">
    {% for item in course %}
    <div class="col-md-4">
        <div class="items">
            <div class="image">
                <img src="{{ item.image }}" class="img-responsive">
            </div>
            <h4 class="title"><a href="{{ item.link }}">{{ item.title }}</a></h4>
            <div class="teachers">
                <ul>
                    {% for teacher in item.teachers %}
                        <li>{{ teacher.email }}</li>
                    {% endfor %}    
                </ul>
            </div>
        </div>
    </div>
    {% endfor %}
    </div>
{% endfor %}


<pre>
    {{ courses | var_dump }}
</pre>

