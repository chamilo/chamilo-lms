<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'skill.lib.php';
require_once api_get_path(LIBRARY_PATH).'skill.visualizer.lib.php';
$this_section = SECTION_PLATFORM_ADMIN;

//api_protect_admin_script();

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jquery_ui_js(true);
$htmlHeadXtra[] = api_get_js('jquery.jsPlumb.all.js');

//Display::display_header();
Display::display_reduced_header();

$skill  = new Skill();
$skills = $skill->get_all(true);
$type   = 'edit'; //edit

$tree   = $skill->get_skills_tree(null, true);

$skill_visualizer = new SkillVisualizer($tree, $type);

echo '<a class="a_button gray" id="add_item_link" href="#">Add item</a>';

$skill_visualizer->display_html();

$url = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?1=1';
?>
<script>

var url     = '<?php echo $url; ?>';
var skills  = []; //current window divs
var parents = []; //list of parents normally there should be only 2
var hidden_parent = '';


var offset_x                = <?php echo $skill_visualizer->offset_x; ?>;
var offset_y                = <?php echo $skill_visualizer->offset_y; ?>;
var space_between_blocks_x  = <?php echo $skill_visualizer->space_between_blocks_x; ?>;
var space_between_blocks_y  = <?php echo $skill_visualizer->space_between_blocks_y; ?>;
var center_x                = <?php echo $skill_visualizer->center_x; ?>;

var parents = ['block_1'];

