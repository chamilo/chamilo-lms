{% include 'default/glossary/header.tpl' %}
{% include 'default/glossary/javascript.tpl' %}

<div class="btn-toolbar actions-bar" >
    {% if is_allowed_to_edit %}
        <div class="btn-group edit">
            <a href="{{root}}&amp;action=add" class=" glossary btn" title="{{'Add'|get_lang}}">
                <i class="size-32 icon-new-glossary-term"></i>
            </a>
            <a href="{{root}}&amp;action=import_csv" class="btn import_csv" title="{{'ImportCSV'|get_lang}}">
                <i class="size-32 icon-import-csv"></i>
            </a>
            <a href="{{root}}&amp;action=export_csv" class="btn export_csv" title="{{'ExportAsCSV'|get_lang}}">
                <i class="size-32 icon-export-csv"></i>
            </a>
            <a href="javascript:void(0)" onclick="ui.remove_by_course('entries', this);return false;" class="btn delete_all" title="{{'DeleteAll'|get_lang}}">
                <i class="size-32 icon-delete-all"></i>
            </a>
        </div>
    {% endif %}    
    <div class="btn-group edit">
        {% if view == 'table' %}
            <a href="{{root}}&amp;view=list" class="btn" title="{{'ViewList'|get_lang}}">
                <i class="size-32 icon-view-text"></i>
            </a>
        {% else %}
            <a href="{{root}}&amp;view=table" class="btn" title="{{'ViewTable'|get_lang}}">
                <i class="size-32 icon-view-detailed"></i>
            </a>
        {% endif %}
    </div>
    
</div>

{% if view == 'table' %}
    {% include 'default/glossary/table.tpl' %}
{% else %}
    {% include 'default/glossary/list.tpl' %}
{% endif %}
