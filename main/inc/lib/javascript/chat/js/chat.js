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
var intervals = new Array();
var timer;
var user_status = 0;
var widthBox = 320; // see css class .chatbox
//var ajax_url = 'chat.php'; // This variable is loaded in the template/layout/head.tpl file
var doubleCheck = '<span class="chatbox_checked"><i class="fa fa-check"></i><i class="fa fa-check"></i></span>';
var currentToken = '';

function set_user_status(status)
{
	if (status == 1) {
		stopChatHeartBeat();
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

$(function() {
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
		createMyContactsWindow();
        set_user_status(1);
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
		closeWindow(chat_id);
	});

	// Close main chat
	$('body').on('click', '.chatboxhead .close_chat', function(){
		closeChat();
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
					currentToken = data.sec_token;
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
						if (currentUserId == my_user_id) {
							// show contact list
							createMyContactsWindow();
							return true;
						}

						my_items = user_items['items'];
						window_user_info = user_items['window_user_info'];
						$.each(my_items, function(i, item) {
							if (item) {
								// fix strange ie bug
								if ($("#chatbox_"+my_user_id).length <= 0) {
									createChatBox(
										my_user_id,
										window_user_info.complete_name,
										1,
										window_user_info.online,
										window_user_info.avatar_small
									);
								}
								createChatBubble(my_user_id, item);
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
			}
		});
	}
}

function stopChatHeartBeat()
{
	clearInterval(timer);
	timer = null;
}

function startChatHeartBeat()
{
	timer = setInterval(chatHeartbeat, chatHeartbeatTime);
}

/*
 * Shows the user messages in all windows
 *
 * Item array structure :
 *
 * item.s = type of message: 1 = message, 2 = "sent at" string
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
					//document.title = newMessagesWin[x].username+' says...';
					//titleChanged = 1;
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
				// Each window
				my_items = user_items['items'];
				userInfo = user_items['window_user_info'];

				update_online_user(
					my_user_id,
					userInfo.user_is_online
				);

				$.each(my_items, function(i, item) {
					if (item) {
						// fix strange ie bug
						if ($("#chatbox_"+my_user_id).length <= 0) {
							createChatBox(
								my_user_id,
								userInfo.complete_name,
								0,
								userInfo.online,
								userInfo.avatar_small
							);
						}

						if ($("#chatbox_"+my_user_id).css('display') == 'none') {
							$("#chatbox_"+my_user_id).css('display','block');
							restructureChatBoxes();
						}

						newMessages[my_user_id] = {'status':true, 'username':item.username};
						newMessagesWin[my_user_id]= {'status':true, 'username':item.username};

						var chatBubble = createChatBubble(my_user_id, item);

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
		}
	}); //ajax
}

/**
 * Draws a message bubble
 * @param my_user_id
 * @param item
 * @returns {string}
 */
function createChatBubble(my_user_id, item, appendType = 'append')
{
	var myDiv = 'chatboxmessage_me';
	if (my_user_id == item.from_user_info.id) {
		myDiv = 'chatboxmessage';
	}
	var sentDate = '';
	if (moment.unix(item.date).isValid()) {
		sentDate = moment.unix(item.date).format('LLL');
	}

	var check = '';
	var unCheckClass = ' check_status';
	if (my_user_id != item.from_user_info.id) {
		check = '<i class="fa fa-check"></i><i class="fa fa-check"></i>';
		if (item.recd == 1) {
			unCheckClass = '';
			check = doubleCheck;
		}
	}

	var messageObject = $("#chatbox_"+my_user_id+" .chatboxcontent").find('#message_id_' + item.id);
	var exists = messageObject.length !== 0;
	var messageHeader = '<div id="message_id_'+item.id+'" class="chatbox-common well '+myDiv+'" title="'+sentDate+'" >';
	var messageEnd = '</div>';

	var message = '';
	if (my_user_id == item.from_user_info.id) {
		message += '<span class="chatboxmessagefrom">'+item.from_user_info.complete_name+':&nbsp;&nbsp;</span>';
	}

	message += '<div class="chatboxmessagecontent">'+item.message+'</div>';
	message += '<div class="chatbox_checks' + unCheckClass + '">'+check+'</div>';

	if (exists) {
		messageObject.html(message);
		$(messageObject).linkify({
			target: "_blank"
		});
	} else {
		message = messageHeader + message + messageEnd;
		if (appendType == 'append') {
			$("#chatbox_"+my_user_id+" .chatboxcontent").append(message);
		} else {
			$("#chatbox_"+my_user_id+" .chatboxcontent").prepend(message);
		}

		$("#chatbox_"+my_user_id+" .chatboxcontent").linkify({
			target: "_blank"
		});
	}

	return message;
}

/**
 * Disconnect user from chat
 */
function closeChat()
{
	$.post(
		ajax_url + "?action=close", {},
		function (data) {
			// Disconnects from chat
			set_user_status(0);
			// Clean cookies
			Cookies.set('chatbox_minimized', new Array());
			// Delete all windows
			$('.chatbox ').remove();

			// Reset variables
            chatBoxes = new Array();
            intervals = new Array();
		}
	);
}

function closeWindow(user_id)
{
	$('#chatbox_'+user_id).css('display','none');
	restructureChatBoxes();
	$.post(
		ajax_url + "?action=close_window",
		{
			chatbox: user_id
		},
		function (data) {
		}
	);
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
function createMyContactsWindow()
{
	var user_id = 'contacts';
	var oldChatBox = $("#chatbox_"+user_id);
	if (oldChatBox.length > 0) {
		// reload contact list
		if (oldChatBox.css('display') == 'none') {
			oldChatBox.css('display','block');
			restructureChatBoxes();
		}

		chatboxContent = oldChatBox.find('.chatboxcontent');

		$.post(ajax_url + "?action=get_contacts", {
			to: 'user_id'
		}, function (data) {
			chatboxContent.html(data);
		});

		$("#chatbox_"+user_id+" .chatboxtextarea").focus();

		return;
	}

	var chatbox = $('<div>')
		.attr({
			id: 'chatbox_' + user_id
		})
		.addClass('chatbox')
		.css('bottom', 0);

	var chatboxHead = $('<div>')
		.addClass('chatboxhead')
		.append('');

	$('<div>')
		.addClass('chatboxtitle')
		.append(chatLang)
		.appendTo(chatboxHead);

	var chatboxoptions = $('<div>')
		.addClass('chatboxoptions')
		.appendTo(chatboxHead);

	$('<a>')
		.addClass('btn btn-xs togglelink')
		.attr({
			href: 'javascript:void(0)',
			rel: user_id
		})
		.html('<em class="fa fa-toggle-down"></em>')
		.appendTo(chatboxoptions);

	$('<a>')
		.addClass('btn btn-xs close_chat')
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

	$.post(ajax_url + "?action=get_contacts", {
		to: 'user_id'
	}, function (data) {
		$('<div>').html(data).appendTo(chatboxContent);
	});

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

	minimizedChatBoxes = new Array();
	if (Cookies.get('chatbox_minimized')) {
		minimizedChatBoxes = Cookies.getJSON('chatbox_minimized');

		minimize = 0;
		for (j = 0; j < minimizedChatBoxes.length; j++) {
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

	var user_is_online = return_online_user(user_id, online, userImage);

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

	if (!hide_chat_video) {
		if (!!Modernizr.prefixed('RTCPeerConnection', window) &&
			(online === '1' || online === 1)
		) {
			/*$('<a>')
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
				.appendTo(chatboxoptions);*/
		}
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
		if (Cookies.get('chatbox_minimized')) {
			minimizedChatBoxes = Cookies.getJSON('chatbox_minimized');
		}
		minimize = 0;
		for (j = 0; j < minimizedChatBoxes.length; j++) {
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
			//$("#chatbox_"+user_id+" .chatboxtextarea").focus();
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
	var visibleMessages = $("#chatbox_"+userId+" .chatboxcontent").find('.chatbox-common').length;
	$.ajax({
		url: ajax_url+"?action=get_previous_messages&user_id="+userId+"&visible_messages="+visibleMessages,
		cache: false,
		dataType: "json",
		success: function(items) {
			items = items.reverse();
			$.each(items, function(i, item) {
				if (item) {
					if ($("#chatbox_"+userId).css('display') == 'none') {
						$("#chatbox_"+userId).css('display','block');
						restructureChatBoxes();
					}
					var chatBubble = createChatBubble(userId, item, 'prepend');
					if ($('#chatbox_'+userId+' .chatboxcontent').css('display') == 'none') {
						$('#chatbox_'+userId+' .chatboxhead').toggleClass('chatboxblink');
					}
				}
			});
		}
	});
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

/**
 * @param user_id
 */
function toggleChatBoxGrowth(user_id)
{
	if ($('#chatbox_'+user_id+' .chatboxcontent').css('display') == 'none') {
		// Show box
		var minimizedChatBoxes = new Array();
		if (Cookies.get('chatbox_minimized')) {
			minimizedChatBoxes = Cookies.getJSON('chatbox_minimized');
		}

		var newCookie = new Array();
		for (var i = 0; i < minimizedChatBoxes.length; i++) {
			if (minimizedChatBoxes[i] != user_id) {
				newCookie.push(minimizedChatBoxes[i]);
			}
		}

		Cookies.set('chatbox_minimized', newCookie);
		$('#chatbox_'+user_id+' .chatboxcontent').css('display','block');
		$('#chatbox_'+user_id+' .chatboxinput').css('display','block');
		$('.togglelink').html('<em class="fa fa-toggle-down"></em>');
	} else {
		// hide box
		if (Cookies.get('chatbox_minimized')) {
			newCookie = Cookies.getJSON('chatbox_minimized');
            if ($.isArray(newCookie)) {
                for (i = 0; i < newCookie.length; i++) {
                    if (newCookie[i] == user_id) {
                        newCookie.splice(i, 1);
                    }
                }
                newCookie.push(user_id);
            } else {
                newCookie = new Array();
                newCookie.push(user_id);
            }

			Cookies.set('chatbox_minimized', newCookie);
		}

		$('#chatbox_'+user_id+' .chatboxcontent').css('display','none');
		$('#chatbox_'+user_id+' .chatboxinput').css('display','none');
		$('.togglelink').html('<em class="fa fa-toggle-up"></em>');
	}
}

function checkMessageStatus(messageId, chatBox)
{
	$.ajax({
		type: 'GET',
		dataType: 'json',
		url: ajax_url + "?action=get_message_status",
		data: {
			message_id: messageId
		},
		success: function (messageInfo) {
			if (messageInfo) {
				if (messageInfo.recd == 1) {
					$('#message_id_' + messageId + ' .chatbox_checks ').html(doubleCheck);
					clearInterval(intervals[messageId]);
				}
			}
		}
	});
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
				message: message,
				chat_sec_token: currentToken
			}, function (messageId) {
				if (messageId.id > 0) {
					currentToken = messageId.sec_token;
					message = message.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;");
					var item = {
						from_user_info : {id: currentUserId, complete_name: 'me'},
						username: username,
						date: moment().unix(),
						f: currentUserId,
						message: message,
						id: messageId.id
					};
					createChatBubble(user_id, item);
					$("#chatbox_" + user_id + " .chatboxcontent").scrollTop(
						$("#chatbox_" + user_id + " .chatboxcontent")[0].scrollHeight
					);

					intervals[messageId.id] = setInterval(checkMessageStatus, chatHeartbeatTime, messageId.id);
				} else {
					$("#chatbox_" + user_id + " .chatboxcontent").
                    append('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i><br />');
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
