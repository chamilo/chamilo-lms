
/*BASIC INTERFACE OPEN ELEARNING*/

let WORKINGOBJ = Tobj;

var getObj = undefined;

if(window){
    if(window.frameElement){
        if(window.frameElement.getAttribute){
            getObj = window.frameElement.getAttribute("data");
        }
    }
}

var Tobj = new Object();

if(typeof getObj ==='undefined'){

    Tobj.id = 0;
    Tobj.idFab = 0;
    Tobj.unikid = 0;
    Tobj.idString = 0;
    Tobj.type = 'text';
    Tobj.subtype = 'text';
    Tobj.text = '';
    Tobj.text2 = '';
    Tobj.text3 = '';
    Tobj.text4 = '';
    Tobj.text5 = '';
    Tobj.text6 = '';
    Tobj.val = '';
    Tobj.val2 = '';
    Tobj.val3 = '';
    Tobj.val4 = '';
    Tobj.val5 = '';
    Tobj.val6 = '';

}else{

    var getObjD = getObj.split("@");

    Tobj.id = getObjD[0];
    Tobj.idFab = getObjD[1];
    Tobj.unikid = getObjD[2];
    Tobj.idString = getObjD[3];
    Tobj.type = getObjD[4];
    Tobj.subtype = getObjD[5];
    Tobj.text = getObjD[6];
    Tobj.text2 = getObjD[7];
    Tobj.text3 = getObjD[8];
    Tobj.text4 = getObjD[9];
    Tobj.text5 = getObjD[10];
    Tobj.text6 = getObjD[11];
    Tobj.val = getObjD[12];
    Tobj.val2 = getObjD[13];
    Tobj.val3 = getObjD[14];
    Tobj.val4 = getObjD[15];
    Tobj.val5 = getObjD[16];
    Tobj.val6 = getObjD[17];

}

WORKINGOBJ = Tobj;

$('body').append('<textarea id="finalcode" style="display:none;" ></textarea>');

function objetSendToString(){
    
    var Tobj = WORKINGOBJ;
    var str = "";
	str += Tobj.id + '@';
	str += Tobj.idFab + '@';
	str += Tobj.unikid + '@';
    str += Tobj.idString + '@';
    str += Tobj.type + '@';
	str += Tobj.subtype + '@';
    str += Tobj.text + '@';
    str += Tobj.text2 + '@';
    str += Tobj.text3 + '@';
    str += Tobj.text4 + '@';
    str += Tobj.text5 + '@';
    str += Tobj.text6 + '@';
    str += Tobj.val + '@';
    str += Tobj.val2 + '@';
    str += Tobj.val3 + '@';
    str += Tobj.val4 + '@';
    str += Tobj.val5 + '@';
    str += Tobj.val6 + '@';
    $('#finalcode').text(str);

}