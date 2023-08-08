<p>
    {{HelloX}}
</p>

<p>
    {{NotificationInYourForums}}
</p>

{% for course in notifyInfo %}
    <h3>{{ course.courseName }}</h3>
    <ul>
    {% for session in course %}
        {% for thread in session %}
            <li><a href="{{ thread.threadLink }}">
                    <em class="fa fa-chevron-circle-right"></em>{{ thread.threadTitle }}
                </a> : {{ thread.threadNbPost }} {{ NewForumPost }}
            </li>
        {% endfor %}
    {% endfor %}
    </ul>
{% endfor %}
<p>{{SignatureFormula}}</p>
