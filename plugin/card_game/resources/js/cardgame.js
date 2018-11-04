/* For license terms, see /license.txt */
/**
 * Whenever the cardgame.js file is included into the loaded JavaScript,
 * search for the user picture in the left menu and add a div
 * "enjoy-cardgame" to it to show a little drawing cards icon to enable
 * this plugin
 */
$(document).ready(function ($) {

  if (!document.getElementById('havedeckcardgame')) {
    $('.sidebar .panel-body .img-circle').parent().parent().after('<div id="plugin-cardgame-icon" class="enjoy-cardgame" onClick="onlyOpenCardView();" ></div>')
  } else {
    $('.sidebar .panel-body .img-circle').parent().parent().after('<div id="plugin-cardgame-icon" class="enjoy-cardgame-active" onClick="installCardView()" ></div>')
  }
})

/**
 * This function inserts the <article> element 'home-card' for the card game
 * before the .page-content element on the page (the right side of the
 * "my courses" page) only if the element with id 'home-card' does not exist
 * yet.
 */
function installCardView () {
  if (!document.getElementById('home-card')) {
    var mess1 = $('#cardgamemessage').html()
    var h = '<article id="home-card" style="border:solid 1px gray;" >'
    h += '<div class="cardgame-pack" >'
    var panClass = 'pimg0' + $('#pancardgame').html()

    // Setup the 3x5 table of images
    h += '<div class="puzzlecardone ' + panClass + '" >'
    h += '<div id="puzzlepart1"  class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart2"  class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart3"  class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart4"  class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart5"  class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart6"  class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart7"  class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart8"  class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart9"  class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart10" class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart11" class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart12" class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart13" class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart14" class="puzzlepart1" ></div>'
    h += '<div id="puzzlepart15" class="puzzlepart1" ></div>'
    h += '</div>'

    h += '<div id="puzzleMinOther1" class="puzzleMinOther" ></div>'
    h += '<div id="puzzleMinOther2" class="puzzleMinOther" ></div>'
    h += '<div id="puzzleMinOther3" class="puzzleMinOther" ></div>'
    h += '<div id="puzzleMinOther4" class="puzzleMinOther" ></div>'

    h += '<div class="card-one" onclick="cardOpenCardView();" ></div>'
    h += '<div class="linescissors" ></div>'

    h += '<div id="viewDeckBottom" class="viewDeckBottom" onClick="minimizePuzzle()" ></div>'
    h += '<div id="messagePackDeck" class="messagePackDeck" >' + mess1 + '</div>'

    h += '<div id="scissors" class="scissorsrightinit" onclick="animationOpenCardView();" ></div>'
    h += '</div>'
    h += '</article>'

    $('.page-content').before(h)

  } else {
    $('#home-card').fadeToggle()
  }

}

/**
 * Animate the scissors
 */
function animationOpenCardView () {

  $('#scissors').addClass('scissorsrightinitfinal')

  setTimeout(function () {
    $('#scissors').fadeOut()
    $('.linescissors').fadeOut()
    $('#messagePackDeck').css('display', 'none')
    $('#messagePackDeck').removeClass('messagePackDeck')

  }, 2600)

  setTimeout(function () {
    $('.cardgame-pack').addClass('cardgame-open')
    $('.card-one').addClass('card-one-2')
  }, 3000)

}

function cardOpenCardView () {

  $('.card-one').removeClass('card-one-2')

  setTimeout(function () {
    $('.puzzlecardone').fadeIn()
    randomOpenCardView()
  }, 700)

}

