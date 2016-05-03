<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ _s.institution }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script type="application/ld+json">
        {
          "@context":       "http://schema.org",
          "@type":          "EmailMessage",
          "description":    "Chamilo Mail Notification",
          "potentialAction": {
            "@type": "ViewAction",
            "target":   "{{ link }}"
          }
        }
    </script>
</head>
<body style="margin: 0; padding: 0;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td>
                <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse;">
                    <tr>
                        <td>
                            {% include template ~ '/mail/header.tpl' %}
                        </td>
                    </tr>
                    <tr>
                        <td cellpadding="0" cellspacing="0" style="padding: 40px 10px">
                            {{ content }}
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
