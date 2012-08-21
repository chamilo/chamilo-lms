<script>
/* For licensing terms, see /license.txt */

var url = '{{ url }}';

var skill_to_load_from_get = '{{ skill_id_to_load }}';

function get_skill_info(my_id) {
    var skill = false;
    $.ajax({
        url: url+'&a=get_skill_info&id='+my_id,
        async: false,        
        success: function(json) {
            skill = jQuery.parseJSON(json);
            return skill;
        }
    });    
    return skill;
}

function get_gradebook_info(id) {
    var item = false;
    $.ajax({
        url: url+'&a=get_gradebook_info&id='+id,
        async: false,        
        success: function(json) {
            item = jQuery.parseJSON(json);
            return item;
        }
    });    
    return item;
}

function add_skill(params) {
    $.ajax({
        async: false,
        url: url+'&a=add&'+params,
        success:function(my_id) {
            //Close dialog
            $("#dialog-form").dialog("close");                                      
        }                           
    });
}
    
/* Skill search input in the left menu */
function check_skills_sidebar() {
    //Selecting only selected skills
    $("#skill_id option:selected").each(function() {
        var skill_id = $(this).val();                
        if (skill_id != "" ) {
            $.ajax({
                url: "{{ url }}&a=skill_exists", 
                data: "skill_id="+skill_id,
//                async: false, 
                success: function(return_value) {                   
                    if (return_value == 0 ) {
                        alert("{{ 'SkillDoesNotExist'|get_lang }}");                                                
                        //Deleting select option tag
                        $("#skill_id option[value="+skill_id+"]").remove();                    
                       
                        //Deleting holder
                        $("#skill_search .holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });                        
                    } else {
                        $("#skill_id option[value="+skill_id+"]").remove();
                          
                        //Deleting holder
                        $("#skill_search .holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });
                        
                        if ($('#skill_to_select_id_'+skill_id).length == 0) {
                            skill_info = get_skill_info(skill_id);                        
                            li = fill_skill_search_li(skill_id, skill_info.name);
                            $("#skill_holder").append(li); 
                        }
                    }
                },            
            });                
        }
    });
}

function fill_skill_search_li(skill_id, skill_name) {
    return '<li><input id="skill_to_select_id_'+skill_id+'" rel="'+skill_id+'" name="'+skill_name+'" class="skill_to_select" type="checkbox" value=""> <a href="#" class="load_wheel" rel="'+skill_id+'">'+skill_name+'</a></li>';
}

  
function check_skills_edit_form() {
    //selecting only selected users
    $("#parent_id option:selected").each(function() {
        var skill_id = $(this).val();
        
        if (skill_id != "" ) {
            $.ajax({ 
                async: false,
                url: "{{ url }}&a=skill_exists", 
                data: "skill_id="+skill_id,
                success: function(return_value) {                  
                    if (return_value == 0 ) {
                        alert("{{ 'SkillDoesNotExist'|get_lang }}");                                                
                        //Deleting select option tag
                        $("#parent_id").find('option').remove();
                        //Deleting holder
                        $("#skill_row .holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });                        
                    } else {
                        $("#parent_id").empty();                        
                        $("#skill_edit_holder").find('li').remove();
                        
                        //Deleting holder
                        $("#skill_row .holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });
                        
                        skill = get_skill_info(skill_id);                        
                                                                         
                        $("#skill_edit_holder").append('<li class="bit-box" id="skill_option_'+skill_id+'"> '+skill.name+'</li>');
                        $("#parent_id").append('<option class="selected" selected="selected" value="'+skill_id+'"></option>');
                    }
                },            
            });                
        }
    });
}

function check_gradebook() {
    //selecting only selected users
    $("#gradebook_id option:selected").each(function() {
        var gradebook_id = $(this).val();
        
        if (gradebook_id != "" ) {
            $.ajax({ 
                url: "{{ url }}&a=gradebook_exists", 
                data: "gradebook_id="+gradebook_id,
                success: function(return_value) {                    
                    if (return_value == 0 ) {
                        alert("{{ 'GradebookDoesNotExist'|get_lang }}");                                                
                        //Deleting select option tag
                        $("#gradebook_id option[value="+gradebook_id+"]").remove();                        
                        //Deleting holder
                        $("#gradebook_row .holder li").each(function () {
                            if ($(this).attr("rel") == gradebook_id) {
                                $(this).remove();
                            }
                        });                        
                    } else {                        
                        //Deleting holder                        
                        $("#gradebook_row .holder li").each(function () {
                            if ($(this).attr("rel") == gradebook_id) {
                                $(this).remove();
                            }
                        });
                        
                        if ($('#gradebook_item_'+gradebook_id).length == 0) {
                            gradebook = get_gradebook_info(gradebook_id);                            
                            if (gradebook) {
                                $("#gradebook_holder").append('<li id="gradebook_item_'+gradebook_id+'" class="bit-box"> '+gradebook.name+' <a rel="'+gradebook_id+'" class="closebutton" href="#"></a></li>');
                            }                            
                        }
                    }
                },            
            });                
        }
    });
}

function delete_gradebook_from_skill(skill_id, gradebook_id) {
    $.ajax({
        url: url+'&a=delete_gradebook_from_skill&skill_id='+skill_id+'&gradebook_id='+gradebook_id,
        async: false,        
        success: function(result) {
            //if (result == 1) {
                $('#gradebook_item_'+gradebook_id).remove();
                $("#gradebook_id option").each(function() {
                    if ($(this).attr("value") == gradebook_id) {
                        $(this).remove();
                    }
                });
            //}
        }
    });
}

function submit_profile_search_form() {
    $("#skill_wheel").remove();
    var skill_list = {};

    if ($("#profile_search li").length != 0) {            
        $("#profile_search li").each(function(index) {
            id = $(this).attr("id").split('_')[3];            
            if (id) {                         
                skill_list[index] = id;
            }
        }); 
    }

    if (skill_list.length != 0) {
        skill_list = { 'skill_id' : skill_list };
        skill_params = $.param(skill_list);        

        $.ajax({
            url: url+'&a=profile_matches&'+skill_params,
            async: false,        
            success: function (html) {
                //users = jQuery.parseJSON(users);
                $('#wheel_container').html(html);

            }
        });
    }
    //return skill;
}


$(document).ready(function() {
    /* Skill search */ 
    
    /* Skill item list onclick  */
    $("#skill_holder").on("click", "input.skill_to_select", function() {
        skill_id = $(this).attr('rel');
        skill_name = $(this).attr('name');
        if ($('#profile_match_item_'+skill_id).length == 0 ) {
            $('#profile_search').append('<li class="bit-box" id="profile_match_item_'+skill_id+'">'+skill_name+'  <a rel="'+skill_id+'" class="closebutton" href="#"></a> </li>');        
        } else {            
            $('#profile_match_item_'+skill_id).remove();
        }
    });
    
     /* URL link when searching skills */
    $("#skill_holder").on("click", "a.load_wheel", function() {
        skill_id = $(this).attr('rel');
        skill_to_load_from_get = 0;
        load_nodes(skill_id, main_depth);
    });
    
    
    /* Profile matcher */
    
        
    /* Submit button */
    $("#search_profile_form").submit(function() {
        submit_profile_search_form();
    });
    
    /* Close button in profile matcher items */
    $("#profile_search").on("click", "a.closebutton", function() {
        skill_id = $(this).attr('rel');        
        $('input[id=skill_to_select_id_'+skill_id+']').attr('checked', false);
        $('#profile_match_item_'+skill_id).remove();
        submit_profile_search_form();
    });    
           
    
    /* Wheel skill popup form */
    
    /* Close button in gradebook select */
    $("#gradebook_holder").on("click", "a.closebutton", function() {
        gradebook_id = $(this).attr('rel');
        skill_id = $('#id').attr('value');         
        delete_gradebook_from_skill(skill_id, gradebook_id);        
    });    

    $("#skill_id").fcbkcomplete({
        json_url: "{{ url }}&a=find_skills",
        cache: false,
        filter_case: false,
        filter_hide: true,
        complete_text:"{{ 'StartToType' | get_lang }}",
        firstselected: true,
        //onremove: "testme",
        onselect:"check_skills_sidebar",
        filter_selected: true,
        newel: true
    });    
    
    $("#parent_id").fcbkcomplete({
        json_url: "{{ url }}&a=find_skills",
        cache: false,
        filter_case: false,
        filter_hide: true,
        complete_text:"{{ 'StartToType' | get_lang }}",
        firstselected: true,
        //onremove: "testme",
        onselect:"check_skills_edit_form",
        filter_selected: true,
        newel: true
    });    
    
    $("#gradebook_id").fcbkcomplete({
        json_url: "{{ url }}&a=find_gradebooks",
        cache: false,
        filter_case: false,
        filter_hide: true,
        complete_text:"{{ 'StartToType' | get_lang }}",
        firstselected: true,
        //onremove: "testme",
        onselect:"check_gradebook",
        filter_selected: true,
        newel: true
    });

    //Open dialog
    $("#dialog-form").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 600, 
        height  : 550
    });
        
    /* ...adding "+1" to "y" function's params is really funny */
    /* Exexute the calculation based on a JSON file provided */
    /*d3.json("wheel.json", function(json) {*/
    /** Get the JSON list of skills and work on it */
      
    var my_domain = [1,2,3,4,5,6,7,8,9];
    
    var col = 9;
    var color_patterns = [];
    
    color_patterns[1] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Blues[col]);      
    color_patterns[2] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Purples[col]);
    //color_patterns[2] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Blues[6]);
    color_patterns[3] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Greens[col]);    
    color_patterns[4] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Reds[col]);
    color_patterns[5] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Oranges[col]);
    color_patterns[6] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.YlOrBr[col]);
    
    color_patterns[7] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.YlGn[col]);    
    color_patterns[8] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.YlGnBu[col]);
    color_patterns[9] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.GnBu[col]);
    color_patterns[10] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.BuGn[col]);
    color_patterns[11] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.PuBuGn[col]);
    color_patterns[12] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.PuBu[col]);
    color_patterns[13] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.BuPu[col]);
    color_patterns[14] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.RdPu[col]);
    color_patterns[15] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.PuRd[col]);
    color_patterns[16] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.OrRd[col]);
    color_patterns[17] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.YlOrRd[col]);
    color_patterns[18] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Greys[col]);
        
    //var normal_fill = d3.scale.category10().domain(my_domain);
   
    var main_depth = 1000;
        
    load_nodes(0, main_depth);
    
    function load_nodes(load_skill_id, main_depth) {
        
        /** Define constants and size of the wheel */
        /** Total width of the wheel (also counts for the height) */
        var w = 900,
        h = w,
        r = w / 2,
        /** x/y positionning of the center of the wheel */
        x = d3.scale.linear().range([0, 2 * Math.PI]),
        y = d3.scale.pow().exponent(1.1).domain([0, 1]).range([0, r]),
        /** Padding in pixels before the string starts */
        padding = 3,
        /** Duration of click animations */
        duration = 1000,
        /** Levels to show */
        levels_to_show = 3;

        reduce_top = 1;
        
        /* Locate the #div id element */
        $("#skill_wheel").remove();
        $("#wheel_container").html('');
        $("#wheel_container").append('<div id="skill_wheel"></div>');
        
        var div = d3.select("#skill_wheel");

        /* Remove the image (make way for the dynamic stuff */
        div.select("img").remove();
        
        /* Append an element "svg" to the #vis section */
        var vis = div.append("svg")
        //.attr("class", "Blues")
        .attr("width", w + padding * 2)
        .attr("height", h + padding * 2)
        .append("g")
        .attr("transform", "translate(" + (r + padding) + "," + (r/reduce_top + padding) + ")");

        /* ...update translate variables to change coordinates of wheel's center */
        /* Add a small label to help the user */
        div.append("p")
        .attr("id", "intro")
        .text("{{ "ClickToZoom"|get_lang }}");
        
        /* Generate the partition layout */
        var partition = d3.layout.partition()
        .sort(null)
        /** Value here seems to be a calculation of the size of the elements
                depending on the level of depth they're at. Changing it makes
                elements pass over the limits of others... */
        //.size([1, 2])
        .value(function(d) {
            //return 5.8 - d.depth;
            //When having more than 4 children seems that the code above doesn't work
            return 1;
        });

        /* Generate an arc which will define the whole wheel context */
        var arc = d3.svg.arc()
        .startAngle(function(d) {
            return Math.max(0, Math.min(2 * Math.PI, x(d.x)));
        })
        .endAngle(function(d) {
            return Math.max(0, Math.min(2 * Math.PI, x(d.x + d.dx)));
        })
        .innerRadius(function(d) {
            return Math.max(0, d.y ? y(d.y) : d.y);
        })
        .outerRadius(function(d) {
            return Math.max(0, y(d.y + d.dy));
        });
        
        load_skill_condition = '';
        
        //First the $_GET value
        if (skill_to_load_from_get != 0) {
            load_skill_condition = 'skill_id=' + skill_to_load_from_get;
        }
        
        //The JS load
        if (load_skill_id != 0) { 
            load_skill_condition = 'skill_id=' + load_skill_id;            
        }
                
        d3.json("{{ wheel_url }}&main_depth="+main_depth+"&"+load_skill_condition, function(json) {
            
            /** Define the list of nodes based on the JSON */
            var nodes = partition.nodes({
                children: json                
            });
            
            /* Setting all skills */
            var path = vis.selectAll("path").data(nodes);

            /* Setting all texts */
            var text = vis.selectAll("text").data(nodes);    
            
            /* Setting icons */
            var icon = vis.selectAll("icon").data(nodes);                  
            
            /* Path settings */
            path.enter().append("path")
            .attr("id", function(d, i) {            
                return "path-" + i;
            })
            .attr("d", arc)
            .attr("fill-rule", "evenodd")
            .attr("class", "skill_partition skill_background")
    //        .style("fill", colour)
            .style("fill", function(d) { 
                return set_skill_style(d, 'fill');
            })
            .style("stroke", function(d) {
                return set_skill_style(d, 'stroke');
            })
            .on("mouseover", function(d, i) {                    
                $("#icon-" + i).show();                         
            })
            .on("mouseout", function(d, i) {
                $("#icon-" + i).hide();
            })
            .on("mousedown", function(d, i) {
                //Handles 2 mouse clicks
                handle_mousedown_event(d, path, text, icon, arc, x, y, r, padding);  
            })            
            .on("click", function(d){
                //click_partition(d, path, text, icon, arc, x, y, r, padding);
            });

            /* End setting skills */  
            
            
            /* Text settings */
         
            var textEnter = text.enter().append("text")
            .style("fill-opacity", 1)
            .style("fill", function(d) {                
                return brightness(d3.rgb(d.color)) < 125 ? "#eee" : "#000";                
            })  
            .attr("text-anchor", function(d) {
                return x(d.x + d.dx / 2) > Math.PI ? "end" : "start";
            })
            .attr("dy", ".2em")
            .attr("transform", function(d) {
                /** Get the text details and define the rotation and general position */
                var multiline = (d.name || "").split(" ").length > 1,
                angle = x(d.x + d.dx / 2) * 180 / Math.PI - 90,
                rotate = angle + (multiline ? -.5 : 0);
                return "rotate(" + rotate + ")translate(" + (y(d.y) + padding) + ")rotate(" + (angle > 90 ? -180 : 0) + ")";
            })
            .on("mouseover", function(d, i) {                    
                $("#icon-" + i).show();                         
            })
            .on("mouseout", function(d, i) {
                $("#icon-" + i).hide();                
            })  
            .on("mousedown", function(d, i) {
                //Handles 2 mouse clicks
                handle_mousedown_event(d, path, text, icon, arc, x, y, r, padding);  
            })            
            .on("click", function(d){
                //click_partition(d, path, text, icon, arc, x, y, r, padding);
            });

            /** Managing text - always maximum two words */
            
            textEnter.append("tspan")
            .attr("x", 0)
            .text(function(d) {
                return d.depth ? d.name.split(" ")[0] : "";
            });
            textEnter.append("tspan")
            .attr("x", 0)
            .attr("dy", "1em")
            .text(function(d) {
                return d.depth ? d.name.split(" ")[1] || "" : "";
            });
                             
            /* Icon settings */
                
            var icon_click = icon.enter().append("text")
            .style("fill-opacity", 1)
            .style("fill", function(d) {                
                return "#000";
            })  
            .attr("text-anchor", function(d) {
                return x(d.x + d.dx / 2) > Math.PI ? "end" : "start";
            })
            .attr("dy", ".2em")            
            .attr("transform", function(d) {
                /** Get the text details and define the rotation and general position */                
                angle = x(d.x + d.dx / 2) * 180 / Math.PI - 90,
                rotate = angle;
                return "rotate(" + rotate + ")translate(" + (y(d.y) + padding +80) + ")rotate(" + (angle > 90 ? -180 : 0) + ")";
            })
            .on("click", function(d){
                open_popup(d);
            });
           
            icon_click.append("tspan")
            .attr("id", function(d, i) {            
                return "icon-" + i;
            })
            .attr("x", 0)
            .attr("display", 'none')            
            .text(function(d) {
                //return "Click";
            });
        });
    }
    
    function handle_mousedown_event(d, path, text, icon, arc, x, y, r, padding) {                
        switch (d3.event.which) {
            case 1:
                //alert('Left mouse button pressed');
                click_partition(d, path, text, icon, arc, x, y, r, padding);
                break;
            case 2:
                //alert('Middle mouse button pressed');
                break;                        
            case 3:                        
                open_popup(d);                        
                //alert('Right mouse button pressed');
                break;
            default:
                //alert('You have a strange mouse');
        }
    }
    
    function open_popup(d) {
        //Cleaning selected        
        $("#gradebook_id").find('option').remove();        
        $("#parent_id").find('option').remove();
        
        $("#gradebook_holder").find('li').remove();
        $("#skill_edit_holder").find('li').remove();
                
        var skill = get_skill_info(d.id);       
        
        if (skill) {
            var parent_info = get_skill_info(skill.extra.parent_id);
            
            $("#id").attr('value',   skill.id);
            $("#name").attr('value', skill.name);
            $("#short_code").attr('value', skill.short_code);                        
            $("#description").attr('value', skill.description);                
            
            //Filling parent_id                        
            $("#parent_id").append('<option class="selected" value="'+skill.extra.parent_id+'" selected="selected" >');            
            
            $("#skill_edit_holder").append('<li class="bit-box">'+parent_info.name+'</li>');
            
            //Filling the gradebook_id
            jQuery.each(skill.gradebooks, function(index, data) {                    
                $("#gradebook_id").append('<option class="selected" value="'+data.id+'" selected="selected" >');
                $("#gradebook_holder").append('<li id="gradebook_item_'+data.id+'" class="bit-box">'+data.name+' <a rel="'+data.id+'" class="closebutton" href="#"></a> </li>');    
            });
        }        
        
        //description = $( "#description" );        
        $("#dialog-form").dialog({
            buttons: {
                 "{{ "Edit"|get_lang }}" : function() {
                     var params = $("#add_item").find(':input').serialize();
                     add_skill(params);
                     console.log(params);
                  },                  
                  "{{ "Delete"|get_lang }}" : function() {
                  },
            },
            close: function() {     
                $("#name").attr('value', '');
                $("#description").attr('value', '');                
            }
        });
        $("#dialog-form").dialog("open");
    }
    
    var colors1 = $.xcolor.analogous('#da0'); //8 colors
    var colors = colors1;
    
    // Generating array of colors
    color_loops = 4;
    for (i= 0; i < color_loops; i++) {
        last_color = colors[colors.length-1].getHex();        
        glue_color = $.xcolor.complementary(last_color);
        //colors.push(glue_color.getHex());        
        colors2 = $.xcolor.analogous(glue_color);
        colors = $.merge(colors, colors2);
    }
    
    function get_color(d) {
        depth = d.depth;
        if (d.family_id) {            
            /*var p = color_patterns[d.family_id];            
            color = p(depth -1 + d.counter);
            d.color = color;*/                  
            if (depth > 1) {              
                family1 = colors[d.family_id];
                family2 = colors[d.family_id + 2];                
                position = d.depth*d.counter;                
                //part_color = $.xcolor.gradientlevel(family1, family2, position, 100);
                part_color = $.xcolor.lighten(family1, position, 15);                
                color = part_color.getHex();
                //console.log(d.depth + " - " + d.name + " + "+ color+ "+ " +d.counter);
            } else {
                color = colors[d.family_id];                
            }
            d.color = color;            
            return color;
        }
        color = '#fefefe';
        d.color = color;
        return color; //missing colors
    }
        
    /*      
    gray tones for all skills that have no particular property ("Basic skills wheel" view)
    yellow tones for skills that are provided by courses in Chamilo ("Teachable skills" view)
    bright blue tones for personal skills already acquired by the student currently looking at the weel ("My skills" view)
    dark blue tones for skills already acquired by a series of students, when looking at the will in the "Owned skills" view.
    bright green for skills looked for by a HR director ("Profile search" view)
    dark green for skills most searched for, summed up from the different saved searches from HR directors ("Most wanted skills")
    bright red for missing skills, in the "Required skills" view for a student when looking at the "Most wanted skills" (or later, when we will have developed that, for the "Matching position" view)
    */
        
    function set_skill_style(d, attribute) {
        //Nice rainbow colors
        return_fill = get_color(d);
        
        
        /*var p = color_patterns[18];
        color = p(depth -1 + d.counter);
        return_fill = d.color = color;*/
         
        
        //return_fill = 'grey';
        
        return_stroke = 'black';
        
        //If user achieved that skill
        if (d.achieved) {
            return_fill = 'cornflowerblue';
            //return_stroke = '#FCD23A';           
        }
        
        //darkblue
        
        //If the skill has a gradebook attached
        if (d.skill_has_gradebook) {
            return_fill = 'yellow';
            //return_stroke = 'grey';
        }
        
        switch (attribute) {
            case 'fill':
                return return_fill;
                break;
            case 'stroke':
                return return_stroke;
                break;
        }
    }
    
    function click_partition(d, path, text, icon, arc, x, y, r, p) {
        if (d.depth == 2) {
            /*main_depth +=1;
            load_nodes(main_depth);*/
        }
        var duration = 1000;
        
        path.transition()
        .duration(duration)
        .attrTween("d", arcTween(d, arc, x, y, r));

        /* Updating text position */
        
        // Somewhat of a hack as we rely on arcTween updating the scales.
        text.style("visibility", function(e) {
            return isParentOf(d, e) ? null : d3.select(this).style("visibility");
        })
        .transition().duration(duration)
        .attrTween("text-anchor", function(d) {
            return function() {
                return x(d.x + d.dx / 2) > Math.PI ? "end" : "start";
            };
        })
        .attrTween("transform", function(d) {
            var multiline = (d.name || "").split(" ").length > 1;
            return function() {
                var angle = x(d.x + d.dx / 2) * 180 / Math.PI - 90,
                rotate = angle + (multiline ? -.5 : 0);
                return "rotate(" + rotate + ")translate(" + (y(d.y) + p) + ")rotate(" + (angle > 90 ? -180 : 0) + ")";
            };
        })
        .style("fill-opacity", function(e) {
            return isParentOf(d, e) ? 1 : 1e-6;
        })
        .each("end", function(e) {
            d3.select(this).style("visibility", isParentOf(d, e) ? null : "hidden");
        });
        
        
        
        /* Updating icon position */        
        
        icon.transition().duration(duration)
        .attrTween("text-anchor", function(d) {
            return function() {
                return x(d.x + d.dx / 2) > Math.PI ? "end" : "start";
            };
        })
        .attrTween("transform", function(d) {            
            return function() {
                var angle = x(d.x + d.dx / 2) * 180 / Math.PI - 90,
                rotate = angle;
                return "rotate(" + rotate + ")translate(" + (y(d.y) + p) + ")rotate(" + (angle > 90 ? -180 : 0) + ")";
            };
        })
        .style("fill-opacity", function(e) {
            //return isParentOf(d, e) ? 1 : 1e-6;
        })
        .each("end", function(e) {
            //d3.select(this).style("visibility", isParentOf(d, e) ? null : "hidden");
        });
    }

    /* Returns whether p is parent of c */
    function isParentOf(p, c) {
        if (p === c) return true;
        if (p.children) {
            return p.children.some(function(d) {
                return isParentOf(d, c);
            });
        }
        return false;
    }

    /* Generated random colour */
    function colour(d) {
        
        if (d.children) {
            // There is a maximum of two children!
            var colours = d.children.map(colour),
            a = d3.hsl(colours[0]),
            b = d3.hsl(colours[1]);
            // L*a*b* might be better here...
            return d3.hsl((a.h + b.h) / 2, a.s * 1.2, a.levels_to_show / 1.2);
        }        
        return d.colour || "#fff";
    }

    /* Interpolate the scales! */
    function arcTween(d, arc, x, y, r) {
        var my = maxY(d),
        xd = d3.interpolate(x.domain(), [d.x, d.x + d.dx]),
        yd = d3.interpolate(y.domain(), [d.y, my]),
        yr = d3.interpolate(y.range(), [d.y ? 20 : 0, r]);
        return function(d) {
            return function(t) {
                x.domain(xd(t));
                y.domain(yd(t)).range(yr(t));
                return arc(d);
            };
        };
    }

    /*  */
    function maxY(d) {
        return d.children ? Math.max.apply(Math, d.children.map(maxY)) : d.y + d.dy;
    }

    /* Use a formula for contrasting colour
       http://www.w3.org/WAI/ER/WD-AERT/#color-contrast
    */
    function brightness(rgb) {
        return rgb.r * .299 + rgb.g * .587 + rgb.b * .114;
    }

});
</script>


