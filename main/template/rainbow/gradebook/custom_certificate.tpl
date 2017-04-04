<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>{{ 'Certificate' | get_lang }}</title>
</head>
<body style="margin:0; padding:0; background-color: #92c647;">
<table border="0" bgcolor="#92c647" cellpadding="0" cellspacing="0" align="center">
    <tr>
        <td>&nbsp;
        </td>
    </tr>
    <tr>
        <td>&nbsp;
        </td>
    </tr>
    <tr>
        <td bgcolor="#92c647"><img src="{{ _p.web_css_theme }}images/header_top.png" style="display: block;"></td>
    </tr>
    <tr>
        <td>
            <table border="0" cellspacing="0" cellpadding="0" width="100%">
                <tr>
                    <td bgcolor="#92c647" width=58 height=91>
                        <img src="{{ _p.web_css_theme }}images/lado-b.png" style="display:block;">
                    </td>
                    <td bgcolor="#92c647" width=700 height=91 style="font-family:ccourier; line-height: 38px; color:#FFF; font-size: 35px;">
                        {{ 'CertificateHeader' | get_lang }}
                    </td>
                    <td bgcolor="#92c647" width=58 height=91>
                        <img src="{{ _p.web_css_theme }}images/lado-header.png" style="display:block;">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table bgcolor="#FFFFFF" border="0" cellspacing="0" cellpadding="0" width="100%" height=900>
                <tr>
                    <td bgcolor="#92c647" height=755><img src="{{ _p.web_css_theme }}images/lado-a.png" style="display:block;"></td>
                    <td height=755 style="font-family: ccourier; line-height: 22px; color:#92c647; padding: 40px; font-size: 18px;" valign="top">
                        <h3 style="color: #672290; font-size: 24px;">
                            {{ complete_name }}
                        </h3>
                        <p style="font-size: 14px;">
                            {{ 'UserHasParticipateDansDePlatformeXTheContratDateXCertificateDateXTimeX' | get_lang | format(_s.site_name, certificate_generated_date_no_time, terms_validation_date_no_time, time_in_platform_in_hours)}}
                        </p>
                        <br />
                        <p style="font-size: 14px;">{{ 'ThisTrainingHasXHours' | get_lang | format(time_in_platform_in_hours)}}</p><br />
                        <p style="font-size: 14px;">{{ 'TheContentsAreValidated' | get_lang }}:</p>
                            {% if sessions %}
                                <ul style="color: #672290; font-size: 14px;">
                                    {% for session in sessions %}
                                        <li>  {{ session.session_name }}</li>
                                    {% endfor %}
                                </ul>
                            {% endif %}<br />
                        <h4 style="color: #672290; font-size: 14px;">{{ complete_name }}</h4>
                        <p style="color:#92c647; font-size: 14px;">{{ 'SkillsValidated' | get_lang }}:</p>
                            {% if skills %}
                                <ul style="color: #672290; font-size: 14px;">
                                {% for skill in skills %}
                                    <li>{{ skill.name }}</li>
                                {% endfor %}
                                </ul>
                            {% endif %}
                            <br />
                        <p style="color:#92c647; font-size: 14px;">Berlin/Paris, {{ 'The' | get_lang }} <span style="font-weight: bold; color: #672290;">{{ certificate_generated_date_no_time }}</span><br />
                            {{ 'ThePlatformTeam' | get_lang }}</p>
                        <br />
                    </td>
                    <td height=755 bgcolor="#92c647">
                        <img src="{{ _p.web_css_theme }}images/lado-b.png" style="display:block;">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table border="0" cellspacing="0" cellpadding="0" width="100%" height=91>
                <tr>
                    <td bgcolor="#92c647" width=58 height=91><img src="{{ _p.web_css_theme }}images/lado-b.png"  style="display:block;"></td>
                    <td bgcolor="#92c647" width=500 height=91 style="font-family: ccourier; line-height: 18px; color:#FFF;">
                        {{ 'CertificateFooter' | get_lang }}
                    </td>
                    <td bgcolor="#92c647" width=245 height=91><img src="{{ _p.web_css_theme }}images/lado-footer.png" style="display:block;"></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

