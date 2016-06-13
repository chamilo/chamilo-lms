<h1>Skype Plugin</h1>

<p>This pluging was integrated within Chamilo LMS core</p>

<p>To configure this plugin you need execute this SQL queries:</p>

<ul>
<li>Enable the Skype extra field:</li>
</ul>

<pre><code>INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible, changeable, created_at)
VALUES
(1, 1, 'skype', 'Skype', 1, 1, now());
</code></pre>

<ul>
<li>Enable the LinkedInURl extra field:</li>
</ul>

<pre><code>INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible, changeable, created_at)
VALUES
(1, 1, 'linkedin_url', 'LinkedInUrl', 1, 1, now());
</code></pre>

<ul>
<li>Enable the configuration settings:</li>
</ul>

<pre><code>INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, access_url_changeable)
VALUES
('allow_show_skype_account', NULL, 'radio', 'Platform', 'true', 'AllowShowSkypeAccountTitle', 'AllowShowSkypeAccountComment', 1),
('allow_show_linkedin_url', NULL, 'radio', 'Platform', 'true', 'AllowShowLinkedInUrlTitle', 'AllowShowLinkedInUrlComment', 1);
</code></pre>

<pre><code>INSERT INTO settings_options (variable, value, display_text)
VALUES
('allow_show_skype_account', 'true', 'Yes'),
('allow_show_skype_account', 'false', 'No'),
('allow_show_linkedin_url', 'true', 'Yes'),
('allow_show_linkedin_url', 'false', 'No');
</code></pre>
