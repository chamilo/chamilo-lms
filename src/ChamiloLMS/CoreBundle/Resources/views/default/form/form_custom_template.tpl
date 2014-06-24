{# Widgets #}

{% block form_widget %}
{% spaceless %}
    {% if compound %}
        {{ block('form_widget_compound') }}
    {% else %}
        {{ block('form_widget_simple') }}
    {% endif %}
{% endspaceless %}
{% endblock form_widget %}

{% block form_widget_simple %}
{% spaceless %}
    {% set type = type|default('text') %}
    {% if attr.prepend_input is defined and attr.append_input is defined and attr.prepend_input is not empty and attr.append_input is not empty %}
        {{ block('form_widget_prepend_append_input') }}
    {% elseif attr.prepend_input is defined and attr.prepend_input is not empty %}
        {{ block('form_widget_prepend_input') }}
    {% elseif attr.append_input is defined and attr.append_input is not empty %}
        {{ block('form_widget_append_input') }}
    {% else %}
        <input type="{{ type }}" {{ block('widget_attributes') }} {% if value is not empty %}value="{{ value }}" {% endif %}>
    {% endif %}
    {% if attr.help_block is defined %}
        {{ block('help_block') }}
    {% endif %}
    {% if attr.help is defined %}
        {{ block('help_inline') }}
    {% endif %}
{% endspaceless %}
{% endblock form_widget_simple %}

{% block form_widget_prepend_append_input %}
{% spaceless %}
    <div class="input-prepend input-append">
        <span class="add-on">{{ attr.prepend_input|trans({}, translation_domain) }}</span>
        {% set append_input = attr.append_input|trans({}, translation_domain) %}
        {% set attr = attr|merge({'prepend_input': '', 'append_input': ''}) %}
        {{ block('form_widget_simple') }}
        <span class="add-on">{{ append_input }}</span>
    </div>
{% endspaceless %}
{% endblock form_widget_prepend_append_input %}

{% block form_widget_prepend_input %}
{% spaceless %}
    <div class="input-prepend">
        <span class="add-on">{{ attr.prepend_input|trans({}, translation_domain) }}</span>
        {% set attr = attr|merge({'prepend_input': ''}) %}
        {{ block('form_widget_simple') }}
    </div>
{% endspaceless %}
{% endblock form_widget_prepend_input %}

{% block form_widget_append_input %}
{% spaceless %}
    <div class="input-append">
        {% set append_input = attr.append_input|trans({}, translation_domain) %}
        {% set attr = attr|merge({'append_input': ''}) %}
        {{ block('form_widget_simple') }}
        <span class="add-on">{{ append_input }}</span>
    </div>
{% endspaceless %}
{% endblock form_widget_append_input %}

{% block form_widget_compound %}
{% spaceless %}
    {% if form.parent is empty %}
        {{ form_errors(form) }}
    {% endif %}
    {{ block('form_rows') }}
    {{ form_rest(form) }}
{% endspaceless %}
{% endblock form_widget_compound %}

{% block collection_widget %}
{% spaceless %}
    {% if prototype is defined %}
        {% set attr = attr|merge({'data-prototype': form_row(prototype) }) %}
    {% endif %}
    {{ block('form_widget') }}
{% endspaceless %}
{% endblock collection_widget %}

{% block textarea_widget %}
{% spaceless %}
    <textarea {{ block('widget_attributes') }}>{{ value }}</textarea>
{% endspaceless %}
{% endblock textarea_widget %}

{% block choice_widget %}
{% spaceless %}
    {% if expanded %}
        {{ block('choice_widget_expanded') }}
    {% else %}
        {{ block('choice_widget_collapsed') }}
    {% endif %}
{% endspaceless %}
{% endblock choice_widget %}

{% block choice_widget_expanded %}
{% spaceless %}
    {% set child_vars = {'attr': attr} %}
    {% for child in form %}
        {{ form_label(child, label, child_vars) }}
    {% endfor %}
{% endspaceless %}
{% endblock choice_widget_expanded %}

{% block choice_widget_collapsed %}
{% spaceless %}
    <select {{ block('widget_attributes') }}{% if multiple %} multiple="multiple"{% endif %}>
        {% if empty_value is not none %}
            <option value="">{{ empty_value|trans({}, translation_domain) }}</option>
        {% endif %}
        {% if preferred_choices|length > 0 %}
            {% set options = preferred_choices %}
            {{ block('choice_widget_options') }}
            {% if choices|length > 0 and separator is not none %}
                <option disabled="disabled">{{ separator }}</option>
            {% endif %}
        {% endif %}
        {% set options = choices %}
        {{ block('choice_widget_options') }}
    </select>
{% endspaceless %}
{% endblock choice_widget_collapsed %}

{% block choice_widget_options %}
{% spaceless %}
    {% for group_label, choice in options %}
        {% if choice is iterable %}
            <optgroup label="{{ group_label|trans({}, translation_domain) }}">
                {% set options = choice %}
                {{ block('choice_widget_options') }}
            </optgroup>
        {% else %}
            <option value="{{ choice.value }}"{% if choice is selectedchoice(value) %} selected="selected"{% endif %}>{{ choice.label|trans({}, translation_domain) }}</option>
        {% endif %}
    {% endfor %}
{% endspaceless %}
{% endblock choice_widget_options %}

{% block checkbox_widget %}
{% spaceless %}
    <input type="checkbox" {{ block('widget_attributes') }}{% if value is defined %} value="{{ value }}"{% endif %}{% if checked %} checked="checked"{% endif %}>
{% endspaceless %}
{% endblock checkbox_widget %}

{% block radio_widget %}
{% spaceless %}
    <input type="radio" {{ block('widget_attributes') }}{% if value is defined %} value="{{ value }}"{% endif %}{% if checked %} checked="checked"{% endif %}>
{% endspaceless %}
{% endblock radio_widget %}

{% block datetime_widget %}
{% spaceless %}
    {% if widget == 'single_text' %}
        {{ block('form_widget_simple') }}
    {% else %}
        <div {{ block('widget_container_attributes') }}>
            {{ form_errors(form.date) }}
            {{ form_errors(form.time) }}
            {{ form_widget(form.date) }}
            {{ form_widget(form.time) }}
        </div>
    {% endif %}
{% endspaceless %}
{% endblock datetime_widget %}

{% block date_widget %}
{% spaceless %}
    {% if widget == 'single_text' %}
        {{ block('form_widget_simple') }}
    {% else %}
        <div {{ block('widget_container_attributes') }}>
            {{ date_pattern|replace({
                '{{ year }}': form_widget(form.year),
                '{{ month }}': form_widget(form.month),
                '{{ day }}': form_widget(form.day),
            })|raw }}
        </div>
    {% endif %}
{% endspaceless %}
{% endblock date_widget %}

{% block time_widget %}
{% spaceless %}
    {% if widget == 'single_text' %}
        {{ block('form_widget_simple') }}
    {% else %}
        <div {{ block('widget_container_attributes') }}>
            {{ form_widget(form.hour, { 'attr': { 'size': '1' } }) }}:{{ form_widget(form.minute, { 'attr': { 'size': '1' } }) }}{% if with_seconds %}:{{ form_widget(form.second, { 'attr': { 'size': '1' } }) }}{% endif %}
        </div>
    {% endif %}
{% endspaceless %}
{% endblock time_widget %}

{% block number_widget %}
{% spaceless %}
    {# type="number" doesn't work with floats #}
    {% set type = type|default('text') %}
    {{ block('form_widget_simple') }}
{% endspaceless %}
{% endblock number_widget %}

{% block integer_widget %}
{% spaceless %}
    {% set type = type|default('number') %}
    {{ block('form_widget_simple') }}
{% endspaceless %}
{% endblock integer_widget %}

{% block money_widget %}
{% spaceless %}
    {{ money_pattern|replace({ '{{ widget }}': block('form_widget_simple') })|raw }}
{% endspaceless %}
{% endblock money_widget %}

{% block url_widget %}
{% spaceless %}
    {% set type = type|default('url') %}
    {{ block('form_widget_simple') }}
{% endspaceless %}
{% endblock url_widget %}

{% block search_widget %}
{% spaceless %}
    {% set attr = attr|merge({'class': (attr.class|default('') ~ ' search-query')|trim}) %}
    {% set type = type|default('search') %}
    {{ block('form_widget_simple') }}
{% endspaceless %}
{% endblock search_widget %}

{% block percent_widget %}
{% spaceless %}
    {% set type = type|default('text') %}
    {{ block('form_widget_simple') }} %
{% endspaceless %}
{% endblock percent_widget %}

{% block password_widget %}
{% spaceless %}
    {% set type = type|default('password') %}
    {{ block('form_widget_simple') }}
{% endspaceless %}
{% endblock password_widget %}

{% block hidden_widget %}
{% spaceless %}
    {% set type = type|default('hidden') %}
    {{ block('form_widget_simple') }}
{% endspaceless %}
{% endblock hidden_widget %}

{% block email_widget %}
{% spaceless %}
    {% set type = type|default('email') %}
    {{ block('form_widget_simple') }}
{% endspaceless %}
{% endblock email_widget %}

{# Labels #}

{% block form_label %}
{% spaceless %}
    {% if not compound %}
        {% set label_attr = label_attr|merge({'for': id}) %}
    {% endif %}
    {% if required %}
        {% set label_attr = label_attr|merge({'class': (label_attr.class|default('') ~ ' required')|trim}) %}
    {% endif %}
    {% if form_type is defined and form_type == 'horizontal' %}
        {% set label_attr = label_attr|merge({'class': (label_attr.class|default('') ~ ' control-label')|trim}) %}
    {% endif %}
    {% if label is empty %}
        {% set label = name|humanize %}
    {% endif %}
    <label{% for attrname, attrvalue in label_attr %} {{ attrname }}="{{ attrvalue }}"{% endfor %}>{{ label|trans({}, translation_domain) }}</label>
{% endspaceless %}
{% endblock form_label %}

{% block checkbox_label %}
{% spaceless %}
    {% if not compound %}
        {% set label_attr = label_attr|merge({'for': id}) %}
    {% endif %}
    {% set label_attr = label_attr|merge({'class': (label_attr.class|default('') ~ ' checkbox')|trim}) %}
    {% if attr.inline is defined and attr.inline %}
        {% set label_attr = label_attr|merge({'class': (label_attr.class|default('') ~ ' inline')|trim}) %}
    {% endif %}
    {% if required %}
        {% set label_attr = label_attr|merge({'class': (label_attr.class|default('') ~ ' required')|trim}) %}
    {% endif %}
    {% if label is empty %}
        {% set label = name|humanize %}
    {% endif %}
{% endspaceless %}
    <label{% for attrname, attrvalue in label_attr %} {{ attrname }}="{{ attrvalue }}"{% endfor %}>
        {{ form_widget(form) }} {{ label|trans({}, translation_domain) }}
    </label>
{% endblock checkbox_label %}

{% block radio_label %}
{% spaceless %}
    {% if not compound %}
        {% set label_attr = label_attr|merge({'for': id}) %}
    {% endif %}
    {% set label_attr = label_attr|merge({'class': (label_attr.class|default('') ~ ' radio')|trim}) %}
    {% if required %}
        {% set label_attr = label_attr|merge({'class': (label_attr.class|default('') ~ ' required')|trim}) %}
    {% endif %}
    {% if label is empty %}
        {% set label = name|humanize %}
    {% endif %}
{% endspaceless %}
    <label{% for attrname, attrvalue in label_attr %} {{ attrname }}="{{ attrvalue }}"{% endfor %}>
        {{ form_widget(form) }} {{ label|trans({}, translation_domain) }}
    </label>
{% endblock radio_label %}


{# Rows #}

{% block repeated_row %}
{% spaceless %}
    {#
No need to render the errors here, as all errors are mapped
to the first child (see RepeatedTypeValidatorExtension).
#}
    {{ block('form_rows') }}
{% endspaceless %}
{% endblock repeated_row %}

{% block form_row %}
{% spaceless %}
    {% if form_type is defined and form_type == 'inline' %}
        {{ block('inline_row') }}
    {% elseif form_type is defined and form_type == 'horizontal' %}
        {{ block('horizontal_row') }}
    {% else %}
        {{ form_label(form) }}
        {{ form_widget(form) }}
        {{ form_errors(form) }}
    {% endif %}
{% endspaceless %}
{% endblock form_row %}

{% block inline_row %}
    {{ form_widget(form) }}&nbsp;
{% endblock inline_row %}

{% block horizontal_row %}
{% spaceless %}
    <div class="control-group{% if errors|length %} error{% endif %}">
        {{ form_label(form) }}
        <div class="controls">
            {{ form_widget(form) }}
            {{ form_errors(form) }}
        </div>
    </div>
{% endspaceless %}
{% endblock horizontal_row %}

{% block checkbox_row %}
{% spaceless %}
    {#
In order to make the click area bigger, the checkbox is
placed inside the label. See checkbox_label block.
#}
    {% if form_type is defined and form_type == 'horizontal' %}
        {{ block('horizontal_checkbox_row') }}
    {% else %}
        {{ form_label(form) }}
        {{ form_errors(form) }}
    {% endif %}
{% endspaceless %}
{% endblock checkbox_row %}

{% block horizontal_checkbox_row %}
{% spaceless %}
    <div class="control-group">
        <div class="controls">
            {{ form_label(form) }}
            {{ form_errors(form) }}
        </div>
    </div>
{% endspaceless %}
{% endblock horizontal_checkbox_row %}

{% block search_row %}
{% spaceless %}
    {{ form_widget(form) }}
    {{ form_errors(form) }}
{% endspaceless %}
{% endblock search_row %}

{% block hidden_row %}
    {{ form_widget(form) }}
{% endblock hidden_row %}

{# Misc #}

{% block form_enctype %}
{% spaceless %}
    {% if multipart %}enctype="multipart/form-data"{% endif %}
{% endspaceless %}
{% endblock form_enctype %}

{% block form_errors %}
{% spaceless %}
    {% if errors|length == 1 %}
    <span class="help-inline">{{
        errors[0].messagePluralization is null
            ? errors[0].messageTemplate|trans(errors[0].messageParameters, 'validators')
            : errors[0].messageTemplate|transchoice(errors[0].messagePluralization, errors[0].messageParameters, 'validators')
    }}</span>
    {% elseif errors|length > 0 %}
    <ul class="help-block">
        {% for error in errors %}
            <li>{{
                error.messagePluralization is null
                    ? error.messageTemplate|trans(error.messageParameters, 'validators')
                    : error.messageTemplate|transchoice(error.messagePluralization, error.messageParameters, 'validators')
            }}</li>
        {% endfor %}
    </ul>
    {% endif %}
{% endspaceless %}
{% endblock form_errors %}

{% block form_rest %}
{% spaceless %}
    {% for child in form %}
        {% if not child.rendered %}
            {{ form_row(child) }}
        {% endif %}
    {% endfor %}
{% endspaceless %}
{% endblock form_rest %}

{% block help_block %}
{% spaceless %}
    {% if attr.help_block is defined %}
    <span class="help-block">{{ attr.help_block|trans({}, translation_domain) }}</span>
    {% endif %}
{% endspaceless %}
{% endblock help_block %}

{% block help_inline %}
{% spaceless %}
    {% if attr.help is defined %}
    <span class="help-inline">{{ attr.help|trans({}, translation_domain) }}</span>
    {% endif %}
{% endspaceless %}
{% endblock help_inline %}

{# Support #}

{% block form_rows %}
{% spaceless %}
    {% set child_vars = {} %}
    {% if form_type is defined %}
        {% set child_vars = child_vars|merge({'form_type': form_type}) %}
    {% else %}
        {% set child_vars = child_vars|merge({'form_type': 'horizontal'}) %}
    {% endif %}

    {% for child in form %}
        {{ form_row(child, child_vars) }}
    {% endfor %}
{% endspaceless %}
{% endblock form_rows %}

{% block widget_attributes %}
{% spaceless %}
    id="{{ id }}" name="{{ full_name }}"{% if read_only %} readonly="readonly"{% endif %}{% if disabled %} disabled="disabled"{% endif %}{% if required %} required="required"{% endif %}{% if max_length %} maxlength="{{ max_length }}"{% endif %}{% if pattern %} pattern="{{ pattern }}"{% endif %}
    {% for attrname, attrvalue in attr %}{% if attrname in ['placeholder', 'title'] %}{{ attrname }}="{{ attrvalue|trans({}, translation_domain) }}" {% else %}{{ attrname }}="{{ attrvalue }}" {% endif %}{% endfor %}
{% endspaceless %}
{% endblock widget_attributes %}

{% block widget_container_attributes %}
{% spaceless %}
    {% if id is not empty %}id="{{ id }}" {% endif %}
    {% for attrname, attrvalue in attr %}{{ attrname }}="{{ attrvalue }}" {% endfor %}
{% endspaceless %}
{% endblock widget_container_attributes %}

{# Deprecated in Symfony 2.1, to be removed in 2.3 #}

{% block generic_label %}{{ block('form_label') }}{% endblock %}
{% block widget_choice_options %}{{ block('choice_widget_options') }}{% endblock %}
{% block field_widget %}{{ block('form_widget_simple') }}{% endblock %}
{% block field_label %}{{ block('form_label') }}{% endblock %}
{% block field_row %}{{ block('form_row') }}{% endblock %}
{% block field_enctype %}{{ block('form_enctype') }}{% endblock %}
{% block field_errors %}{{ block('form_errors') }}{% endblock %}
{% block field_rest %}{{ block('form_rest') }}{% endblock %}
{% block field_rows %}{{ block('form_rows') }}{% endblock %}