{% include 'default/notebook/header.tpl' %}
{% include 'default/notebook/javascript.tpl' %}

<div class="btn-toolbar actions-bar" >
    {% if is_allowed_to_edit %}
        <div class="btn-group edit">
        <a href="{{root}}&amp;action=add" class="notebook btn new term" title="{{'Add'|get_lang}}">
            <i class="size-32 icon-new-note"></i>
        </a>
            <a href="{{root}}&amp;action=import_csv" class="btn" title="{{'ImportCSV'|get_lang}}">
                <i class="size-32 icon-import-csv"></i>
            </a>
            <a href="{{root}}&amp;action=export_csv" class="btn" title="{{'ExportAsCSV'|get_lang}}">
                <i class="size-32 icon-export-csv"></i>
            </a>
            <a href="javascript:void(0)" onclick="ui.remove_by_course('entries', this);return false;" class="btn delete_all" title="{{'DeleteAll'|get_lang}}">
                <i class="size-32 icon-delete-all"></i>
            </a>
        </div>
    {% endif %}
    <div class="btn-group sort">
        <a href="{{root}}&amp;action=index&amp;sort_column=creation_date&amp;sort_direction={%if sort_direction == 'ASC' and sort_column == 'creation_date' %}DESC{% else %}ASC{% endif %}"
        class="btn" 
        title="{{'SorteByCreatedDate'|get_lang}}">
                <i class="size-32 icon-sort-by-created-date"></i>
        </a>
        <a href="{{root}}&amp;action=index&amp;sort_column=update_date&amp;sort_direction={%if sort_direction == 'ASC' and sort_column == 'update_date' %}DESC{% else %}ASC{% endif %}" 
        class="btn" 
        title="{{'SorteByUpdatedDate'|get_lang}}">
                <i class="size-32 icon-sort-by-modified-date"></i>
        </a>
        <a href="{{root}}&amp;action=index&amp;sort_column=title&amp;sort_direction={%if sort_direction == 'ASC' and sort_column == 'title' %}DESC{% else %}ASC{% endif %}" 
        class="btn" 
        title="{{'SortByTitle'|get_lang}}">
                <i class="size-32 icon-sort-by-title"></i>
        </a>
    </div>
</div>

{% include 'default/notebook/list.tpl' %}

