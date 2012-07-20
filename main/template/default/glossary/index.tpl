{% include 'default/glossary/header.tpl' %}
{% include 'default/glossary/javascript.tpl' %}

<div class="actions" >
    {% if is_allowed_to_edit %}
        <a href="{{root}}&amp;action=add" class=" glossary btn new term" title="{{'Add'|get_lang}}"></a>
        <a href="{{root}}&amp;action=import_csv" class="btn import_csv" title="{{'ImportCSV'|get_lang}}"></a>
        <a href="{{root}}&amp;action=export_csv" class="btn export_csv" title="{{'ExportAsCSV'|get_lang}}"></a>
        <a href="javascript:void(0)" onclick="ui.remove_by_course('entries', this);return false;" class="btn delete_all" title="{{'DeleteAll'|get_lang}}"></a>
    {% endif %}    
    {% if view == 'table' %}
        <a href="{{root}}&amp;view=list" class="btn view text" title="{{'ExportAsCSV'|get_lang}}"></a>
    {% else %}
        <a href="{{root}}&amp;view=table" class="btn view detailed" title="{{'ExportAsCSV'|get_lang}}"></a>
    {% endif %}
    
</div>

{% if view == 'table' %}
    {% include 'default/glossary/table.tpl' %}
{% else %}
    {% include 'default/glossary/list.tpl' %}
{% endif %}
