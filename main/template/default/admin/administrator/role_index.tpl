<script>
$(function() {
    //$("#settings").tabs();
    $.ajax({
        url:'{{ web_admin_ajax_url }}?a=version',
        success:function(version){
            $(".admin-block-version").html(version);
        }
    });
});
</script>

<div id="settings">
    <div class="row">
    {% for block_item in blocks %}
        <div id="tabs-{{ loop.index }}" class="span6">
            <div class="well_border {{ block_item.class }}">
                <h4>{{ block_item.icon }} {{ block_item.label }}</h4>
                <div style="list-style-type:none">
                    {{ block_item.search_form }}
                </div>
                {% if block_item.items is not null %}
                    <ul>
                    {% for url in block_item.items %}
                        <li>
                            <a href="{{ url.url }}">
                                {{ url.label }}
                            </a>
                        </li>
                    {% endfor %}
                    </ul>
                {% endif %}

                {% if block_item.extra is not null %}
                    <div>
                        {{ block_item.extra }}
                    </div>
                {% endif %}
            </div>
        </div>
    {% endfor %}
    </div>

    <div class="row">
        <div class="span6">
            <div class="well_border">
                <ul>
                    <li>
                        <a href="{{ url('question_score.controller:indexAction') }}">{{ 'Question score name' |trans }}</a>
                    </li>
                    <li>
                        <a href="{{ url('question_score_name.controller:indexAction') }}">{{ 'Question names' |trans }}</a>
                    </li>
                    <li>
                        <a href="{{ url('role.controller:indexAction') }}">{{ 'Roles' |trans }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
