{# Course navigation icons - these are two optional features that have to be enabled through admin settings #}
{% if show_header == true %}

    {% if show_course_shortcut is not null %}
        <div class="nav-tools">
            {{ show_course_shortcut }}
        </div>
    {% endif %}

    {% if show_course_navigation_menu is not null %}
        <script>
            function createCookie(name, value, days) {
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime()+(days*24*60*60*1000));
                    var expires = "; expires="+date.toGMTString();
                }
                else var expires = "";
                document.cookie = name+"="+value+expires+"; path=/";
            }
            function readCookie(name) {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++)
                {
                    var c = ca[i];
                    while (c.charAt(0)==' ') c = c.substring(1,c.length);
                    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
                }
                return null;
            }
            
        </script>
    {{ show_course_navigation_menu }}
    {% endif %}
{% endif %}
