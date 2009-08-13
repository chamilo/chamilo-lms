
//select表示select本身，view表示跳出的�?�項
;(function($){

  $.fn.extend({
  
    //計算文字Byte數，並回傳
    getByteLength: function(strIN){
        var i, cnt=0;
            
        for (i=0; i<strIN.length; i++){            
            if (escape(strIN.charAt(i)).length >= 4) cnt+=2;
            else cnt++;            
        } 
            
        return cnt;
    } 
    
  });
  
  $.fn.extend({
  
    //檢查文字的Byte數，超�?�limit指定的個數的話，就回傳true 和 未超�?�之�?的文字index
    getIndexByByte: function(strIN,limit){
        var i, cnt=0;
            
        for (i=0; i<strIN.length; i++){            
            if (escape(strIN.charAt(i)).length >= 4) cnt+=2;
            else cnt++;            
            
            if(cnt>limit)
            {
                return [true,i];
            }
        } 
            
        return false;
    } 
    
  });
  
  $.fn.extend({

    finalselect: function(options) {
        
        var settings =
	    {	
	        id:null,
		    animalSpeed:100,
            selectWidth:"190px",		   
            selectImage:"image/select.png",
            selectText:"My friend",
		    zIndex: 0,    
		    viewHeight:"100px",
		    viewWidth:"300px",
		    viewMouseoverColor:"#cfdfff",//#dcdcdc
		    viewTop:"28px",//top,bottom
		    viewLeft:" -1px"//left,right
	    };
	    
	    
        if (typeof(options)!='undefined')
	    {
	        //將整批options的值assign給settings
		    jQuery.extend(settings, options);
	    }
             
        var tmp='<div id="'+settings.id+'-select" style="cursor:default;font-size:12px;z-index:'+settings.zIndex+';border: solid 0px #999; padding: 0px; width: 180px; position: relative;">'
        tmp+='<div id="'+settings.id+'-Text" style="background: url('+settings.selectImage+') no-repeat 0 0; width: '+settings.selectWidth+'; height: 21px; color: Black; padding: 0  0 0 0;">';
        tmp+='<div class="textshow" style="padding: 0px;">'+settings.selectText+'</div><div class="valueshow" style="display:none;"></div></div><div id="'+settings.id+'-selectshow" style="overflow-y:auto; overflow-x:hidden; height:'+settings.viewHeight+';width:'+settings.viewWidth+'; display:none; position: absolute; left:'+settings.viewLeft+'; top:'+settings.viewTop+'; border: solid 1px #999; background: white;"></div></div>';
        

        
        var _handler = function() {
            // 從這裡開始
            $(this).html(tmp);
            bindArrowClick();
            bindSelectMouseover();
            bindSelectMouseleave();
            
        };
        
        
        
        var bindArrowClick=function(){
            var tmp=$('#'+settings.id+'-Text');
            $("#"+settings.id+'-Text').bind("click", function(e){            
                var obj=$('#'+settings.id+'-selectshow');
                if(obj.css('display')=='none')
                {
                   // obj.slide();                
                   obj.slideDown(settings.animalSpeed,function(){                        
                        obj.show();                       
                        obj.css('overflow','auto');
                        obj.css('overflow-x','hidden');
                    });
                }
                else
                {
                    obj.slideUp(settings.animalSpeed,function(){                        
                        obj.hide();
                    });
                }
       
            });
        };
        
        var bindItemMouseover=function(){
        
            var inx=0;
            while($(".selectitem",$("#"+settings.id+"-selectshow")).get(inx)!=null)
            {
                var item=$(".selectitem",$("#"+settings.id+"-selectshow")).get(inx);
                
                $(item).bind("mouseover", function(e){
                  $(this).css('background-color',settings.viewMouseoverColor);
                });
                
                $(item).bind("mouseout", function(e){
                  $(this).css('background-color','#fff');
                });
                
                $(item).bind("click", function(e){
                 
                    var tmpstr=$(".thistext",$(this)).html();                     
                    var arr=$().getIndexByByte(tmpstr,30); 
                    if(arr[0]==true)
                        tmpstr=tmpstr.substring(0,arr[1])+'...';                    

                    $(".textshow",$("#"+settings.id+"-Text")).html(tmpstr);
                    document.getElementById(settings.id+'-selectshow').style.display="none";
                    
                    $(".valueshow",$("#"+settings.id+"-Text")).html($(".selectvalue",$(this)).html());
                    
                });

                inx++;
            }

        }
        
        var bindSelectMouseover=function(){
            $('#'+settings.id+'-Text').bind("mouseover",function(){
                if($.browser.msie==false)
                    $('#'+settings.id+'-Text').css("background-position","0 -21px");
            });
        }
        
        var bindSelectMouseleave=function(){
            $('#'+settings.id+'-Text').bind("mouseout",function(){
                if($.browser.msie==false)
                    $('#'+settings.id+'-Text').css("background-position","0 0px");
            });
        }
        
        this.setViewTop = function(top){
            $('#'+settings.id+'-selectshow').css('top',top+'px');
        } 
        
        this.setViewLeft = function(left){
            $('#'+settings.id+'-selectshow').css('left',left+'px');
        }     
        
        this.getLength = function(){
            return $('.selectitem',$('#'+settings.id+'-selectshow')).length;
        }   
       
       
        //add item到select裡�?�
        //在傳itemtext時，用<span class="thistext"></span>包起�?顯示的 "文字"
        //例如:<span class="thistext">哇哈哈</span>，這樣select�?�擇後，就會顯示 "哇哈哈"
        this.addItem = function(itemtext,itemvalue){            
            
            var itemhtml='<div class="selectitem"><div class="selecttext">'+itemtext
            +'</div><div class="selectvalue" style=" display:none;">'+itemvalue+'</div></div><div class="selectborder"><div>';
            
            $("#"+settings.id+'-selectshow').html($("#"+settings.id+'-selectshow').html()+itemhtml);           
            
            bindItemMouseover();             
        };
        
        this.removeItem = function(index){
            if($('.selectitem',$('#'+settings.id+'-selectshow')).length>index)
            $($('.selectitem',$('#'+settings.id+'-selectshow')).get(index)).remove();
            if($('.selectborder',$('#'+settings.id+'-selectshow')).length>index)
            $($('.selectborder',$('#'+settings.id+'-selectshow')).get(index)).remove();
        }
        
        
        
        this.getValue = function(){
            return $('.valueshow',$('#'+settings.id+'-Text')).html();
        }
        
        this.getText = function(){
            return $('.textshow',$('#'+settings.id+'-Text')).html();
        }
        

        return this.each(_handler);     
    }

  });

})(jQuery);