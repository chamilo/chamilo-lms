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
        connector:[ "Flowchart", { stub:28 } ],
        hoverPaintStyle:connectorHoverStyle,
        connectorHoverStyle:connectorHoverStyle,
        anchors: ['BottomCenter','TopCenter'],                
        endpoint:"Rectangle",
        paintStyle:{ width:10, height:10, fillStyle:edit_arrow_color },
        isSource:true,
        scope:'blue rectangle',
        maxConnections:10,
        connectorStyle : {
            gradient:{
                stops:[[0, edit_arrow_color], [0.5, edit_arrow_color], [1, edit_arrow_color]]
            }, //gradient stuff
            lineWidth:2,
            strokeStyle: edit_arrow_color
        },
        isTarget:true,
        dropOptions : exampleDropOptions
    };
    
    
    


    /* Clean window block classes*/
    function cleanclass(obj) {
        obj.removeClass('first_window');
        obj.removeClass('second_window');
        obj.removeClass('third_window');
    }

    function open_parent(parent_id, id) {   
        console.log("open_parent call : id " + id + " parent_id:" + parent_id);                
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
        jsPlumb.animate(id, { left: left_value, top:top_value }, { duration:duration_value });       
        
        //Modifying root block position
        pos_parent = $('#'+parents[0]).position();
        jsPlumb.animate(parents[0], { left: center_x, top:offset_y }, { duration:duration_value });       
        
        top_value = 2*space_between_blocks_y +offset_y ; 
        load_children(numeric_id, top_value);   
    }
    
    function load_parent(parent_id, id) {
        console.log("load_parent call : id " + id + " parent_id:" + parent_id);
        var ix= 0;         
        $.ajax({
            url: url+'&a=load_direct_parents&id='+id,
            async: false, 
            success: function(json) {                
                var json = jQuery.parseJSON(json);
                
                $.each(json,function(i,item) {
                    left_value  = center_x + space_between_blocks_x * ix;
                    top_value   = offset_y;
                    
                    $('body').append('<div id="block_'+item.id+ '" class="open_block window " >'+item.name+'</div>');                
                    var es  = prepare("block_" + item.id,  editEndpoint);
                    var es2 = prepare("block_" + id,  editEndpoint);
                      
                    jsPlumb.connect({
                        source: es, target:es2 
                    });                    
                    jsPlumb.animate("block_" + item.id, { left: left_value, top : top_value }, { duration : duration_value});
                    
                    if (item.parent_id) {
                        console.log('setting hidden_parent '+item.parent_id);
                        hidden_parent = "block_" + item.parent_id;                        
                    } else {
                        console.log('setting NO--- hidden_parent ');
                    }
                    ix++;   
                });
                
            }
         });
    }
    
    function load_children(my_id, top_value) {
        console.log("load_children call : my_id " + my_id + " top_value:" + top_value);        
        //Loading children
        var ix = 0;
        $.getJSON(url+'&a=load_children&id='+my_id, {},         
            function(json) {                
                $.each(json,function(i,item) {                    
                    left_value  = ix*space_between_blocks_x +  center_x/2 - block_size / 2;
                    //top_value   = 300;
                    //Display::url($skill['name'], '#', array('id'=>'edit_block_'.$block_id, 'class'=>'edit_block'))
                    item.name = '<a href="#" class="edit_block" id="edit_block_'+item.id+'">'+item.name+'</a>'; 
                    
                    $('body').append('<div id="block_'+item.id+ '" class="third_window open_block window " >'+item.name+'</div>');                    
                    
                    var es = prepare("block_" + item.id,  editEndpoint);
                    var e2 = prepare("block_" + my_id,  editEndpoint);
                     
                    jsPlumb.connect({
                            source: es, target:e2
                    });                    
                    jsPlumb.animate("block_" + item.id, {
                        left: left_value, top : top_value
                        }, { duration:duration_value});
                    ix++;   
                });
            }
        );
    }
    
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
    
