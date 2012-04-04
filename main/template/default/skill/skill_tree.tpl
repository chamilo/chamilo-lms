<style>
/* just because */
body {    
    /* fallback */ 
    background-color: #eee; 
    background-image: url(images/radial_bg.png); 
    background-position: center center; 
    background-repeat: no-repeat; 
    
    /* Safari 4-5, Chrome 1-9 */ /* Can't specify a percentage size? Laaaaaame. */ 
    background: -webkit-gradient(radial, center center, 0, center center, 460, from(#eee), to(#666)); 
    /* Safari 5.1+, Chrome 10+ */ 
    background: -webkit-radial-gradient(circle, #eee, #666); 
    /* Firefox 3.6+ */ 
    background: -moz-radial-gradient(circle, #eee, #666); 
    /* IE 10 */ 
    background: -ms-radial-gradient(circle, #eee, #666);
}
</style>
<script type="text/javascript">

//js settings
var url             = '{{url}}';
var skills          = []; //current window divs
var parents         = []; //list of parents normally there should be only 2
var first_parent   = '';
var duration_value  = 500;


//Block settings see the SkillVisualizer Class
var offset_x                = {{skill_visualizer.offset_x}};
var offset_y                = {{skill_visualizer.offset_y}};
var space_between_blocks_x  = {{skill_visualizer.space_between_blocks_x}};
var space_between_blocks_y  = {{skill_visualizer.space_between_blocks_y}};
var center_x                = {{skill_visualizer.center_x}};
var block_size              = {{skill_visualizer.block_size}};

//Setting the parent by default 
var parents = ['block_1'];

jsPlumb.ready(function() {
    
    //Open dialog
    $("#dialog-form").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 550, 
        height  : 480
    });
    
    //Filling skills select
    /*
    $.getJSON("{{url}}&a=get_skills&parent_id="+parents[0], {},     
        function(data) {
            $.each(data, function(n, parent) {
                // add a new option with the JSON-specified value and text
                $("<option />").attr("value", parent.id).text(parent.name).appendTo("#parent_id");
            });
        }
    );*/
    
    //Filling gradebook select
    $.getJSON("{{url}}&a=get_gradebooks", {},     
        function(data) {
            $.each(data, function(n, gradebook) {
                // add a new option with the JSON-specified value and text
                $("<option />").attr("value", gradebook.id).text(gradebook.name).appendTo("#gradebook_id");
            });
        }
    );
    
    //Add button
    
    $("#add_item_link").click(function() {        
        $("#name").attr('value', '');
        $("#description").attr('value', '');        
        $("#parent_id option:selected").removeAttr('selected');
        $("#gradebook_id option:selected").removeAttr('selected');               
        $("#dialog-form").dialog("open");
        
        //Filling skills select
        var my_id = 1;
        
        if (parents.length > 1) {
            my_id = parents[1].split('_')[1];            
        }
        //Remove all options
        $("#parent_id").find('option').remove();

        $.getJSON("{{url}}&a=get_skills&id="+my_id, {
            },     
            function(data) {
                $.each(data, function(n, parent) {
                    // add a new option with the JSON-specified value and text
                    $("<option />").attr("value", parent.id).text(parent.name).appendTo("#parent_id");
                });
            }
        );
    });
    
    var name = $( "#name" ),
    description = $( "#description" ),  
    allFields = $( [] ).add( name ).add( description ), tips = $(".validateTips");    
    
    //Add button process
    
    $("#dialog-form").css('z-index', '9001');
    
    $("#dialog-form").dialog({              
        buttons: {
            "{{"Add"|get_lang}}" : function() {                
                var bValid = true;
                bValid = bValid && checkLength( name, "name", 1, 255 );
                
                if (bValid) {
                    var params = $("#add_item").serialize();
                          
                    $.ajax({
                        url: url+'&a=add&'+params,
                        success:function(data) {                            
                            //new window
                            parent_id = $("#parent_id option:selected").attr('value');                            
                        
                            //Great stuff                         
                            open_block('block_'+parent_id, 0);
                                                                     
                            $("#dialog-form").dialog("close");                                      
                        }                           
                    });
                }
            }
        },              
        close: function() {     
            $("#name").attr('value', '');
            $("#description").attr('value', '');                
        }
    });
    
    //Clicking in a box skill (we use live instead of bind because we're creating divs on the fly )
    $(".open_block").live('click', function() {      
        var id = $(this).attr('id');
        
        console.log('click.open_block id: ' + id);
        console.log('parents: ' + parents);
        
        //if is root
        if (parents[0] == id) {
            parents = [id];
        }     
        
        if (parents[1] != id) {
            console.log('parents.length ' +parents.length);
            
            if (parents.length == 2 ) {
                first_parent = parents[0];   
                //console.log('deleting: '+parents[0]);        
                //removing father
                console.log("first_parent " + first_parent);
                
                for (var i = 0; i < skills.length; i++) {  
                    console.log('looping '+skills[i].element + ' ');
                    if (skills[i].element == parents[0] ) {
                        console.log('deleting parent:'+ skills[i].element + ' here ');                             
                        jsPlumb.deleteEndpoint(skills[i].element);
                        jsPlumb.detachAllConnections(skills[i].element);
                        jsPlumb.removeAllEndpoints(skills[i].element);  
                        $("#"+skills[i].element).remove();
                    }
                }                                
                parents.splice(0,1);                
                parents.push(id);
                console.log('parents after slice/push: ' + parents);
            }     
                
            if ($(this).hasClass('first_window')) {  
                console.log('im in a first_window (root)');
                //show the first_parent
                //if (first_parent != '') {
                   parents = [first_parent, id];
                   console.log(parents);
                   open_parent(first_parent, id);
                //}
            }
            if (jQuery.inArray(id, parents) == -1) {                              
                parents.push(id);
                console.log('parents  push' + parents);
            }
            open_block(id, 0);
        }
        
        //Setting class       
        cleanclass($(this));
        $(this).addClass('second_window');        
        
        parent_div = $("#"+parents[0]);
        cleanclass(parent_div);
        parent_div.addClass('first_window');
        
        parent_div = $("#"+parents[1]);        
        cleanclass(parent_div);
        parent_div.addClass('second_window');
       
        console.log(parents);
       // console.log(skills);        
        console.log('first_parent : ' + first_parent);         
    });
    
    //Skill title click  
    $(".edit_block").live('click',function() {      
        var my_id = $(this).attr('id');
        my_id = my_id.split('_')[2];
        
        //Cleaning selected
        $("#parent_id option:selected").removeAttr('selected');
        $("#gradebook_id option:selected").removeAttr('selected');
        
        $.ajax({
            url: url+'&a=get_skill_info&id='+my_id,             
            success: function(json) {
                var skill = jQuery.parseJSON(json);
                $("#name").attr('value', skill.name);
                $("#id").attr('value',   skill.id);                        
                $("#description").attr('value', skill.description);                
                //filling parent_id
                $("#parent_id option[value='"+skill.extra.parent_id+"']").attr('selected', 'selected');
                //filling the gradebook_id         
                jQuery.each(skill.gradebooks, function(index, data) {                    
                    $("#gradebook_id option[value='"+data.id+"']").attr('selected', 'selected');            
                });
            }
        });        
                
        $("#gradebook_id").trigger("liszt:updated");
        $("#parent_id").trigger("liszt:updated");
        $("#dialog-form").dialog("open");
        return false;
    });
        
    
    //Clicking in a box
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
    
;(function() {
         
    prepare = function(div, endpointOptions) {
        console.log('preparing = '+div);
        console.log('endpointOptions = '+endpointOptions);
        //jsPlumbDemo.initHover(elId);
        //jsPlumbDemo.initAnimation(elId);        
        var endPoint = jsPlumb.addEndpoint(div, endpointOptions);
        //jsPlumbDemo.initjulio(e);        
        skills.push({
            element: div, endp:endPoint
        });
        return endPoint;        
    },    
    window.jsPlumbDemo = {  	   
        init : function() {
            console.log('Import defaults');
            
            jsPlumb.Defaults.Anchors = [ "BottomCenter", "TopCenter" ];
            //jsPlumb.DefaultDragOptions = { cursor: "crosshair", zIndex:2000 };
            jsPlumb.Defaults.Container = "skill_tree";
                
            
            /*jsPlumb.Defaults.PaintStyle     = { strokeStyle:'#666' };
            jsPlumb.Defaults.EndpointStyle  = { width:20, height:16, strokeStyle:'#666' };
            jsPlumb.Defaults.Endpoint       = "Rectangle";
            jsPlumb.Defaults.Anchors        = ["TopCenter", "TopCenter"];*/
/*
            // this is the paint style for the connecting lines..
			var connectorPaintStyle = {
				lineWidth:5,
				strokeStyle:"#deea18",
				joinstyle:"round"
			},
			// .. and this is the hover style. 
			connectorHoverStyle = {
				lineWidth:7,
				strokeStyle:"#2e2aF8"
			},
            // the definition of source endpoints (the small blue ones)
			sourceEndpoint = {
				endpoint:"Dot",
				paintStyle:{ fillStyle:"#225588",radius:7 },
				isSource:true,
				connector:[ "Flowchart", { stub:40 } ],
				connectorStyle:connectorPaintStyle,
				hoverPaintStyle:connectorHoverStyle,
				connectorHoverStyle:connectorHoverStyle,
                dragOptions:{
                },
                overlays:[
                	[ "Label", { 
	                	location:[0.5, 1.5], 
	                	label:"Drag",
	                	cssClass:"endpointSourceLabel" 
	                } ]
                ]
			},
			// a source endpoint that sits at BottomCenter
			bottomSource = jsPlumb.extend( { anchor:"BottomCenter" }, sourceEndpoint),
			// the definition of target endpoints (will appear when the user drags a connection) 
			targetEndpoint = {
				endpoint:"Dot",					
				paintStyle:{ fillStyle:"#558822",radius:11 },
				hoverPaintStyle:connectorHoverStyle,
				maxConnections:-1,
				dropOptions:{ hoverClass:"hover", activeClass:"active" },
				isTarget:true,			
                overlays:[
                	[ "Label", { location:[0.5, -0.5], label:"Drop", cssClass:"endpointTargetLabel" } ]
                ]
			},	
            init = function(connection) {
				connection.getOverlay("label").setLabel(connection.sourceId.substring(6) + "-" + connection.targetId.substring(6));
			};	
            
            var allSourceEndpoints = [], allTargetEndpoints = [];
            _addEndpoints = function(toId, sourceAnchors, targetAnchors) {
                for (var i = 0; i < sourceAnchors.length; i++) {
                    var sourceUUID = toId + sourceAnchors[i];
                    allSourceEndpoints.push(jsPlumb.addEndpoint(toId, sourceEndpoint, { anchor:sourceAnchors[i], uuid:sourceUUID }));
                }
                for (var j = 0; j < targetAnchors.length; j++) {
                    var targetUUID = toId + targetAnchors[j];
                    allTargetEndpoints.push(jsPlumb.addEndpoint(toId, targetEndpoint, { anchor:targetAnchors[j], uuid:targetUUID }));
                }
            };
            
            console.log('addEndpoints');

            _addEndpoints("window4", ["TopCenter", "BottomCenter"], ["LeftMiddle", "RightMiddle"]);
			_addEndpoints("window2", ["LeftMiddle", "BottomCenter"], ["TopCenter", "RightMiddle"]);
			_addEndpoints("window3", ["RightMiddle", "BottomCenter"], ["LeftMiddle", "TopCenter"]);
			_addEndpoints("window1", ["LeftMiddle", "RightMiddle"], ["TopCenter", "BottomCenter"]);

         	// listen for new connections; initialise them the same way we initialise the connections at startup.
			jsPlumb.bind("jsPlumbConnection", function(connInfo, originalEvent) { 
				init(connInfo.connection);
			});
						
			// make all the window divs draggable						
			jsPlumb.draggable(jsPlumb.getSelector(".window"));

			// connect a few up
			jsPlumb.connect({
                                uuids:["window2BottomCenter", "window3TopCenter"]});
			jsPlumb.connect({
                                uuids:["window2LeftMiddle", "window4LeftMiddle"]});
			jsPlumb.connect({
                                uuids:["window4TopCenter", "window4RightMiddle"]});
			jsPlumb.connect({
                                uuids:["window3RightMiddle", "window2RightMiddle"]});
			jsPlumb.connect({
                                uuids:["window4BottomCenter", "window1TopCenter"]});
			jsPlumb.connect({
                                uuids:["window3BottomCenter", "window1BottomCenter"]});
*/
            //jsPlumb.setMouseEventsEnabled(true);
            
            open_block('block_1', 0);
            
            
            
            {# $js #}
                
            // listen for clicks on connections, and offer to delete connections on click.			
			jsPlumb.bind("click", function(conn, originalEvent) {
				/*if (confirm("Delete connection from " + conn.sourceId + " to " + conn.targetId + "?"))
					jsPlumb.detach(conn); */
			});            
        }
    };
})();

$(document).ready(function() {
/*
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
    });*/
    
    $(".chzn-select").chosen();
    $("#menu").draggable();
});

;(function() {
   
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

</script>
<div id="menu" class="well" style="top:20px; left:20px; width:300px; z-index: 9000; opacity: 0.9;">
    <h3>{{'Skills'|get_lang}}</h3>
    <div class="btn-group">
        <a style="z-index: 1000" class="btn" id="add_item_link" href="#">{{'AddSkill'|get_lang}}</a>
        <a style="z-index: 1000" class="btn" id="return_to_admin" href="{{_p.web_main}}admin">{{'BackToAdmin'|get_lang}}</a>
    </div>
</div>
           
<div id="skill_tree">
</div>
{# $html #}

<div id="dialog-form" style="display:none; z-index:9001;">    
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
</div>