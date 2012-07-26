<script type="text/javascript">
    
$(document).ready(function() {
    $('#entries').dataTable( {
        "oLanguage": {
            "sLengthMenu": "_MENU_ " + "{{'DataTableLengthMenu'|get_lang}}",
            "sZeroRecords": "{{'DataTableZeroRecords'|get_lang}}",
            "sInfo": "{{'DataTableInfo'|get_lang}}",
            "sInfoEmpty": "{{'DataTableInfoEmpty'|get_lang}}",
            "sInfoFiltered": "{{'DataTableInfoFiltered'|get_lang}}",
            "sSearch": "{{'DataTableSearch'|get_lang}}",
            "oPaginate": {
                "sPrevious": "",
                "sNext": ""
            }
        }
     });
} );

</script>

<table id="entries" class="data_table glossary entries" data-c_id="{{c_id}}" data-session_id="{{session_id}}" >
    <thead>
        <tr>
            <th class="sorting">
                {{'TermName'|get_lang}}
            </th>
            <th class="sorting">                
                {{'TermDefinition'|get_lang}}
            </th>
            {% if is_allowed_to_edit %}
                <th>
                    {{'Actions'|get_lang}}
                </th>
            {% endif %}
        </tr>
    </thead>
    <tbody>
        {% for item in items %}
            <tr id="glossary_{{item.id}}" class="glossary term" data-id="{{item.id}}" data-c_id="{{item.c_id}}" data-type="glossary">
                <td class="title">
                    {{item.name}}
                </td>
                <td class="">
                    {{item.description}}
                </td>
                {% if is_allowed_to_edit %}
                    <td class="td_actions">
                        {% if session_id == item.session_id %}
                            <a  href="{{root}}&amp;action=edit&amp;id={{item.id}}" 
                                title="{{'Edit'|get_lang}}">
                                <i class="size-22 icon-edit"></i>
                            </a>                    
                            <a  href="{{root}}&amp;action=delete&amp;id={{item.id}}" 
                                onclick="ui.remove('glossary_{{item.id}}', this); return false;"
                                title="{{'Delete'|get_lang}}">
                                <i class="size-22 icon-delete"></i>
                            </a>
                        {% else %}
                            <img    title="{{'EditionNotAvailableFromSession'|get_lang}}" 
                                    alt="{{'EditionNotAvailableFromSession'|get_lang}}" 
                                    src="{{'edit_na.png'|icon(22)}}" 
                                    style="vertical-align:middle;">
                        {% endif %}
                    </td>
                {% endif %}
            </tr>
        {% endfor %}
    </tbody>
</table>
<div style="clear:both"></div>

