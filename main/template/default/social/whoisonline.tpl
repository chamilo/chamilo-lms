{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <div class="row">
        <div class="col-md-12">
            <div class="search-user">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {{ 'SearchUsers' | get_lang}}
                    </div>
                    <div class="panel-body">
                        {{ social_search }}
                    </div>
                </div>
            </div>
            <div id="whoisonline">
                {{ whoisonline }}
            </div>
        </div>
    </div>
{% endblock %}
