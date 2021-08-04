/* For licensing terms, see /license.txt */
/**
 * When the document is ready, do some preparation for the dictionary H5P content type elements
 */
$(document).ready(function () {

  var u = window.top.location.href

  if (u.indexOf('action=edit') == -1) {
    $('#dictionary').css('display', 'none')
    var btn = '<a id="addElement" href="#" onClick="showEditForm();" class="btn btn-success">'
    btn += 'Create Page</a><br><br>'
    //$("#dictionary").parent().prepend(btn);
  }

  $('#dictionary_title').parent().parent().css('display', 'none')
  $('#dictionary_node_type').parent().parent().css('display', 'none')
  $('#dictionary_submit').parent().parent().css('display', 'none')
  $('#dictionary_descript').parent().parent().css('display', 'none')

  $('#dictionary_terms_a').parent().parent().css('display', 'none')
  $('#dictionary_terms_b').parent().parent().css('display', 'none')
  $('#dictionary_terms_c').parent().parent().css('display', 'none')
  $('#dictionary_terms_d').parent().parent().css('display', 'none')
  $('#dictionary_terms_e').parent().parent().css('display', 'none')
  $('#dictionary_terms_f').parent().parent().css('display', 'none')

  $('#dictionary_opt_1').parent().parent().css('display', 'none')
  $('#dictionary_opt_2').parent().parent().css('display', 'none')
  $('#dictionary_opt_3').parent().parent().css('display', 'none')

  $('#dictionary_submit').parent().prepend('<a style="margin-right:10px;" href="list.php" class="btn btn-default" ><b>' + $('#h5p_cancel').html() + '</b></a>')

  $('#dictionary_submit').click(function (e) {

    var pagetype = $('#dictionary_node_type').val()

    if (pagetype == 'dialogcard') {
      if (controlsFieldsdialogcard() == false) {
        e.preventDefault()
      }
    }

    var title = $('#dictionary_title').val()
    if (title == '') {
      $('#dictionary_title').css('background', 'pink')
      e.preventDefault()
    } else {
      if (u.indexOf('action=edit') == -1) {
        if (pagetype == '') {
          e.preventDefault()
        }
      }
    }
  })

})

/**
 * Add an H5P option to the breadcrumb bar
 */
$(document).ready(function () {

  var menuBc = '<li class="active">&nbsp;&nbsp;'
  menuBc += '<a href="list.php" ><img src="resources/img/edit.png" alt="">H5P</a>&nbsp;&nbsp;</li>'

  var btn = '&nbsp;&nbsp;<a id="addElement" href="#" '
  btn += ' onClick="showEditForm();" class="btn btn-success">+</a>'

  var u = window.top.location.href

  $('.breadcrumb').html(menuBc + btn)
  $('.view-options').css('display', 'none')
  if (u.indexOf('action=edit') != -1) {
    $('.styleOfPages').css('display', 'none')
  }
  if (u.indexOf('node_process') != -1) {
    $('#addElement').css('display', 'none')
  }
  $('#addElement').css('background', '#30353d').css('border-color', '#30353d')
  $('.breadcrumb').css('background', '#474f5a')
  $('.breadcrumb').css('color', 'white')
  $('.breadcrumb li').css('color', 'white')
  $('.breadcrumb li a').css('color', 'white')
})

/**
 * Show the edition form
 */
function showEditForm() {
  $('#dictionary').css('display', '')
  $('#nodeselection').css('display', 'block')
  $('#addElement').css('display', 'none')
  $('.alert-info').css('display', 'none')
  checkRadio(1)
}

/**
 * Hide the edition form
 */
function closeEditFormulaire () {
  $('#dictionary').css('display', 'none')
  $('#addElement').css('display', '')
}

/**
 * Show the CKEditor files manager
 */
function showFileManger () {
  var urlContent = '../../main/inc/lib/elfinder/filemanager.php'
  var OpenWind = window.open(urlContent, 'FileManager', 'menubar=no, scrollbars=no, top=50, left=50, width=700, height=600')
  console.log(OpenWind.setUrl)
}

/**
 * Show the images selection filemanager of CKEditor
 * @type {string}
 */
var targetInputImg = ''