jsPlumb.bind("ready", function() {
    
    $("#dialog-form").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 550, 
        height  : 380
    });
    
    //Filling skills select
    $.getJSON( "<?php echo $url.'&a=get_skills' ?>", {},     
        function(data) {
            $.each(data, function(n, parent) {
                // add a new option with the JSON-specified value and text
                $("<option />").attr("value", parent.id).text(parent.name).appendTo("#parent_id");
            });
        }
    );
    
    //Filling gradebook select
    $.getJSON( "<?php echo $url.'&a=get_gradebooks' ?>", {},     
        function(data) {
            $.each(data, function(n, gradebook) {
                // add a new option with the JSON-specified value and text
                $("<option />").attr("value", gradebook.id).text(gradebook.name).appendTo("#gradebook_id");
            });
        }
    );
    
    //On click box
    $(".open_block").live('click', function() {        
        var id = $(this).attr('id');
        
        //console.log(skills);
        //console.log('parents : '+parents[1] + ' id: '+id);
        
        //if is root
        if (parents[0] == id) {
            parents = [id];
        }
        
        
        if (parents[1] != id) {
            //means that we have 2 parents   
         
            if (parents.length == 2 ) {
                hidden_parent = parents[0];   
                //console.log('deleting: '+parents[0]);        
                //removing father
                for (var i = 0; i < skills.length; i++) {
                               
                    if ( skills[i].element == parents[0] ) {
                         //console.log('deleting :'+ skills[i].element + ' here ');                             
                         jsPlumb.deleteEndpoint(skills[i].endp);
                         $("#"+skills[i].element).remove();
                         //skills.splice(i,1)
                    }
                }
                                
                parents.splice(0,1);                
                parents.push(id); 
                console.log('addded : ' + id);               
            }
              
            if (parents.length == 1 && hidden_parent != ''){
                //show the hidden_parent
                
                parents = [hidden_parent, id];
               //    console.log(parents);
                open_parent(hidden_parent, id);
                hidden_parent = '';
            } 
            
            if (jQuery.inArray(id, parents) == -1) {                              
                parents.push(id);
            }
            open_block(id);
        }
        console.log(parents);
       // console.log(skills);        
        console.log('hidden_parent : ' + hidden_parent);         
    });
    
    function open_parent(parent_id, id) {                 
        var numeric_parent_id = parent_id.split('_')[1];
        var numeric_id = id.split('_')[1];        
        load_parent(numeric_parent_id, numeric_id);               
    }
    
    //open block function
    function open_block(id) {
        console.log("open_block id : " + id);      
        var numeric_id = id.split('_')[1];  
        for (var i = 0; i < skills.length; i++) {
            //Remove everything except parents
            if (jQuery.inArray(skills[i].element, parents) == -1) {
                 //console.log('deleting this'+ skills[i].element);
                 jsPlumb.deleteEndpoint(skills[i].endp);
                 $("#"+skills[i].element).remove();
                 //skills.splice(i,1);
                 //console.log('Removing '+skills[i].element);
            }
        }
        /*if ($('#'+id).length == 0) {
            $('body').append('<div id="'+id+'" class="open_block window " >'+id+'</div>'); 
        }*/
       
        //Modifying current block position
        pos = $('#'+id).position();        
        left_value  = center_x; 
                
        if (parents.length == 2) { 
            top_value  = space_between_blocks_y + offset_y;
        } else {
            top_value = pos.left;
        }
        jsPlumb.animate(id, {left: left_value, top:top_value}, { duration:1000 });       
        
        //Modifying root block position
        pos_parent = $('#'+parents[0]).position();
        jsPlumb.animate(parents[0], {left: center_x, top:offset_y}, { duration:1000 });
        
        
        top_value = parents.length * space_between_blocks_y; 
        load_children(numeric_id, top_value);   
    }
    
    function load_parent(parent_id, id) {
         var ix= 0;
         $.getJSON(url+'&a=load_direct_parents&id='+id, {},         
             function(json) {
                $.each(json,function(i,item) {
                    left_value  = center_x + space_between_blocks_x * ix;
                    top_value   = offset_y;
                    
                    $('body').append('<div id="block_'+item.id+ '" class="open_block window " >'+item.name+'</div>');                
                    var es = prepare("block_" + item.id,  editEndpoint);
                    var es2 = prepare("block_" + id,  editEndpoint);
                      
                    jsPlumb.connect({source: es, target:es2 });                    
                    jsPlumb.animate("block_" + item.id, {left: left_value, top : top_value}, { duration:1000 });              
                    if (item.parent_id) {
                        hidden_parent = "block_" + item.parent_id;
                    }
                    ix++;   
                });
            }
        );
    }
    
    function load_children(my_id, top_value) {        
        //Loading children
        var ix = 0;
        $.getJSON(url+'&a=load_children&id='+my_id, {},         
            function(json) {                
                $.each(json,function(i,item) {                    
                    left_value  = offset_x+space_between_blocks_x * ix;
                    //top_value   = 300;
                    $('body').append('<div id="block_'+item.id+ '" class="open_block window " >'+item.name+i+'</div>');                    
                    
                    var es = prepare("block_" + item.id,  editEndpoint);
                    var e2 = prepare("block_" + my_id,  editEndpoint);
                     
                    jsPlumb.connect({source: es, target:e2});                    
                    jsPlumb.animate("block_" + item.id, {left: left_value, top : top_value}, { duration:1000 });
                    ix++;   
                });
            }
        );
    }    
    
    $(".edit_block").click(function() {
        var my_id = $(this).attr('id');
        my_id = my_id.split('_')[2];
        return false;
    });
        
    //Filling select
    $("#add_item_link").click(function() {
        $("#dialog-form").dialog("open");
        $("#gradebook_id").addClass('chzn-select');               
        $("#gradebook_id").chosen();
        $("#parent_id").chosen();                
    });    
    
    var name = $( "#name" ),
    description = $( "#description" ),  
    allFields = $( [] ).add( name ).add( description ), tips = $(".validateTips");    
    
    $("#dialog-form").dialog({              
        buttons: {
            "Add" : function() {                
                var bValid = true;
                bValid = bValid && checkLength( name, "name", 1, 255 );
                var params = $("#add_item").serialize();          
                      
                $.ajax({
                    url: url+'&a=add&'+params,
                    success:function(data) {
                             
                        /*jsPlumb.connect({
                            source : "block_2", 
                            target : "block_1",
                            overlays : overlays            
                        });*/
                        
                        /*
                        calEvent.title          = $("#name").val();
                        calEvent.start          = calEvent.start;
                        calEvent.end            = calEvent.end;
                        calEvent.allDay         = calEvent.allDay;
                        calEvent.description    = $("#content").val();                              
                        calendar.fullCalendar('updateEvent', 
                                calEvent,
                                true // make the event "stick"
                        );*/
                        
                        $("#dialog-form").dialog("close");                                      
                    }                           
                });
            },            
        },              
        close: function() {     
            $("#name").attr('value', '');
            $("#description").attr('value', '');                
        }
    });    
 
    $(".window").bind('click', function() {
        var id = $(this).attr('id');
        id = id.split('_')[1];        
        //$("#dialog-form").dialog("open");
    });    

    // chrome fix.
    document.onselectstart = function () { return false; };             

    // render mode
    var resetRenderMode = function(desiredMode) {
        var newMode = jsPlumb.setRenderMode(desiredMode);        
        jsPlumbDemo.init();
    };    
    resetRenderMode(jsPlumb.CANVAS);       
});
var exampleDropOptions = {
                tolerance:'touch',
                hoverClass:'dropHover',
                activeClass:'dragActive'
            };

