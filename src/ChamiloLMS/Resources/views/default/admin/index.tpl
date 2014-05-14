{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <div id="settings">
        <div class="row">
            {% for role in app.admin_toolbar_roles %}
                {% if is_granted('ROLE_ADMIN') %}
                    {# Skip session admin #}
                    {% if (role != 'session_manager') %}
                        {{ role | var_dump }}
                        {% include app.template_style ~ "/admin/" ~ role ~ "/role_index.tpl" %}
                    {% endif %}
                {% else %}
                    {% include app.template_style ~ "/admin/" ~ role ~ "/role_index.tpl" %}
                {% endif %}
            {% endfor %}
        </div>
    </div>

    {% if is_granted('ROLE_ADMIN') %}
        <script>
        $(function() {
            $.ajax({
                url:'{{ web_admin_ajax_url }}?a=version',
                success:function(version){
                    $(".admin-block-version").html(version);
                }
            });
        });
        </script>
        <div class="row">
            <div class="col-md-12">
                <div class="well_border">
                <h3>{{ 'VersionCheck' | trans }} </h3>
                <div class="admin-block-version"></div>
                </div>
            </div>
        </div>
    {% endif %}

{% endblock %}
