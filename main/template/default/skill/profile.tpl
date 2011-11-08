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

{if !empty($search_skill_list) }
     <ul class="holder">
        {foreach $search_skill_list as $search_skill_id}        
            <li class="bit-box">
                {$skill_list[$search_skill_id].name}
                <a class="closebutton" href="?a=remove_skill&id={$search_skill_id}"></a>
            </li>        
        {/foreach}
    </ul>
    <a class="a_button gray small" href="?a=save_profile"> {"SaveThisSearch"|get_lang}</a>
{/if}


{if !empty($user_list) }
    {foreach $user_list as $user}
        <div class="ui-widget">
            <div class="ui-widget-header">
                {$user['user'].username}
            </div>
            <div class="ui-widget-content ">
                
                <img src="{$user['user'].avatar_small}" />
                {$user['user'].complete_name}
                
                <h3>Skills</h3>
                <ul>    
                    {$user.total_found_skills} / {$total_search_skills}                
                {foreach $user['skills'] as $skill_data}                 
                    <li>
                        <span class="label_tag notice">{$skill_list[$skill_data.skill_id].name}</span>
                        {if $skill_data.found}
                             * I have this skill * 
                        {/if} 
                        
                    </li>                    
                {/foreach}
                </ul>
            </div>    
        </div>  
    {/foreach}
{else}
    {"No results"|get_lang}
{/if}


    


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