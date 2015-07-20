<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>{{ _s.institution }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    </head>
    <body style="margin: 0; padding: 0;">

    <table align="center" width="100%" style="background-color:#F8F8F8; font-family:Helvetica Neue,Helvetica,Arial,sans-serif;">
        <tr>
            <td>

                <table border="0" align="center" cellpadding="20" cellspacing="0" width="600px" style="background-color:#ffffff;margin-top:25px;margin-bottom:25px;border-radius:10px; border:1px solid #ddd">
                    <tr>
                        <td>
                            {% include template ~ '/mail/header.tpl' %}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="padding:20px; text-align: justify; color:#666666">
                                {{ content }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            {% include template ~ '/mail/footer.tpl' %}
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
    </body>
</html>
