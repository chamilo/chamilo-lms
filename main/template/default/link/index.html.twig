
{{javascript}}

<script type="text/javascript">

    {% if is_allowed_to_edit %}
        var sec_token = '{{sec_token}}';
    {% endif %}

$(function() {
	$('.collapsible .head').click(function(e) {
        $(this).toggleClass('collapsed');
        $(this).next().toggle();
		return true;
	});

    {% if is_allowed_to_edit %}
        $(".categories.sortable" ).sortable({
            axis: 'y',
            handle: '.handle',
            //placeholder: 'ui-state-highlight',
            update: function(event, ui) {
                var c_id = 0;
                var ids = [];
                var items;
                items = $(this).children('li');
                items.each(function(index, li){
                    li = $(li);
                    var id;
                    id = li.attr('data-id');
                    c_id = li.attr('data-c_id');
                    ids.push(id);
                });

                LinkCategory.sort(c_id, ids, message.update);
            }
        });
        $(".categories.sortable" ).disableSelection();

        $(".links.sortable" ).sortable({
            axis: 'y',
            placeholder: 'ui-state-highlight',
            update: function(event, ui) {
                var c_id = 0;
                var ids = [];
                var items;
                items = $(this).children('li');
                items.each(function(index, li){
                    li = $(li);
                    var id;
                    id = li.attr('data-id');
                    c_id = li.attr('data-c_id');
                    ids.push(id);
                });

                Link.sort(c_id, ids, message.update);
            }
        });
        $(".links.sortable" ).disableSelection();

    {% endif %}

});

 function expand_all(){
    $('.collapsible .head').removeClass('collapsed').next().show();
 }

 function collapse_all(){
    $('.collapsible .head').addClass('collapsed').next().hide();
 }

 function delete_category(name){
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
    };
    LinkCategory.del(c_id, id, f);
 }

 function delete_all(){
    if(!confirm("{{'ConfirmYourChoice'|get_lang}}")){
        return false;
    }

    var f = function(data){
        if(data.success){
            var item = $('.data');
            item.remove();
        }
        message.update(data);
    };
    Link.delete_by_course({{c_id}}, {{session_id}}, f);

 }

 function delete_link(name, btn){
    if(!confirm("{{'ConfirmYourChoice'|get_lang}}")){
        return false;
    }

    var item = $('#'+name);
    var id = item.attr('data-id');
    var c_id = item.attr('data-c_id');

    var f = function(data){
        if(data.success){
            item.remove();
        }else{
            $(btn).removeClass("loading");
        }
        message.update(data);
    };
    $(btn).addClass("loading");
    Link.del(c_id, id, f);
 }

function switch_li_visibility(name, btn)
{
    var li = $('#'+name);
    var id = li.attr('data-id');
    var c_id = li.attr('data-c_id');

    var is_visible = !li.hasClass('text-muted')

    var f = function(data){
        if(data.success){
            var btn =  $('.btn.visibility', li);
            if(is_visible){
                btn.addClass('show');
                btn.removeClass('hide');
                li.addClass('text-muted');
                li.removeClass('visible');
            }else{
                btn.removeClass('show');
                btn.addClass('hide');
                li.removeClass('text-muted');
                li.addClass('visible');
            }
        }
        message.update(data);
        $(btn).removeClass("loading");
    };
    if(is_visible){
        Link.hide(c_id, id, f);
    }else{
        Link.show(c_id, id, f);
    }
    $(btn).addClass("loading");
}

function validate_link(name, btn){
    var li = $('#'+name);
    var id = li.attr('data-id');
    var c_id = li.attr('data-c_id');

    var f = function(data){
        if(data.success){
            li.addClass('valid');
            li.removeClass('invalid');
        }else{
            li.addClass('invalid');
            li.removeClass('valid');
        }
        message.update(data);
        $(btn).removeClass("loading");
    };
    $(btn).addClass("loading");
    Link.validate(c_id, id, f);
}

</script>

<div id="messages">
{% for message in messages %}
    {{ message }}
{% endfor %}
</div>

<div class="btn-toolbar actions-bar" >
    {% if is_allowed_to_edit %}
        <div class="btn-group edit">
            <a href="{{root}}&amp;action=add_category" class="btn new_folder" title="{{'AddCategory'|get_lang}}">
                <em class="size-32 icon-new-folder"></em>
            </a>
            <a href="{{root}}&amp;action=add_link" class="btn new_link" title="{{'AddLink'|get_lang}}">
                <em class="size-32 icon-new-link"></em>
            </a>
            <a href="javascript:void(0)" onclick="delete_all();return false;" class="btn btn-default" title="{{'DeleteAll'|get_lang}}">
                <em class="size-32 icon-delete-all"></em>
            </a>
            <a href="{{root}}&amp;action=import_csv" class="btn import_csv" title="{{'ImportCSV'|get_lang}}">
                <em class="size-32 icon-import-csv"></em>
            </a>
            <a href="{{root}}&amp;action=export_csv" class="btn" title="{{'ExportAsCSV'|get_lang}}">
                <em class="size-32 icon-export-csv"></em>
            </a>
        </div>
    {% endif %}
    <div class="btn-group">
        <a href="javascript:void(0)" onclick="expand_all();return false;" class="btn btn-default" title="{{'ShowAll'|get_lang}}">
            <em class="size-32 icon-expand"></em>
        </a>
        <a href="javascript:void(0)" onclick="collapse_all();return false;" class="btn btn-default" title="{{'ShowNone'|get_lang}}">
            <em class="size-32 icon-collapse"></em>
        </a>
    </div>
</div>