function randomOpenCardView () {

  var part = Math.floor(Math.random() * (15 - 1 + 1)) + 1

  $('.puzzlecardone').css('display', 'block')
  $('#scissors').fadeOut()
  $('.linescissors').fadeOut()
  $('#messagePackDeck').css('display', 'none')
  $('#messagePackDeck').removeClass('messagePackDeck')

  var memocardgame = $('#memocardgame').html()
  var parts = memocardgame
  var arrayOfStrings = parts.split(';')

  var findDouble = false
  for (var i = 0; i < arrayOfStrings.length; i++) {
    var idpart = arrayOfStrings[i]
    idpart = idpart.replace('!!', '')
    idpart = idpart.replace('!', '')
    idpart = idpart.replace('!', '')
    idpart = idpart.replace(';', '')
    $('#puzzlepart' + idpart).css('opacity', 0)

    if (part == idpart) {
      findDouble = true
    }

  }
  if (findDouble) {
    part = Math.floor(Math.random() * (15 - 1 + 1)) + 1
  }
  if (memocardgame.indexOf('!' + part + ';') != -1) {
    part = Math.floor(Math.random() * (15 - 1 + 1)) + 1
  }
  if (memocardgame.indexOf('!' + part + ';') != -1) {
    part = Math.floor(Math.random() * (15 - 1 + 1)) + 1
  }

  if (memocardgame.indexOf('!' + part + ';') != -1) {
    var mess2 = $('#cardgameloose').html()
    $('#messagePackDeck').html(mess2)
    $('#messagePackDeck').css('display', 'block')
    $('#messagePackDeck').css('backgroundColor', 'red')
    $('#messagePackDeck').addClass('messagePackDeckBottom')
    var lk = $('#linkcardgame').html()
    $.ajax({ url: lk + '?loose=1' }).done(function () { })

  } else {
    var lk = $('#linkcardgame').html()
    $.ajax({ url: lk + '?part=' + part }).done(function () { })

    setTimeout(function () {
      $('#puzzlepart' + part).addClass('puzzlepartstar')
    }, 600)

    setTimeout(function () {
      $('#puzzlepart' + part).css('opacity', 0)
    }, 1200)

    setTimeout(function () {
      var mess2 = $('#cardgameengage').html()
      $('#messagePackDeck').html(mess2)
      $('#messagePackDeck').css('display', 'block')
      $('#messagePackDeck').addClass('messagePackDeckBottom')
      $('#viewDeckBottom').css('display', 'block')
    }, 1500)

  }
  $('#plugin-cardgame-icon').removeClass('enjoy-cardgame-active')
  $('#plugin-cardgame-icon').addClass('enjoy-cardgame')
}

/**
 * This function adds the cardgame area block (in invisible state), then
 * changes the visibility of blocks inside the puzzle area to show them
 */
function onlyOpenCardView () {

  installCardView();

  var memocardgame = $('#memocardgame').html()

  $('.puzzlecardone').css('display', 'block')
  $('#scissors').css('display', 'none')
  $('.linescissors').css('display', 'none')
  $('#messagePackDeck').css('display', 'none')
  $('#messagePackDeck').removeClass('messagePackDeck')

  var lk = $('#linkcardgame').html()
  var parts = memocardgame
  // alert(parts);
  var arrayOfStrings = parts.split(';')

  for (var i = 0; i < arrayOfStrings.length; i++) {
    var idpart = arrayOfStrings[i]
    idpart = idpart.replace('!!', '')
    idpart = idpart.replace('!', '')
    idpart = idpart.replace('!', '')
    idpart = idpart.replace(';', '')
    $('#puzzlepart' + idpart).css('opacity', 0)
  }

  var mess2 = $('#cardgameengage').html()
  $('#messagePackDeck').html(mess2)
  $('#messagePackDeck').css('display', 'block')
  $('#messagePackDeck').addClass('messagePackDeckBottom')
  $('#viewDeckBottom').css('display', 'block')
}

function minimizePuzzle () {

  $('.puzzlecardone').addClass('puzzleMin')
  setTimeout(function () {

    $('.puzzleMinOther').css('display', 'block')

    var panNumber = parseInt($('#pancardgame').html())

    if (panNumber > 1) {
      $('#puzzleMinOther1').addClass('pimg01')
    }
    if (panNumber > 2) {
      $('#puzzleMinOther2').addClass('pimg02')
    }
    if (panNumber > 3) {
      $('#puzzleMinOther3').addClass('pimg03')
    }
    if (panNumber > 4) {
      $('#puzzleMinOther4').addClass('pimg04')
    }
  }, 1000)
}

