// Arrow settings

var debug = 1;

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

//Admin arrows
  

var editEndpointOptions = {  
    isTarget:true, 
    maxConnections:100,
    endpoint:"Rectangle",    
    paintStyle:{ 
       fillStyle:"yellow" },
    detachable:false,
    connector:"Straight"
};


//Student arrows    

//If user does not completed the skill
var default_arrow_color = '#ccc';     //gray  
var defaultEndpoint = {
    connector:[ "Flowchart", { stub:28 } ],
    anchors: ['BottomCenter','TopCenter'],            
    endpoint:"Rectangle",
    paintStyle:{ width:1, height:1, fillStyle:default_arrow_color },
    isSource:false,
    scope:'blue rectangle',
    maxConnections:10,
    connectorStyle : {
        gradient:{ stops:[[0, default_arrow_color], [0.5, default_arrow_color], [1, default_arrow_color]] },
        lineWidth:5,
        strokeStyle:default_arrow_color
    },
    isTarget:false,          
    setDraggableByDefault : false,      
};            

// If user completed the skill 
var done_arrow_color = '#73982C'; //green   
var doneEndpointOptions = {                
    connector:[ "Flowchart", { stub:28 } ],
    anchors: ['BottomCenter','TopCenter'],    
    endpoint:"Rectangle",
    paintStyle:{ width:1, height:1, fillStyle:done_arrow_color},
    isSource:false,
    scope:'blue rectangle',
    maxConnections:10,
    connectorStyle : {
        gradient:{ stops:[[0, done_arrow_color], [0.5, done_arrow_color], [1, done_arrow_color]] },
        lineWidth:5,
        strokeStyle:done_arrow_color
    },
    isTarget:false,
    setDraggableByDefault : false,                         
};

//Functions   

/* Clean window block classes*/
function cleanclass(obj) {
    obj.removeClass('first_window');
    obj.removeClass('second_window');
    obj.removeClass('third_window');
}

/* When clicking the red block */
function open_parent(parent_id, id) {   
    console.log("open_parent call : id " + id + " parent_id:" + parent_id);                
    var numeric_parent_id = parent_id.split('_')[1];
    var numeric_id = id.split('_')[1];    
    
    $('#'+id).css('top', '0px');
    
    // @todo move this in a function
    var final_left =0;
    var normal_width = 0;

    $('.skill_child').each( function(){                                          
        normal_width = $(this).width();
        return true;                    
    });    


    
    load_parent(numeric_parent_id, numeric_id);
}

function open_block_student(id) {
    open_block(id, 1)
}

/*   
 *  When clicking a children block 
    @param  string block id i.e "block_1" 
    @param  int load user data or not    
*/
function open_block(id, load_user_data, create_root) {
    if (debug) console.log("open_block id : " + id+" load_user_data: " +load_user_data + ', create_root ' + create_root);      
    
    var numeric_id = id.split('_')[1];    
    
    for (var i = 0; i < skills.length; i++) {
        //Remove everything except parents
        if (jQuery.inArray(skills[i].element, parents) == -1) {
            //if (debug) console.log('deleting this skill '+ skills[i].element + " id: " + i);
            jsPlumb.detachAllConnections(skills[i].element);
            jsPlumb.removeAllEndpoints(skills[i].element);            
            $("#"+skills[i].element).remove();                 
        }
    }        

    /*/Modifying current block position
    pos = $('#'+id).position();        
    left_value  = center_x; 

    if (parents.length == 2) { 
        top_value  = space_between_blocks_y + offset_y;
    } else {
        top_value = 100;
    }       
    
    jsPlumb.animate(id, { left: left_value, top:top_value }, { duration:duration_value });       

    //Modifying root block position
    pos_parent = $('#'+parents[0]).position();
    jsPlumb.animate(parents[0], { left: center_x, top:offset_y }, { duration:duration_value });
    top_value = 2*space_between_blocks_y +offset_y ; */
    
    load_children(numeric_id, 0, load_user_data, create_root);    
}

