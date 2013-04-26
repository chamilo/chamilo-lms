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

//js settings (see the skills.js for more)
var url             = '{{url}}';

//Block settings see the SkillVisualizer Class
var offset_x                = {{skill_visualizer.offset_x}};
var offset_y                = {{skill_visualizer.offset_y}};
var space_between_blocks_x  = {{skill_visualizer.space_between_blocks_x}};
var space_between_blocks_y  = {{skill_visualizer.space_between_blocks_y}};
var center_x                = {{skill_visualizer.center_x}};
var block_size              = {{skill_visualizer.block_size}};

$(window).resize(function() {    
    jsPlumb.repaintEverything();    
    /*jsPlumb.repaint(".skill_root");    
    // When resize repaint everything + fix the root position
    normal_weight = $('.skill_child :first-child').width();                
    sum = $('body').width() / 2 - normal_weight/2;
    $('.skill_root').animate({ left: sum, top:0 }, { duration: 100 });*/
});

jsPlumb.ready(function() {
    //Setting the loading dialog
    var loading = $( "#dialog-loading" );    
    loading.dialog( "destroy" );
    loading.dialog({
        autoOpen:false,
        height: 120,
        modal: true,
        zIndex: 10000,
        resizable :false,        
        closeOnEscape : false,
        disabled: true,            
        open: function(event, ui) { $(this).parent().children().children('.ui-dialog-titlebar-close').hide(); }
    });    
    
    jQuery.ajaxSetup({
        beforeSend: function() {
            loading.dialog( "open" );
            //$('#skill_tree').hide();
            console.log('before------------------->>');
        },
        complete: function(){
            loading.dialog( "close" );                    
            //$('#skill_tree').show();
            console.log('complete------------------->>');
        },
        success: function() {}
    });
        
    //Return to root button
    $('#return_to_root').live('click', function(){
        clean_values();
        console.log('Clean values');        
        console.log('Reopen the root ');
    });
    
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
    
    //Add button on click    
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
    
    //Add button process
    
    var name = $( "#name" ),
    description = $( "#description" ),  
    allFields = $( [] ).add( name ).add( description ), tips = $(".validateTips");    
    
    $("#dialog-form").css('z-index', '9001');
    
    $("#dialog-form").dialog({              
        buttons: {
            "{{"Add"|get_lang}}" : function() {                
                var bValid = true;
                bValid = bValid && checkLength( name, "name", 1, 255 );
                
                if (bValid) {
                    var params = $("#add_item").serialize();
                          
                    $.ajax({
                        async: false,
                        url: url+'&a=add&'+params,
                        success:function(my_id) {                            
                            //Setting the selected id from the form
                            parent_id = $("#parent_id option:selected").attr('value');                            
                            
                            //Reseting jsplumb
                            jsPlumb.reset();                            
                            //Deletes all windows
                            $('.skill_root').remove();
                            $('.skill_child').remove();
                            
                            //cleaning skills
                            skills = [];
                            
                            //Setting the first parent
                            first_parent = parents[0];
                            
                            //Deleting the first parent
                            console.log('parents before '+parents);
                            parents.splice(0,1);     
                            console.log('parents now '+parents);                            
                            
                            //Remove parent block                      
                            $('#block_'+parent_id).remove();
                            
                            //Open the block                 
                            open_block('block_'+parent_id, 0, 1);                            
                            
                            //Close dialog
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
            
            //If there are 2 parents in the skill_tree
            if (parents.length == 2 ) {
                first_parent = parents[0]; 
                $('#'+parents[1]).css('top', '0px');
                //console.log('deleting: '+parents[0]);        
                //removing father
                console.log("first_parent " + first_parent);
                
                for (var i = 0; i < skills.length; i++) {  
                    //console.log('looping '+skills[i].element + ' ');
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
                $('#'+first_parent).css('top', '0px');
                //show the first_parent
                //if (first_parent != '') {
                   parents = [first_parent, id];                   
                   open_parent(first_parent, id);
                //}
            }
            
            if (jQuery.inArray(id, parents) == -1) {                              
                parents.push(id);
                console.log('parents push: ' + parents);
            }
            open_block(id, 0, 0);            
        }
        
        //Setting class       
        cleanclass($(this));   
        $(this).addClass('second_window');
        
        parent_div = $("#"+parents[0]);
        cleanclass(parent_div);
        parent_div.addClass('first_window');
        parent_div.addClass('skill_root');
        
        parent_div = $("#"+parents[1]);        
        cleanclass(parent_div);
        parent_div.addClass('second_window');
       
        //console.log(parents);
       // console.log(skills);        
        console.log('first_parent : ' + first_parent);             
        
        //redraw
        jsPlumb.repaintEverything();
        jsPlumb.repaint(id);
 
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
        
        var endPoint = jsPlumb.addEndpoint(div, endpointOptions);         
        skills.push({
            element: div, endp:endPoint
        });
        return endPoint;        
    },    
    window.jsPlumbDemo = {  	   
        init : function() {
            console.log('Import defaults');            
            jsPlumb.Defaults.Anchors = [ "BottomCenter", "TopCenter" ];            
            jsPlumb.Defaults.Container = "skill_tree";
            
            open_block('block_1', 0, 1);
           
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

<div id="dialog-loading">
    <div class="modal-body">
        <p style="text-align:center">
            {{ "Loading"|get_lang }}
        <img src="{{ _p.web_img}}loadingAnimation.gif"/>
        </p>
    </div>    
</div>

<div id="menu" class="well" style="top:20px; left:20px; width:380px; z-index: 9000; opacity: 0.9;">
    <h3>{{'Skills'|get_lang}}</h3>
    <div class="btn-group">
        <a style="z-index: 1000" class="btn" id="add_item_link" href="#">{{'AddSkill'|get_lang}}</a>
        <a style="z-index: 1000" class="btn" id="return_to_root" href="#">{{'Root'|get_lang}}</a>
        <a style="z-index: 1000" class="btn" id="return_to_admin" href="{{_p.web_main}}admin">{{'BackToAdmin'|get_lang}}</a>
        
    </div>
</div>
           
<div id="skill_tree"></div>

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