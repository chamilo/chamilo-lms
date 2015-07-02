
           {% if _u.logged == 1 %}
               {% if _u.status != 6 %}
                   <li>
                   {{ message_link }}
                   </li>
               {% endif %}

            {% endif %}