function load_children(my_id, top_value, load_user_data, create_root) {
    if (debug) console.log("load_children : my_id " + my_id + ", top_value:" + top_value +", load_user_data: "+load_user_data+", create_root: "+create_root);
    
    //Fix the block vertical position
    //$('#block_'+my_id).css('top', '150px');

    my_top_root = 150;
    my_top_children = 250;    

    //Creating the root           
    if (create_root == 1) {
        my_top_root = 0;
        my_top_children = 150;
        if (my_id == 1) {      
            $('#skill_tree').append('<div id="block_'+my_id+'" class="skill_root first_window" >Root </div>');
        } else {      
            console.log('Loading root info by ajax '+my_id);
            $.ajax({
                async: false,//very important other wise it would not work
                url: url+'&a=get_skill_info&id='+my_id,             
                success: function(json) {
                    var skill = jQuery.parseJSON(json);                  
                    $('#skill_tree').append('<div id="block_'+my_id+'" class="skill_root open_block first_window" >' +skill.name+'</div>');                    
                    console.log('Adding root block: '+my_id);
                }
            });
        }
        
        //Adding to the skill list
        skills.push({
            element: "block_" + my_id
        });
        
        //   jsPlumb.animate('block_'+my_id, { left: 500, top:50 }, { duration: 100 });       
        /*
        var root_end_point_options = {  
        //     anchor:"Continuous",
            maxConnections:100,
            endpoint:"Dot", 
            paintStyle:{ fillStyle:"gray" },        
        };

        //The root is the source    
        jsPlumb.makeSource("block_" + my_id, root_end_point_options);*/
    }
    
    //Loading children
    
    $.ajax({
        url: url+'&a=load_children&load_user_data='+load_user_data+'&id='+my_id,
        dataType: 'json',
        async: false,        
        success: function(result) {
            if (result.success) {
                json = result.data;
                
                console.log('getJSON result: ' + result.success);
                //console.log('getJSON json: ' + json);

                $.each(json,function(i, item) {
                    if (debug) console.log('Loading children: #' + item.id + " " +item.name);
        
                     //item.name = '<a href="#" class="edit_block" id="edit_block_'+item.id+'">'+item.name+'</a>';                    
                    item.name = '<a href="#" class="edit_block" id="edit_block_'+item.id+'">'+item.name+'</a>';                    

                    var status_class = ' ';
                    my_edit_point_options = editEndpointOptions;
                    if (item.passed == 1) {
                        my_edit_point_options = doneEndpointOptions;
                        status_class = 'done_window';
                    }

                    $('#skill_tree').append('<div id="block_'+item.id+ '" class="skill_child open_block '+status_class+'" >'+item.name+'</div>');
                    
                    //Fix the block vertical position
                    $('#block_'+item.id).css('top', my_top_children+'px');
                    
                    if (debug) console.log('Append block: '+item.id);

                    endpoint = jsPlumb.makeTarget("block_" + item.id, my_edit_point_options);                

                    jsPlumb.connect({source: "block_" + my_id, target:"block_" + item.id});

                    skills.push({
                        element: "block_" + item.id, endp:endpoint
                    });

                    console.log('added to array skills id: ' + item.id+" - name: "+item.name);
                });

                jsPlumb.draggable(jsPlumb.getSelector(".skill_child"));
                //jsPlumb.draggable(jsPlumb.getSelector(".skill_root"));

                console.log('draggable');

                //Fixing the root position in the organigram
                var final_left =0;
                var normal_width = 0;
                
                $('.skill_child').each( function(){                                          
                    normal_width = $(this).width();
                    return true;                    
                });              
                
                //normal_width = $('.skill_child :first').width();                
                final_left = $('body').width() / 2 - normal_width/2;
                
                console.log('normal_width ----->   '+normal_width);
                console.log('body.width ----->   '+$('body').width());   
                console.log('final_left ----->   '+final_left);   
                
                jsPlumb.animate('block_'+my_id, { left: final_left, top:my_top_root }, { duration: 100 });                
                console.log('setting animate for block_'+my_id);                
                console.log('final parents '+parents);
            } //result
        }
    });
}

/* Loads parent blocks */
function load_parent(parent_id, id) {
    if (debug) console.log("load_parent call : id " + id + " parent_id:" + parent_id);         
    $.ajax({
        url: url+'&a=load_direct_parents&id='+id,
        async: false, 
        success: function(json) {              
            var json = jQuery.parseJSON(json);

            $.each(json,function(i,item) {
                if (item.parent_id == 0) {

                    $('#skill_tree').append('<div id="block_'+item.id+ '" class="skill_root first_window ">'+item.name+'</div>');                

                    jsPlumb.connect({
                        source: 'block_'+parent_id, target:'block_'+id 
                    });
                    if (debug) console.log('setting NO--- first_parent ');                    
                    first_parent = '';             
                } else {
                    $('#skill_tree').append('<div id="block_'+item.id+ '" class="open_block skill_child ">'+item.name+'</div>');                

                    jsPlumb.connect({
                        source: 'block_'+parent_id, target:'block_'+id 
                    });

                    if (debug) console.log('setting first_parent '+item.parent_id);
                    first_parent = "block_" + item.parent_id;             
                    jsPlumb.draggable(jsPlumb.getSelector(".skill_root"));
                }                
                my_top_root = 0;
                var normal_width = 0;
                
                $('.skill_child').each( function(){                                          
                    normal_width = $(this).width();
                    return true;                    
                });              
                final_left = $('body').width() / 2 - normal_width/2;

                console.log('normal_width ----->   '+normal_width);
                console.log('body.width ----->   '+$('body').width());   
                console.log('final_left ----->   '+final_left);                   

                $('#block_'+item.id).css('left', final_left+'px');
                jsPlumb.repaint('block_'+item.id);
                
                

                //jsPlumb.animate(id, { left: final_left, top:my_top_root }, { duration: 100 });
                //jsPlumb.animate(parent_id, { left: final_left, top:my_top_root }, { duration: 100 });   

            });                
        }
    });
}

function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        o.addClass( "ui-state-error" );
        updateTips( "Length of " + n + " must be between " +min + " and " + max + "." );
        return false;
    } else {
        return true;
    }
}

function updateTips( t ) {
    tips = $( ".validateTips" )
    tips
        .text( t )
        .addClass( "ui-state-highlight" );
    setTimeout(function() {
        tips.removeClass( "ui-state-highlight", 1500 );
    }, 500 );
}