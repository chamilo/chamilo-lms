{% if data_list is not empty %}
<div id="learning_path_toc" class="scorm-list">
    <div class="scorm-title">
        <h4>{{ lp_title_scorm }}</h4>
    </div>
    <div class="scorm-body">
        <div id="inner_lp_toc" class="inner_lp_toc scrollbar-light">
            {% for item in data_list %}
            <div id="toc_{{ item.id }}" class="{{ item.class }} item-{{ item.type }}">
                {% if item.type == 'dir' %}
                <div class="section {{ item.css_level }}" title="{{ item.description }}">
                    {{ item.title }}
                </div>
                {% else %}
                <div class="item {{ item.css_level }}" title="{{ item.description }}">
                    <a name="atoc_{{ item.id }}"></a>
                    <a class="items-list" href="#"
                       onclick="switch_item('{{ item.current_id }}','{{ item.id }}'); return false;">
                        {{ item.title }}
                    </a>
                </div>
                {% endif %}
            </div>
            {% endfor %}
        </div>
    </div>
</div>
{% endif %}
{% if data_panel is not empty %}
<div id="learning_path_toc" class="scorm-collapse">
    <div class="scorm-title">
        <h4>
             {{ lp_title_scorm }}
        </h4>
    </div>
    <div class="panel-group" role="tablist" aria-multiselectable="true">
        {% if data_panel.not_parents %}
            <ul class="scorm-collapse-list">
                {% for item in data_panel.not_parents %}
                <li id="toc_{{ item.id }}" class="{{ item.class }} item-{{ item.type }}">
                    <div class="sub-item type-{{ item.type }}">
                        <a name="atoc_{{ item.id }}"></a>
                        <a class="item-action" href="#"
                           onclick="switch_item('{{ item.current_id }}','{{ item.id }}'); return false;">
                            <i class="fa fa-chevron-circle-right" aria-hidden="true"></i>
                            {{ item.title }}
                        </a>
                    </div>
                </li>
                {% endfor %}
            </ul>
        {% endif %}

        {% for item in data_panel.are_parents %}

        <div class="panel panel-default {{ item.parent ? 'lower':'higher' }}" data-lp-id="{{ item.id }}"
             {{ item.parent ? 'data-lp-parent="' ~ item.parent ~ '"' : '' }}>
            <div class="status-heading">
                <div class="panel-heading" role="tab" id="heading-{{ item.id }}">
                    <h4>
                        <a class="item-header" role="button" data-toggle="collapse"
                            data-parent="#scorm-panel{{ item.parent ? '-' ~ item.parent : '' }}"
                            href="#collapse-{{ item.id }}" aria-expanded="true"
                            aria-controls="collapse-{{ item.id }}">
                            {{ item.title }}
                        </a>
                    </h4>
                </div>
            </div>
            <div id="collapse-{{ item.id }}" class="panel-collapse collapse {{ item.parent_current }}"
                 role="tabpanel" aria-labelledby="heading-{{ item.id }}">
                <div class="panel-body">
                    <ul class="list">
                        {% set  counter = 0 %}
                        {% set  final = item.children|length %}
                        {% for subitem in item.children %}
                        {% set  counter = counter + 1 %}
                        <li id="toc_{{ subitem.id }}"
                            class="{{ subitem.class }} {{ subitem.type }} {{ counter == final ? 'final':'' }}">
                            <div class="sub-item item-{{ subitem.type }}">
                                <a name="atoc_{{ subitem.id }}"></a>
                                <a class="item-action" href="#"
                                   onclick="switch_item('{{ subitem.current_id }}','{{ subitem.id }}'); return false;">
                                    <i class="fa fa-chevron-circle-right" aria-hidden="true"></i>
                                    {{ subitem.title }}
                                </a>
                            </div>
                        </li>

                        {% endfor %}
                    </ul>
                </div>
            </div>
        </div>
        {% endfor %}

    </div>
</div>
{% endif %}