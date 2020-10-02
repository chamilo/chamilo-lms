<div class="container">
    {% if plugin_pre_footer is not null %}
    <div id="plugin_pre_footer">
        {{ plugin_pre_footer }}
    </div>
    {% endif %}
    <section class="sub-footer">
        <div class="row" style="display: flex; margin: auto; flex-wrap: wrap">

            <img src="http://chamilo-1.11.ddev.site/main/img/gallery/logo_footer.png" style="width: 150px; height: 50px; margin-right: 50px">

            <p style="max-width: 750px; font-size: large"><strong>Fréquence Écoles</strong> vous donne l’autorisation de copier et d‘utiliser l’ensemble des contenus pédagogiques
                développés pour <strong>Super Demain.</strong></p>

        </div>
        {% if footer_extra_content  %}
        {{ footer_extra_content }}
        {% endif %}
    </section>
</div>

{{ execution_stats }}