{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <section id="page-profile">
        <div class="row">
            <div class="col-md-12">
                <div class="section-profile"><i class="fa fa-square"></i> Mi Perfil</div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-7">
                <div class="block">
                    <dl class="dl-horizontal">
                        <dt>Email:</dt>
                        <dd>loremip90@gmail.com</dd>
                        <dt>Contraseña:</dt>
                        <dd>*************</dd>
                        <dt>Fecha de Nacimiento:</dt>
                        <dd>05/31/80</dd>
                        <dt>Género:</dt>
                        <dd>Femenino</dd>
                        <dt>Institución:</dt>
                        <dd>Universidad del Pacífico</dd>
                        <dt>Dirección:</dt>
                        <dd>Calle Spición Llona 2345 - Miraflores</dd>
                    </dl>
                    <div class="tool-profile">
                        <a href="#" class="btn btn-press btn-sm">Editar Perfil</a>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                {{ social_avatar_block }}
            </div>
        </div>
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
