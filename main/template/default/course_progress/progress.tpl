{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
{{ data | var_dump }}
<div id="course-progress" class="thematic">
    <div class="row">
        <div class="col-md-12">
            <div class="score-progress">
                <h2>{{ 'Progress' | get_lang }}: <span id="div_result">{{ score_progress }}</span> %</h2>
            </div>
        </div>
    </div>
</div>
{% endblock %}