function showFileManagerSelectImg () {

  $('<div \>').dialog({
    modal: true, width: '80%', title: 'Select your file', zIndex: 99999,
    create: function (event, ui) {
      $(this).elfinder({
        resizable: false,
        url: '../../main/inc/lib/elfinder/connectorAction.php',
        commandsOptions: {
          getfile: {
            oncomplete: 'destroy'
          }
        }, getFileCallback: function (file) {
          $('#' + targetInputImg).val(file.url)
          var imgBlok = targetInputImg.replace('imagecard', 'imageBlockPreview')
          var imgPath = $('#' + targetInputImg).val()
          $('.' + imgBlok).css('background-image', 'url(' + imgPath + ')')
          jQuery('a.ui-dialog-titlebar-close[role="button"]').click()
          jQuery('button.ui-dialog-titlebar-close[role="button"]').click()
        }
      }).elfinder('instance')
    }
  })
}

/**
 * Get an element by Id
 * @param n
 * @returns {string|*}
 */
function getbyelem (n) {

    if (document.getElementById(n)) {

        var tagName = document.getElementById(n).tagName

        if (tagName == 'SELECT') {
            var get_id = document.getElementById(n)
            var resultselect = get_id.options[get_id.selectedIndex].value
            //console.log(resultselect);
            return resultselect
        }

        if (tagName == 'INPUT') {
            return document.getElementById(n).value
        }

        if (tagName == 'TEXTAREA') {
            var ct = document.getElementById(n).value
            ct = ct.replace('\n', '<br />')
            return ct
        }
    } else {

      return '-'
    }
}

/**
 * Check the given radio button
 * @param r
 */
function checkRadio (r) {
  $('#rad' + r).prop('checked', true)
  $('#dictionary_node_type').val(r)
}

/**
 * Show the interface for the words match H5P element type
 */
function interfacewordsmatch () {

  $('.dataTables_wrapper').css('display', 'none')
  $('#dictionary_node_type').parent().parent().css('display', 'none')
  $('#nodeselection').parent().parent().css('display', 'none')

  $('#dictionary_title').parent().parent().css('display', '')
  $('#dictionary_title').parent().append('<i>' + $('#h5p_title_help').html() + '</i>')

  $('#dictionary_submit').parent().parent().css('display', '')

  $('#dictionary_descript').parent().parent().css('display', '')
  $('#dictionary_descript').parent().append('<i>' + $('#h5p_descr_help').html() + '</i>')
  $('#dictionary_descript').val($('#h5p_wordsmatch_tutor').html())

  $('#dictionary').css('display', '')
  $('#addElement').css('display', 'none')
  $('#nodeselection').css('display', 'none')

  $('#dictionary_terms_a').parent().parent().css('display', '')
  $('#dictionary_terms_a').val($('#h5p_wordsmatch_load').html())
  $('#dictionary_terms_a').parent().append('<i>' + $('#h5p_wordsmatch_help').html() + '</i>')

  interfaceNameLabel('terms_a', $('#h5p_wordsmatch_term_a').html())

}

/**
 * Show the interface for the Name Label H5P element type
 */
function interfaceNameLabel (idjq, name) {

  $('label').each(function (index) {

    var forSrcAttr = $(this).attr('for')
    if (typeof forSrcAttr === 'undefined') {
      forSrcAttr = ''
    }
    if (forSrcAttr.indexOf('_' + idjq) != -1) {
      $(this).html(name)
    }

  })

}

/**
 * Show the interface for the Memory H5P element type
 */
function interfacememory () {

  interfacedialogcard()
}

/**
 * Show the interface for the Dialog Card H5P element type
 */
