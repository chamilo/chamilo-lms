{# topbar #}
{% include 'layout/topbar.tpl'|get_template %}
<script>

var SkillWheel = {
    currentSkill: null,
    getSkillInfo: function(skillId) {
        return $.getJSON(
            '{{ url }}',
            {
                a: 'get_skill_info',
                id: parseInt(skillId)
            }
        );
    },
    showFormSkill: function(skillId) {
        skillId = parseInt(skillId);

        var formSkill = $('form[name="form"]');

        var getSkillInfo = SkillWheel.getSkillInfo(skillId);

        $.when(getSkillInfo).done(function(skillInfo) {
            SkillWheel.currentSkill = skillInfo;

            var getSkillParentInfo = SkillWheel.getSkillInfo(skillInfo.extra.parent_id);

            $.when(getSkillParentInfo).done(function(skillParentInfo) {
                formSkill.find('p#parent').text(skillParentInfo.name);
            });

            formSkill.find('p#name').text(skillInfo.name);

            if (skillInfo.short_code.length > 0) {
                formSkill.find('p#short_code').text(skillInfo.short_code).parent().parent().show();
            } else {
                formSkill.find('p#short_code').parent().parent().hide();
            }

            if (skillInfo.description.length > 0) {
                formSkill.find('p#description').text(skillInfo.description).parent().parent().show();
            } else {
                formSkill.find('p#description').parent().parent().hide();
            }

            if (skillInfo.gradebooks.length > 0) {
                formSkill.find('ul#gradebook').empty().parent().parent().show();

                $.each(skillInfo.gradebooks, function(index, gradebook) {
                    var $gradebookCourse = $('<a>',{href: _p.web_main + 'auth/courses.php'})
                        .text(gradebook.course_code);

                    $('<li>').html($gradebookCourse).appendTo(formSkill.find('ul#gradebook'));
                });
            } else {
                formSkill.find('ul#gradebook').parent().parent().hide();
            }

            $('#frm-skill').modal('show');
        });
    }
};

/* Skill wheel settings */
var debug = true;
var url = '{{ url }}';
var skill_to_load_from_get = '{{ skill_id_to_load }}';

//Just in case we want to use it
var main_depth = 4;
var main_parent_id = 0;

// Used to split in two word or not
var max_size_text_length = 20;

/* ColorBrewer settings */
var my_domain = [1,2,3,4,5,6,7,8,9];
var col = 9;
var color_patterns = [];

/*

See colorbrewer documentation

color_patterns[1] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Blues[col]);
color_patterns[2] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Purples[col]);
color_patterns[2] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Blues[6]);
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
color_patterns[17] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.YlOrRd[col]);*/

//Too make the gray tones lighter
col = 3;
color_patterns[18] = d3.scale.ordinal().domain(my_domain).range(colorbrewer.Blues[col]);

//If you want to use the category10()
//var normal_fill = d3.scale.category10().domain(my_domain);

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

/* The partiton name will have 1 or 2 lines? */
function is_multiline(word) {
    if (word) {
        if (word.length > max_size_text_length) {
            return (word).split(" ").length > 1;
        }
    }
    return false;
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
var userSkills;

/**
    Manage the partition background colors
**/
function set_skill_style(d, attribute, searched_skill_id) {
    //Default border color (stroke)
    return_stroke = '#000';

    //0. Nice rainbow colors (Comment 1.0 to see the rainbow!)
    return_fill = get_color(d);

    //1. Grey colors using colorbrewer
    var p = color_patterns[18];
    color = p(depth -1 + d.counter);
    return_fill = color;

    //2. Yellow - If the skill has a gradebook attached
    if (d.skill_has_gradebook) {
        return_fill = '#F89406';
        //return_stroke = 'grey';
    }

    //3. Red - if you search that skill
    if (d.isSearched) {
        return_fill = '#B94A48';
    }

    if (!userSkills) {
        $.ajax({
            url: url + '&a=get_all_user_skills',
            async: false,
            success: function (skills) {
                userSkills = jQuery.parseJSON(skills);
            }
        });
    }

    // Old way (it makes a lot of ajax calls)
    //4. Blue - if user achieved that skill
    //var skill = false;
    /*$.ajax({
        url: url+'&a=get_user_skill&profile_id='+d.id,
        async: false,
        success: function(skill) {
            if (skill == 1) {
                return_fill = '#3A87AD';
            }
        }
    });*/

    // New way (Only 1 ajax call)
    // 4. Blue - if user achieved that skill
    if (userSkills[d.id]) {
        return_fill = '#A1D99B';
    }

    // 5. Grey / Black if the skill is disabled
    if (d.status < 1) {
        return_fill = '#48616C';
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
function click_partition(d, path, text, icon, arc, x, y, r, p, vis) {
    if (debug) {
        console.log('Clicking a partition skill id: '+d.id);
        console.log(d);
        console.log('real parent_id: '+d.real_parent_id + ' parent_id: ' +d.parent_id);
        console.log('depth ' + d.depth);
        console.log('main_depth ' + main_depth);
        console.log('main_parent_id: ' + main_parent_id);
    }

    if (d.depth >= main_depth) {
        //main_depth += main_depth;
        if (main_parent_id) {
            load_nodes(main_parent_id, main_depth);
        } else {
            load_nodes(d.id, main_depth);
        }
    }

    if (d.id) {
        console.log('Getting skill info');
        skill_info = get_skill_info(d.parent_id);
        main_parent_id  = skill_info.extra.parent_id;
        main_parent_id  = d.parent_id;
        console.log('Setting main_parent_id: ' + main_parent_id);
    }

    //console.log(main_parent_id);

    /* "No id" means that we reach the center of the wheel go to the root*/
    if (!d.id) {
        load_nodes(main_parent_id, main_depth);
    }

    if (debug) console.log('Continue to click_partition');

    //console.log(main_parent_id);

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
        var multiline = is_multiline(d.name); //(d.name || "").split(" ").length > 1;
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

/* Handles mouse clicks */
function handle_mousedown_event(d, path, text, icon, arc, x, y, r, padding, vis) {
    switch (d3.event.which) {
        case 1:
            //alert('Left mouse button pressed');
            click_partition(d, path, text, icon, arc, x, y, r, padding, vis);
            break;
        case 2:
            //alert('Middle mouse button pressed');
            break;
        case 3:
            if (typeof d.id === 'undefined') {
                break;
            }

            SkillWheel.showFormSkill(d.id);
            //alert('Right mouse button pressed');
            break;
        default:
            //alert('You have a strange mouse :D '); //
    }
}

/*
    Loads the skills partitions thanks to a json call
 */
function load_nodes(load_skill_id, main_depth, extra_parent_id) {
    if (debug) {
        console.log('Load nodes ----->');
        console.log('Loading skill id: '+load_skill_id+' with depth ' + main_depth);
        console.log('main_parent_id before: ' + main_parent_id);
    }

    // "Root partition" on click switch
    if (main_parent_id && load_skill_id) {
        skill_info = get_skill_info(load_skill_id);
        if (skill_info && skill_info.extra) {
            main_parent_id = skill_info.extra.parent_id;
        } else {
            main_parent_id = 0;
        }
        console.log('main_parent_id after: ' + main_parent_id);
    }

    if (load_skill_id && load_skill_id == 1)  {
        main_parent_id = 0;
    }

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
    .attr("width", '100%')
    .attr("height", $(window).height())
    .attr('viewBox', '0 0 ' + (w + padding * 2) + ' ' + (h + padding * 2))
    .append("g")
    .attr("transform", "translate(" + (r + padding) + "," + (r/reduce_top + padding) + ")");

    /* ...update translate variables to change coordinates of wheel's center */
    /* Add a small label to help the user */
    div.append("p")
    .attr("id", "intro")
    .text("{{ "ClickToZoom"|get_lang }}");

    $( window ).resize(function() {
        $( "#skill_wheel svg" )
            .attr("height", $(window).height());
    });
    /* Generate the partition layout */
    var partition = d3.layout.partition()
    .sort(null)
    /** Value here seems to be a calculation of the size of the elements
            depending on the level of depth they're at. Changing it makes
            elements pass over the limits of others... */
    //.size([1, 2])
    .value(function(d) {
        //return 5.8 - d.depth;
        //When having more than 4 children seems that the code above does not work
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
        .on("contextmenu", function(d, i) {
            //Handles mouse clicks
            handle_mousedown_event(d, path, text, icon, arc, x, y, r, padding, vis);
            //Blocks "right click menu"
            d3.event.preventDefault();
            return false;
        })
        .on("mousedown", function(d, i) {
        })
        .on("click", function(d) {
            //Simple click
            handle_mousedown_event(d, path, text, icon, arc, x, y, r, padding, vis);
        });

        /*//Redefine the root
        path_zero = vis.selectAll("#path-0").on("mousedown", function(d){

               d = get_skill_info(extra_parent_id);
               d.parent_id = d.extra.parent_id;
               click_partition(d, path, text, icon, arc, x, y, r, padding, vis);
        });*/

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
        .attr("rel", "tooltip_skill")
        .attr("title", function(d) {
            return d.name;
        })
        .attr("dy", ".2em")
        .attr("transform", function(d) {
            /** Get the text details and define the rotation and general position */
            var multiline = is_multiline(d.name); //(d.name || "").split(" ").length > 1,
            angle = x(d.x + d.dx / 2) * 180 / Math.PI - 90,
            rotate = angle + (multiline ? -.5 : 0);
            return "rotate(" + rotate + ")translate(" + (y(d.y) + padding) + ")rotate(" + (angle > 90 ? -180 : 0) + ")";
        })
        .on("mouseover", function(d, i) {
            //$("#icon-" + i).show();
        })
        .on("mouseout", function(d, i) {
            //$("#icon-" + i).hide();
        })
        .on("contextmenu", function(d, i) {
            handle_mousedown_event(d, path, text, icon, arc, x, y, r, padding, vis);
            d3.event.preventDefault();
        })
        .on("mousedown", function(d, i) {
        })
        .on("click", function(d) {
            handle_mousedown_event(d, path, text, icon, arc, x, y, r, padding, vis);
        });

        /** Managing text - maximum two words */
        textEnter.append("tspan")
        .attr("x", 0)
        .text(function(d) {
            if (d.short_code) {
                return d.short_code;
            }

            if (d.depth && d.name) {
                var nameParts = d.name.split(' ');

                if (nameParts[0].length > max_size_text_length) {
                    return nameParts[0].substring(0, max_size_text_length - 3)  + '...';
                }

                return nameParts[0];
            }

            return d.depth ? d.name : '';
        });

        textEnter.append("tspan")
        .attr("x", 0)
        .attr("dy", "1em")
        .text(function(d) {
            if (d.short_code) {
                return null;
            }

            if (d.depth && d.name) {
                var nameParts = d.name.split(' ');

                if (nameParts.length >= 2) {
                    if (nameParts[1].length > max_size_text_length) {
                        return nameParts[1].substring(0, max_size_text_length - 3)  + '...';
                    }

                    return nameParts[1];
                }

                return '';
            }

            return d.depth ? d.name : '';
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
            SkillWheel.showFormSkill(d.id);
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

    if (debug) {
        console.log('<------ End load nodes ----->');
    }
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

</script>