<div class="container-fluid">
    <div class="row-fluid">
        
        <div class="span3">
            <div class="well">
                <h3>{{ 'Skills'|get_lang }}</h3>
                <hr>
                
                <form id="skill_search" class="form-search">
                    <select id="skill_id" name="skill_id" />
                    </select>
                    <br /><br />
                    
                    <ul id="skill_holder" class="holder holder_simple">
                        <li><a class="load_wheel" rel="1" href="#">Root</a></li>
                    </ul>
                </form>
                
                <h3>{{ 'ProfileSearch'|get_lang }}</h3>
                <hr>
                {{ 'WhatSkillsAreYouLookinFor'|get_lang }}
                
                <ul id="profile_search" class="holder holder_simple">
                </ul>

                {{ 'RightClickOnSkillsInTheWheelToAddThemToThisProfileSearchBox'|get_lang }}               
                
                <form id="search_profile_form" class="form-search">
                    <input class="btn" type="submit" value="{{ "SearchProfileMatches"|get_lang }}">
                </form>                
                
                <h3>{{ 'MySkills'|get_lang }}</h3>
                <hr>
                
                <h3>{{ 'GetNewSkills'|get_lang }}</h3>
                <hr>
                
                <h3>{{ 'SkillInfo'|get_lang }}</h3>
                <hr>
                
                
            </div>                
        </div>
            
        <div id="wheel_container" class="span9">
            <div id="skill_wheel">
                <img src="">
            </div>
        </div>
