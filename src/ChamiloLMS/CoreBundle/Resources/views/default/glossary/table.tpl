<script type="text/javascript">

$(document).ready(function() {
    $('#entries').dataTable( {
        "oLanguage": {
            "sLengthMenu": "_MENU_ " + "{{'DataTableLengthMenu'|trans}}",
            "sZeroRecords": "{{'DataTableZeroRecords'|trans}}",
            "sInfo": "{{'DataTableInfo'|trans}}",
            "sInfoEmpty": "{{'DataTableInfoEmpty'|trans}}",
            "sInfoFiltered": "{{'DataTableInfoFiltered'|trans}}",
            "sSearch": "{{'DataTableSearch'|trans}}",
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
                {{'TermName'|trans}}
            </th>
            <th class="sorting">
                {{'TermDefinition'|trans}}
            </th>
            {% if is_allowed_to_edit %}
                <th>
                    {{'Actions'|trans}}
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
                                title="{{'Edit'|trans}}">
                                <i class="size-22 icon-edit"></i>
                            </a>
                            <a  href="{{root}}&amp;action=delete&amp;id={{item.id}}"
                                onclick="ui.remove('glossary_{{item.id}}', this); return false;"
                                title="{{'Delete'|trans}}">
                                <i class="size-22 icon-delete"></i>
                            </a>
                        {% else %}
                            <img    title="{{'EditionNotAvailableFromSession'|trans}}"
                                    alt="{{'EditionNotAvailableFromSession'|trans}}"
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

