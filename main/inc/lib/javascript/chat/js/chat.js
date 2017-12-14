/*

Copyright (c) 2009 Anant Garg (anantgarg.com | inscripts.com)

This script may be used for non-commercial purposes only. For any
commercial purposes, please contact the author at
anant.garg@inscripts.com

Changes and Chamilo Integration: Julio Montoya <gugli100@gmail.com>


THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

*/

var windowFocus = true;
var username;
var currentUserId;
var chatHeartbeatCount = 0;
var minChatHeartbeat = 4000;
var maxChatHeartbeat = 33000;
var chatHeartbeatTime = minChatHeartbeat;
var originalTitle;
var blinkOrder = 0;
var chatboxFocus = new Array();
var newMessages = new Array();
var newMessagesWin = new Array();
var chatBoxes = new Array();
var timer;
var user_status = 0;
var widthBox = 320; // see css class .chatbox
//var ajax_url = 'chat.php'; // This variable is loaded in the template/layout/head.tpl file

function set_user_status(status)
{
    if (status == 1) {
        startChatHeartBeat();
        $('.user_status_main').html(online_button);
        $('#chatboxtitlemain').html(connect_lang);
    } else {
        stopChatHeartBeat();
        $('.user_status_main').html(offline_button);
        $('#chatboxtitlemain').html(disconnect_lang);
    }

    $.ajax({
        url: ajax_url+"?action=set_status",
        data: "status="+status,
        cache: false,
        success: function(data) {
        }
    });
    user_status = status;
}

$(document).ready(function() {
	originalTitle = document.title;
	startChatSession();
	$([window, document]).blur(function() {
		windowFocus = false;
	}).focus(function(){
		windowFocus = true;
		document.title = originalTitle;
	});

	/* "On" conditions, divs are created dynamically */
    // User name header toggle
	$('body').on('click', '#chatboxtitlemain', function() {
	    if (user_status == 1) {
            set_user_status(0);
        } else {
	        set_user_status(1);
        }
	});

	// User name header toogle
	$('body').on('click', '.chatboxtitle', function(){
		chatbox = $(this).parents(".chatbox");
		var chat_id = chatbox.attr('id');
		chat_id = chat_id.split('_')[1];
		toggleChatBoxGrowth(chat_id);
	});

	// Minimize button
	$('body').on('click', '.chatboxhead .togglelink', function(){
		var chat_id =  $(this).attr('rel');
		toggleChatBoxGrowth(chat_id);
	});

	// Close button
	$('body').on('click', '.chatboxhead .closelink', function(){
		var chat_id =  $(this).attr('rel');
		closeChatBox(chat_id);
	});
});

function showChatConnect()
{
    if (user_status == 1) {
        button = online_button;
        label = connect_lang;
    } else {
        button = offline_button;
        label = disconnect_lang;
    }
    $("<div />").attr("id","chatmain")
	.addClass("chatboxmain")
	.html('<div class="chatboxheadmain"><div class="user_status_main">'+button+'</div><div id="chatboxtitlemain">'+label+'</div><div class="chatboxoptions"></div></div>')
	.appendTo($( "body" ));
}

/**
 * Start chat session
 */
function startChatSession()
{
    /* fix bug BT#5728 whereby users cannot move to the next question in IE9 */
    if (typeof ajax_url != 'undefined') {
        $.ajax({
          url: ajax_url+"?action=startchatsession",
          cache: false,
          dataType: "json",
          success: function(data) {
            if (data) {
                username = data.me;
                currentUserId = data.user_id;
                user_status = data.user_status;
                showChatConnect();
                if (user_status == 1) {
                    startChatHeartBeat();
                } else {
                    stopChatHeartBeat();
                }

                $.each(data.items, function(my_user_id, user_items) {
                    my_items = user_items['items'];
                    $.each(my_items, function(i, item) {
                        if (item) {
                            // fix strange ie bug
                            if ($("#chatbox_"+my_user_id).length <= 0) {
                                createChatBox(
                                    my_user_id,
                                    item.username,
                                    1,
                                    item.user_info.online,
                                    item.user_info.avatar
                                );
                            }

                            if (item.s == 2) {
                                // info message
                                //$("#chatbox_"+my_user_id+" .chatboxcontent").append('<div class="'+messageLogMe+'"><span class="chatboxinfo">'+item.m+'</span></div>');
                            } else {
                                var chatBubble = createChatBubble(my_user_id, item);
                                $("#chatbox_"+my_user_id+" .chatboxcontent").append(chatBubble);
                            }
                        }
                    });
                });

                for (i = 0; i < chatBoxes.length; i++) {
                    my_user_id = chatBoxes[i];
                    $("#chatbox_"+my_user_id+" .chatboxcontent").scrollTop(
                        $("#chatbox_"+my_user_id+" .chatboxcontent")[0].scrollHeight
                    );
                }
            }
        }});
    }
}

