{% if data_list is not empty %}
<div id="learning_path_toc" class="scorm-list">
    <div class="scorm-body">
        <div id="inner_lp_toc" class="inner_lp_toc scrollbar-light">
            {% for item in data_list %}
                <div id="toc_{{ item.id }}" class="{{ item.class }}">
                    {% if item.type == 'dir' %}
                        <div class="section {{ item.css_level }}" title="{{ item.description }}">
                            {{ item.title }}
                        </div>
                    {% else %}
                        <div class="item {{ item.css_level }}" title="{{ item.description }}">
                            <a name="atoc_{{ item.id }}"></a>
                            <a class="items-list" href="#" onclick="switch_item('{{ item.current_id }}','{{ item.id }}'); return false;" >
                                {{ item.title }}
                            </a>
                        </div>
                    {% endif %}
                </div>
            {% endfor %}
        </div>
    </div>
</div>
{% else %}
<div class="panel-group" id="scorm-panel" role="tablist" aria-multiselectable="true">
  {% for item in data_panel %}
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="heading-{{ item.id }}">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" data-parent="#scorm-panel" href="#collapse-{{ item.id }}" aria-expanded="true" aria-controls="collapse-{{ item.id }}">
          {{ item.title }}
        </a>
      </h4>
    </div>
    <div id="collapse-{{ item.id }}" class="panel-collapse collapse {{ item.parent_current }}" role="tabpanel" aria-labelledby="heading-{{ item.id }}">
      <div class="panel-body">
            <ul class="section-list">
            {% for subitem in item.children %}
                <li class="list-item {{ subitem.class }} {{ subitem.current }}" title="{{ subitem.title }}">
                    <a name="atoc_{{ subitem.id }}"></a>
                    <a href="#" onclick="switch_item('{{ subitem.current_id }}','{{ subitem.id }}'); return false;" >
                        {{ subitem.title }}
                    </a>
                </li>
            {% endfor %}
            </ul>
      </div>
    </div>
  </div>
  {% endfor %}
</div>
{% endif %}
