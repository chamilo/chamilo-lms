<div class="avatar-profile">
    {% if show_group %}
        <a href="{{ _p.web_main ~ 'social/group_view.php?id=' ~ group_id }}">
            <img src="{{ user_group_image.file }}" class="social-groups-image">
        </a>

        {% if user_is_group_admin %}
            <div id="edit_image">
                <a href="{{ _p.web_main ~ 'social/group_edit.php?id=' ~ group_id }}">
                    {{ 'EditGroup'|get_lang }}
                </a>
            </div>
        {% endif %}
    {% elseif show_user %}
        <a href="{{ user_image.big }}" class="expand-image">
            <img class="img-responsive" src="{{ user_image.normal }}">
        </a>
    {% endif %}
</div>