function stopChatHeartBeat()
{
    clearInterval(timer);
    timer = null;
}

function startChatHeartBeat()
{
    timer = setInterval('chatHeartbeat();', chatHeartbeatTime);
}

/*
 * Shows the user messages in all windows
 *
 * Item array structure :
 *
 * item.s = type of message: 1 = message, 2 = "sent at" string
 * item.m = message
 * item.f = from_user
 *
 **/
function chatHeartbeat()
{
	var itemsfound = 0;
	if (windowFocus == false) {
		var blinkNumber  = 0;
		var titleChanged = 0;

		for (x in newMessagesWin) {
			if (newMessagesWin[x].status == true) {
				++blinkNumber;
				if (blinkNumber >= blinkOrder) {
					document.title = newMessagesWin[x].username+' says...';
					titleChanged = 1;
					break;
				}
			}
		}

		if (titleChanged == 0) {
			document.title = originalTitle;
			blinkOrder = 0;
		} else {
			++blinkOrder;
		}

	} else {
		for (x in newMessagesWin) {
			newMessagesWin[x].status = false;
		}
	}

	for (x in newMessages) {
		if (newMessages[x] == true) {
			if (chatboxFocus[x] == false) {
				//FIXME: add toggle all or none policy, otherwise it looks funny
				$('#chatbox_'+x+' .chatboxhead').toggleClass('chatboxblink');
			}
		}
	}

	$.ajax({
		url: ajax_url+"?action=chatheartbeat",
		cache: false,
		dataType: "json",
		success: function(data) {
			$.each(data.items, function(my_user_id, user_items) {
                my_items = user_items['items'];
				$.each(my_items, function(i, item) {
					if (item) {
                        // fix strange ie bug
						if ($("#chatbox_"+my_user_id).length <= 0) {
							createChatBox(
							    my_user_id,
                                item.username,
                                0,
                                item.user_info.online,
                                item.user_info.avatar
                            );
						}
						if ($("#chatbox_"+my_user_id).css('display') == 'none') {
							$("#chatbox_"+my_user_id).css('display','block');
							restructureChatBoxes();
						}
						update_online_user(
						    my_user_id,
                            user_items.user_info.online
                        );

						if (item.s == 2) {
							//$("#chatbox_"+my_user_id+" .chatboxcontent").append('<div class="chatboxmessage"><span class="chatboxinfo">'+item.m+'</span></div>');
						} else {
							newMessages[my_user_id] = {'status':true, 'username':item.username};
							newMessagesWin[my_user_id]= {'status':true, 'username':item.username};
							var chatBubble = createChatBubble(my_user_id, item);
							$("#chatbox_"+my_user_id+" .chatboxcontent").append(chatBubble);
						}

						$("#chatbox_"+my_user_id+" .chatboxcontent").scrollTop(
						    $("#chatbox_"+my_user_id+" .chatboxcontent")[0].scrollHeight
                        );

                        if ($('#chatbox_'+my_user_id+' .chatboxcontent').css('display') == 'none') {
                            $('#chatbox_'+my_user_id+' .chatboxhead').toggleClass('chatboxblink');
                        }
						itemsfound += 1;
					}
				});
			});

			chatHeartbeatCount++;

			if (itemsfound > 0) {
				chatHeartbeatTime = minChatHeartbeat;
				chatHeartbeatCount = 1;
			} else if (chatHeartbeatCount >= 10) {
				chatHeartbeatTime *= 2;
				chatHeartbeatCount = 1;
				if (chatHeartbeatTime > maxChatHeartbeat) {
					chatHeartbeatTime = maxChatHeartbeat;
				}
			}
			//timer = setTimeout('chatHeartbeat();',chatHeartbeatTime);
		}
	}); //ajax
}

