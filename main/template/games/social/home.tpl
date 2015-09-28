{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <section id="page-profile">
        {{ social_avatar_block }}

        <div class="row">
            <div class="col-md-12">
                <div class="section-profile"><i class="fa fa-square"></i> {{ 'Skills'|get_lang }}</div>
                <div class="badges">
                    <div class="block">
                        {{ social_skill_block }}
                    </div>
                </div>
            </div>
        </div>
    </section>
{% endblock %}
