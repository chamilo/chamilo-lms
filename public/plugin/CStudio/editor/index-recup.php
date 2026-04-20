<!doctype html>
<html lang="en" >
<head>

<script src="jscss/jquery.js?v=<?php echo $version; ?>"></script>
<link href="jscss/oel-teachdoc.css?v=<?php echo $version; ?>" rel="stylesheet" />
<link href="templates/styles/classic-ux.css?v=<?php echo $version; ?>" rel="stylesheet"/>

</head>
<body style="background-color:#D8D8D8;text-align:center;" >
<br/><br/>
<h2 style="color:red;" >Failed to load content into the editor !</h2>
<br/>
<div style="width:750px;margin-left:auto;margin-right:auto;" id="allHistoryTable" ></div>

<?php

$idPage = -1;

if (isset($_GET['id'])) {
    $idPage = (int) $_GET['id'];
}

?>

    <script>

        $.ajax({
            url : 'history_cache/get-history.php?idteach=' + <?php echo $idPage; ?>,
            type: "POST",
            dataType : 'json',
            success: function(data,textStatus,jqXHR){
                optionsGlobalHistory = data;
                if(optionsGlobalHistory.history.length>0){
                    installTableHistory();
                    $('#allHistoryArea').css("display","");
                }else{
                    $('#allHistoryArea').css("display","none");
                }
            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                $('#allHistoryArea').css("display","none");
            }
        });

        function installTableHistory(){
    
            var nbline = 1;

            var tableH = "<table class='historyTable noselect' >";
            tableH += "<thead><tr>";
            tableH += "<th>Date</th>";
            tableH += "<th>Action</th>";
            tableH += "</tr>";
            tableH += "</thead>";
            tableH += "<tbody>";
            $.each(optionsGlobalHistory.history,function(){
                
                tableH += "<tr>";
                tableH += "<td style='text-align:center;' >" + nameFromHistory(this.data) + "</td>";
                tableH += "<td style='text-align:center;' >";
                tableH += "<a href='index.php?id=<?php echo $idPage; ?>&loadh=" + this.data.replace('.html','') + "' ";
                tableH += " style='cursor:pointer;' >Restore this content</a></td>";
                tableH += "</tr>";

            });
            tableH += "</tbody>";
            tableH += "</table>";

            $('#allHistoryTable').html(tableH);

        }

        
        function nameFromHistory(f) {
            
            f = f.replace(".html","");
            f = f + '-0-0-0-0';
            var getObjD = f.split("-");

            var year = parseInt(getObjD[0]);
            var month = parseInt(getObjD[1]);
            var day = parseInt(getObjD[2]);
            var hour = parseInt(getObjD[3]);
            var period = "AM";

            if (hour>12) {
                period = "PM";
                hour = hour - 12;
            }

            var  months = ["December" ,"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            return day + '&nbsp;' + months[month] + '&nbsp;' + year + '&nbsp;&nbsp;<small>(' + hour + ':00' + period + ")</small>";

        }


    </script>

</body>
</html>