/**
 * Draws a message buble
 * @param my_user_id
 * @param item
 * @returns {string}
 */
function createChatBubble(my_user_id, item)
{
    var myDiv = 'chatboxmessage_me';
    if (my_user_id == item.f) {
        myDiv = 'chatboxmessage';
    }
    var sentDate = '';
    if (moment.unix(item.date).isValid()) {
        sentDate = moment.unix(item.date).format('LLL');
    }

    return '<div class="boot-tooltip well '+myDiv+'" title="'+sentDate+'" >' +
        '<span class="chatboxmessagefrom">'+item.username+':&nbsp;&nbsp;</span>' +
        '<span class="chatboxmessagecontent">'+item.m+'</span></div>';
}

function closeChatBox(user_id) {
	$('#chatbox_'+user_id).css('display','none');
        restructureChatBoxes();
        $.post(ajax_url+"?action=closechat", {chatbox: user_id} , function(data){
	});
}

function restructureChatBoxes()
{
	var align = 0;
	for (x in chatBoxes) {
		user_id = chatBoxes[x];
		if ($("#chatbox_"+user_id).css('display') != 'none') {
			if (align == 0) {
				$("#chatbox_"+user_id).css('right', '10px');
			} else {
				width = (align)*(widthBox+7) + 5 + 5;
				$("#chatbox_"+user_id).css('right', width+'px');
			}
			align++;
		}
	}
}

/**
 * Function that fires the chat with an user (creates a chat bloclk)
 * @param int		user id
 * @param string	user's firstname + lastname
 * @param status
 *
 **/
function chatWith(user_id, user_name, status, userImage)
{
    set_user_status(1);
	createChatBox(user_id, user_name, 0, status, userImage);
	$("#chatbox_"+user_id+" .chatboxtextarea").focus();
	getMoreItems(user_id, 'last');
}

/**
 * Creates a div
 */