var connectorPaintStyle = {
                lineWidth:5,
                strokeStyle:"#deea18",
                joinstyle:"round"
            };
            // .. and this is the hover style. 
            var connectorHoverStyle = {
                lineWidth:7,
                strokeStyle:"#2e2aF8"
            };
            
            
            //Settings when editing stuff
            var edit_arrow_color = '#ccc';        
                
            var editEndpoint = {  
                //connectorStyle:connectorPaintStyle,
                connector:[ "Flowchart", { stub:35 } ],
                hoverPaintStyle:connectorHoverStyle,
                connectorHoverStyle:connectorHoverStyle,
                anchors: ['BottomCenter','TopCenter'],                
                endpoint:"Rectangle",
                paintStyle:{ width:20, height:20, fillStyle:edit_arrow_color },
                isSource:true,
                scope:'blue rectangle',
                maxConnections:10,
                connectorStyle : {
                    gradient:{stops:[[0, edit_arrow_color], [0.5, edit_arrow_color], [1, edit_arrow_color]]}, //gradient stuff
                    lineWidth:2,
                    strokeStyle: edit_arrow_color
                },
                isTarget:true,
                dropOptions : exampleDropOptions
            };
            
;(function() {
         
    prepare = function(elId, endpoint) {
        jsPlumbDemo.initHover(elId);
        //jsPlumbDemo.initAnimation(elId);        
        var e = jsPlumb.addEndpoint(elId, endpoint);
        jsPlumbDemo.initjulio(e);        
        skills.push({element:elId, endp:e});        
        return e;
    },
    
    window.jsPlumbDemo = {    
        initjulio :function(e) {
        },      
        initHover :function(elId) {            
            
            $("#" + elId).click(function() {
                var  all = jsPlumb.getConnections({source:elId});               
            });
            
            /*$("#" + elId).hover(
                function() { $(this).addClass("bigdot-hover"); },
                function() { $(this).removeClass("bigdot-hover"); }
            );*/
        },        
        init : function() {
        
            jsPlumb.Defaults.DragOptions = { cursor: 'pointer', zIndex:2000 };
            jsPlumb.Defaults.PaintStyle = { strokeStyle:'#666' };
            jsPlumb.Defaults.EndpointStyle = { width:20, height:16, strokeStyle:'#666' };
            jsPlumb.Defaults.Endpoint = "Rectangle";
            jsPlumb.Defaults.Anchors = ["TopCenter", "TopCenter"];

            var connections = [];
            var updateConnections = function(conn, remove) {
                if (!remove) connections.push(conn);
                else {
                    var idx = -1;
                    for (var i = 0; i < connections.length; i++) {
                        if (connections[i] == conn) {
                            idx = i; break;
                        }
                    }
                    if (idx != -1) connections.splice(idx, 1);
                }
                if (connections.length > 0) {
                    var s = "<span>current connections</span><br/><br/><table><tr><th>scope</th><th>source</th><th>target</th></tr>";
                    for (var j = 0; j < connections.length; j++) {
                        s = s + "<tr><td>" + connections[j].scope + "</td>" + "<td>" + connections[j].sourceId + "</td><td>" + connections[j].targetId + "</td></tr>";
                    }
                    jsPlumbDemo.showConnectionInfo(s);
                } else 
                    jsPlumbDemo.hideConnectionInfo();
            };              

            jsPlumb.bind("jsPlumbConnection", function(e) {
                updateConnections(e.connection);
            });
            jsPlumb.bind("jsPlumbConnectionDetached", function(e) {
                updateConnections(e.connection, true);
            });

            

            /**
                first example endpoint.  it's a 25x21 rectangle (the size is provided in the 'style' arg to the Endpoint), and it's both a source
                and target.  the 'scope' of this Endpoint is 'exampleConnection', meaning any connection starting from this Endpoint is of type
                'exampleConnection' and can only be dropped on an Endpoint target that declares 'exampleEndpoint' as its drop scope, and also that
                only 'exampleConnection' types can be dropped here.

                the connection style for this endpoint is a Bezier curve (we didn't provide one, so we use the default), with a lineWidth of
                5 pixels, and a gradient.

                note the use of the '$.extend' function to setup generic connection types.  this will save you a lot of typing, and probably
                errors.

            */
                // this is the paint style for the connecting lines..
                        
            
            jsPlumb.Defaults.Overlays = [
                [ "Arrow", { location:0.9 } ], 
                [ "Label", { 
                    location:0.1,
                    label:function(label) {
                        return label.connection.labelText || ""; 
                    },
                    cssClass:"aLabel",
         
                }] 
            ];
            
            
                           
            jsPlumb.setMouseEventsEnabled(true);            
            
            //Default
            var default_arrow_color = '#ccc';       
            var defaultEndpoint = {
                anchors: ['BottomCenter','TopCenter'],            
                endpoint:"Rectangle",
                paintStyle:{ width:1, height:1, fillStyle:default_arrow_color },
                isSource:false,
                scope:'blue rectangle',
                maxConnections:10,
                connectorStyle : {
                    gradient:{stops:[[0, default_arrow_color], [0.5, default_arrow_color], [1, default_arrow_color]]},
                    lineWidth:5,
                    strokeStyle:default_arrow_color
                },
                isTarget:false,          
                setDraggableByDefault : false,      
            };            
            
            // Done end point 
            var done_arrow_color = '#73982C';   
            var doneEndpoint = {                
                endpoint:"Rectangle",
                paintStyle:{ width:1, height:1, fillStyle:done_arrow_color},
                isSource:false,
                scope:'blue rectangle',
                maxConnections:10,
                connectorStyle : {
                    gradient:{stops:[[0, done_arrow_color], [0.5, done_arrow_color], [1, done_arrow_color]]},
                    lineWidth:5,
                    strokeStyle:done_arrow_color
                },
                isTarget:false,
                setDraggableByDefault : false,                         
            };

            <?php $skill_visualizer->display_js();?>
            // three ways to do this - an id, a list of ids, or a selector (note the two different types of selectors shown here...anything that is valid jquery will work of course)
            //jsPlumb.draggable("window1");
            //jsPlumb.draggable(["window1", "window2"]);
            //jsPlumb.draggable($("#window1"));
            var divsWithWindowClass = jsPlumbDemo.getSelector(".window");
            jsPlumb.draggable(divsWithWindowClass);

            // add the third example using the '.window' class.             
            //jsPlumb.addEndpoint(divsWithWindowClass, exampleEndpoint3);

            // each library uses different syntax for event stuff, so it is handed off
            // to the draggableConnectorsDemo-<library>.js files.
            jsPlumbDemo.attachBehaviour();          
        }
    };
})();

