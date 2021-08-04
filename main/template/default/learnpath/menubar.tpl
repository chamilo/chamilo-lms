{% if navigation_in_the_middle %}
    <style>
        .c-menu-left {
            top: 50% !important;
        }
        .menu-button a{
            text-underline: none !important;
        }
    </style>
{% endif %}
<nav id="btn-menu-float" class="c-menu-{{ menu_location }}">
    <div class="circle {{ show_toolbar_by_default == 1 ? 'open' : '' }}">
        {% if show_left_column == 1 %}
            {% set label = "Collapse" | get_lang %}
            {% set icon_expand = "fa-compress" %}
            {% if lp_mode == 'embedframe' %}
                {% set label = "Expand" | get_lang %}
                {% set icon_expand = "fa-expand" %}
            {% endif %}
            <a href="#"
               id="lp-view-expand-toggle"
               class="icon-toolbar expand"
               role="button"
               title="{{ label }}"
            >
                <span class="fa {{ icon_expand }}" aria-hidden="true"></span>
                <span class="sr-only"></span>
            </a>
        {% endif %}
        <a id="home-course"
           title="{{ 'Home'|get_lang }}"
           href="{{ button_home_url }}"
           class="icon-toolbar" target="_self"
           onclick="javascript: window.parent.API.save_asset();">
            <em class="fa fa-home"></em> <span class="hidden-xs hidden-sm"></span>
        </a>
        {{ navigation_bar }}
    </div>
    <a title="{{ 'Options'|get_lang }}" class="menu-button fa fa-bars icons" href="#"></a>
</nav>