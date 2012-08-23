<script>

/* Skill wheel settings */

var url = '{{ url }}';
var skill_to_load_from_get = '{{ skill_id_to_load }}';	
  
/* ColorBrewer settings */
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

//Too make the gray tones lighter
col = 3;
color_patterns[18] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Greys[col]);

//If you want to use the category10()
//var normal_fill = d3.scale.category10().domain(my_domain);

//Just in case we want to use it
var main_depth = 1000;

//First 8 colors
var colors = $.xcolor.analogous('#da0'); //8 colors

//How long will be the array of colors?
var color_loops = 4;

// Generating array of colors thanks to the "$.xcolor.analogous" function we can create a rainbow style!
for (i= 0; i < color_loops; i++) {
    //Getting the latest color hex of the 8 colors loaded 
    last_color = colors[colors.length-1].getHex();
    //Getting the complementary
    glue_color = $.xcolor.complementary(last_color);
    //Generating 8 more colors
    temp_color_array = $.xcolor.analogous(glue_color);
    //Adding the color to the main array
    colors = $.merge(colors, temp_color_array);
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

    /*  Calculate maxY */
    function maxY(d) {
        return d.children ? Math.max.apply(Math, d.children.map(maxY)) : d.y + d.dy;
    }

    /* Use a formula for contrasting colour
       http://www.w3.org/WAI/ER/WD-AERT/#color-contrast
    */
    function brightness(rgb) {
        return rgb.r * .299 + rgb.g * .587 + rgb.b * .114;
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
            return color;
        }
        color = '#fefefe';        
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
   
    /* Manage the partition colors */
    function set_skill_style(d, attribute, searched_skill_id) {
        //Default stroke
        return_stroke = 'black';
        
        //Nice rainbow colors
        return_fill = get_color(d);        
        
        //Grey colors using colorbrewer
        var p = color_patterns[18];
        color = p(depth -1 + d.counter);
        return_fill = d.color = color;                
                
        
        //blue - if user achieved that skill
        if (d.achieved) {
            return_fill = '#3A87AD';
            //return_stroke = '#FCD23A';
        }
        
        //yellow - If the skill has a gradebook attached
        if (d.skill_has_gradebook) {      
            return_fill = '#F89406';            
            //return_stroke = 'grey';
        }
        
        //red - if to show the searched skill
        if (searched_skill_id) {
            if (d.id ==  searched_skill_id) {
                return_fill = '#B94A48';
            }
        }
        
        switch (attribute) {
            case 'fill':
                //In order to identify the color of the text (white, black) used in other function
                d.color = return_fill;
                return return_fill;
                break;
            case 'stroke':
                return return_stroke;
                break;
        }
    }
    
    /* When you click a skill partition */
    function click_partition(d, path, text, icon, arc, x, y, r, p) {
        if (d.depth == 2) {
            /*main_depth +=1;
            load_nodes(main_depth);*/
        }
        
        /* "No id" means that we reach the center of the wheel go to the root*/
        if (!d.id) {
            load_nodes(0, main_depth);
        } 
        
        //Duration of the transition
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
                        
        //Add an icon in the partition
        /* Updating icon position */
        /*
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
        });*/
    }
    
    /* 
        Open a popup in order to modify the skill 
     */
    function open_popup(skill_id, parent_id) {
        //Cleaning selects
        $("#gradebook_id").find('option').remove();        
        $("#parent_id").find('option').remove();
        //Cleaning lists
        $("#gradebook_holder").find('li').remove();
        $("#skill_edit_holder").find('li').remove();
                
        var skill = false;
        if (skill_id) {
            skill = get_skill_info(skill_id);
        }
        
        var parent = false;
        if (parent_id) {
            parent = get_skill_info(parent_id);
        }
        
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
            
            $("#dialog-form").dialog({
                buttons: {
                     "{{ "Edit"|get_lang }}" : function() {
                         var params = $("#add_item").find(':input').serialize();
                         add_skill(params);                     
                      },                                    
                      /*"{{ "Delete"|get_lang }}" : function() {
                      },*/
                      "{{ "CreateChildSkill"|get_lang }}" : function() {                          
                          open_popup(0, skill.id);

                      },
                      "{{ "AddSkillToProfileSearch"|get_lang }}" : function() {                          
                          add_skill_in_profile_list(skill.id, skill.name);
                      }
                },
                close: function() {     
                    $("#name").attr('value','');
                    $("#description").attr('value', '');
                    //Redirect to the main root
                    load_nodes(0, main_depth);
                                 
                }
            });            
            $("#dialog-form").dialog("open");            
        }  
        
        if (parent) {
            $("#id").attr('value','');
            $("#name").attr('value', '');
            $("#short_code").attr('value', '');
            $("#description").attr('value', '');
            
            //Filling parent_id                        
            $("#parent_id").append('<option class="selected" value="'+parent.id+'" selected="selected" >');            
            
            $("#skill_edit_holder").append('<li class="bit-box">'+parent.name+'</li>');
            
            //Filling the gradebook_id
            jQuery.each(parent.gradebooks, function(index, data) {                    
                $("#gradebook_id").append('<option class="selected" value="'+data.id+'" selected="selected" >');
                $("#gradebook_holder").append('<li id="gradebook_item_'+data.id+'" class="bit-box">'+data.name+' <a rel="'+data.id+'" class="closebutton" href="#"></a> </li>');    
            });            
            
            $("#dialog-form").dialog({
                buttons: {
                     "{{ "Save"|get_lang }}" : function() {
                         var params = $("#add_item").find(':input').serialize();
                         add_skill(params);                     
                      }
           
                },
                close: function() {     
                    $("#name").attr('value', '');
                    $("#description").attr('value', '');
 	                   load_nodes(0, main_depth);              
                }
            });
            $("#dialog-form").dialog("open");        
        }
    }
        
    /* Handles mouse clicks */
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
                open_popup(d.id);                        
                //alert('Right mouse button pressed');
                break;
            default:
                //alert('You have a strange mouse :D '); //
        }
    }    

/* 
    Loads the skills partitions thanks to a json call
 */
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
            return set_skill_style(d, 'fill', load_skill_id);
        })
        .style("stroke", function(d) {
            return set_skill_style(d, 'stroke');
        })
        .on("mouseover", function(d, i) {
            //$("#icon-" + i).show();                         
        })
        .on("mouseout", function(d, i) {
            //$("#icon-" + i).hide();
        })
        .on("mousedown", function(d, i) {
            //Handles mouse clicks
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
        
        
        /*
        var icon_click = icon.enter().append("text")
        .style("fill-opacity", 1)
        .style("fill", function(d) {                
            //return "#000";
        })  
        .attr("text-anchor", function(d) {
            return x(d.x + d.dx / 2) > Math.PI ? "end" : "start";
        })
        .attr("dy", ".2em")            
        .attr("transform", function(d) {
            ///Get the text details and define the rotation and general position
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
        });*/
    });
}


/* Skill AJAX calls */

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


$(document).ready(function() {

});
</script>