$(document).ready( function() {     
    //When creating a connection see
    //http://jsplumb.org/apidocs/files/jsPlumb-1-3-2-all-js.html#bind 
    jsPlumb.bind("jsPlumbConnection", function(conn) {
        //alert("Connection created " + conn.sourceId + " to " + conn.targetId + " ");
            //jsPlumb.detach(conn); 
    });
    //When double clicking a connection
    jsPlumb.bind("click", function(conn) {        
        if (confirm("Delete connection from " + conn.sourceId + " to " + conn.targetId + "?"))
            jsPlumb.detach(conn); 
    });
    
     //When double clicking a connection
    jsPlumb.bind("click", function(endpoint) {
        if (confirm("Delete connection from " + conn.sourceId + " to " + conn.targetId + "?"))
            jsPlumb.detach(conn); 
    })
});

;(function() {
    
    jsPlumbDemo.showConnectionInfo = function(s) {
        $("#list").html(s);
        $("#list").fadeIn({complete:function() { jsPlumb.repaintEverything(); }});
    };
    
    jsPlumbDemo.hideConnectionInfo = function() {
        $("#list").fadeOut({complete:function() { jsPlumb.repaintEverything(); }});
    };
    
    jsPlumbDemo.getSelector = function(spec) {
        return $(spec);
    };
    
    jsPlumbDemo.attachBehaviour = function() {
        $(".hide").click(function() {
            jsPlumb.toggle($(this).attr("rel"));
        });

        $(".drag").click(function() {
            var s = jsPlumb.toggleDraggable($(this).attr("rel"));
            $(this).html(s ? 'disable dragging' : 'enable dragging');
            if (!s) $("#" + $(this).attr("rel")).addClass('drag-locked'); else $("#" + $(this).attr("rel")).removeClass('drag-locked');
            $("#" + $(this).attr("rel")).css("cursor", s ? "pointer" : "default");
        });

        $(".detach").click(function() {
            jsPlumb.detachAll($(this).attr("rel"));
        });

        $("#clear").click(function() { 
            jsPlumb.detachEverything(); showConnections(); 
        });
    };

})();

