{% if navigation_in_the_middle %}
    <style>
        .c-menu-left {
            top: 50% !important;
        }
    </style>
{% endif %}
<nav id="btn-menu-float" class="c-menu-{{ menu_location }}">
    <div class="circle {{ show_toolbar_by_default == 1 ? 'open' : '' }}">
        {% if show_left_column == 1 %}
            <a href="#" title = "{{ 'Expand'|get_lang }}" id="lp-view-expand-toggle"
               class="icon-toolbar expand" role="button">
                {% if lp_mode == 'embedframe' %}
                    <span class="fa fa-compress" aria-hidden="true"></span>
                    <span class="sr-only">{{ 'Expand'|get_lang }}</span>
                {% else %}
                    <span class="fa fa-expand" aria-hidden="true"></span>
                    <span class="sr-only">{{ 'Expand'|get_lang }}</span>
                {% endif %}
            </a>
        {% endif %}
        <a id="home-course"
           title = "{{ 'Home'|get_lang }}"
           href="{{ button_home_url }}"
           class="icon-toolbar" target="_self"
           onclick="javascript: window.parent.API.save_asset();">
            <em class="fa fa-home"></em> <span class="hidden-xs hidden-sm"></span>
        </a>
        {{ navigation_bar }}
    </div>
    <a class="menu-button fa fa-bars icons" href="#"></a>
</nav>