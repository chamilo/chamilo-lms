<script>
/* For licensing terms, see /license.txt */

$(document).ready(function() {    
    
     //Open dialog
    $("#dialog-form").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 550, 
        height  : 480
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
        
    load_nodes(main_depth);
    
    function load_nodes(main_depth) {
        
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
        /*$("#vis").remove();
        $("body").append('<div id="vis"></div>');*/
        var div = d3.select("#vis");

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
        .size([1, 2])    
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
                
        d3.json("{{ wheel_url }}&main_depth="+main_depth, function(json) {
            
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
            .on("click", function(d){
                click_partition(d, path, text, icon, arc, x, y, r, padding);
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
            .on("click", function(d){
                click_partition(d, path, text, icon, arc, x, y, r, padding);
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
                return "rotate(" + rotate + ")translate(" + (y(d.y) + padding) + ")rotate(" + (angle > 90 ? -180 : 0) + ")";
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
                return "Click";
            });
        });
    }
    
    function open_popup(d) {
        $( "#name" ).attr('value', d.name);
        //description = $( "#description" );        
        $("#dialog-form").dialog({
            buttons: {
                 "{{ "DoSomething"|get_lang }}" : function() {
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
<div id="menu" class="well" style="top:20px; left:20px; width:380px; z-index: 9000; opacity: 0.9;">
    <h3>{{ 'Skills'|get_lang }}</h3>
    <div class="btn-group">
        <a style="z-index: 1000" class="btn" id="add_item_link" href="#">{{ 'AddSkill'|get_lang }}</a>
        <a style="z-index: 1000" class="btn" id="return_to_root" href="#">{{ 'Root'|get_lang }}</a>
        <a style="z-index: 1000" class="btn" id="return_to_admin" href="{{ _p.web_main }}admin">{{ 'BackToAdmin'|get_lang }}</a>        
    </div>
</div>
           
<div id="vis">
    <img src="">
</div>


<div id="dialog-form" style="display:none; z-index:9001;">    
    <p class="validateTips"></p>
    <form class="form-horizontal" id="add_item" name="form">
        <fieldset>
            <input type="hidden" name="id" id="id"/>
            <div class="control-group">            
                <label class="control-label" for="name">{{ 'Name' | get_lang }}</label>            
                <div class="controls">
                    <input type="text" name="name" id="name" size="40" />             
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
