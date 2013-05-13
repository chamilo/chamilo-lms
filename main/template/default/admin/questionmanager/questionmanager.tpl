{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <div class="row">
        <div id="tabs-1" class="span6">
            <div class="well_border">
                <h4>{{ 'Admin'  | get_lang }}</h4>
                <ul>
                    <li>
                        <a href="{{ app.url_generator.generate('admin_questions') }}">
                        {{ 'Questions' | get_lang }}</a>
                    </li>
                    <li><a href="user_list.php">{{ 'ExtraFields' | get_lang }}</a></li>
                    <li><a href="{{ app.url_generator.generate('admin_category_new')}}">{{ 'AddACategory' | get_lang }}</a></li>
                </ul>

            </div>
        </div>
    </div>
{% endblock %}
