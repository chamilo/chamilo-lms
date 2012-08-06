<div id="menu" class="well" style="top:20px; left:20px; width:380px; z-index: 9000; opacity: 0.9;">
    <h3>{{'Skills'|get_lang}}</h3>
    <div class="btn-group">
        <a style="z-index: 1000" class="btn" id="add_item_link" href="#">{{'AddSkill'|get_lang}}</a>
        <a style="z-index: 1000" class="btn" id="return_to_root" href="#">{{'Root'|get_lang}}</a>
        <a style="z-index: 1000" class="btn" id="return_to_admin" href="{{_p.web_main}}admin">{{'BackToAdmin'|get_lang}}</a>
        
    </div>
</div>
           
<div id="vis"><img src=""></div>
{{ html }}

<!--div id="dialog-form" style="display:none; z-index:9001;">    
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
</div-->
