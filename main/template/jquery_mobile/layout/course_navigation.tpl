{# Course navigation icons #}
{% if show_header == true %}

    {% if show_course_shortcut is not null %}
        <div class="span12">
            {{ show_course_shortcut }}
        </div>
    {% endif %}

    {% if show_course_navigation_menu is not null %}    
        <script>                    
            $(document).ready( function() {
                if (readCookie('menu_state') == 0) {
                    swap_menu();
                }
            });                    
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
            function swap_menu() {
                toolnavlist_el = document.getElementById('toolnav');
                center_el = document.getElementById('center');
                swap_menu_link_el = document.getElementById('swap_menu_link');

                if (toolnavlist_el.style.display == 'none') {
                    toolnavlist_el.style.display = '';
                    if (center_el) {
                        center_el.style.margin = '0 190px 0 0';
                    }
                    swap_menu_link_el.innerHTML = '{{'Hide'|get_lang}}';
                    createCookie('menu_state',1,10);
                } else {
                    toolnavlist_el.style.display = 'none';
                    if (center_el) {
                        center_el.style.margin = '0 0 0 0';
                    }
                    swap_menu_link_el.innerHTML = '{{'Show'|get_lang}}';
                    createCookie('menu_state',0,10);
                }
            }
            document.write('<div class="span12 pull-right"> <a class="btn" href="javascript: void(0);" id="swap_menu_link" onclick="javascript: swap_menu();">{{'Hide'|get_lang}}<\/a></div>');                    
            </script>                    
    {{ show_course_navigation_menu }}
    {% endif %}
{% endif %}