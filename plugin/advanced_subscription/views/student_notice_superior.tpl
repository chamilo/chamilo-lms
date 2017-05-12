<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ "MailTitle"| get_plugin_lang('AdvancedSubscriptionPlugin') | format(session.name) }}</title>
</head>

<body>
<table width="700" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td><img src="{{ _p.web_plugin }}advanced_subscription/views/img/header.png" width="700" height="20" alt=""></td>
    </tr>
    <tr>
        <td><img src="{{ _p.web_plugin }}advanced_subscription/views/img/line.png" width="700" height="25" alt=""></td>
    </tr>
    <tr>
        <td valign="top"><table width="700" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="50">&nbsp;</td>
                    <td width="394"><img src="{{ _p.web_css }}/themes/{{ "stylesheets"|api_get_setting }}/images/header-logo.png" width="230" height="60" alt="Ministerio de Educación"></td>
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
                    <td style="color: #93c5cd; font-family: Times New Roman, Times, serif; font-size: 24px; font-weight: bold; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: #93c5cd;">{{ "MailTitleStudentRequestToSuperior" | get_plugin_lang("AdvancedSubscriptionPlugin") }}</td>
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
                    <td valign="top"><p>{{ "MailDear" | get_plugin_lang("AdvancedSubscriptionPlugin") }}</p>
                        <h2>{{ superior.complete_name }}</h2>
                        <p>{{ "MailContentStudentRequestToSuperior" | get_plugin_lang("AdvancedSubscriptionPlugin") | format(student.complete_name, session.name, session.date_start, session.description) }}</p>
                        <p>{{ "MailContentStudentRequestToSuperiorSecond" | get_plugin_lang("AdvancedSubscriptionPlugin") }}</p>
                        <table width="100%" border="0" cellspacing="3" cellpadding="4" style="background:#EDE9EA">
                            <tr>
                                <td width="58" valign="middle"><img src="{{ student.picture.file }}" width="50" height="50" alt=""></td>
                                <td width="211" valign="middle"><h4>{{ student.complete_name }}</h4></td>
                                <td width="90" valign="middle"><a href="{{ student.acceptUrl }}&modal_size=lg" class="ajax"><img src="{{ _p.web_plugin }}advanced_subscription/views/img/aprobar.png" width="90" height="25" alt=""></a></td>
                                <td width="243" valign="middle"><a href="{{ student.rejectUrl }}&modal_size=lg" class="ajax"><img src="{{ _p.web_plugin }}advanced_subscription/views/img/desaprobar.png" width="90" height="25" alt=""></a></td>
                            </tr>
                        </table>
                        <p>{{ "MailThankYou" | get_plugin_lang("AdvancedSubscriptionPlugin") }}</p>
                        <p>{{ signature }}</p></td>
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
        <td><img src="{{ _p.web_plugin }}advanced_subscription/views/img/line.png" width="700" height="25" alt=""></td>
    </tr>
    <tr>
        <td><img src="{{ _p.web_plugin }}advanced_subscription/views/img/footer.png" width="700" height="20" alt=""></td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
</table>
</body>
</html>