function createChatBox(user_id, chatboxtitle, minimizeChatBox, online, userImage)
{
    var oldChatBox = $("#chatbox_"+user_id);
	if (oldChatBox.length > 0) {
		if (oldChatBox.css('display') == 'none') {
			oldChatBox.css('display','block');
			restructureChatBoxes();
		}

		$("#chatbox_"+user_id+" .chatboxtextarea").focus();
		return;
	}

	user_is_online = return_online_user(user_id, online, userImage);

	var chatbox = $('<div>')
		.attr({
			id: 'chatbox_' + user_id
		})
		.addClass('chatbox')
		.css('bottom', 0);

	var chatboxHead = $('<div>')
		.addClass('chatboxhead')
		.append(user_is_online);

    $('<div>')
        .addClass('chatimage')
        .append('<img src="'+userImage+'"/>')
        .appendTo(chatboxHead);

	$('<div>')
		.addClass('chatboxtitle')
		.append(chatboxtitle)
		.appendTo(chatboxHead);

	var chatboxoptions = $('<div>')
		.addClass('chatboxoptions')
		.appendTo(chatboxHead);

 	if (!!Modernizr.prefixed('RTCPeerConnection', window) &&
        (online === '1' || online === 1)
    ) {
		$('<a>')
			.addClass('btn btn-xs ajax')
			.attr({
				href: ajax_url + '?action=create_room&to=' + user_id
			})
            .data({
                title: '<em class="fa fa-video-camera"></em>',
                size: 'sm'
            })
            .on('click', function () {
                $(this).data('title', $('.chatboxtitle').text());
            })
			.html('<em class="fa fa-video-camera"></em>')
			.appendTo(chatboxoptions);
	}

	$('<a>')
		.addClass('btn btn-xs togglelink')
		.attr({
			href: 'javascript:void(0)',
			rel: user_id
		})
		.html('<em class="fa fa-toggle-down"></em>')
		.appendTo(chatboxoptions);

	$('<a>')
		.addClass('btn btn-xs closelink')
		.attr({
			href: 'javascript:void(0)',
			rel: user_id
		})
		.html('<em class="fa fa-close"></em>')
		.appendTo(chatboxoptions);

	$('<br>')
		.attr('clear', 'all')
		.appendTo(chatboxHead);

	var chatboxContent = $('<div>').addClass('chatboxcontent');
	var chatboxInput = $('<div>').addClass('chatboxinput');

	$('<textarea>')
		.addClass('chatboxtextarea')
		.on('keydown', function(e) {
			return checkChatBoxInputKey(e.originalEvent, this, user_id);
		})
		.appendTo(chatboxInput);

	chatbox
		.append(chatboxHead)
		.append(chatboxContent)
		.append(chatboxInput)
		.appendTo('body');

	var chatBoxeslength = 0;
	for (x in chatBoxes) {
		if ($("#chatbox_"+chatBoxes[x]).css('display') != 'none') {
			chatBoxeslength++;
		}
	}

	if (chatBoxeslength == 0) {
		$("#chatbox_"+user_id).css('right', '10px');
	} else {
		width = (chatBoxeslength)*(widthBox+7) + 5 + 5;
		$("#chatbox_"+user_id).css('right', width+'px');
	}

	chatBoxes.push(user_id);

	if (minimizeChatBox == 1) {
		minimizedChatBoxes = new Array();

		if ($.cookie('chatbox_minimized')) {
			minimizedChatBoxes = $.cookie('chatbox_minimized').split(/\|/);
		}
		minimize = 0;
		for (j=0;j<minimizedChatBoxes.length;j++) {
			if (minimizedChatBoxes[j] == user_id) {
				minimize = 1;
			}
		}

		if (minimize == 1) {
                        $('.togglelink').html('<em class="fa fa-toggle-up"></em>');
			$('#chatbox_'+user_id+' .chatboxcontent').css('display','none');
			$('#chatbox_'+user_id+' .chatboxinput').css('display','none');
		}
	}

	chatboxFocus[user_id] = false;

	$("#chatbox_"+user_id+" .chatboxtextarea").blur(function(){
		chatboxFocus[user_id] = false;
		$("#chatbox_"+user_id+" .chatboxtextarea").removeClass('chatboxtextareaselected');
	}).focus(function(){
		chatboxFocus[user_id] = true;
		newMessages[user_id] = false;
		$('#chatbox_'+user_id+' .chatboxhead').removeClass('chatboxblink');
		$("#chatbox_"+user_id+" .chatboxtextarea").addClass('chatboxtextareaselected');
	});

	$("#chatbox_"+user_id).click(function() {
		if ($('#chatbox_'+user_id+' .chatboxcontent').css('display') != 'none') {
			$("#chatbox_"+user_id+" .chatboxtextarea").focus();
		}
	});
	$("#chatbox_"+user_id).show();

	$("#chatbox_"+user_id+" .chatboxcontent").scroll(function () {
        var iCurScrollPos = $(this).scrollTop();
        if (iCurScrollPos == 0) {
            getMoreItems(user_id);
            return false;
        }
    });
}

/**
 * @param int userId
 * @param string scrollType
 */
function getMoreItems(userId, scrollType)
{
    var visibleMessages = $("#chatbox_"+userId+" .chatboxcontent").find('div').length;
    $.ajax({
		url: ajax_url+"?action=get_previous_messages&user_id="+userId+"&visible_messages="+visibleMessages,
		cache: false,
		dataType: "json",
		success: function(items) {
            $.each(items, function(i, item) {
                if (item) {
                    if ($("#chatbox_"+userId).css('display') == 'none') {
                        $("#chatbox_"+userId).css('display','block');
                        restructureChatBoxes();
                    }
                    var chatBubble = createChatBubble(userId, item);
                    $("#chatbox_"+userId+" .chatboxcontent").prepend(chatBubble);

                    if ($('#chatbox_'+userId+' .chatboxcontent').css('display') == 'none') {
                        $('#chatbox_'+userId+' .chatboxhead').toggleClass('chatboxblink');
                    }

                    // When using scroll set the scroll window to the first
                    var scrollValue = 10;
                    if (scrollType === 'last') {
                        // When loading for the first time show the last msg
                        scrollValue = $("#chatbox_"+userId+" .chatboxcontent").height();
                    }

                    $("#chatbox_"+userId+" .chatboxcontent").scrollTop(
                        scrollValue
                    );

                }
            });
		}
	}); //ajax
}

/**
 * Creates the div user status (green/gray button next to the user name)
 * @param int       user id
 * @param int       status  1 or 0
 */
