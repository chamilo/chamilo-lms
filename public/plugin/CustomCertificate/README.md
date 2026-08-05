# Custom certificate

CustomCertificate lets administrators and teachers define an alternative certificate template for Chamilo courses.

The plugin does not replace the standard gradebook certificate workflow. It extends it: when the plugin is enabled and a course is configured to use custom certificates, certificate downloads can be redirected to the custom template managed by this plugin.

## What this plugin does

- Provides a default certificate template managed by platform administrators.
- Allows teachers or administrators to define a course-specific certificate template.
- Supports certificate content with Chamilo certificate tags such as user name, course title, grade and certificate date.
- Supports front content, optional back content, issue date options, signatures, logos, seal and background image.
- Allows printing/exporting certificates using the custom template when the course setting is enabled.

## Where to configure it

### Platform configuration

Enable the plugin from **Administration > Plugins**.

Then open the plugin from the plugins list to configure the default certificate template. The plugin configuration form intentionally does not include a second enable/disable switch; the active state is managed only from the plugins list.

### Course configuration

Inside a course, open the course settings and enable the CustomCertificate course options:

- **Custom certificate enable in course**
- **Use default custom certificate**, only if the course should use the platform default template instead of its own template

After enabling the course option, open the CustomCertificate course tool to edit the course-specific certificate template.

## Recommended workflow

1. Enable the plugin in the plugins list.
2. Configure a default certificate template as platform administrator.
3. Open the target course settings and choose one CustomCertificate option:
   - enable the course-specific custom certificate; or
   - use the platform default custom certificate.
4. Configure the course template when the course-specific option is enabled.
5. Generate a normal gradebook certificate for a learner.
6. Download/print the certificate and verify that the custom template is used.

## Template tags

The certificate editor supports the certificate tags displayed next to the editor, for example:

- `((user_firstname))`
- `((user_lastname))`
- `((course_title))`
- `((gradebook_grade))`
- `((date_certificate))`
- `((start_date))`
- `((end_date))`
- `((date_expediction))`

The available tags depend on the gradebook and course context.

## Permissions

- Platform administrators can configure the default certificate template.
- Teachers and administrators can configure custom certificates for courses where they have access.
- Students can only download their own certificates through the normal certificate workflow.

## Security notes

Certificate HTML is filtered before being stored or rendered. Uploaded images are validated as images and stored through Chamilo's plugin filesystem storage.

Avoid adding external JavaScript, third-party tracking code or remote images to certificate templates.

## Troubleshooting

If the custom certificate is not used:

1. Confirm that the plugin is enabled from the plugins list.
2. Confirm that the course settings page shows the **Custom certificate** panel.
3. Confirm that exactly one course option is active: **Custom certificate enable in course** or **Use default custom certificate**.
4. Confirm that the learner has a generated gradebook certificate.
5. Confirm that the course or default template has been saved successfully.

If images do not appear, verify that the image was uploaded through the plugin form and that the web server can write to `var/upload/plugins/CustomCertificate` on local installations.

## Notes

CustomCertificate is intended for certificate templates. It should not be used for generic page content, banners or footer blocks.


## Chamilo 2 storage note

CustomCertificate stores plugin-managed runtime files through Chamilo's plugin Flysystem storage.

On a default local installation, this maps to:

```text
var/upload/plugins/CustomCertificate/
```

Certificate images are stored under:

```text
var/upload/plugins/CustomCertificate/certificates/
```

The plugin does not store runtime files in `public/plugin/CustomCertificate/` because Chamilo 2 scans plugin directories while building the Symfony container. It also does not depend on legacy Chamilo 1.x course paths or removed upload path constants.

Images are displayed through a controlled plugin endpoint or embedded as data URIs when generating certificates.
