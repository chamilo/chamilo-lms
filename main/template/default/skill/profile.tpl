<script type="text/javascript">
$(document).ready( function() {     
    
    $("#skills").fcbkcomplete({
        json_url: "{$url}?a=find_skills",
        cache: false,
        filter_case: false,
        filter_hide: true,
        complete_text:"{'StartToType'|get_lang}",
        firstselected: true,
        //onremove: "testme",
        onselect:"check_skills",
        filter_selected: true,
        newel: true
    });
});

function check_skills() {
    //selecting only selected users
    $("#skills option:selected").each(function() {
        var skill_id = $(this).val();        
        if (skill_id != "" ) {            
            $.ajax({ 
                url: "{$url}?a=skill_exists", 
                data: "skill_id="+skill_id,
                success: function(return_value) {                    
                if (return_value == 0 ) {
                        alert("{'SkillDoesNotExist'|get_lang}");                                                
                        //Deleting select option tag
                        $("#skills option[value="+skill_id+"]").remove();                        
                        //Deleting holder
                        $(".holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });                        
                    }                    
                },            
            });                
        }        
    });
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
</script>
{$form}

{foreach $user_list as $user}
    <div class="ui-widget">
        <div class="ui-widget-header">
            {$user['user'].username}
        </div>
        <div class="ui-widget-content">
    
        {$user['user'].firstname}
        {$user['user'].lastname}
        
        
    
        <h3>Skills</h3>
        <ul>
        {foreach $user['skills'] as $skill}            
            <li>{$skill.skill_id} </li>
        {/foreach}
        </ul>
    </div>    
    </div>  
{/foreach}

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
                <select id="gradebook_id" name="gradebook_id[]" multiple="multiple"/>
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