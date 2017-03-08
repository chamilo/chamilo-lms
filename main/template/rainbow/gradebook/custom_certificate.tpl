<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>{{ 'Certificate' | get_lang }}</title>
</head>
<body style="margin:0; padding:0;">
<table border="0" cellpadding="0" cellspacing="0" style="width:726px;" align="center">
    <tr>
        <td>
            &nbsp;
        </td>
    </tr>
    <tr>
        <td>
            &nbsp;
        </td>
    </tr>
    <tr>
	<td><img src="{{ _p.web_css_theme }}images/header_top.png" style="display: block;"></td>
    </tr>
    <tr>
        <td>
            <table border="0" cellspacing="0" cellpadding="0" width="100%">
                <tr>
                    <td bgcolor="#80CC28" width=58 height=91>
                        <img src="{{ _p.web_css_theme }}images/lado-b.png" style="display:block;">
                    </td>
                    <td bgcolor="#80CC28" width=610 height=91 style="font-family: Courier; line-height: 26px; color:#FFF; font-size: 30px;">
                        {{ 'CertificateHeader' | get_lang }}
                    </td>
                    <td bgcolor="#80CC28" width=58 height=91>
                        <img src="{{ _p.web_css_theme }}images/lado-header.png" style="display:block;">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
	<tr>
            <td>
                <table border="0" cellspacing="0" cellpadding="0" width="100%" height=900>
                    <tr>
			<td bgcolor="#80CC28" height=700><img src="{{ _p.web_css_theme }}images/lado-a.png" style="display:block;"></td>
			<td height=700 style="font-family: Courier; line-height: 26px; color:#80CC28; padding: 40px;" valign="top">
                            <h3 style="color: #672290;">
                                {{ complete_name }}
                            </h3>
                                <p>{{ 'UserHasParticipateDansDePlatformeXTheContratDateXCertificateDateXTimeX' | get_lang | format(_s.site_name, certificate_generated_date, terms_validation_date, time_in_platform)}}</p>
                                <p>{{ 'TheContentsAreValidated' | get_lang }}:</p>
                                    {% if sessions %}
                                        <ul style="color: #672290;">
                                            {% for session in sessions %}
                                                <li>  {{ session.session_name }}</li>
                                            {% endfor %}
                                        </ul>
                                    {% endif %}
                                <h4 style="color: #672290;">Erika Mustermann</h4>
                                <p>{{ 'SkillsValidatedOfUserX' | get_lang | format(complete_name) }}:</p>
                                    {% if skills %}
                                        <ul style="color: #672290;">
                                        {% for skill in skills %}
                                            <li>{{ skill.name }}</li>
                                        {% endfor %}
                                        </ul>
                                    {% endif %}
                                Berlin/Paris, den <span style="font-weight: bold; color: #672290;">21.12.2016</span><br>
                                Das Team von PARKUR
                                <br>
                            </td>
			<td height=700 bgcolor="#80CC28"><img src="{{ _p.web_css_theme }}images/lado-b.png" style="display:block;"></td>
                    </tr>
            	</table>
            </td>
	</tr>
    <tr>
        <td>
            <table border="0" cellspacing="0" cellpadding="0" width="100%" height=91>
                <tr>
                    <td bgcolor="#80CC28" width=58 height=91><img src="{{ _p.web_css_theme }}images/lado-b.png"  style="display:block;"></td>
                    <td bgcolor="#80CC28" width=400 height=91 style="font-family: Courier; line-height: 15px; color:#FFF;">
                        {{ 'CertificateFooter' | get_lang }}
                    </td>
                    <td bgcolor="#80CC28" width=245 height=91><img src="{{ _p.web_css_theme }}images/lado-footer.png" style="display:block;"></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>









