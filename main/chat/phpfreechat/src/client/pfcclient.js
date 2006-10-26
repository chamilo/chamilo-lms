var is_ie = navigator.appName.match("Explorer");
var is_khtml = navigator.appName.match("Konqueror") || navigator.appVersion.match("KHTML");
var is_ff = navigator.appName.match("Netscape");

/**
 * This class is the client part of phpFreeChat
 * (depends on prototype library)
 * @author Stephane Gully
 */
var pfcClient = Class.create();

//defining the rest of the class implmentation
pfcClient.prototype = {
  
  initialize: function()
  {    
    // load the graphical user interface builder
    this.gui = new pfcGui();
    // load the resources manager (labels and urls)
    this.res = new pfcResource();

    this.nickname = pfc_nickname;
    this.nickid   = pfc_nickid;
    this.usermeta = $H();
    this.chanmeta = $H();
    this.nickwhoisbox = $H();

    // this array contains all the sent command
    // used the up and down key to navigate in the history
    // (doesn't work on IE6)
    this.cmdhistory   = Array();
    this.cmdhistoryid = -1;
    this.cmdhistoryissearching = false;
    
    /*
    this.channels      = Array();
    this.channelids    = Array();
    */
    this.privmsgs      = Array();
    this.privmsgids    = Array();
    
    this.timeout       = null;
    this.refresh_delay = pfc_refresh_delay;
    this.max_refresh_delay = pfc_max_refresh_delay;
    this.last_refresh_time = 0;
    /* unique client id for each windows used to identify a open window
     * this id is passed every time the JS communicate with server
     * (2 clients can use the same session: then only the nickname is shared) */
    this.clientid      = pfc_clientid;

    this.isconnected   = false;
    this.nicklist      = $H();
    this.nickcolor     = Array();
    this.colorlist     = Array();

    this.blinktmp     = Array();
    this.blinkloop    = Array();
    this.blinktimeout = Array();
  },

  connectListener: function()
  {
    this.el_words     = $('pfc_words');
    this.el_handle    = $('pfc_handle');
    this.el_container = $('pfc_container');
//    this.el_online    = $('pfc_online');
    this.el_errors    = $('pfc_errors');

    this.detectactivity = new DetectActivity(this.el_container);
    // restore the window title when user come back to the active zone
    if (pfc_notify_window) this.detectactivity.onunactivate = this.gui.unnotifyWindow.bindAsEventListener(this.gui);

    /* the events callbacks */
    this.el_words.onkeypress = this.callbackWords_OnKeypress.bindAsEventListener(this);
// don't use this line because when doing completeNick the "return false" doesn't work (focus is lost)
//    Event.observe(this.el_words,     'keypress',  this.callbackWords_OnKeypress.bindAsEventListener(this), false);
    Event.observe(this.el_words,     'keydown',   this.callbackWords_OnKeydown.bindAsEventListener(this), false);
    Event.observe(this.el_words,     'focus',     this.callbackWords_OnFocus.bindAsEventListener(this), false);
    Event.observe(this.el_handle,    'keydown',   this.callbackHandle_OnKeydown.bindAsEventListener(this), false);
    Event.observe(this.el_handle,    'change',    this.callbackHandle_OnChange.bindAsEventListener(this), false);
    Event.observe(this.el_container, 'mousemove', this.callbackContainer_OnMousemove.bindAsEventListener(this), false);
    Event.observe(this.el_container, 'mousedown', this.callbackContainer_OnMousedown.bindAsEventListener(this), false);
    Event.observe(this.el_container, 'mouseup',   this.callbackContainer_OnMouseup.bindAsEventListener(this), false);
    Event.observe(document.body,     'unload',    this.callback_OnUnload.bindAsEventListener(this), false);
  },

  refreshGUI: function()
  {
    this.minmax_status = pfc_start_minimized;
    var cookie = getCookie('pfc_minmax_status');
    if (cookie != null)
      this.minmax_status = (cookie == 'true');
    
    cookie = getCookie('pfc_nickmarker');
    this.nickmarker = (cookie == 'true');
    if (cookie == '' || cookie == null)
      this.nickmarker = pfc_nickmarker;
    
    cookie = getCookie('pfc_clock');
    this.clock = (cookie == 'true');
    if (cookie == '' || cookie == null)
      this.clock = pfc_clock;

    cookie = getCookie('pfc_showsmileys');
    this.showsmileys = (cookie == 'true');
    if (cookie == '' || cookie == null)
      this.showsmileys = pfc_showsmileys;
    
    cookie = getCookie('pfc_showwhosonline');
    this.showwhosonline = (cookie == 'true');
    if (cookie == '' || cookie == null)
      this.showwhosonline = pfc_showwhosonline;

    // '' means no forced color, let CSS choose the text color
    this.current_text_color = '';
    cookie = getCookie('pfc_current_text_color');
    if (cookie != null)
      this.switch_text_color(cookie);

    this.refresh_loginlogout();
    this.refresh_minimize_maximize();
    this.refresh_Smileys();
    this.refresh_nickmarker();
  },

  /**
   * Show a popup dialog to ask user to choose a nickname
   */
  askNick: function(nickname)
  {
    // ask to choose a nickname
    if (nickname == '') nickname = this.nickname;
    var newnick = prompt(this.res.getLabel('Please enter your nickname'), nickname);
    if (newnick)
      this.sendRequest('/nick', newnick);
  },
  
  /**
   * Reacte to the server response
   */
  handleResponse: function(cmd, resp, param)
  {
    if (pfc_debug)
      if (cmd != "update") trace('handleResponse: '+cmd + "-"+resp+"-"+param);
    if (cmd == "connect")
    {
      //alert(cmd + "-"+resp+"-"+param);
      if (resp == "ok")
      {                
        if (this.nickname == '')
          // ask to choose a nickname
          this.askNick(this.nickname);
        else
        {
          this.sendRequest('/nick', this.nickname);
        }
        
        // give focus the the input text box if wanted
        if (pfc_focus_on_connect) this.el_words.focus();

        this.isconnected = true;

        // start the polling system
        this.updateChat(true);
      }
      else
        this.isconnected = false;
      this.refresh_loginlogout();
    }
    else if (cmd == "quit")
    {
      if (resp =="ok")
      {
        // stop updates
        this.updateChat(false);
        this.isconnected = false;
        this.refresh_loginlogout();
      }
    }
    else if (cmd == "join" || cmd == "join2")
    {
      if (resp =="ok")
      {
        // create the new channel
        var tabid = param[0];
        var name  = param[1];
        this.gui.createTab(name, tabid, "ch");
        if (cmd != "join2") this.gui.setTabById(tabid);
        this.refresh_Smileys();
        this.refresh_WhosOnline();
      }
      else if (resp == "max_channels")
      {
        this.displayMsg( cmd, this.res.getLabel('Maximum number of joined channels has been reached') );
      }
      else
        alert(cmd + "-"+resp+"-"+param);
    }
    else if (cmd == "leave")
    {
      //alert(cmd + "-"+resp+"-"+param);
      if (resp =="ok")
      {
        // remove the channel
        var tabid = param;
        this.gui.removeTabById(tabid);

        // synchronize the channel client arrays
        /*
        var index = -1;
        index = this.channelids.indexOf(tabid);
        this.channelids = this.channelids.without(tabid);
        this.channels   = this.channels.without(this.channels[index]);
        */
        
        // synchronize the privmsg client arrays
        index = -1;
        index = indexOf(this.privmsgids, tabid);
        this.privmsgids = without(this.privmsgids, tabid);
        this.privmsgs   = without(this.privmsgs, this.privmsgs[index]);
        
      }
    }
    else if (cmd == "privmsg" || cmd == "privmsg2")
    {
      if (resp == "ok")
      {
        // create the new channel
        var tabid = param[0];
        var name  = param[1];
        this.gui.createTab(name, tabid, "pv");
        if (cmd != "privmsg2") this.gui.setTabById(tabid);
        
        this.privmsgs.push(name);
        this.privmsgids.push(tabid);
        
      }
      else if (resp == "max_privmsg")
      {
        this.displayMsg( cmd, this.res.getLabel('Maximum number of private chat has been reached') );
      }
      else if (resp == "unknown")
      {
        // speak to unknown user
      }
      else if (resp == "speak_to_myself")
      {
        this.displayMsg( cmd, this.res.getLabel('You are not allowed to speak to yourself') );
      }
      else
        alert(cmd + "-"+resp+"-"+param);
    }
    else if (cmd == "nick")
    {
      if (resp == "connected" || resp == "notchanged")
      {
        cmd = '';

        // join the default channels comming from the parameter list
        for (var i=0; i<pfc_defaultchan.length; i++)
        {
          if (i<pfc_defaultchan.length-1)
            cmd = "/join2";
          else
            cmd = "/join";
          this.sendRequest(cmd, pfc_defaultchan[i]);
        }
        // now join channels comming from sessions
        for (var i=0; i<pfc_userchan.length; i++)
        {
          if (i<pfc_userchan.length-1)
            cmd = "/join2";
          else
            cmd = "/join";
          this.sendRequest(cmd, pfc_userchan[i]);
        }

        // join the default privmsg comming from the parameter list
        for (var i=0; i<pfc_defaultprivmsg.length; i++)
        {
          if (i<pfc_defaultprivmsg.length-1)
            cmd = "/privmsg2";
          else
            cmd = "/privmsg";
          this.sendRequest(cmd, pfc_defaultprivmsg[i]);
        }
        // now join privmsg comming from the sessions
        for (var i=0; i<pfc_userprivmsg.length; i++)
        {
          this.sendRequest("/privmsg", pfc_userprivmsg[i]);
        }
      }
      
      if (resp == "ok" || resp == "notchanged" || resp == "changed" || resp == "connected")
      {
        this.el_handle.innerHTML = param;
        this.nickname = param;
        this.usermeta[this.nickid]['nick'] = param;
        this.updateNickBox(this.nickid);

        // clear the possible error box generated by the bellow displayMsg(...) function
        this.clearError(Array(this.el_words));
      }
      else if (resp == "isused")
      {
        this.setError(this.res.getLabel('Choosen nickname is allready used'), Array());
        this.askNick(param);        
      }
      else
        alert(cmd + "-"+resp+"-"+param);
    }
    else if (cmd == "update")
    {
      this.canupdatenexttime = true;
      // if the first ever refresh request never makes it back then the chat will keep
      // trying to refresh as usual
      // this only helps if we temporarily lose connection in the middle of an established
      // chat session
      this.last_refresh_time = new Date().getTime();
    }
    else if (cmd == "version")
    {
      if (resp == "ok")
      {
        this.displayMsg( cmd, this.res.getLabel('phpfreechat current version is %s',param) );
      }
    }
    else if (cmd == "help")
    {
      if (resp == "ok")
      {
        this.displayMsg( cmd, param);
      }
    }
    else if (cmd == "rehash")
    {
      if (resp == "ok")
      {
        this.displayMsg( cmd, this.res.getLabel('Configuration has been rehashed') );
      }
      else if (resp == "ko")
      {
        this.displayMsg( cmd, this.res.getLabel('A problem occurs during rehash') );
      }
    }
    else if (cmd == "banlist")
    {
      if (resp == "ok" || resp == "ko")
      {
        this.displayMsg( cmd, param );
      }
    }
    else if (cmd == "unban")
    {
      if (resp == "ok" || resp == "ko")
      {
        this.displayMsg( cmd, param );
      }
    }
    else if (cmd == "auth")
    {
      if (resp == "ban")
      {
        alert(param);
      }
      if (resp == "frozen")
      {
        alert(param);
      }
      else if (resp == "nick")
      {
        this.displayMsg( cmd, param );
      }
    }
    else if (cmd == "debug")
    {
      if (resp == "ok" || resp == "ko")
      {
        this.displayMsg( cmd, param );
      }
    }
    else if (cmd == "clear")
    {
      var tabid     = this.gui.getTabId();
      var container = this.gui.getChatContentFromTabId(tabid);
      container.innerHTML = "";
    }    
    else if (cmd == "identify")
    {
      this.displayMsg( cmd, param );
    }
    else if (cmd == "checknickchange")
    {
      this.displayMsg( cmd, param );
    }
    else if (cmd == "whois" || cmd == "whois2")
    {
      var nickid = param['nickid'];
      if (resp == "ok")
      {
        this.usermeta[nickid] = $H(param);
        this.updateNickBox(nickid);
        this.updateNickWhoisBox(nickid);
      }
      if (cmd == "whois")
      {
        // display the whois info
        var um = this.getAllUserMeta(nickid);
        var um_keys = um.keys();
        var msg = '';
        for (var i=0; i<um_keys.length; i++)
        {
          var k = um_keys[i];
          var v = um[k];
          if (v &&
              // these parameter are used internaly (don't display it)
              k != 'nickid' &&
              k != 'floodtime' &&
              k != 'flood_nbmsg' &&
              k != 'flood_nbchar')
            msg = msg + '<strong>' + k + '</strong>: ' + v + '<br/>';
        }
        this.displayMsg( cmd, msg );
      }
    }
    else if (cmd == "who" || cmd == "who2")
    {
      var chan   = param['chan'];
      var chanid = param['chanid'];
      var meta   = $H(param['meta']);
      if (resp == "ok") 
      { 
        this.chanmeta[chanid] = meta;

        // send /whois commands for unknown users 
        for (var i=0; i<meta['users']['nickid'].length; i++)
        {
          var nickid = meta['users']['nickid'][i];
          var nick   = meta['users']['nick'][i];
          var um = this.getAllUserMeta(nickid);
          if (!um) this.sendRequest('/whois2', nick);
        }

        // update the nick list display on the current channel
        this.updateNickListBox(chanid);
      }
      if (cmd == "who")
      {
        // display the whois info
        var cm = this.getAllChanMeta(chanid);
        var cm_keys = cm.keys();
        var msg = '';
        for (var i=0; i<cm_keys.length; i++)
        {
          var k = cm_keys[i];
          var v = cm[k];
          if (k != 'users')
          {
            msg = msg + '<strong>' + k + '</strong>: ' + v + '<br/>';
          }
        }
        this.displayMsg( cmd, msg );
      }
    }
    else if (cmd == "getnewmsg")
    {
      if (resp == "ok") 
      {
        this.handleComingRequest(param);
      }
    }
    else
      alert(cmd + "-"+resp+"-"+param);
  },
  
  getAllUserMeta: function(nickid)
  {
    return this.usermeta[nickid];
  },

  getUserMeta: function(nickid, key)
  {
    return this.usermeta[nickid][key];    
  },

  getAllChanMeta: function(chanid)
  {
    return this.chanmeta[chanid];
  },

  getChanMeta: function(chanid, key)
  {
    return this.chanmeta[chanid][key];
  },

  doSendMessage: function()
  {
    var w = this.el_words;
    var wval = w.value;

    // append the string to the history
    this.cmdhistory.push(wval);
    this.cmdhistoryid = this.cmdhistory.length;
    this.cmdhistoryissearching = false;

    // send the string to the server
    re = new RegExp("^(\/[a-zA-Z0-9]+)( (.*)|)");
    if (wval.match(re))
    {
      // a user command
      cmd = wval.replace(re, '$1');
      param = wval.replace(re, '$3');
      this.sendRequest(cmd, param.substr(0, pfc_max_text_len + this.clientid.length));
    }
    else
    {
      // a classic 'send' command

      // empty messages with only spaces
      rx = new RegExp('^[ ]*$','g');
      wval = wval.replace(rx,'');
        
      // truncate the text length 
      wval = wval.substr(0,pfc_max_text_len);

      // colorize the text with current_text_color
      if (this.current_text_color != '' && wval.length != '')
        wval = '[color=#' + this.current_text_color + '] ' + wval + ' [/color]';

      this.sendRequest('/send', wval);
    }
    w.value = '';
    return false;
  },

  /**
   * Try to complete a nickname like on IRC when pressing the TAB key
   * @todo: improve the algorithme, it should take into account the cursor position
   */
  completeNick: function()
  {
    var w = this.el_words;
    var nick_src = w.value.substring(w.value.lastIndexOf(' ')+1,w.value.length);
    if (nick_src != '')
    {
      var tabid = this.gui.getTabId();
      var n_list = this.chanmeta[tabid]['users']['nick'];
      for (var i=0; i<n_list.length; i++)
      {
	var nick = n_list[i];
	if (nick.indexOf(nick_src) == 0)
	  w.value = w.value.replace(nick_src, nick);
      }
    }
  },

  /**
   * Handle the pressed keys
   * see also callbackWords_OnKeydown
   */
  callbackWords_OnKeypress: function(evt)
  {
    var code = (evt.which) ? evt.which : evt.keyCode;
    if (code == Event.KEY_TAB) /* tab key */
    {
      /* FF & Konqueror workaround : ignore TAB key here */
      /* do the nickname completion work like on IRC */
      this.completeNick();
      return false; /* do not leave the tab key default behavior */
    }
    else if (code == Event.KEY_RETURN) /* enter key */
    {
      return this.doSendMessage();
    }
    else if (code == 33 && false) // page up key
    {
      // write the last command in the history
      if (this.cmdhistory.length>0)
      {
        var w = this.el_words;
        if (this.cmdhistoryissearching == false && w.value != "")
          this.cmdhistory.push(w.value);
        this.cmdhistoryissearching = true;
        this.cmdhistoryid = this.cmdhistoryid-1;
        if (this.cmdhistoryid<0) this.cmdhistoryid = this.cmdhistory.length-1;
        w.value = this.cmdhistory[this.cmdhistoryid];
      }
    }
    else if (code == 34 && false) // page down key
    {
      // write the next command in the history
      if (this.cmdhistory.length>0)
      {
        var w = this.el_words;
        if (this.cmdhistoryissearching == false && w.value != "")
          this.cmdhistory.push(w.value);
        this.cmdhistoryissearching = true;
        this.cmdhistoryid = this.cmdhistoryid+1;
        if (this.cmdhistoryid>=this.cmdhistory.length) this.cmdhistoryid = 0;
        w.value = this.cmdhistory[this.cmdhistoryid];
      }
    }
    else
    {
      /* allow other keys */
      return true;
    }
  },
  /**
   * Handle the pressed keys
   * see also callbackWords_OnKeypress
   */
  callbackWords_OnKeydown: function(evt)
  {
    if (!this.isconnected) return false;
    this.clearError(Array(this.el_words));
    var code = (evt.which) ? evt.which : evt.keyCode
    if (code == 9) /* tab key */
    {
      /* IE workaround : ignore TAB key here */
      /* do the nickname completion work like on IRC */
      this.completeNick();
      return false; /* do not leave the tab key default behavior */
    }
    else
    {
      return true;
    }
  },
  callbackWords_OnFocus: function(evt)
  {
    //    if (this.el_handle && this.el_handle.value == '' && !this.minmax_status)
    //      this.el_handle.focus();
  },
  callbackHandle_OnKeydown: function(evt)
  {
  },
  callbackHandle_OnChange: function(evt)
  {
  },
  callback_OnUnload: function(evt)
  {
    /* don't disconnect users when they reload the window
     * this event doesn't only occurs when the page is closed but also when the page is reloaded */
    if (pfc_quit_on_closedwindow)
    {
      if (!this.isconnected) return false;
      this.sendRequest('/quit');
    }
  },

  callbackContainer_OnMousemove: function(evt)
  {
    this.isdraging = true;
  },
  callbackContainer_OnMousedown: function(evt)
  {
    if ( ((is_ie || is_khtml) && evt.button == 1) || (is_ff && evt.button == 0) )
      this.isdraging = false;
  },
  callbackContainer_OnMouseup: function(evt)
  {
    if ( ((is_ie || is_khtml) && evt.button == 1) || (is_ff && evt.button == 0) )
      if (!this.isdraging)
        if (this.el_words && !this.minmax_status)
          this.el_words.focus();
  },

  /**
   * hide error area and stop blinking fields
   */
  clearError: function(ids)
  { 
    this.el_errors.style.display = 'none';
    for (var i=0; i<ids.length; i++)
      this.blink(ids[i].id, 'stop');
  },

  /**
   * show error area and assign to it an error message and start the blinking of given fields
   */
  setError: function(str, ids)
  {
    this.el_errors.innerHTML = str;
    this.el_errors.style.display = 'block';
    for (var i=0; i<ids.length; i++)
      this.blink(ids[i].id, 'start');
  },

  /**
   * blink routines used by Error functions
   */
  blink: function(id, action)
  {
    clearTimeout(this.blinktimeout[id]);
    if ($(id) == null) return;
    if (action == 'start')
    {
      this.blinktmp[id] = $(id).style.backgroundColor;
      clearTimeout(this.blinktimeout[id]);
      this.blinktimeout[id] = setTimeout('pfc.blink(\'' + id + '\',\'loop\')', 500);
    }
    if (action == 'stop')
    {
      $(id).style.backgroundColor = this.blinktmp[id];
    }
    if (action == 'loop')
    {
      if (this.blinkloop[id] == 1)
      {
	$(id).style.backgroundColor = '#FFDFC0';
	this.blinkloop[id] = 2;
      }
      else
      {
	$(id).style.backgroundColor = '#FFFFFF';
	this.blinkloop[id] = 1;
      }
      this.blinktimeout[id] = setTimeout('pfc.blink(\'' + id + '\',\'loop\')', 500);
    }
  },

  displayMsg: function( cmd, msg )
  {
    this.setError(msg, Array());

    // @todo find a better crossbrowser way to display messages
/*
    // get the current selected tab container
    var tabid     = this.gui.getTabId();
    var container = this.gui.getChatContentFromTabId(tabid);

    // to fix IE6 display bug
    // http://sourceforge.net/tracker/index.php?func=detail&aid=1545403&group_id=158880&atid=809601
    div = document.createElement('div');
    // div.style.padding = "2px 5px 2px 5px"; // this will clear the screen in IE6
    div.innerHTML = '<div class="pfc_info pfc_info_'+cmd+'" style="margin:5px">'+msg+'</div>';

    // finaly append this to the message list
    container.appendChild(div); 
    this.gui.scrollDown(tabid, div);
*/
  },

  handleComingRequest: function( cmds )
  {
    var msg_html = $H();
    
    //alert(cmds.inspect());
    
    //    var html = '';
    for(var mid = 0; mid < cmds.length ; mid++)
    {
      var id          = cmds[mid][0];
      var date        = cmds[mid][1];
      var time        = cmds[mid][2];
      var sender      = cmds[mid][3];
      var recipientid = cmds[mid][4];
      var cmd         = cmds[mid][5];
      var param       = cmds[mid][6];
      var fromtoday   = cmds[mid][7];
      var oldmsg      = cmds[mid][8];
      
      // format and post message
      var line = '';
      line += '<div id="pfc_msg'+ id +'" class="pfc_cmd_'+ cmd +' pfc_message';
      line  += (id % 2 == 0) ? ' pfc_evenmsg' : ' pfc_oddmsg';
      if (oldmsg == 1) line += ' pfc_oldmsg';
      line += '">';
      line += '<span class="pfc_date';
      if (fromtoday == 1) line += ' pfc_invisible';
      line += '">'+ date +'</span> ';
      line += '<span class="pfc_heure">'+ time +'</span> ';
      if (cmd == 'send')
      {
	line += ' <span class="pfc_nick">';
	line += '&#x2039;';
	line += '<span ';
        line += 'onclick="pfc.insert_text(\'' + sender.replace("'", '\\\'') + ', \',\'\',false)" ';
	line += 'class="pfc_nickmarker pfc_nick_'+ hex_md5(_to_utf8(sender)) +'">';
	line += sender;
	line += '</span>';
	line += '&#x203A;';
	line += '</span> ';
      }
      if (cmd == 'notice' || cmd == 'me')
	line += '<span class="pfc_words">* '+ this.parseMessage(param) +'</span> ';
      else
	line += '<span class="pfc_words">'+ this.parseMessage(param) +'</span> ';
      line += '</div>';

      if (oldmsg == 0)
        if (cmd == 'send' || cmd == 'me')
        {
          // notify the hidden tab a message has been received
          // don't notify anything if this is old messages
          var tabid = recipientid;
          if (this.gui.getTabId() != tabid)
            this.gui.notifyTab(tabid);
	  // notify the window (change the title)
          if (!this.detectactivity.isActive() && pfc_notify_window)
            this.gui.notifyWindow();
        }
        
      if (msg_html[recipientid] == null)
        msg_html[recipientid] = line;
      else
        msg_html[recipientid] += line;
    }

    // loop on all recipients and post messages
    var keys = msg_html.keys();
    for( var i=0; i<keys.length; i++)
    {
      var recipientid  = keys[i];
      var tabid        = recipientid;
      
      // create the tab if it doesn't exists yet
      var recipientdiv = this.gui.getChatContentFromTabId(tabid);
      
      // create a dummy div to avoid konqueror bug when setting nickmarkers
      var m = document.createElement('div');
      m.innerHTML = msg_html[recipientid];
      this.colorizeNicks(m);
      this.refresh_clock(m);
      // finaly append this to the message list
      recipientdiv.appendChild(m);
      this.gui.scrollDown(tabid, m);
    }
  },
  
  /**
   * Call the ajax request function
   * Will query the server
   */
  sendRequest: function(cmd, param)
  {
    var recipientid = this.gui.getTabId();
    var req = cmd+" "+this.clientid+" "+(recipientid==''?'0':recipientid)+(param?" "+param : "");
    if (pfc_debug)
      if (cmd != "/update") trace('sendRequest: '+req);
    return eval('pfc_handleRequest(req);');
  },

  /**
   * update function to poll the server each 'refresh_delay' time
   */
  updateChat: function(start)
  {
    clearTimeout(this.timeout);
    if (start)
    {
      var res = true;
      if (this.canupdatenexttime || (new Date().getTime() - this.last_refresh_time) > this.max_refresh_delay)
      {
        res = this.sendRequest('/update');
        this.canupdatenexttime = false; // don't update since the 'ok' response is received
      }
      // adjust the refresh_delay if the connection was lost
      if (res == false) {
        this.refresh_delay = this.refresh_delay * 2;
		if(this.refresh_delay > this.max_refresh_delay)
		{
			this.refresh_delay = this.max_refresh_delay;
		}
	  }
      // setup the next update
      this.timeout = setTimeout('pfc.updateChat(true)', this.refresh_delay);
    }
  },

  /**
   * insert a smiley
   */
  insertSmiley: function(s)
  {
    this.el_words.value += s;
    this.el_words.focus();
  },

  updateNickBox: function(nickid)
  {
    // @todo optimize this function because it is called lot of times so it could cause CPU consuming on client side
    var chanids = this.chanmeta.keys();
    for(var i = 0; chanids.length > i; i++)
    {
      this.updateNickListBox(chanids[i]);
    }
  },

  /**
   * fill the nickname list with connected nicknames
   */
  updateNickListBox: function(chanid)
  {
    var nickidlst = this.chanmeta[chanid]['users']['nickid'];
    var nickdiv = this.gui.getOnlineContentFromTabId(chanid).firstChild;
    var ul = document.createElement('ul');
    ul.setAttribute('class',     'pfc_nicklist');
    ul.setAttribute('className', 'pfc_nicklist'); // IE6
    for (var i=0; i<nickidlst.length; i++)
    {
      var nickid = nickidlst[i];

      var li = this.buildNickItem(nickid);
      li.setAttribute('class',     'pfc_nickitem_'+nickid);
      li.setAttribute('className', 'pfc_nickitem_'+nickid); // IE6
      ul.appendChild(li);
    }
    var fc = nickdiv.firstChild;
    if (fc)
      nickdiv.replaceChild(ul,fc);
    else
      nickdiv.appendChild(ul,fc);
    this.colorizeNicks(nickdiv);
  },

  getNickWhoisBox: function(nickid)
  {
    if (!this.nickwhoisbox[nickid])
      this.updateNickWhoisBox(nickid);
    return this.nickwhoisbox[nickid];
  },

  updateNickWhoisBox: function(nickid)
  {
    var div  = document.createElement('div');
    div.setAttribute('class',     'pfc_nickwhois');
    div.setAttribute('className', 'pfc_nickwhois'); // for IE6

    var ul = document.createElement('ul');
    div.appendChild(ul);

    // add the close button
    var li = document.createElement('li');
    li.setAttribute('class',     'pfc_nickwhois_close');
    li.setAttribute('className', 'pfc_nickwhois_close'); // for IE6
    ul.appendChild(li);
    var a = document.createElement('a');
    a.setAttribute('href', '');
    a.pfc_parent = div;
    a.onclick = function(evt){
      this.pfc_parent.style.display = 'none';
      return false;
    }
    var img = document.createElement('img');
    img.setAttribute('src', this.res.getFileUrl('images/close-whoisbox.gif'));
    img.alt = document.createTextNode(this.res.getLabel('Close'));
    a.appendChild(img);
    li.appendChild(a);

    // add the privmsg link (do not add it if this button is yourself)
    if (pfc.getUserMeta(nickid,'nick') != this.nickname)
    {
      var li = document.createElement('li');
      li.setAttribute('class',     'pfc_nickwhois_pv');
      li.setAttribute('className', 'pfc_nickwhois_pv'); // for IE6
      ul.appendChild(li);
      var a = document.createElement('a');
      a.setAttribute('href', '');
      a.pfc_nickid = nickid;
      a.pfc_parent = div;
      a.onclick = function(evt){
        var nick = pfc.getUserMeta(this.pfc_nickid,'nick');
        pfc.sendRequest('/privmsg', nick);
        this.pfc_parent.style.display = 'none';
        return false;
      }
      var img = document.createElement('img');
      img.setAttribute('src', this.res.getFileUrl('images/openpv.gif'));
      img.alt = document.createTextNode(this.res.getLabel('Private message'));
      a.appendChild(img);
      a.appendChild(document.createTextNode(this.res.getLabel('Private message')));
      li.appendChild(a);
    }


    // add the whois information table
    var table = document.createElement('table');
//    table.setAttribute('cellspacing',0);
//    table.setAttribute('cellpadding',0);
//    table.setAttribute('border',0);
    var tbody = document.createElement('tbody');
    table.appendChild(tbody);
    var um = this.getAllUserMeta(nickid);
    var um_keys = um.keys();
    var msg = '';
    for (var i=0; i<um_keys.length; i++)
    {
      var k = um_keys[i];
      var v = um[k];
      if (v && k != 'nickid'
            && k != 'floodtime'
            && k != 'flood_nbmsg'
            && k != 'flood_nbchar'
         )
      {
        var tr = document.createElement('tr');
        var td1 = document.createElement('td');
        td1.setAttribute('class',     'pfc_nickwhois_c1');
        td1.setAttribute('className', 'pfc_nickwhois_c1'); // for IE6
        var td2 = document.createElement('td');
        td2.setAttribute('class',     'pfc_nickwhois_c2');
        td2.setAttribute('className', 'pfc_nickwhois_c2'); // for IE6
        td1.appendChild(document.createTextNode(k));
        td2.appendChild(document.createTextNode(v));
        tr.appendChild(td1);
        tr.appendChild(td2);
        tbody.appendChild(tr);
      }
    }
    div.appendChild(table);

    this.nickwhoisbox[nickid] = div;
  },

  buildNickItem: function(nickid)
  {
    var nick = this.getUserMeta(nickid, 'nick');
    var isadmin = this.getUserMeta(nickid, 'isadmin');
    var li = document.createElement('li');

    var img = document.createElement('img');
    if (isadmin)
      img.setAttribute('src', this.res.getFileUrl('images/user-admin.gif'));
    else
      img.setAttribute('src', this.res.getFileUrl('images/user.gif'));
    img.style.marginRight = '5px';
    img.setAttribute('class',     'pfc_nickbutton');
    img.setAttribute('className', 'pfc_nickbutton'); // for IE6
    img.pfc_nick   = nick;
    img.pfc_nickid = nickid;
    img.onclick = function(evt){
      var d = pfc.getNickWhoisBox(this.pfc_nickid);
      document.body.appendChild(d);
      d.style.display = 'block';
      d.style.zIndex = '400';
      d.style.position = 'absolute';
      d.style.left = (mousePosX(evt)-5)+'px';
      d.style.top  = (mousePosY(evt)-5)+'px';
      return false;
    }
    li.appendChild(img);

  
    // nobr is not xhtml valid but it's a workeround 
    // for IE which doesn't support 'white-space: pre' css rule
    var nobr = document.createElement('nobr');
    var span = document.createElement('span');
    span.setAttribute('class',     'pfc_nickmarker pfc_nick_'+nickid);
    span.setAttribute('className', 'pfc_nickmarker pfc_nick_'+nickid); // for IE6
    span.pfc_nick   = nick;
    span.pfc_nickid = nickid;
    span.onclick = function(){pfc.insert_text(this.pfc_nick+", ","",false); return false;}
    span.appendChild(document.createTextNode(nick));
    nobr.appendChild(span);
    li.appendChild(nobr);
    li.style.borderBottom = '1px solid #AAA';
    return li;
  },
  
  /**
   * clear the nickname list
   */
  clearNickList: function()
  {
    /*
    var nickdiv = this.el_online;
    var fc = nickdiv.firstChild;
    if (fc) nickdiv.removeChild(fc);
    */
  },


  /**
   * clear the message list history
   */
  clearMessages: function()
  {
    //var msgdiv = $('pfc_chat');
    //msgdiv.innerHTML = '';
  },

  /**
   * parse the message
   */
  parseMessage: function(msg)
  {
    var rx = null;
   
    // parse urls
    var rx_url = new RegExp('(^|[^\\"])([a-z]+\:\/\/[a-z0-9.\\/\\?\\=\\&\\-\\_\\#:;%]*)([^\\"]|$)','ig');
    var ttt = msg.split(rx_url);
    if (ttt.length > 1 &&
        !navigator.appName.match("Explorer|Konqueror") &&
        !navigator.appVersion.match("KHTML"))
    {
      msg = '';
      for( var i = 0; i<ttt.length; i++)
      {
        var offset = (ttt[i].length - 7) / 2;
        var delta = (ttt[i].length - 7 - 60);
        var range1 = 7+offset-delta;
        var range2 = 7+offset+delta;
        if (ttt[i].match(rx_url))
        {
          msg = msg + '<a href="' + ttt[i] + '"';
          if (pfc_openlinknewwindow)
            msg = msg + ' onclick="window.open(this.href,\'_blank\');return false;"';
          msg = msg + '>' + (delta>0 ? ttt[i].substring(7,range1)+ ' ... ' + ttt[i].substring(range2,ttt[i].length) :  ttt[i]) + '</a>';
        }
        else
        {
          msg = msg + ttt[i];
        }
      }
    }
    else
    {
      // fallback for IE6/Konqueror which do not support split with regexp
      replace = '$1<a href="$2"';
      if (pfc_openlinknewwindow)
        replace = replace + ' onclick="window.open(this.href,\'_blank\');return false;"';
      replace = replace + '>$2</a>$3';
      msg = msg.replace(rx_url, replace);
    }
    
    // replace double spaces by &nbsp; entity
    rx = new RegExp('  ','g');
    msg = msg.replace(rx, '&nbsp;&nbsp;');

    // try to parse bbcode
    rx = new RegExp('\\[b\\](.+?)\\[\/b\\]','ig');
    msg = msg.replace(rx, '<span style="font-weight: bold">$1</span>');
    rx = new RegExp('\\[i\\](.+?)\\[\/i\\]','ig');
    msg = msg.replace(rx, '<span style="font-style: italic">$1</span>');
    rx = new RegExp('\\[u\\](.+?)\\[\/u\\]','ig');
    msg = msg.replace(rx, '<span style="text-decoration: underline">$1</span>');
    rx = new RegExp('\\[s\\](.+?)\\[\/s\\]','ig');
    msg = msg.replace(rx, '<span style="text-decoration: line-through">$1</span>');
    //    rx = new RegExp('\\[pre\\](.+?)\\[\/pre\\]','ig');
    // msg = msg.replace(rx, '<pre>$1</pre>'); 
    rx = new RegExp('\\[email\\]([A-z0-9][\\w.-]*@[A-z0-9][\\w\\-\\.]+\\.[A-z0-9]{2,6})\\[\/email\\]','ig');
    msg = msg.replace(rx, '<a href="mailto: $1">$1</a>'); 
    rx = new RegExp('\\[email=([A-z0-9][\\w.-]*@[A-z0-9][\\w\\-\\.]+\\.[A-z0-9]{2,6})\\](.+?)\\[\/email\\]','ig');
    msg = msg.replace(rx, '<a href="mailto: $1">$2</a>');
    rx = new RegExp('\\[color=([a-zA-Z]+|\\#?[0-9a-fA-F]{6}|\\#?[0-9a-fA-F]{3})](.+?)\\[\/color\\]','ig');
    msg = msg.replace(rx, '<span style="color: $1">$2</span>');
    // parse bbcode colors twice because the current_text_color is a bbcolor
    // so it's possible to have a bbcode color imbrication
    rx = new RegExp('\\[color=([a-zA-Z]+|\\#?[0-9a-fA-F]{6}|\\#?[0-9a-fA-F]{3})](.+?)\\[\/color\\]','ig');
    msg = msg.replace(rx, '<span style="color: $1">$2</span>');   

    // try to parse smileys
    var smileys = this.res.getSmileyHash();
    var sl = smileys.keys();
    for(var i = 0; i < sl.length; i++)
    {
      rx = new RegExp(RegExp.escape(sl[i]),'g');
      msg = msg.replace(rx, '<img src="'+ smileys[sl[i]] +'" alt="' + sl[i] + '" title="' + sl[i] + '" />');
    }
    
    // try to parse nickname for highlighting 
    rx = new RegExp('(^|[ :,;])'+RegExp.escape(this.nickname)+'([ :,;]|$)','gi');
    msg = msg.replace(rx, '$1<strong>'+ this.nickname +'</strong>$2');

    // don't allow to post words bigger than 65 caracteres
    // doesn't work with crappy IE and Konqueror !
    rx = new RegExp('([^ \\:\\<\\>\\/\\&\\;]{60})','ig');
    var ttt = msg.split(rx);
    if (ttt.length > 1 &&
        !navigator.appName.match("Explorer|Konqueror") &&
        !navigator.appVersion.match("KHTML"))
    {
      msg = '';
      for( var i = 0; i<ttt.length; i++)
      {
        msg = msg + ttt[i] + ' ';
      }
    }
    return msg;
  },

  /**
   * apply nicknames color to the root childs
   */
  colorizeNicks: function(root)
  {
    if (this.nickmarker)
    {
      var nicklist = this.getElementsByClassName(root, 'pfc_nickmarker', '');
      for(var i = 0; i < nicklist.length; i++)
      {
        var cur_nick = nicklist[i].innerHTML;
        var cur_color = this.getAndAssignNickColor(cur_nick);
        nicklist[i].style.color = cur_color;
      }
    }
  },
  
  /**
   * Initialize the color array used to colirize the nicknames
   */
  reloadColorList: function()
  {
    this.colorlist = $A(pfc_nickname_color_list);
  },
  

  /**
   * get the corresponding nickname color
   */
  getAndAssignNickColor: function(nick)
  {
    /* check the nickname is colorized or not */
    var allready_colorized = false;
    var nc = '';
    for(var j = 0; j < this.nickcolor.length; j++)
    {
      if (this.nickcolor[j][0] == nick)
      {
	allready_colorized = true;
	nc = this.nickcolor[j][1];
      }
    }
    if (!allready_colorized)
    {
      /* reload the color stack if it's empty */
      if (this.colorlist.length == 0) this.reloadColorList();
      /* take the next color from the list and colorize this nickname */
      var cid = Math.round(Math.random()*(this.colorlist.length-1));
      nc = this.colorlist[cid];
      this.colorlist.splice(cid,1);
      this.nickcolor.push(new Array(nick, nc));
    }

    return nc;
  },
  

  /**
   * Colorize with 'color' all the nicknames found as a 'root' child
   */
  applyNickColor: function(root, nick, color)
  {
    
    var nicktochange = this.getElementsByClassName(root, 'pfc_nick_'+ hex_md5(_to_utf8(nick)), '');
    for(var i = 0; nicktochange.length > i; i++) 
      nicktochange[i].style.color = color;
    
  },

  /**
   * Returns a list of elements which have a clsName class
   */
  getElementsByClassName: function( root, clsName, clsIgnore )
  {
    var i, matches = new Array();
    var els = root.getElementsByTagName('*');
    var rx1 = new RegExp('.*'+clsName+'.*');
    var rx2 = new RegExp('.*'+clsIgnore+'.*');
    for(i=0; i<els.length; i++) {
      if(els.item(i).className.match(rx1) &&
         (clsIgnore == '' || !els.item(i).className.match(rx2)) ) {
	matches.push(els.item(i));
      }
    }
    return matches;
  },

  showClass: function(root, clsName, clsIgnore, show)
  {
    var elts = this.getElementsByClassName(root, clsName, clsIgnore);
    for(var i = 0; elts.length > i; i++)
    if (show)
      elts[i].style.display = 'inline';
    else
      elts[i].style.display = 'none';
  },


  /**
   * Nickname marker show/hide
   */
  nickmarker_swap: function()
  {
    if (this.nickmarker) {
      this.nickmarker = false;
    } else {
      this.nickmarker = true;
    }
    this.refresh_nickmarker()
    setCookie('pfc_nickmarker', this.nickmarker);
  },
  refresh_nickmarker: function(root)
  {
    var nickmarker_icon = $('pfc_nickmarker');
    if (!root) root = $('pfc_channels_content');
    if (this.nickmarker)
    {
      nickmarker_icon.src   = this.res.getFileUrl('images/color-on.gif');
      nickmarker_icon.alt   = this.res.getLabel("Hide nickname marker");
      nickmarker_icon.title = nickmarker_icon.alt;
      this.colorizeNicks(root);
    }
    else
    {
      nickmarker_icon.src   = this.res.getFileUrl('images/color-off.gif');
      nickmarker_icon.alt   = this.res.getLabel("Show nickname marker");
      nickmarker_icon.title = nickmarker_icon.alt;
      var elts = this.getElementsByClassName(root, 'pfc_nickmarker', '');
      for(var i = 0; elts.length > i; i++)
      {
	// this is not supported in konqueror =>>>  elts[i].removeAttribute('style');
	elts[i].style.color = '';
      }
    }
  },


  /**
   * Date/Hour show/hide
   */
  clock_swap: function()
  {
    if (this.clock) {
      this.clock = false;
    } else {
      this.clock = true;
    }
    this.refresh_clock();
    setCookie('pfc_clock', this.clock);
  },
  refresh_clock: function( root )
  {
    var clock_icon = $('pfc_clock');
    if (!root) root = $('pfc_channels_content');
    if (this.clock)
    {
      clock_icon.src   = this.res.getFileUrl('images/clock-on.gif');
      clock_icon.alt   = this.res.getLabel('Hide dates and hours');
      clock_icon.title = clock_icon.alt;
      this.showClass(root, 'pfc_date', 'pfc_invisible', true);
      this.showClass(root, 'pfc_heure', 'pfc_invisible', true);
    }
    else
    {
      clock_icon.src   = this.res.getFileUrl('images/clock-off.gif');
      clock_icon.alt   = this.res.getLabel('Show dates and hours');
      clock_icon.title = clock_icon.alt;
      this.showClass(root, 'pfc_date', 'pfc_invisible', false);
      this.showClass(root, 'pfc_heure', 'pfc_invisible', false);
    }
    // browser automaticaly scroll up misteriously when showing the dates
    //    $('pfc_chat').scrollTop += 30;
  },
  
  /**
   * Connect/disconnect button
   */
  connect_disconnect: function()
  {
    if (this.isconnected)
      this.sendRequest('/quit');
    else
      this.sendRequest('/connect');
  },
  refresh_loginlogout: function()
  {
    var loginlogout_icon = $('pfc_loginlogout');
    if (this.isconnected)
    {
      loginlogout_icon.src   = this.res.getFileUrl('images/logout.gif');
      loginlogout_icon.alt   = this.res.getLabel('Disconnect');
      loginlogout_icon.title = loginlogout_icon.alt;
    }
    else
    {
      this.clearMessages();
      this.clearNickList();
      loginlogout_icon.src   = this.res.getFileUrl('images/login.gif');
      loginlogout_icon.alt   = this.res.getLabel('Connect');
      loginlogout_icon.title = loginlogout_icon.alt;
    }
  },



  /**
   * Minimize/Maximized the chat zone
   */
  swap_minimize_maximize: function()
  {
    if (this.minmax_status) {
      this.minmax_status = false;
    } else {
      this.minmax_status = true;
    }
    setCookie('pfc_minmax_status', this.minmax_status);
    this.refresh_minimize_maximize();
  },
  refresh_minimize_maximize: function()
  {
    var content = $('pfc_content_expandable');
    var btn     = $('pfc_minmax');
    if (this.minmax_status)
    {
      btn.src = this.res.getFileUrl('images/maximize.gif');
      btn.alt = this.res.getLabel('Magnify');
      btn.title = btn.alt;
      content.style.display = 'none';
    }
    else
    {
      btn.src = this.res.getFileUrl('images/minimize.gif');
      btn.alt = this.res.getLabel('Cut down');
      btn.title = btn.alt;
      content.style.display = 'block';
    }
  },
  
  /**
   * BBcode ToolBar
   */
  insert_text: function(open, close, promptifselempty) 
  {
    var msgfield = $('pfc_words');
    
    // IE support
    if (document.selection && document.selection.createRange)
    {
      msgfield.focus();
      sel = document.selection.createRange();
      var text = sel.text;
      if (text == "" && promptifselempty)
        text = prompt(this.res.getLabel('Enter the text to format'),'');
      if (text == null) text = "";
      if (text.length > 0 || !promptifselempty)
      {
        sel.text = open + text + close;
        // @todo move the cursor just after the BBCODE, this doesn't work when the text to enclose is selected, IE6 keeps the whole selection active after the operation.
        msgfield.focus();
      }
    }
    
    // Moz support
    else if (msgfield.selectionStart || msgfield.selectionStart == '0')
    {
      var startPos = msgfield.selectionStart;
      var endPos = msgfield.selectionEnd;
      
      var text = msgfield.value.substring(startPos, endPos);
      var extralength = 0;
      if (startPos == endPos && promptifselempty)
      {
        text = prompt(this.res.getLabel('Enter the text to format'),'');
        if (text == null) text = "";
        extralength = text.length;
      }
      if (text.length > 0 || !promptifselempty)
      {
        msgfield.value = msgfield.value.substring(0, startPos) + open + text + close + msgfield.value.substring(endPos, msgfield.value.length);
        msgfield.selectionStart = msgfield.selectionEnd = endPos + open.length + extralength + close.length;
        msgfield.focus();
      }
    }
    
    // Fallback support for other browsers
    else
    {
      var text = prompt(this.res.getLabel('Enter the text to format'),'');
      if (text == null) text = "";
      if (text.length > 0 || !promptifselempty)
      {
        msgfield.value += open + text + close;
        msgfield.focus();
      }
    }
    return;
  },
  
  /**
   * Minimize/Maximize none/inline
   */
  minimize_maximize: function(idname, type)
  {
    var element = $(idname);
    if(element.style)
    {
      if(element.style.display == type )
      {
        element.style.display = 'none';
      }
      else
      {
        element.style.display = type;
      }
    }
  },
  
  switch_text_color: function(color)
  {
    /* clear any existing borders on the color buttons */
    var colorbtn = this.getElementsByClassName($('pfc_colorlist'), 'pfc_color', '');
    for(var i = 0; colorbtn.length > i; i++)
    {
      colorbtn[i].style.border = 'none';
      colorbtn[i].style.padding = '0';
    }

    /* assign the new border style to the selected button */
    this.current_text_color = color;
    setCookie('pfc_current_text_color', this.current_text_color);
    var idname = 'pfc_color_' + color;
    $(idname).style.border = '1px solid #555';
    $(idname).style.padding = '1px';

    // assigne the new color to the input text box
    this.el_words.style.color = '#'+color;
    this.el_words.focus();
  },
  
  /**
   * Smiley show/hide
   */
  showHideSmileys: function()
  {
    if (this.showsmileys)
    {
      this.showsmileys = false;
    }
    else
    {
      this.showsmileys = true;
    }
    setCookie('pfc_showsmileys', this.showsmileys);
    this.refresh_Smileys();
  },
  refresh_Smileys: function()
  {
    // first of all : show/hide the smiley box
    var content = $('pfc_smileys');
    if (this.showsmileys)
      content.style.display = 'block';
    else
      content.style.display = 'none';

    // then switch the button icon
    var btn = $('pfc_showHideSmileysbtn');
    if (this.showsmileys)
    {
      if (btn)
      {
        btn.src = this.res.getFileUrl('images/smiley-on.gif');
        btn.alt = this.res.getLabel('Hide smiley box');
        btn.title = btn.alt;
      }
    }
    else
    {
      if (btn)
      {
        btn.src = this.res.getFileUrl('images/smiley-off.gif');
        btn.alt = this.res.getLabel('Show smiley box');
        btn.title = btn.alt;
      }
    }
  },
  
  
  /**
   * Show Hide who's online
   */
  showHideWhosOnline: function()
  {
    if (this.showwhosonline)
    {
      this.showwhosonline = false;
    }
    else
    {
      this.showwhosonline = true;
    }
    setCookie('pfc_showwhosonline', this.showwhosonline);
    this.refresh_WhosOnline();
  },
  refresh_WhosOnline: function()
  {
    // first of all : show/hide the nickname list box
    var root = $('pfc_channels_content');
    var contentlist = this.getElementsByClassName(root, 'pfc_online', '');
    for(var i = 0; i < contentlist.length; i++)
    {
      var content = contentlist[i];
      if (this.showwhosonline)
        content.style.display = 'block';
      else
        content.style.display = 'none';
      content.style.zIndex = '100'; // for IE6, force the nickname list borders to be shown
    }

    // then refresh the button icon
    var btn = $('pfc_showHideWhosOnlineBtn');
    if (!btn) return;
    if (this.showwhosonline)
    {
      btn.src = this.res.getFileUrl('images/online-on.gif');
      btn.alt = this.res.getLabel('Hide online users box');
      btn.title = btn.alt;
    }
    else
    {
      btn.src = this.res.getFileUrl('images/online-off.gif');
      btn.alt = this.res.getLabel('Show online users box');
      btn.title = btn.alt;
    }
    this.refresh_Chat();
  },

  /**
   * Resize chat
   */
  refresh_Chat: function()
  {
    // resize all the tabs content
    var root = $('pfc_channels_content');
    var contentlist = this.getElementsByClassName(root, 'pfc_chat', '');
    for(var i = 0; i < contentlist.length; i++)
    {
      var chatdiv = contentlist[i];
      var style = $H();
      if (!this.showwhosonline)
      {
        chatdiv.style.width = '100%';
      }
      else
      {
        chatdiv.style.width = '';
      }
    }
  }
};
