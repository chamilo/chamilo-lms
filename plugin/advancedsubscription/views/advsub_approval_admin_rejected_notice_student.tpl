<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Solicitud recibida para el curso {{ session.title }}</title>
    <style type="text/css">
        .titulo {
            color: #93c5cd;
            font-family: "Times New Roman", Times, serif;
            font-size: 24px;
            font-weight: bold;
            border-bottom-width: 2px;
            border-bottom-style: solid;
            border-bottom-color: #93c5cd;
         }
    </style>
</head>

<body>
<table width="700" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td><img src="img/header.png" width="700" height="20" alt=""></td>
    </tr>
    <tr>
        <td><img src="img/line.png" width="700" height="25" alt=""></td>
    </tr>
    <tr>
        <td valign="top"><table width="700" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="50">&nbsp;</td>
                    <td width="394"><img src="img/logo-minedu.png" width="230" height="60" alt="Ministerio de Educación"></td>
                    <td width="50">&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td style="color: #93c5cd; font-family: Times New Roman, Times, serif; font-size: 24px; font-weight: bold; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: #93c5cd;">Rechazamos su inscripción al curso {{ session.title }} </td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td height="356">&nbsp;</td>
                    <td valign="top"><p>Estimado:</p>
                        <h2>{{ student.name }}</h2>
                        <p>Lamentamos informarle que su inscripción al curso <strong>{{ session.title }}</strong> iniciando el <strong>{{ session.start_date }}</strong> fue rechazada por falta de cupos. Esperamos mantenga todo su ánimo y participe en otro curso o, en otra oportunidad, a este mismo curso.</p>
                        <p>Gracias.</p>
                        <h3>{{ signature }}</h3></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td width="50">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td width="50">&nbsp;</td>
                </tr>
            </table></td>
    </tr>
    <tr>
        <td><img src="img/line.png" width="700" height="25" alt=""></td>
    </tr>
    <tr>
        <td><img src="img/footer.png" width="700" height="20" alt=""></td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
</table>
</body>
</html>
