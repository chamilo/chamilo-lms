<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ "MailTitle"| get_plugin_lang('AdvancedSubscriptionPlugin') | format(session.title) }}</title>
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
                    <td width="394"><td width="394"><img src="{{ theme_asset('images/header-logo.png') }}" width="230" height="60" alt="Ministerio de Educación"></td></td>
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
                    <td style="color: #93c5cd; font-family: Times New Roman, Times, serif; font-size: 24px; font-weight: bold; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: #93c5cd;">{{ "MailTitleReminderAdmin" | get_plugin_lang("AdvancedSubscriptionPlugin") | format(session.title)}}</td>
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
                        <h2>{{ admin.complete_name }}</h2>
                        <p>{{ "MailContentReminderAdmin" | get_plugin_lang("AdvancedSubscriptionPlugin") | format(session.title, admin_view_url)}}</p>
                        <p>{{ "MailThankYou" | get_plugin_lang("AdvancedSubscriptionPlugin") }}</p>
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
