{% extends "default/layout/main.tpl" %}

{% block body %}

    <div class="col-md-12">
        <div class="badges-tabs">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="{{ _p.web_main }}admin/skill_badge.php">{{ 'Home' | get_lang }}</a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge_list.php">{{ 'Insignias Actuales' | get_lang }}</a>
            </li>
        </ul>
        </div>
        <div class="tab-content">
            <div class="tab-pane active">
                <div class="openbadges-introduction">
                    <h1 class="title"><img src="{{ 'badges.png' | icon(64) }}">Chamilo ahora tiene OpenBadges</h1>
                    <div class="row">
                        <div class="col-xs-12 col-md-6">
                            <p class="lead">
                                Ahora puede obtener reconocimiento de habilidades por aprender en cualquier curso de tu campus virtual Chamilo LMS.
                            </p>
                            <p class="lead">
                                Puede generar insignias para reconocer las habilidades aprendidas de sus usuarios, dar un reconocimiento por el logro, donde ellos
                                podrán mostrar sus capacidades y competencias adquiridas a través de emblemas, que serán visualizadas en su perfil de usuario.
                                Para más información sobre los Open Badges en <a href="http://openbadges.org">http://openbadges.org/</a>.
                            </p>
                        </div>
                        <div class="col-xs-12 col-md-6">
                            <img class="img-responsive" src="{{ 'openbadges.png' | icon() }}">
                        </div>
                    </div>

                    <h3 class="sub-title">Convierta su campus virtual en un lugar de aprendizaje por competencia.</h3>
                    <div class="block-content">
                        <div class="block-title">{{ 'IssuerDetails' | get_lang }}</div>

                        <p>{{ 'Nombre de la organización' | get_lang }} : {{ _s.institution }}</p>
                        <p>{{ 'URL de la plaforma' | get_lang }} : {{ _p.web }}</p>

                        <div class="block-title">{{ 'BackpackDetails' | get_lang }}</div>

                        <p>{{ 'URL de la Mochila' | get_lang }} : {{ backpack }}</p>

                        <p>{{ 'TheBadgesWillBeSentToThatBackpack' | get_lang }}</p>

                    </div>
                </div>
            </div>
        </div>
    </div>
{% endblock %}