function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        o.addClass( "ui-state-error" );
        updateTips( "Length of " + n + " must be between " +
            min + " and " + max + "." );
        return false;
    } else {
        return true;
    }
}
</script>

<div id="dialog-form" style="display:none;">
    <div style="width:500px">
    <form id="add_item" name="form">
        <div class="row">
            <div class="label">
                <label for="name">Name</label>
            </div>      
            <div class="formw">
                <input type="text" name="name" id="name" size="40" />             
            </div>
        </div>        
        <div class="row">
            <div class="label">
                <label for="name">Parent</label>
            </div>      
            <div class="formw">
                <select id="parent_id" name="parent_id" />
                </select>                  
            </div>
        </div>
        
        <div class="row">
            <div class="label">
                <label for="name">Gradebook</label>
            </div>      
            <div class="formw">
                <select id="gradebook_id" name="gradebook_id[]" multiple="multiple" />
                </select>             
                <span class="help-block">
                Gradebook Description
                </span>           
            </div>
        </div>
        <div class="row">
            <div class="label">
                <label for="name">Description</label>
            </div>      
            <div class="formw">
                <textarea name="description" id="description" cols="40" rows="7"></textarea>
            </div>
        </div>  
    </form>
    </div>
</div>
<?php    

//Display::display_footer();

// The header.
/*
$tpl = new Template();

$tpl->assign('content', $content);
$tpl->display_one_col_template();

 *
 */