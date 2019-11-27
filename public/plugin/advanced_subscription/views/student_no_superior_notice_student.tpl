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
                    <td style="color: #93c5cd; font-family: Times New Roman, Times, serif; font-size: 24px; font-weight: bold; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: #93c5cd;">{{ "MailTitleStudentRequestNoSuperiorToStudent"| get_plugin_lang("AdvancedSubscriptionPlugin") | format(session.name) }}</td>
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
                        <h2>{{ student.complete_name }}</h2>
                        <p>{{ "MailContentStudentRequestNoSuperiorToStudent"| get_plugin_lang("AdvancedSubscriptionPlugin") | format(session.name, session.date_start) }}</p>
                        <p>{{ "MailContentStudentRequestNoSuperiorToStudentSecond"| get_plugin_lang("AdvancedSubscriptionPlugin") }}</p>
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