function return_online_user(user_id, status, userImage)
{
	var div_wrapper = $("<div />" );
	var new_div = $("<div />" );

	new_div.attr("id","online_"+user_id);
	new_div.attr("class","user_status");

	if (status == '1' || status == 1) {
		new_div.html(online_button);
	} else {
		new_div.html(offline_button);
	}
	div_wrapper.append(new_div);
	return div_wrapper.html();
}

/**
 * Updates the user status (green/gray button next to the user name)
 */
function update_online_user(user_id, status)
{
	if ($("#online_" +user_id).length > 0) {
		if (status == 1) {
			$("#online_" +user_id).html(online_button);
		} else {
			$("#online_" +user_id).html(offline_button);
		}
	}
}

function toggleChatBoxGrowth(user_id)
{
	if ($('#chatbox_'+user_id+' .chatboxcontent').css('display') == 'none') {
		var minimizedChatBoxes = new Array();
		if ($.cookie('chatbox_minimized')) {
			minimizedChatBoxes = $.cookie('chatbox_minimized').split(/\|/);
		}

		var newCookie = '';
		for (i=0;i<minimizedChatBoxes.length;i++) {
			if (minimizedChatBoxes[i] != user_id) {
				newCookie += user_id+'|';
			}
		}

		newCookie = newCookie.slice(0, -1);
		$.cookie('chatbox_minimized', newCookie);
		$('#chatbox_'+user_id+' .chatboxcontent').css('display','block');
		$('#chatbox_'+user_id+' .chatboxinput').css('display','block');
		$("#chatbox_"+user_id+" .chatboxcontent").scrollTop($("#chatbox_"+user_id+" .chatboxcontent")[0].scrollHeight);
        $('.togglelink').html('<em class="fa fa-toggle-down"></em>');
	} else {
		var newCookie = user_id;
		if ($.cookie('chatbox_minimized')) {
			newCookie += '|'+$.cookie('chatbox_minimized');
		}
		$.cookie('chatbox_minimized',newCookie);
		$('#chatbox_'+user_id+' .chatboxcontent').css('display','none');
		$('#chatbox_'+user_id+' .chatboxinput').css('display','none');
        $('.togglelink').html('<em class="fa fa-toggle-up"></em>');
	}
}

/**
 * Sending message
 * @param event
 * @param chatboxtextarea
 * @param user_id
 * @returns {boolean}
 */
function checkChatBoxInputKey(event, chatboxtextarea, user_id)
{
	if(event.keyCode == 13 && event.shiftKey == 0)  {
		message = $(chatboxtextarea).val();
		message = message.replace(/^\s+|\s+$/g,"");

		$(chatboxtextarea).val('');
		$(chatboxtextarea).focus();
		$(chatboxtextarea).css('height','44px');

		if (message != '') {
            $.post(ajax_url + "?action=sendchat", {
                to: user_id,
                message: message
            }, function (data) {
                if (data == 1) {
                    message = message.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;");
                    var item = {
                        username: username,
                        date: moment().unix(),
                        f: currentUserId,
                        m: message
                    };
                    var bubble = createChatBubble(user_id, item);
                    $("#chatbox_" + user_id + " .chatboxcontent").append(bubble);
                    $("#chatbox_" + user_id + " .chatboxcontent").scrollTop(
                        $("#chatbox_" + user_id + " .chatboxcontent")[0].scrollHeight
                    );
                } else {
                    $("#chatbox_" + user_id + " .chatboxcontent").append('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i><br />');
                }
			});
		}
		chatHeartbeatTime = minChatHeartbeat;
		chatHeartbeatCount = 1;

		return false;
	}

	var adjustedHeight = chatboxtextarea.clientHeight;
	var maxHeight = 94;

	if (maxHeight > adjustedHeight) {
		adjustedHeight = Math.max(chatboxtextarea.scrollHeight, adjustedHeight);
		if (maxHeight)
			adjustedHeight = Math.min(maxHeight, adjustedHeight);
		if (adjustedHeight > chatboxtextarea.clientHeight)
			$(chatboxtextarea).css('height',adjustedHeight+8 +'px');
	} else {
		$(chatboxtextarea).css('overflow','auto');
	}
}

/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */
jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};