</div>

<div id="dialog-form" style="display:none; z-index:9001;">    
    <p class="validateTips"></p>
    <form id="add_item" class="form-horizontal"  name="form">
        <fieldset>
            <input type="hidden" name="id" id="id"/>
            <div class="control-group">            
                <label class="control-label" for="name">{{ 'Name' | get_lang }}</label>            
                <div class="controls">
                    <input type="text" name="name" id="name" size="40" />             
                </div>
            </div>
            
            <div class="control-group">            
                <label class="control-label">{{ 'ShortCode' | get_lang }}</label>            
                <div class="controls">
                    <input type="text" name="short_code" id="short_code" size="40" />             
                </div>
            </div>

             <div id="skill_row" class="control-group">            
                <label class="control-label" for="name">{{'Parent'|get_lang}}</label>            
                <div class="controls">
                    <select id="parent_id" name="parent_id" />
                    </select>
                    <ul id="skill_edit_holder" class="holder holder_simple">
                    </ul>
                </div>
            </div>
            
            <div id="gradebook_row" class="control-group">            
                <label class="control-label" for="name">{{'Gradebook'|get_lang}}</label>            
                <div class="controls">
                    <select id="gradebook_id" name="gradebook_id" multiple="multiple"/>
                    </select>             
                        
                    <ul id="gradebook_holder" class="holder holder_simple">
                    </ul>
                        
                    <span class="help-block">
                    {{ 'WithCertificate'|get_lang }}
                    </span>           
                </div>
            </div>
            
            <div class="control-group">            
                <label class="control-label" for="name">{{ 'Description'|get_lang }}</label>            
                <div class="controls">
                    <textarea name="description" id="description" class="span3" rows="7"></textarea>
                </div>
            </div>  
        </fieldset>
    </form>     
</div>