<div class="data">
    <ul style="list-style: none; margin-left:0;" class="links sortable">
        {% for link in links %}
            {% set link_class %}
                {% if loop.first %}first{% endif %}
                {% if loop.last %}last{% endif %}
                {% if loop.index is odd %}odd{% else %}even{% endif %}
                {% if link.is_visible() %}visible{% else %}invisible{% endif %}
            {% endset %}

            <li id="link_{{link.id}}" class="link {{link_class}}" data-id="{{link.id}}" data-c_id="{{link.c_id}}" data-type="link" >
                <a class="icon" target="{{link.target}}" href="{{root}}&amp;action=go&amp;id={{link.id}}&amp;c_id={{link.c_id}}">
                    {{ 'link.png' |icon(22)}}
                </a>
                <a class="title" target="{{link.target}}" href="{{root}}&amp;action=go&amp;id={{link.id}}&amp;c_id={{link.c_id}}">
                    {%if link.title %}{{link.title}}{% else %}{{link.url}}{%endif%}
                </a>
                {% if link.session_id %}
                    {{session_image}}
                {% endif %}
                <div class="status" style="display:inline-block;">&nbsp;</div>
                {% if is_allowed_to_edit %}
                    <div style="float:right;">
                        <a href="javascript:void(0)" onclick="validate_link('link_{{link.id}}', this);return false;"
                        title="{{'CheckURL'|get_lang}}"
                        class="btn validate_link">

                        </a>
                        <a href="{{root}}&amp;action=edit_link&amp;id={{link.id}}&amp;c_id={{link.c_id}}"
                        title="{{'Edit'|get_lang}}"
                        class="">
                            <em class="size-22 icon-edit"></em>
                        </a>
                        <a href="javascript:void(0)" onclick="switch_li_visibility('link_{{link.id}}', this);return false;"
                        class="btn visibility {%if link.visibility == 1%}hide{%else%}show{%endif%}">

                        </a>
                        <a href="javascript:void(0)" onclick="delete_link('link_{{link.id}}', this);return false;" title="{{'Delete'|get_lang}}" class="">
                            <em class="size-22 icon-delete"></em>
                        </a>
                    </div>
                {% endif %}
                <div class="description">{{link.description}}</div>
            </li>
        {% endfor%}
    </ul>

    <ul id="link_categories" class="categories sortable" style="list-style: none; margin-left:0;">
    {% for category in categories %}
        <li id="category_{{category.id}}" class="link_category collapsible" data-id="{{category.id}}" data-c_id="{{category.c_id}}" data-type="category" >
            <div class="head handle collapsed">
                {% if is_allowed_to_edit %}
                    <div style="float:right;">
                        <a href="{{root}}&amp;action=edit_category&amp;id={{category.id}}&amp;c_id={{category.c_id}}"
                        onclick="event.stopPropagation();"
                        title="{{'Edit'|get_lang}}"
                        class="">
                            <em class="size-22 icon-edit"></em>
                        </a>
                        <a href="javascript:void(0)"
                        onclick="delete_category('category_{{category.id}}');event.stopPropagation();return false;"
                            title="{{'Delete'|get_lang}}"
                            class="">
                            <em class="size-22 icon-delete"></em>
                        </a>
                    </div>
                {% endif %}
                <h3>
                    <a href="{{root}}&amp;action=view&amp;id={{category.id}}&amp;c_id={{category.c_id}}">{{category.category_title|escape}}</a>
                </h3>
                {{category.description}}
            </div>
            <div class="body" style="display:none;">
                <ul style="list-style: none; margin-left:0;" class="links sortable">
                    {% for link in category.links %}
                    {% set link_class %}
                        {% if loop.first %}first{% endif %}
                        {% if loop.last %}last{% endif %}
                        {% if loop.index is odd %}odd{% else %}even{% endif %}
                        {% if link.is_visible() %}visible{% else %}invisible{% endif %}
                    {% endset %}

                        <li id="link_{{link.id}}" class="link {{link_class}}" data-id="{{link.id}}" data-c_id="{{link.c_id}}" data-type="link" >
                            <a class="icon" target="{{link.target}}" href="{{root}}&amp;action=go&amp;id={{link.id}}&amp;c_id={{link.c_id}}">
                                {{ 'link.png' |icon(22) }}
                            </a>
                            <a class ="title" target="{{link.target}}" href="{{root}}&amp;action=go&amp;id={{link.id}}&amp;c_id={{link.c_id}}">
                                {%if link.title %}{{link.title}}{% else %}{{link.url}}{%endif%}
                            </a>
                            {% if link.session_id %}
                                {{session_image}}
                            {% endif %}
                            <div class="status" style="display:inline-block;">&nbsp;</div>
                            {% if is_allowed_to_edit %}
                                <div style="float:right;">
                                    <a href="javascript:void(0)" onclick="validate_link('link_{{link.id}}', this);return false;"
                                    title="{{'CheckURL'|get_lang}}"
                                    class="btn validate_link"></a>
                                    <a href="{{root}}&amp;action=edit_link&amp;id={{link.id}}&amp;c_id={{link.c_id}}"
                                    onclick=""
                                    title="{{'Edit'|get_lang}}"
                                    class="">
                                        <em class="size-22 icon-edit"></em>
                                    </a>
                                    <a href="javascript:void(0)"
                                    onclick="switch_li_visibility('link_{{link.id}}', this);return false;"
                                    class="btn visibility {%if link.visibility == 1%}hide{%else%}show{%endif%}"></a>
                                    <a href="javascript:void(0)"
                                    onclick="delete_link('link_{{link.id}}', this);return false;"
                                    title="{{'Delete'|get_lang}}"
                                    class="">
                                        <em class="size-22 icon-delete"></em>
                                    </a>
                                </div>
                            {% endif %}
                        </li>
                    {% endfor%}
                </ul>
            </div>
            <div class="details "></div>
        </li>
    {% endfor%}
    </ul>
</div>