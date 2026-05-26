<?php

declare(strict_types=1);

$formUpload = '';

if ('scorm' == $typeUpload) {
    $formUpload .= '<h2>Scorm</h2>';
}
if ('document' == $typeUpload) {
    $formUpload .= '<h2>Document</h2>';
}

$formUpload .= '<div id="run" class="bigUpload">
    <div class="bigUploadContainer">';
if ('scorm' == $typeUpload) {
    $formUpload .= '<span>S&eacute;lectionner le fichier ZIP sur votre ordinateur
        <br/>
        et appuyer sur <span style="color:blue;" >Upload</span>.
        <br/>
        </span>';
}
if ('document' == $typeUpload) {
    $formUpload .= '<span>S&eacute;lectionner le fichier sur votre ordinateur
        <br/>
        et appuyer sur <span style="color:blue;" >Upload</span>.
        <br/>
        </span>';
}
$formUpload .= '<form action="inc/bigUpload.php?action=post-unsupported" method="post" enctype="multipart/form-data" id="bigUploadForm">
            <input type="file" id="bigUploadFile" name="bigUploadFile" />
            <button class=" btn btn-primary " name="button" type="button" onclick="upload();" id="bigUploadSubmit" ><em class="fa fa-upload"></em> Upload</button>
            <input type="button" class="bigUploadButton bigUploadAbort" style="display:none;" value="Annuler" onclick="abort();" />
            <input id="scormid" name="scormid" type="hidden" value="testsco" />
        </form>
        <div id="bigUploadProgressBarContainer">
            <div id="bigUploadProgressBarFilled">
            </div>
        </div>
        <div id="bigUploadTimeRemaining"></div>
        <div id="bigUploadResponse"></div>
        <div id="finalNameSrc"></div>
    </div>
    <iframe height="25px"  scrolling="no" style="border:none;" id="kp" src="inc/keep_alive.php" style="width:100%;height:30px;" ></iframe>
</div>
<div id="see" style="display:none;" >
<img style="margin:15px;" src="css/load745.gif" />';

$formUpload .= '</div>';

$formUpload .= '<script src="js/big_upload.js"></script>
<script src="js/init.js"></script>
<link href="css/upload.css" rel="stylesheet" type="text/css">';