function interfacedialogcard () {

  $('.dataTables_wrapper').css('display', 'none')
  $('#dictionary_node_type').parent().parent().css('display', 'none')
  $('#nodeselection').parent().parent().css('display', 'none')

  $('#dictionary_title').parent().parent().css('display', '')
  $('#dictionary_title').parent().append('<i>' + $('#h5p_title_help').html() + '</i>')

  $('#dictionary_submit').parent().parent().css('display', '')

  $('#dictionary_descript').parent().parent().css('display', '')
  $('#dictionary_descript').parent().append('<i>' + $('#h5p_dialogcard_help').html() + '</i>')
  $('#dictionary_descript').val($('#h5p_dialogcard_tutor').html())

  $('#dictionary').css('display', '')
  $('#addElement').css('display', 'none')
  $('#nodeselection').css('display', 'none')

  $('#dictionary_terms_a').parent().parent().css('display', '')
  var dterma = $('#dictionary_terms_a').val()
  $('#dictionary_terms_a').parent().append(interfaceCard('a', dterma))
  interfaceNameLabel('terms_a', $('#h5p_dialogcard_term_a').html())

  $('#dictionary_terms_b').parent().parent().css('display', '')
  var dtermb = $('#dictionary_terms_b').val()
  $('#dictionary_terms_b').parent().append(interfaceCard('b', dtermb))
  interfaceNameLabel('terms_b', '')

  $('#dictionary_terms_c').parent().parent().css('display', '')
  var dtermc = $('#dictionary_terms_c').val()
  $('#dictionary_terms_c').parent().append(interfaceCard('c', dtermc))
  interfaceNameLabel('terms_c', '')

  $('#dictionary_terms_d').parent().parent().css('display', '')
  var dtermd = $('#dictionary_terms_d').val()
  $('#dictionary_terms_d').parent().append(interfaceCard('d', dtermd))

  interfaceNameLabel('terms_d', '')

  $('#dictionary_terms_e').parent().parent().css('display', '')
  var dterme = $('#dictionary_terms_e').val()
  $('#dictionary_terms_e').parent().append(interfaceCard('e', dterme))

  interfaceNameLabel('terms_e', '')

  $('#dictionary_terms_f').parent().parent().css('display', '')
  var dtermf = $('#dictionary_terms_f').val()
  $('#dictionary_terms_f').parent().append(interfaceCard('f', dtermf))

  interfaceNameLabel('terms_f', '')

  plusDialogCard()

  setTimeout(function () {
    interfaceEvents(dterma, 'a')
    interfaceEvents(dtermb, 'b')
    interfaceEvents(dtermc, 'c')
    interfaceEvents(dtermd, 'd')
    interfaceEvents(dterme, 'e')
    interfaceEvents(dtermf, 'f')
    displayInterfaceCardAll()
  }, 200)
  setTimeout(function () {
    compileInterfaceCardAll()
  }, 500)

}

/**
 * Add a dialog card
 */
function plusDialogCard () {

  var h = '<div class="divAddBlockDialog" >'
  h += '<a class="addBlockCard" onClick="plusDialogCardProcess();" /></a>'
  h += '</div>'
  $('#dictionary_terms_f').parent().append(h)

}

/**
 * Add a dialog card process
 */
function plusDialogCardProcess () {

  if (lastCardBlockId == 'a') {
    $('.cardBlockEditb').css('display', 'block')
    $('#outrecto' + 'b').val('?')
  }
  if (lastCardBlockId == 'b') {
    $('.cardBlockEditc').css('display', 'block')
    $('#outrecto' + 'c').val('?')
  }
  if (lastCardBlockId == 'c') {
    $('.cardBlockEditd').css('display', 'block')
    $('#outrecto' + 'd').val('?')
  }
  if (lastCardBlockId == 'd') {
    $('.cardBlockEdite').css('display', 'block')
    $('#outrecto' + 'e').val('?')
  }
  if (lastCardBlockId == 'e') {
    $('.cardBlockEditf').css('display', 'block')
    $('#outrecto' + 'f').val('?')
  }

  displayInterfaceCardAll()
}

/**
 * Show the interface for the Card H5P element type
 */
function interfaceCard (letterId, collTerms) {

  $('#dictionary_terms_' + letterId).css('display', 'none')

  $('#dictionary_terms_' + letterId).parent().parent().css('margin-bottom', '0px')

  var h = '<div class="dialogBlockEdit' + GlobalTypeNode + ' cardBlockEdit' + letterId + '" >'

  h += '<div class="lineBlockTxt" ><span class="labelBlockEdit"><br>Front&nbsp;:&nbsp;</span>'
  h += '<div id="recto' + letterId + '" class="pell arealeft" ></div></div>'

  if (GlobalTypeNode == 'memory') {
    h += '<div style="display:none;" class="lineBlockTxt" >'
  } else {
    h += '<div class="lineBlockTxt" >'
  }

  h += '<span class="labelBlockEdit" >Back&nbsp;:&nbsp;</span>'
  h += '<div id="verso' + letterId + '" class="pell arealeft" /></div>'

  h += '<input id="outrecto' + letterId + '" style="display:none;" type="text" />'
  h += '<input id="outverso' + letterId + '" style="display:none;" type="text" />'
  h += '<input id="imagecard' + letterId + '" style="display:none;" type="text" />'
  h += '<div class="imageBlockPreview imageBlockPreview' + letterId + '" onClick="targetInputImg=\'imagecard' + letterId + '\';showFileManagerSelectImg();" ></div>'

  if (letterId != 'a') {
    h += '<a onClick="deleteInterfaceCard(\'' + letterId + '\')" class="deleteBlockCard deleteBlockCard' + letterId + '" /></a>'
  }

  h += '</div>'

  return h

}

