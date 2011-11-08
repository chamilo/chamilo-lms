<script type="text/javascript">

//js settings
var url             = '{$url}';
var skills          = []; //current window divs
var parents         = []; //list of parents normally there should be only 2
var hidden_parent   = '';
var duration_value  = 500;


//Block settings see the SkillVisualizer Class
var offset_x                = {$skill_visualizer->offset_x};
var offset_y                = {$skill_visualizer->offset_y};
var space_between_blocks_x  = {$skill_visualizer->space_between_blocks_x};
var space_between_blocks_y  = {$skill_visualizer->space_between_blocks_y};
var center_x                = {$skill_visualizer->center_x};
var block_size              = {$skill_visualizer->block_size};

//Setting the parent by default 
var parents = ['block_1'];

/* Clean window block classes*/
function cleanclass(obj) {
    obj.removeClass('first_window');
    obj.removeClass('second_window');
    obj.removeClass('third_window');
}

jsPlumb.bind("ready", function() {
    
    //Open dialog
    $("#dialog-form").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 550, 
        height  : 480,
    });
   
    
    //On box click -(we use live instead of bind because we're creating divs on the fly )
    $(".open_block").live('click', function() {        
        var id = $(this).attr('id');
        
        //if is root
        if (parents[0] == id) {
            parents = [id];
        }     
        
        if (parents[1] != id) {            
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
            }     
                
            if ($(this).hasClass('first_window')) {                
                 //show the hidden_parent
                //if (hidden_parent != '') {
                   parents = [hidden_parent, id];
                   //    console.log(parents);
                   open_parent(hidden_parent, id);
                //}
            }
            if (jQuery.inArray(id, parents) == -1) {                              
                parents.push(id);
            }
            open_block(id);
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
        console.log('hidden_parent : ' + hidden_parent);         
    });
    
    
    
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
            },
        });
        
                
        $("#gradebook_id").trigger("liszt:updated");
        $("#parent_id").trigger("liszt:updated");
        
                
        $("#dialog-form").dialog("open");
        return false;
    });
        
    //Filling select
    $("#add_item_link").click(function() {        
        
        $("#name").attr('value', '');
        $("#description").attr('value', '');
          
        
        $("#dialog-form").dialog("open");
                      
    });    
    
      
    
    $("#dialog-form").dialog({
        close: function() {     
            $("#name").attr('value', '');
            $("#description").attr('value', '');                
        }
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
    
    
    
         
    prepare = function(elId, endpoint) {
        jsPlumbDemo.initHover(elId);
        //jsPlumbDemo.initAnimation(elId);        
        var e = jsPlumb.addEndpoint(elId, endpoint);
        jsPlumbDemo.initjulio(e);        
        skills.push({
            element:elId, endp:e
        });        
        return e;
    },
    
    window.jsPlumbDemo = {    
        initjulio :function(e) {
        },      
        initHover :function(elId) {            
            
            $("#" + elId).click(function() {
                var  all = jsPlumb.getConnections({
                    source:elId
                });               
            });
            
            /*$("#" + elId).hover(
                function() { $(this).addClass("bigdot-hover"); },
                function() { $(this).removeClass("bigdot-hover"); }
            );*/
        },        
        init : function() {
        
            jsPlumb.Defaults.DragOptions    = { cursor: 'pointer', zIndex:2000 };
            jsPlumb.Defaults.PaintStyle     = { strokeStyle:'#666' };
            jsPlumb.Defaults.EndpointStyle  = { width:20, height:16, strokeStyle:'#666' };
            jsPlumb.Defaults.Endpoint       = "Rectangle";
            jsPlumb.Defaults.Anchors        = ["TopCenter", "TopCenter"];

            

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
                //[ "Arrow", { location:0.5 } ],  if you want to add an arrow in the connection          
            ];
                           
            jsPlumb.setMouseEventsEnabled(true);            
            
            {$js}
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


;(function() {
    
    jsPlumbDemo.showConnectionInfo = function(s) {
        $("#list").html(s);
        $("#list").fadeIn({ complete:function() { jsPlumb.repaintEverything(); }});
    };
    
    jsPlumbDemo.hideConnectionInfo = function() {
        $("#list").fadeOut({ complete:function() { jsPlumb.repaintEverything(); }});
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


$(document).ready( function() {     
    //When creating a connection see
    //http://jsplumb.org/apidocs/files/jsPlumb-1-3-2-all-js.html#bind 
    jsPlumb.bind("jsPlumbConnection", function(conn) {
        //alert("Connection created " + conn.sourceId + " to " + conn.targetId + " ");
            //jsPlumb.detach(conn); 
    });
});

</script>

{$html}

<div id="dialog-form" style="display:none;">
    
    <form id="add_item" name="form">
        <input type="hidden" name="id" id="id"/>
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
                <label for="name">Gradebook</label>
            </div>      
            <div class="formw">
                <select id="gradebook_id" name="gradebook_id[]" multiple="multiple"/>
                </select>
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