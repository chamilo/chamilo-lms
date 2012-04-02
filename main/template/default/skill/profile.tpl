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
    
    
    //Open dialog
    $("#dialog-form").dialog({
        autoOpen: false,
        modal   : true, 
        width   : 550, 
        height  : 350,
    });
    
    var name = $( "#name" ),
    description = $( "#description" ),  
    allFields = $( [] ).add( name ).add( description ), tips = $(".validateTips");    
        
    
    $("#dialog-form").dialog({              
        buttons: {
            "Add" : function() {                
                var bValid = true;
                bValid = bValid && checkLength( name, "name", 1, 255 );
                var params = $("#save_profile_form").serialize();          
                      
                $.ajax({
                    url: '{$url}?a=save_profile&'+params,
                    success:function(data) {    
                        $("#dialog-form").dialog("close");
                        alert("{"Saved"|get_lang}");
                    }                           
                });
            },            
        },              
        close: function() {     
            $("#name").attr('value', '');
            $("#description").attr('value', '');                
        }
    });

    $("#add_profile").click(function() {
        $("#name").attr('value', '');
        $("#description").attr('value', '');            
        $("#dialog-form").dialog("open");
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
    <div class="skills-skills">
        <h3>{"Skills"|get_lang}</h3>
        <ul class="holder">             
            {foreach $search_skill_list as $search_skill_id}        
                <li class="bit-box">
                    {$skill_list[$search_skill_id].name}
                    <a class="closebutton" href="?a=remove_skill&id={$search_skill_id}"></a>
                </li>        
            {/foreach}
        </ul>
        <a id="add_profile" class="btn" href="#"> {"SaveThisSearch"|get_lang}</a>
    </div>
{/if}


{if !empty($profiles) }
    <div class="skills-profiles">
        <h3>{"SkillProfiles"|get_lang}</h3>
        <ul class="holder">
            {foreach $profiles as $profile}        
                <li class="bit-box">
                    <a href="?a=load_profile&id={$profile.id}">{$profile.name}</a>                
                </li>        
            {/foreach}
        </ul>    
    </div>
{/if}

{if !empty($order_user_list) }
    <div class="skills-users">
    {foreach $order_user_list as $count => $user_list}
        <h2> {"Matches"|get_lang} {$count}/{$total_search_skills} </h2>
        {foreach $user_list as $user}
            <div class="ui-widget" style="width:400px">
                <div class="ui-widget-header">                    
                    <h3>
                        <img src="{$user['user'].avatar_small}" /> {$user['user'].complete_name} ({$user['user'].username})
                    </h3>
                </div>
                <div class="ui-widget-content ">
                    <h4>{"Skills"|get_lang} {$user.total_found_skills} / {$total_search_skills}</h4>
                    <ul>                
                        {foreach $user['skills'] as $skill_data}                 
                            <li>
                                {if !empty({$skill_list[$skill_data.skill_id].name})}
                                    <span class="label_tag skill">{$skill_list[$skill_data.skill_id].name}</span>
                                {else}
                                    {"SkillNotFound"|get_lang}
                                {/if}
                                {if $skill_data.found}                                     
                                     *{"IHaveThisSkill"|get_lang}*
                                {/if}
                            </li>
                        {/foreach}
                    </ul>
                </div>    
            </div>  
        {/foreach}
    {/foreach}        
    </div>
{else}     
     {if !empty($search_skill_list) }
        <div class="warning-message">{"NoResults"|get_lang}</div>
     {/if}
     
{/if}

<div id="dialog-form" style="display:none;">    
    <form id="save_profile_form" class="form-horizontal" name="form">       
        <fieldset>
            <div class="control-group">            
                <label class="control-label" for="name">{"Name"|get_lang}</label>            
                <div class="controls">
                    <input type="text" name="name" id="name" size="40" />             
                </div>
            </div>        
            <div class="control-group">            
                <label class="control-label" for="name">{"Description"|get_lang}</label>            
                <div class="controls">
                    <textarea name="description" id="description" class="span2"  rows="7"></textarea>
                </div>
            </div>  
        </fieldset>
    </form>    
</div>