/**
 * Show the interface for the Events H5P element type
 */
function interfaceEvents (collTerms, let) {

  if (collTerms.indexOf('|') == -1 || collTerms == '') {
    collTerms = collTerms + '||'
  }

  var parts = collTerms.split('|')

  $('#imagecard' + let).val(parts[2])

  $('.imageBlockPreview' + let).css('background-image', 'url(' + parts[2] + ')')

  var editor1 = window.pell.init({
    element: document.getElementById('recto' + let),
    defaultParagraphSeparator: 'p',
    onChange: function (html) {
      $('#outrecto' + let).val(html)
    }
  })
  editor1.content.innerHTML = parts[0]
  $('#outrecto' + let).val(parts[0])

  var editor2 = window.pell.init({
    element: document.getElementById('verso' + let),
    defaultParagraphSeparator: 'p',
    onChange: function (html) {
      $('#outverso' + let).val(html)
    }
  })

  if (parts[1] == 'undefined') {
    parts[1] == ''
  }
  editor2.content.innerHTML = parts[1]
  $('#outverso' + let).val(parts[1])

}

/**
 * Prepare the interface for all 6 cards in a Card H5P element type
 */
function compileInterfaceCardAll () {
  compileInterfaceCard('a')
  compileInterfaceCard('b')
  compileInterfaceCard('c')
  compileInterfaceCard('d')
  compileInterfaceCard('e')
  compileInterfaceCard('f')
  setTimeout(function () {compileInterfaceCardAll()}, 300)
}

var lastCardBlockId = 'b'
/**
 * Display the interface for all 6 cards in a Card H5P element type
 */
function displayInterfaceCardAll () {

  displayInterfaceCard('b')
  displayInterfaceCard('c')
  displayInterfaceCard('d')
  displayInterfaceCard('e')
  displayInterfaceCard('f')

  $('.deleteBlockCard' + lastCardBlockId).css('display', 'block')

}

/**
 * Delete the interface of a Card H5P element type
 */
function deleteInterfaceCard (letterId) {

  $('.cardBlockEdit' + letterId).css('display', 'none')
  $('.deleteBlockCard' + letterId).css('display', 'none')
  $('#outrecto' + letterId).val('')
  displayInterfaceCardAll()

}
/**
 * Show the interface for the words match H5P element type
 */
function displayInterfaceCard (letterId) {

  var rectoRef = $('#outrecto' + letterId).val()

  if (rectoRef != '') {
    $('.cardBlockEdit' + letterId).css('display', 'block')
    $('.deleteBlockCard' + letterId).css('display', 'none')
    lastCardBlockId = letterId
  } else {
    $('.cardBlockEdit' + letterId).css('display', 'none')
  }
}

/**
 * Prepae the interface for the Card H5P element type
 */
function compileInterfaceCard (letterId) {

  var rectoRef = $('#outrecto' + letterId).val()
  var versoRef = $('#outverso' + letterId).val()
  var imagecardRef = $('#imagecard' + letterId).val()

  if (imagecardRef == 'img/' || imagecardRef == '' || imagecardRef == 'dialogcard.jpg') {
    imagecardRef = 'dialogcard.jpg'
    $('#imagecard' + letterId).val('dialogcard.jpg')
    $('.imageBlockPreview' + letterId).css('background-image', 'url(resources/img/' + imagecardRef + ')')
  }

  var fullStr = rectoRef + '|' + versoRef + '|' + imagecardRef
  $('#dictionary_terms_' + letterId).val(fullStr)

}

/**
 * Mark the cards depending on the answer
 */
function controlsFieldsdialogcard () {

  var b = true
  var rectoRef = $('#outrectoa').val()
  if (rectoRef == '') {
    b = false
    $('#rectoa').css('background', 'pink')
  } else {
    $('#rectoa').css('background', 'white')
  }

  var versoRef = $('#outversoa').val()
  if (versoRef == '') {
    b = false
    $('#versoa').css('background', 'pink')
  } else {
    $('#versoa').css('background', 'white')
  }
  return b
}

/**
 * Show the interface for the drag-the-words H5P element type
 */
