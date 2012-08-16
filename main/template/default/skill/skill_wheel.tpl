<script>
/* For licensing terms, see /license.txt */

$(document).ready(function() {
    /** Define constants and size of the wheel */
    /** Total width of the wheel (also counts for the height) */
    var w = 800,
    h = w,
    r = w / 2,
    /** x/y positionning of the center of the wheel */
    x = d3.scale.linear().range([0, 2 * Math.PI]),
    y = d3.scale.pow().exponent(1.1).domain([0, 1]).range([0, r]),
    /** Padding in pixels before th string starts */
    p = 3,
    /** Duration of click animations */
    duration = 1000,
    /** Levels to show */
    l = 3;
       
    /* Locate the #div id element */
    var div = d3.select("#vis");
    
    /* Remove the image (make way for the dynamic stuff */
    div.select("img").remove();
    
    /* Append an element "svg" to the #vis section */
    var vis = div.append("svg")
    //.attr("class", "Blues")
    .attr("width", w + p * 2)
    .attr("height", h + p * 2)
    .append("g")
    .attr("transform", "translate(" + (r + p) + "," + (r/1.5 + p) + ")"); 
        
    /* ...update translate variables to change coordinates of wheel's center */
    /* Add a small label to help the user */
    div.append("p")
    .attr("id", "intro")
    .text("Click to zoom!");
        
    /* Generate the partition layout */
    var partition = d3.layout.partition()
    .sort(null)
    /** Value here seems to be a calculation of the size of the elements
            depending on the level of depth they're at. Changing it makes
            elements pass over the limits of others... */
    .value(function(d) {
        return 5.8 - d.depth;
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
        
    /* ...adding "+1" to "y" function's params is really funny */
    /* Exexute the calculation based on a JSON file provided */
    /*d3.json("wheel.json", function(json) {*/
    /** Get the JSON list of skills and work on it */
    
    //var achieved_fill = d3.scale.category10().domain(["yes","no"]);
    var normal_fill = d3.scale.category20c();
    
    /*var colorScale = d3.scale.quantize()
    .domain([1.0, 5.0])
    .range(colorbrewer.YlGnBu[5]);
*/

    var z = d3.scale.ordinal().domain(["1", "2", "3"]).range(colorbrewer.RdBu[9]);
    
    function set_skill_style(d, attribute) {
        return_fill = normal_fill;
        return_fill = z(d.depth);
        return_background = 'blue';
            
        if (d.achieved) {
            //return_fill = 'green';
            return_background = 'blue';
        } else {
            //return_fill = colorScale;
            return_background = '1px border #fff';
        }
        
        switch (attribute) {
            case 'fill':
                return return_fill;
                break;
            case 'background':
                return return_background;
                break;
        }
    }
    
    
    d3.json("{{ wheel_url }}", function(json) {
        /** Define the list of nodes based on the JSON */
        var nodes = partition.nodes({
            children: json
        });

        /* Setting all skills */
        var path = vis.selectAll("path").data(nodes);
                
        path.enter().append("path")
        .attr("id", function(d, i) {            
            return "path-" + i;
        })
        .attr("class", function(d) { return "q" +  "1-9"; })
        .attr("d", arc)
        .attr("fill-rule", "evenodd")
        //.style("fill", colour)
        .style("fill", function(d) { 
            return set_skill_style(d, 'fill');
        })
        .style("background", function(d) {
            return set_skill_style(d, 'background');
        })
        .on("click", click);
        
        /* End setting skills */

        var text = vis.selectAll("text").data(nodes);
        
        var textEnter = text.enter().append("text")
        .style("fill-opacity", 1)
        .style("fill", function(d) {
            return brightness(d3.rgb(colour(d))) < 125 ? "#eee" : "#000";
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
            return "rotate(" + rotate + ")translate(" + (y(d.y) + p) + ")rotate(" + (angle > 90 ? -180 : 0) + ")";
        })
        .on("click", click);
          
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
        
        function click(d) {
            path.transition()
            .duration(duration)
            .attrTween("d", arcTween(d));

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
        }
    });

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
            return d3.hsl((a.h + b.h) / 2, a.s * 1.2, a.l / 1.2);
        }        
        return d.colour || "#fff";
    }
    
    function set_attributes(d) {
        console.log(d);
        return d.style('fill', 'green');
    }

    /* Interpolate the scales! */
    function arcTween(d) {
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


<!--div id="dialog-form" style="display:none; z-index:9001;">    
    <p class="validateTips"></p>
    <form class="form-horizontal" id="add_item" name="form">
        <fieldset>
            <input type="hidden" name="id" id="id"/>
            <div class="control-group">            
                <label class="control-label" for="name">{{'Name'|get_lang}}</label>            
                <div class="controls">
                    <input type="text" name="name" id="name" size="40" />             
                </div>
            </div>        
            <div class="control-group">            
                <label class="control-label" for="name">{{'Parent'|get_lang}}</label>            
                <div class="controls">
                    <select id="parent_id" name="parent_id" />
                    </select>                  
                </div>
            </div>                
            <div class="control-group">            
                <label class="control-label" for="name">{{'Gradebook'|get_lang}}</label>            
                <div class="controls">
                    <select id="gradebook_id" name="gradebook_id[]" multiple="multiple"/>
                    </select>             
                    <span class="help-block">
                    {{'WithCertificate'|get_lang}}
                    </span>           
                </div>
            </div>
            <div class="control-group">            
                <label class="control-label" for="name">{{'Description'|get_lang}}</label>            
                <div class="controls">
                    <textarea name="description" id="description" class="span3" rows="7"></textarea>
                </div>
            </div>  
        </fieldset>
    </form>    
</div-->
