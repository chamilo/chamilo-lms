{{javascript}}

<script type="text/javascript">

{% if is_allowed_to_edit %}
    var sec_token = '{{sec_token}}';
{% endif %}

    
 function delete_entry(name, btn){   
    if(!confirm("{{'ConfirmYourChoice'|get_lang}}")){
        return false;
    } 

    var item = $('#'+name);
    var id = item.attr('data-id'); 
    var c_id = item.attr('data-c_id'); 

    var f = function(data){
        if(data.success){
            item.remove();
        }
        message.update(data);
        $(btn).removeClass("loading");
    };
    CourseDescription.del(c_id, id, f);
    $(btn).addClass("loading");
 }

 function delete_all(){
    if(!confirm("{{'ConfirmYourChoice'|get_lang}}")){
        return false;
    } 
    
    var f = function(data){
        if(data.success){
            var item = $('.course_descriptions');
            item.remove();
        }
        message.update(data);
    };
    CourseDescription.delete_by_course({{c_id}}, {{session_id}}, f);
    
 }
 
</script>

{% for message in messages %}
    {{ message }}
{% endfor %}

{% if is_allowed_to_edit %}
    <div class="btn-toolbar actions-bar" >
        <div class="btn-group edit new">
            {% for type in types %}        
                <a href="{{root}}&amp;action=add&amp;description_type={{type.id}}" class="btn "> 
                    <img title="{{type.title}}" alt="{{type.title}}" src="{{type.icon|icon(32)}} ">
                </a>    
            {% endfor %}
        </div>
        <div class="btn-group edit">
            <a href="{{root}}&amp;action=import_csv" class="btn import_csv" title="{{'ImportCSV'|get_lang}}">
                <i class="size-32 icon-import-csv"></i>
            </a>
            <a href="{{root}}&amp;action=export_csv" class="btn export_csv" title="{{'ExportAsCSV'|get_lang}}">
                <i class="size-32 icon-export-csv"></i>
            </a>
            <a href="javascript:void(0)" onclick="delete_all();return false;" class="btn delete_all" title="{{'DeleteAll'|get_lang}}">
                <i class="size-32 icon-delete-all"></i>
            </a>
        </div>
    </div>
{% endif %}

<ul style="list-style: none; margin-left:0;" class="course_descriptions">
{% for description in descriptions %}
    <li id="description_{{description.id}}" class="course_description" data-id="{{description.id}}" data-c_id="{{description.c_id}}" data-type="course_description">
        <div class="title sectiontitle">
            {% if is_allowed_to_edit %}
                <div class="pull-right element-actions">
                {% if session_id == description.session_id %}                    
                    <a href="{{root}}&amp;action=delete&amp;id={{description.id}}" 
                       onclick="delete_entry('description_{{description.id}}', this); return false;"
                       title="{{'Delete'|get_lang}}">
                        <i class="size-22 icon-delete"></i>
                    </a>

                    <a href="{{root}}&amp;action=edit&amp;id={{description.id}}" 
                       title="{{'Edit'|get_lang}}">
                        <i class="size-22 icon-edit"></i>
                    </a>
                {% else %}
                    <img title="{{'EditionNotAvailableFromSession'|get_lang}}" 
                         alt="{{'EditionNotAvailableFromSession'|get_lang}}" 
                         src="{{'edit_na.png'|icon(22)}}" 
                         style="vertical-align:middle;">
                {% endif %}
                </div>
            {% endif %}
            
            <img title="{{description.type.title}}" alt="{{description.type.title}}" src="{{description.type.icon|icon(32)}}" class="icon">
            {{description.title}}
        </div>
        <div class="sectioncomment">
            {{description.content}}
        </div>
    </li>
{% endfor %}
</ul>