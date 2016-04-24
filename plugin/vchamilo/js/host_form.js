/**
 * Pop-up testing connection with database.
 */
function opencnxpopup(webroot) {
    // Inputted data.
    var dbhost = document.getElementById('id_vdbhost').value;
    var dblogin = document.getElementById('id_vdbuser').value;
    var dbpass = document.getElementById('id_vdbpassword').value;

    // PHP file linked the pop-up, and name.
    var url = webroot+"/plugin/vchamilo/views/manage.testcnx.php" + "?" + "vdbhost=" + dbhost + "&" + "vdblogin=" + dblogin
            + "&" + "vdbpass=" + dbpass;
    // Pop-up's options.
    var options = "width=500,height=300,toolbar=no,menubar=no,location=no,scrollbars=no,status=no";

    // Opening the pop-up (title not working in Firefox).
    var windowobj = window.open(url, '', options);
}

/**
 * Pop-up testing connection with database.
 */
function opendatapathpopup(webroot) {

    // Input data.
    var datapath = document.getElementById('id_vdatapath').value;

    // PHP file linked the pop-up, and name.
    var url = webroot + "/plugin/vchamilo/views/manage.testdatapath.php?dataroot=" + escape(datapath);

    // Pop-up's options.
    var options = "width=500,height=300,toolbar=no,menubar=no,location=no,scrollbars=no,status=no";

    // Opening the pop-up (title not working in Firefox).
    var windowobj = window.open(url, '', options);
    // Needed to be valid in IE.
    // windowobj.document.title = vchamilo_testdatapath;
}

/**
 * Activates/desactivates services selection.
 */
function switcherServices(mnetnewsubnetwork) {

    // Retrieve 'select' elements from form.
    var mnetenabled = document.getElementById('id_mnetenabled');
    var multimnet = document.getElementById('id_multimnet');
    var services = document.getElementById('id_services');

    // Default values for services.
    var mnetfreedefault = '0';
    var defaultservices = 'default';
    var subnetworkservices = 'subnetwork';

    // Do the actions.
    if (multimnet.value == mnetfreedefault
            || multimnet.value == mnetnewsubnetwork) {
        services.value = defaultservices;
        services.disabled = true;
    } else {
        services.disabled = false;
        services.value = subnetworkservices;
    }
}

function syncSchema(){

    var originelement = document.getElementById("id_shortname");

    var syncedelement2 = document.getElementById("id_vdbname");
    var syncedelement3 = document.getElementById("id_vdatapath");
    var syncedelement4 = document.getElementById("id_vhostname");

    syncedelement2.value = syncedelement2.value.replace(/<%%INSTANCE%%>/g, originelement.value);
    syncedelement3.value = syncedelement3.value.replace(/<%%INSTANCE%%>/g, originelement.value);
    syncedelement4.value = syncedelement4.value.replace(/<%%INSTANCE%%>/g, originelement.value);
}

function onLoadInit(){
    var originelement = document.getElementById("id_shortname");
    originelement.onchange = syncSchema;
}
