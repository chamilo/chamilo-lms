/* Define constants and size of the wheel */
/** Total width of the wheel (also counts for the height) */

$(document).ready(function() {    
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
    
    d3.json("skills.json.php", function(json) {
        /** Define the list of nodes based on the JSON */
        var nodes = partition.nodes({
            children: json
        });

        var path = vis.selectAll("path").data(nodes);
        
        path.enter().append("path")
        .attr("id", function(d, i) {
            return "path-" + i;
        })
        .attr("d", arc)
        .attr("fill-rule", "evenodd")
        .style("fill", colour)
        .on("click", click);

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
          
        /** Managing text - alway maximum two words */
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


    });
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