function interfacedragthewords () {

  $('.dataTables_wrapper').css('display', 'none')
  $('#dictionary_node_type').parent().parent().css('display', 'none')
  $('#nodeselection').parent().parent().css('display', 'none')

  $('#dictionary_title').parent().parent().css('display', '')
  $('#dictionary_title').parent().append('<i>' + $('#h5p_title_help').html() + '</i>')

  $('#dictionary_submit').parent().parent().css('display', '')

  $('#dictionary_descript').parent().parent().css('display', '')
  $('#dictionary_descript').parent().append('<i>' + $('#h5p_descr_help').html() + '</i>')
  $('#dictionary_descript').val($('#h5p_dragthewords_tutor').html())

  $('#dictionary').css('display', '')
  $('#addElement').css('display', 'none')
  $('#nodeselection').css('display', 'none')

  $('#dictionary_terms_a').parent().parent().css('display', '')
  $('#dictionary_terms_a').val($('#h5p_dragthewords_load').html())
  $('#dictionary_terms_a').parent().append('<i>' + $('#h5p_dragthewords_help').html() + '</i>')

}

/**
 * Show the interface for the mark-the-words H5P element type
 */
function interfacemarkthewords () {

  $('.dataTables_wrapper').css('display', 'none')
  $('#dictionary_node_type').parent().parent().css('display', 'none')
  $('#nodeselection').parent().parent().css('display', 'none')

  $('#dictionary_title').parent().parent().css('display', '')
  $('#dictionary_title').parent().append('<i>' + $('#h5p_title_help').html() + '</i>')

  $('#dictionary_submit').parent().parent().css('display', '')

  $('#dictionary_descript').parent().parent().css('display', '')
  $('#dictionary_descript').parent().append('<i>' + $('#h5p_descr_help').html() + '</i>')
  $('#dictionary_descript').val($('#h5p_markthewords_tutor').html())

  $('#dictionary').css('display', '')
  $('#addElement').css('display', 'none')
  $('#nodeselection').css('display', 'none')

  $('#dictionary_terms_a').parent().parent().css('display', '')
  $('#dictionary_terms_a').val($('#h5p_markthewords_load').html())
  $('#dictionary_terms_a').parent().append('<i>' + $('#h5p_markthewords_help').html() + '</i>')

  interfaceNameLabel('terms_a', $('#h5p_markthewords_term_a').html())

}

/**
 * Show the interface for the guess-the-answer H5P element type
 */
function interfaceguesstheanswer () {

  $('.dataTables_wrapper').css('display', 'none')
  $('#dictionary_node_type').parent().parent().css('display', 'none')
  $('#nodeselection').parent().parent().css('display', 'none')

  $('#dictionary_title').parent().parent().css('display', '')
  $('#dictionary_title').parent().append('<i>' + $('#h5p_title_help').html() + '</i>')

  $('#dictionary_submit').parent().parent().css('display', '')

  $('#dictionary_descript').parent().parent().css('display', '')
  $('#dictionary_descript').parent().append('<i>' + $('#h5p_descr_help').html() + '</i>')
  $('#dictionary_descript').val($('#h5p_guesstheanswer_tutor').html())

  $('#dictionary').css('display', '')
  $('#addElement').css('display', 'none')
  $('#nodeselection').css('display', 'none')

  $('#dictionary_terms_a').parent().parent().css('display', '')
  $('#dictionary_terms_a').val($('#h5p_guesstheanswer_load').html())
  $('#dictionary_terms_a').parent().append('<i>' + $('#h5p_guesstheanswer_help').html() + '</i>')

  $('#dictionary_terms_b').parent().parent().css('display', '')
  var im = '<div class="imageBlockPreviewLarge imageBlockPreviewa" '
  im += 'onClick="targetInputImg=\'dictionary_terms_b\';showFileManagerSelectImg();" ></div>'
  $('#dictionary_terms_b').parent().append('<div>' + im + '</div>')

  $('#dictionary_terms_c').parent().parent().css('display', '')

  interfaceNameLabel('terms_a', $('#h5p_guesstheanswer_term_a').html())
  interfaceNameLabel('terms_b', $('#h5p_guesstheanswer_term_b').html())
  interfaceNameLabel('terms_c', $('#h5p_guesstheanswer_term_c').html())

  setTimeout(function () {
    overviewguesstheanswer()
  }, 500)

}
/**
 * Show an overview of the guess-the-answer H5P element type
 */
function overviewguesstheanswer () {

  var imagecardRef = $('#dictionary_terms_b').val()

  if (imagecardRef == 'img/' || imagecardRef == '' || imagecardRef == 'dialogcard.jpg') {
    imagecardRef = 'resources/img/dialogcard.jpg'
  }

  $('.imageBlockPreviewLarge').css('background-image', 'url(' + imagecardRef + ')')

  setTimeout(function () {
    overviewguesstheanswer()
  }, 500)

}
