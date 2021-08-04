setTimeout(function () {

  var mapData = {
    'document': [{
      'id': '937205e4-c2b0-ead3-2db4-66719b3612e2',
      'parent': null,
      'children': null,
      'content': 'Chamilo',
      'offset': {'x': 360, 'y': 394}
    }], 'lastModified': 1592229869766
  }
  if (dataMMLoad != '') {
    mapData = dataMMLoad
  }
  var view = document.getElementById('map-container')
  view = kampfer.mindMap.window
  var command = new kampfer.mindMap.command.CreateNewMap(mapData, view)
  command.execute()

  var ht = '<li id="mindmapmenu" style="width:128px;height:128px;" >'
  ht += '<img alt="Save" style="width:128px;height:128px;cursor:pointer;" onClick="saveMapProcess(true);" src="img/mindmap128.png" /></li>'
  $('#main-menu').html(ht)

}, 100)

var onlyOneUpdate = true

setTimeout(function () {
  saveMapProcess(false)
}, 6000)

function saveMapProcess (redir) {

  if (onlyOneUpdate == false) {return false}

  if (redir) {
    var ht = '<img alt="Save" style="width:128px;height:128px;cursor:pointer;" src="img/mindmap128gray.png" />'
    $('#mindmapmenu').html(ht)
  }

  var map = kampfer.mindMap.mapManager.getMapData()
  var mapString = JSON.stringify(map)

  var formData = {datamap: mapString}

  $.ajax({
    url: '../ajax/mindmap.ajax.php?id=' + idMM,
    type: 'POST', data: formData,
    success: function (data, textStatus, jqXHR) {

      onlyOneUpdate = true

      if (data.indexOf('KO') == -1) {

        if (redir) {
          window.location.href = '../list.php?cid=' + MMGetParamValue('cid') + '&sid=' + MMGetParamValue('sid')
        }

      } else {
        alert('Error !')
      }

    },
    error: function (jqXHR, textStatus, errorThrown) {

      alert('Error : ' + textStatus)
      onlyOneUpdate = true
    }

  })
}

function MMGetParamValue (param) {

  var u = document.location.href
  var reg = new RegExp('(\\?|&|^)' + param + '=(.*?)(&|$)')
  matches = u.match(reg)

  if (matches == null) {return ''}

  var vari = matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g, ' ') : ''

  for (var i = 100; i > -1; i--) {
    vari = vari.replace('#page' + i, '')
  }
  return vari

}
