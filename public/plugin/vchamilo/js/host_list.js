function selectallhosts() {
    $('.vnodessel').attr('checked', true);
}

function deselectallhosts() {
    $('.vnodessel').attr('checked', false);
}

function setpreset(form, select) {
    presetvalue = select.options[select.selectedindex].value;
    parts = presetvalue.split('/');

    form.elements['variable'].value = parts[0];
    form.elements['subkey'].value = parts[1];
}