/**
 * This class centralize the pfc' Graphic User Interface manipulations
 * (depends on prototype library)
 * @author Stephane Gully
 */
var pfcGui = Class.create();
pfcGui.prototype = {
  
  initialize: function()
  {
//    this.builder = new pfcGuiBuilder();
    this.current_tab    = '';
    this.current_tab_id = '';
    this.tabs       = Array();
    this.tabids     = Array();
    this.tabtypes   = Array();
    this.chatcontent   = $H();
    this.onlinecontent = $H();
    this.scrollpos     = $H();
    this.elttoscroll   = $H();
    this.windownotifynb = 0;
  },

  /**
   * Scroll down the message list area by elttoscroll height
   * - elttoscroll is a message DOM element which has been appended to the tabid's message list
   * - this.elttoscroll is an array containing the list of messages that will be scrolled 
   *   when the corresponding tab will be shown (see setTabById bellow).
   *   It is necessary to keep in cache the list of hidden (because the tab is inactive) messages 
   *   because the 'scrollTop' javascript attribute
   *   will not work if the element (tab content) is hidden.
   */
  scrollDown: function(tabid, elttoscroll)
  {
    // check the wanted tabid is the current active one
    if (this.getTabId() != tabid)
    {
      // no it's not the current active one so just cache the elttoscroll in the famouse this.elttoscroll array
      if (!this.elttoscroll[tabid]) this.elttoscroll[tabid] = Array();
      this.elttoscroll[tabid].push(elttoscroll);
      return;
    }
    // the wanted tab is active so just scroll down the tab content element
    // by elttoscroll element height (use 'offsetHeight' attribute)
    var content = this.getChatContentFromTabId(tabid);
    content.scrollTop += elttoscroll.offsetHeight+2;
    this.scrollpos[tabid] = content.scrollTop;
  },
  
  isCreated: function(tabid)
  {
    /*
    for (var i = 0; i < this.tabids.length ; i++)
    {
      if (this.tabids[i] == tabid) return true;
    }
    return false;
    */
    return (indexOf(this.tabids, tabid) >= 0);
  },
  
  setTabById: function(tabid)
  {
    // first of all save the scroll pos of the visible tab
    var content = this.getChatContentFromTabId(this.current_tab_id);
    this.scrollpos[this.current_tab_id] = content.scrollTop;
    
    // start without selected tabs
    this.current_tab     = '';
    this.current_tab_id  = '';
    var tab_to_show = null;
    // try to fine the tab to select and select it! 
    for (var i=0; i<this.tabids.length; i++)
    {
      var tabtitle   = $('pfc_channel_title'+this.tabids[i]);
      var tabcontent = $('pfc_channel_content'+this.tabids[i]);
      if (this.tabids[i] == tabid)
      {
        // select the tab
        tabtitle.setAttribute('class', 'selected');
        tabtitle.setAttribute('className', 'selected'); // for IE6
        //Element.addClassName(tabtitle, 'selected');
        tab_to_show = tabcontent;
        this.current_tab     = this.tabs[i];
        this.current_tab_id  = tabid;
      }
      else
      {
        // unselect the tab
        tabtitle.setAttribute('class', '');
        tabtitle.setAttribute('className', ''); // for IE6
        //Element.removeClassName(tabtitle, 'selected');
        tabcontent.style.display = 'none';
      }
    }

    // show the new selected tab
    tab_to_show.style.display = 'block';
    
    // restore the scroll pos
    var content = this.getChatContentFromTabId(tabid);
    content.scrollTop = this.scrollpos[tabid];

    // scroll the new posted message
    if (this.elttoscroll[tabid] &&
        this.elttoscroll[tabid].length > 0)
    {
      // on by one
      for (var i=0; i<this.elttoscroll[tabid].length; i++)
        this.scrollDown(tabid,this.elttoscroll[tabid][i]);
      // empty the cached element list because it has been scrolled
      this.elttoscroll[tabid] = Array();
    }
    
    this.unnotifyTab(tabid);
  },
  
  getTabId: function()
  {
    return this.current_tab_id;
  },

  getChatContentFromTabId: function(tabid)
  {
    // return the chat content if it exists
    var cc = this.chatcontent[tabid];
    if (cc) return cc;

    // if the chat content doesn't exists yet, just create a cached one
    cc = document.createElement('div');
    cc.setAttribute('id', 'pfc_chat_'+tabid);
    cc.setAttribute('class', 'pfc_chat');
    cc.setAttribute('className', 'pfc_chat'); // for IE6

    //    Element.addClassName(cc, 'pfc_chat');
    cc.style.display = "block"; // needed by IE6 to show the online div at startup (first loaded page)
    //    cc.style.marginLeft = "5px";

    this.chatcontent[tabid] = cc;
    return cc;
  },
  getOnlineContentFromTabId: function(tabid)
  {
    // return the online content if it exists
    var oc = this.onlinecontent[tabid];
    if (oc) return oc;

    oc = document.createElement('div');
    oc.setAttribute('id', 'pfc_online_'+tabid);
    oc.setAttribute('class', 'pfc_online');
    oc.setAttribute('className', 'pfc_online'); // for IE6
    //Element.addClassName(oc, 'pfc_online');
    // I set the border style here because seting it in the CSS is not taken in account
    //    oc.style.borderLeft = "1px solid #555";
    oc.style.display = "block"; // needed by IE6 to show the online div at startup (first loaded page)
    
    // Create a dummy div to add padding
    var div = document.createElement('div');
    div.style.padding = "5px";
    oc.appendChild(div);

    this.onlinecontent[tabid] = oc;
    return oc;
  },
  
  removeTabById: function(tabid)
  {
    // remove the widgets
    var tabparent_t = $('pfc_channels_list');
    var tabparent_c = $('pfc_channels_content');
    var tab_t = $('pfc_channel_title'+tabid);
    var tab_c = $('pfc_channel_content'+tabid);
    tabparent_t.removeChild(tab_t);
    tabparent_c.removeChild(tab_c);

    // empty the chat div content
    var div_chat = this.getChatContentFromTabId(tabid);
    div_chat.innerHTML = '';

    // remove the tab from the list
    var tabpos = indexOf(this.tabids, tabid);
    var name = this.tabs[tabpos];
    this.tabids     = without(this.tabids, this.tabids[tabpos]);
    this.tabs       = without(this.tabs, this.tabs[tabpos]);
    this.tabtypes   = without(this.tabtypes, this.tabtypes[tabpos]);
    tabpos = indexOf(this.tabids, this.getTabId());
    if (tabpos<0) tabpos = 0;
    this.setTabById(this.tabids[tabpos]);
    return name;    
  },

  /*
  removeTabByName: function(name)
  {
    var tabid = hex_md5(_to_utf8(name));
    var ret = this.removeTabById(tabid);
    if (ret == name)
      return tabid;
    else
      return 0;
  },
  */
  
  createTab: function(name, tabid, type)
  {
    // do not create empty tabs
    if(name == '') return;
    if(tabid == '') return;

    // do not create twice a the same tab
    if (this.isCreated(tabid)) return;

    //    var tabid = hex_md5(_to_utf8(name));
    //alert(name+'='+tabid);
    this.tabs.push(name);
    this.tabids.push(tabid);
    this.tabtypes.push(type);

    //alert(this.tabs.toString());
    
    var li_title = document.createElement('li');
    li_title.setAttribute('id', 'pfc_channel_title'+tabid);

    var li_div = document.createElement('div');
    li_div.setAttribute('id', 'pfc_tabdiv'+tabid);
    li_title.appendChild(li_div);
    
    var a1 = document.createElement('a');
    a1.setAttribute('class', 'pfc_tabtitle');
    a1.setAttribute('className', 'pfc_tabtitle'); // for IE6    
    a1.setAttribute('href', '#');
    a1.pfc_tabid = tabid;
    a1.onclick = function(){pfc.gui.setTabById(this.pfc_tabid); return false;}
    li_div.appendChild(a1);

    if (pfc_displaytabimage)
    {
      var img = document.createElement('img');
      img.setAttribute('id', 'pfc_tabimg'+tabid);
      if (type == 'ch')
        img.setAttribute('src', pfc.res.getFileUrl('images/ch.gif'));
      if (type == 'pv')
        img.setAttribute('src', pfc.res.getFileUrl('images/pv.gif'));
      a1.appendChild(img);
    }
    
    // on ajoute le nom du channel
    a1.appendChild(document.createTextNode(name));

    if (pfc_displaytabclosebutton)
    {
      var a2 = document.createElement('a');
      a2.pfc_tabid = tabid;
      a2.onclick = function(){
        var res = confirm(pfc.res.getLabel('Do you really want to leave this room ?'));
        if (res == true) pfc.sendRequest('/leave', this.pfc_tabid); return false;
      }
      a2.alt   = pfc.res.getLabel('Close this tab');
      a2.title = a2.alt;
      a2.setAttribute('class', 'pfc_tabclose');
      a2.setAttribute('className', 'pfc_tabclose'); // for IE6
      var img = document.createElement('img');
      img.setAttribute('src', pfc.res.getFileUrl('images/tab_remove.gif'));
      a2.appendChild(img);
      li_div.appendChild(a2);
    }
    
    var div_content = document.createElement('div');
    div_content.setAttribute('id', 'pfc_channel_content'+tabid);   
    //    Element.addClassName(div_content, 'pfc_content');
    div_content.setAttribute('class', 'pfc_content');
    div_content.setAttribute('className', 'pfc_content'); // for IE6    
    div_content.style.display = 'none';

    var div_chat    = this.getChatContentFromTabId(tabid);
    var div_online  = this.getOnlineContentFromTabId(tabid);
    div_content.appendChild(div_chat);
    div_content.appendChild(div_online);
   
    $('pfc_channels_list').appendChild(li_title);
    $('pfc_channels_content').appendChild(div_content);

    // force the height of the chat/online zone in pixel in order fix blank screens on IE6
    div_chat.style.height   = ($('pfc_channels_content').offsetHeight-1)+'px';
    div_online.style.height = ($('pfc_channels_content').offsetHeight-1)+'px';

    return tabid;
  },

  /**
   * This function change the window title in order to catch the attention
   */
  notifyWindow: function()
  {
    this.windownotifynb += 1;
    var rx = new RegExp('^\\[[0-9]+\\](.*)','ig');
    document.title = document.title.replace(rx,'$1');
    document.title = '['+this.windownotifynb+']'+document.title;
  },
  unnotifyWindow: function()
  {
    this.windownotifynb = 0;
    var rx = new RegExp('^\\[[0-9]+\\](.*)','ig');
    document.title = document.title.replace(rx,'$1');
  },

  /**
   * This function change the tab icon in order to catch the attention
   */
  notifyTab: function(tabid)
  {
    // first of all be sure the tab highlighting is cleared
    this.unnotifyTab(tabid);

    var tabpos = indexOf(this.tabids, tabid);
    var tabtype = this.tabtypes[tabpos];
   
    // handle the tab's image modification
    var img = $('pfc_tabimg'+tabid);
    if (img)
    {
      if (tabtype == 'ch')
        img.src = pfc.res.getFileUrl('images/ch-active.gif');
      if (tabtype == 'pv')
        img.src = pfc.res.getFileUrl('images/pv-active.gif');
    }
  
    // handle the blicking effect
    var div = $('pfc_tabdiv'+tabid);
    if (div)
    {
      if (div.blinkstat == true)
      {
        div.setAttribute('class',     'pfc_tabblink1');
        div.setAttribute('className', 'pfc_tabblink1'); // for IE6
      }
      else
      {
        div.setAttribute('class',     'pfc_tabblink2');
        div.setAttribute('className', 'pfc_tabblink2'); // for IE6
      }
      div.blinkstat = !div.blinkstat;
      div.blinktimeout = setTimeout('pfc.gui.notifyTab(\''+tabid+'\');', 500);
    }
  },

  /**
   * This function restore the tab icon to its default value
   */
  unnotifyTab: function(tabid)
  {
    var tabpos = indexOf(this.tabids, tabid);
    var tabtype = this.tabtypes[tabpos];

    // restore the tab's image
    var img = $('pfc_tabimg'+tabid);
    if (img)
    {
      if (tabtype == 'ch')
        img.src = pfc.res.getFileUrl('images/ch.gif');
      if (tabtype == 'pv')
        img.src = pfc.res.getFileUrl('images/pv.gif');
    }

    // stop the blinking effect
    var div = $('pfc_tabdiv'+tabid);
    if (div) 
    {
      div.removeAttribute('class');
      div.removeAttribute('className'); // for IE6
      clearTimeout(div.blinktimeout);
    }
  },

  loadSmileyBox: function()
  {
    var container = $('pfc_smileys');
    var smileys = pfc.res.getSmileyReverseHash();//getSmileyHash();
    var sl = smileys.keys();
    for(var i = 0; i < sl.length; i++)
    {
      s_url    = sl[i];
      s_symbol = smileys[sl[i]];

      var img = document.createElement('img');
      img.setAttribute('src', s_url);
      img.setAttribute('alt', s_symbol);
      img.setAttribute('title', s_symbol);
      img.s_symbol = s_symbol.unescapeHTML();
      img.onclick = function(){ pfc.insertSmiley(this.s_symbol); }
      container.appendChild(img);
    }
  },

  buildChat: function()
  {
    var container = $('pfc_container');

    // clean the chat box
    container.innerHTML = '';

    // minimize/maximize button
    var img = document.createElement('img');
    img.setAttribute('id', 'pfc_minmax');
    if (pfc_start_minimized)
      img.setAttribute('src', pfc.res.getFileUrl('images/maximize.gif'));
    else
      img.setAttribute('src', pfc.res.getFileUrl('images/minimize.gif'));
    img.setAttribute('alt', '');
    img.onclick = function(){ pfc.swap_minimize_maximize(); }
    container.appendChild(img);

    // title
    var h2 = document.createElement('h2');
    h2.setAttribute('id', 'pfc_title');
    h2.innerHTML = pfc_title;
    container.appendChild(h2);

    // content expandable
    var contentexp = document.createElement('div');
    contentexp.setAttribute('id', 'pfc_content_expandable');
    container.appendChild(contentexp);

    // channels : <div id="pfc_channels">
    var channels = document.createElement('div');
    channels.setAttribute('id', 'pfc_channels');
    contentexp.appendChild(channels);
    // channels list : <ul id="pfc_channels_list">
    var channelslist = document.createElement('ul');
    channelslist.setAttribute('id', 'pfc_channels_list');
    channels.appendChild(channelslist);
    // channels content : <div id="pfc_channels_content">
    var channelscontent = document.createElement('div');
    channelscontent.setAttribute('id', 'pfc_channels_content');
    channels.appendChild(channelscontent);

    // input container : <div id="pfc_input_container">
    var inputcontainer = document.createElement('div');
    inputcontainer.setAttribute('id', 'pfc_input_container');
    contentexp.appendChild(inputcontainer);

    // this is the table which will contains input word and send button
    // (I didn't found a cleaner way to align these input elements horizontaly in the same line)
    var table1 = document.createElement('table');
    table1.setAttribute('style','border-collapse:collapse;margin:0;padding:0;');
    var tbody1 = document.createElement('tbody');
    table1.appendChild(tbody1);
    var tr1 = document.createElement('tr');
    var td1 = document.createElement('td');
    var td2 = document.createElement('td');
    td1.setAttribute('width', '100%');
    tbody1.appendChild(tr1);
    tr1.appendChild(td1);
    tr1.appendChild(td2);
    inputcontainer.appendChild(table1);

    // input words : <input id="pfc_words" type="text" ... />
    var inputwords = document.createElement('input');
    inputwords.setAttribute('id', 'pfc_words');
    inputwords.setAttribute('type', 'text');
    inputwords.setAttribute('title', pfc.res.getLabel("Enter your message here"));
    inputwords.setAttribute('maxlength', pfc_max_text_len);
    td1.appendChild(inputwords);

    // send button : <input id="pfc_send" type="button" ... />
    var sendbtn = document.createElement('input');
    sendbtn.setAttribute('id', 'pfc_send');
    sendbtn.setAttribute('type', 'button');
    sendbtn.setAttribute('value', pfc.res.getLabel("Send"));
    sendbtn.setAttribute('title', pfc.res.getLabel("Click here to send your message"));
    sendbtn.onclick = function(){ pfc.doSendMessage(); } 
    td2.appendChild(sendbtn);

    // command container : <div id="pfc_cmd_container">
    var cmdcontainer = document.createElement('div');
    cmdcontainer.setAttribute('id', 'pfc_cmd_container');
    inputcontainer.appendChild(cmdcontainer);

    // move the phpfreechat logo into the cmd container box
    var a = document.createElement('a');
    a.setAttribute('id', 'pfc_logo');
    a.setAttribute('href','http://www.phpfreechat.net');
    if (pfc_openlinknewwindow)
      a.onclick = function(){ window.open(this.href,'_blank'); return false; } 
    var img = document.createElement('img');
    img.setAttribute('src', 'http://www.phpfreechat.net/pub/logo_80x15.gif');
    img.setAttribute('alt', pfc.res.getLabel("PHP FREE CHAT [powered by phpFreeChat-%s]",pfc_version));
    img.title = img.alt;
    a.appendChild(img);
    cmdcontainer.appendChild(a);
    
    // handle box : <input id="pfc_handle" type="button" ...
    var handle = document.createElement('p');
    handle.setAttribute('id', 'pfc_handle');
    handle.setAttribute('title', pfc.res.getLabel("Enter your nickname here"));
    handle.appendChild(document.createTextNode(pfc.nickname));
    handle.onclick = function(){ pfc.askNick(''); } 
    cmdcontainer.appendChild(handle);

    // buttons : <div class="pfc_btn">

    // button login/logout
    var btn = document.createElement('div');
    btn.setAttribute('class', 'pfc_btn')
    btn.setAttribute('className', 'pfc_btn'); // for IE6
    var img = document.createElement('img');
    img.setAttribute('id', 'pfc_loginlogout');
    img.setAttribute('src', pfc.res.getFileUrl('images/logout.gif'));
    img.onclick = function(){ pfc.connect_disconnect(); } 
    btn.appendChild(img);
    cmdcontainer.appendChild(btn);

    // button nickname color on/off
    var btn = document.createElement('div');
    btn.setAttribute('class', 'pfc_btn');
    btn.setAttribute('className', 'pfc_btn'); // for IE6
    var img = document.createElement('img');
    img.setAttribute('id', 'pfc_nickmarker');
    img.setAttribute('src', pfc.res.getFileUrl('images/color-on.gif'));
    img.onclick = function(){ pfc.nickmarker_swap(); }
    btn.appendChild(img);
    cmdcontainer.appendChild(btn);
 
    // button clock on/off
    var btn = document.createElement('div');
    btn.setAttribute('class', 'pfc_btn');
    btn.setAttribute('className', 'pfc_btn'); // for IE6
    var img = document.createElement('img');
    img.setAttribute('id', 'pfc_clock');
    img.setAttribute('src', pfc.res.getFileUrl('images/clock-on.gif'));
    img.onclick = function(){ pfc.clock_swap(); }
    btn.appendChild(img);
    cmdcontainer.appendChild(btn);

    // button smileys on/off
    if (pfc_btn_sh_smileys)
    {
      var btn = document.createElement('div');
      btn.setAttribute('class', 'pfc_btn');
      btn.setAttribute('className', 'pfc_btn'); // for IE6
      var img = document.createElement('img');
      img.setAttribute('id', 'pfc_showHideSmileysbtn');
      img.setAttribute('src', pfc.res.getFileUrl('images/smiley-on.gif'));
      img.onclick = function(){ pfc.showHideSmileys(); }
      btn.appendChild(img);
      cmdcontainer.appendChild(btn);
    }

    // button whoisonline on/off
    if (pfc_btn_sh_whosonline)
    {
      var btn = document.createElement('div');
      btn.setAttribute('class', 'pfc_btn');
      btn.setAttribute('className', 'pfc_btn'); // for IE6
      var img = document.createElement('img');
      img.setAttribute('id', 'pfc_showHideWhosOnlineBtn');
      img.setAttribute('src', pfc.res.getFileUrl('images/online-on.gif'));
      img.onclick = function(){ pfc.showHideWhosOnline(); }
      btn.appendChild(img);
      cmdcontainer.appendChild(btn);
    }

    // bbcode container : <div id="pfc_bbcode_container">
    bbcontainer = document.createElement('div');
    bbcontainer.setAttribute('id', 'pfc_bbcode_container');
    inputcontainer.appendChild(bbcontainer);

    // bbcode strong
    var btn = document.createElement('div');
    btn.setAttribute('class', 'pfc_btn');
    btn.setAttribute('className', 'pfc_btn'); // for IE6
    var img = document.createElement('img');
    img.setAttribute('class', 'pfc_bt_strong');
    img.setAttribute('className', 'pfc_bt_strong'); // for IE6
    img.setAttribute('title', pfc.res.getLabel("Bold"));
    img.setAttribute('src', pfc.res.getFileUrl('images/bt_strong.gif'));
    img.onclick = function(){ pfc.insert_text('[b]','[/b]',true); }
    btn.appendChild(img);
    bbcontainer.appendChild(btn);

    // bbcode italics
    var btn = document.createElement('div');
    btn.setAttribute('class', 'pfc_btn');
    btn.setAttribute('className', 'pfc_btn'); // for IE6
    var img = document.createElement('img');
    img.setAttribute('class', 'pfc_bt_italics');
    img.setAttribute('className', 'pfc_bt_italics'); // for IE6
    img.setAttribute('title', pfc.res.getLabel("Italics"));
    img.setAttribute('src', pfc.res.getFileUrl('images/bt_em.gif'));
    img.onclick = function(){ pfc.insert_text('[i]','[/i]',true); }
    btn.appendChild(img);
    bbcontainer.appendChild(btn);

    // bbcode underline
    var btn = document.createElement('div');
    btn.setAttribute('class', 'pfc_btn');
    btn.setAttribute('className', 'pfc_btn'); // for IE6
    var img = document.createElement('img');
    img.setAttribute('class', 'pfc_bt_underline');
    img.setAttribute('className', 'pfc_bt_underline'); // for IE6
    img.setAttribute('title', pfc.res.getLabel("Underline"));
    img.setAttribute('src', pfc.res.getFileUrl('images/bt_ins.gif'));
    img.onclick = function(){ pfc.insert_text('[u]','[/u]',true); }
    btn.appendChild(img);
    bbcontainer.appendChild(btn);

    // bbcode del
    var btn = document.createElement('div');
    btn.setAttribute('class', 'pfc_btn')
    btn.setAttribute('className', 'pfc_btn'); // for IE6
    var img = document.createElement('img');
    img.setAttribute('class', 'pfc_bt_delete');
    img.setAttribute('className', 'pfc_bt_delete'); // for IE6
    img.setAttribute('title', pfc.res.getLabel("Delete"));
    img.setAttribute('src', pfc.res.getFileUrl('images/bt_del.gif'));
    img.onclick = function(){ pfc.insert_text('[s]','[/s]',true); }
    btn.appendChild(img);
    bbcontainer.appendChild(btn);

    // bbcode mail
    var btn = document.createElement('div');
    btn.setAttribute('class', 'pfc_btn');
    btn.setAttribute('className', 'pfc_btn'); // for IE6
    var img = document.createElement('img');
    img.setAttribute('class', 'pfc_bt_mail');
    img.setAttribute('className', 'pfc_bt_mail'); // for IE6
    img.setAttribute('title', pfc.res.getLabel("Mail"));
    img.setAttribute('src', pfc.res.getFileUrl('images/bt_mail.gif'));
    img.onclick = function(){ pfc.insert_text('[email]','[/email]',true); }
    btn.appendChild(img);
    bbcontainer.appendChild(btn);

    // bbcode color
    var btn = document.createElement('div');
    btn.setAttribute('class', 'pfc_btn');
    btn.setAttribute('className', 'pfc_btn'); // for IE6
    var img = document.createElement('img');
    img.setAttribute('class', 'pfc_bt_color');
    img.setAttribute('className', 'pfc_bt_color'); // for IE6
    img.setAttribute('title', pfc.res.getLabel("Color"));
    img.setAttribute('src', pfc.res.getFileUrl('images/bt_color.gif'));
    img.onclick = function(){ pfc.minimize_maximize('pfc_colorlist','inline'); }
    btn.appendChild(img);
    bbcontainer.appendChild(btn);
    // color list
    var clist = document.createElement('div');
    clist.setAttribute('id', 'pfc_colorlist');
    bbcontainer.appendChild(clist);
    var clist_v = pfc_bbcode_color_list;
    for (var i=0 ; i<clist_v.length ; i++)
    {
      var bbc = clist_v[i];
      var elt = document.createElement('img');
      elt.bbc = bbc;
      elt.setAttribute('class', 'pfc_color');
      elt.setAttribute('className', 'pfc_color'); // for IE6
      elt.setAttribute('id', 'pfc_color_'+bbc);
      elt.style.backgroundColor = '#'+bbc;
      elt.setAttribute('src', pfc.res.getFileUrl('images/color_transparent.gif'));
      elt.setAttribute('alt', bbc);
      elt.onclick = function(){ pfc.switch_text_color(this.bbc); }
      clist.appendChild(elt);
    }

    // error box : <p id="pfc_errors">
    var errorbox = document.createElement('div');
    errorbox.setAttribute('id', 'pfc_errors');
    inputcontainer.appendChild(errorbox);

    // smiley box :  <div id="pfc_smileys">
    var smileybox = document.createElement('div');
    smileybox.setAttribute('id', 'pfc_smileys');
    inputcontainer.appendChild(smileybox);
    this.loadSmileyBox();
  }
  
};
