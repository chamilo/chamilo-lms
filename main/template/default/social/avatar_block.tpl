<div class="social-profile">
    {% if show_group %}
    <div class="img-profile">
        <a href="{{ _p.web_main ~ 'social/group_view.php?id=' ~ group_id }}">
            <img src="{{ user_group_image.file }}" class="img-responsive img-circle">
        </a>
    </div>
    <div class="group-title"><h4>{{ user_group.name }}</h4></div>
    <div class="group-description">{{ user_group.description }}</div>
    {% if user_is_group_admin %}
        <div id="edit_image">
            <a class="btn btn-default" href="{{ _p.web_main ~ 'social/group_edit.php?id=' ~ group_id }}">
                {{ 'EditGroup'|get_lang }}
            </a>
        </div>
    {% endif %}
    {% elseif show_user %}
        <a href="{{ user_image.big }}" class="expand-image">
            <img class="img-responsive img-circle" src="{{ user_image.normal }}">
        </a>
    {% endif %}
</div>
