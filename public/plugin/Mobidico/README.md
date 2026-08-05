# Mobidico for Chamilo 2

Mobidico is a course plugin that launches an external Mobidico session for the current Chamilo user.

## Purpose

The original plugin is not a glossary importer. It is a course-tool launcher:

1. Chamilo sends the current user ID and the configured API key to the external Mobidico server.
2. The Mobidico server returns a session token.
3. Chamilo opens the external Mobidico application with that token.

## Chamilo 2 adaptation

The plugin is installed as a course tool using the normal plugin properties:

```php
public $isCoursePlugin = true;
public $addCourseTool = true;
```

During install/configuration it calls:

```php
$this->install_course_fields_in_all_courses(true);
```

That creates the `c_tool` rows used by the Chamilo 2 course home.

## Important: no shortcuts

Mobidico must not create `c_shortcut` rows. Previous development builds did this and produced broken links like:

```text
/r/course_tool/links/{resourceNodeId}/link
```

Those links fail because a Mobidico course tool is not a `CLink` resource. This plugin version removes invalid shortcuts pointing to Mobidico course-tool resource nodes.

## Configuration

Required settings:

```text
mobidico_url
api_key
```

Optional settings:

```text
request_timeout
verify_ssl
```

Example:

```text
mobidico_url: https://mobidico.example.com
api_key: secret-key
request_timeout: 5
verify_ssl: true
```

## Testing with a local mock

Create a temporary mock only in local development:

```bash
cd /var/www/chamilo2

mkdir -p public/plugin/Mobidico/mock/app/desktop/php

cat > public/plugin/Mobidico/mock/app/desktop/php/authenticate.php <<'PHP'
<?php

header('Content-Type: application/json');

if (empty($_POST['chamiloid']) || empty($_POST['API_KEY'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Missing chamiloid or API_KEY',
    ]);
    exit;
}

echo json_encode([
    'status' => 'OK',
    'session' => 'mock-session-for-user-'.(int) $_POST['chamiloid'],
]);
PHP

mkdir -p public/plugin/Mobidico/mock/app

cat > public/plugin/Mobidico/mock/app/index.html <<'HTML'
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Mock Mobidico</title>
</head>
<body>
    <h1>Mock Mobidico opened</h1>
    <p id="session"></p>
    <script>
        const params = new URLSearchParams(window.location.search)
        document.getElementById('session').textContent = 'Session: ' + params.get('session')
    </script>
</body>
</html>
HTML
```

Configure:

```text
mobidico_url: https://chamilo2.local/plugin/Mobidico/mock
api_key: test
request_timeout: 5
verify_ssl: true
```

Remove the mock before committing:

```bash
rm -rf public/plugin/Mobidico/mock
```

## Validation SQL

```sql
SELECT s.id, s.title, s.shortcut_node_id, s.resource_node_id, ct.iid AS c_tool_iid, ct.title AS c_tool_title, t.title AS tool_title
FROM c_shortcut s
LEFT JOIN c_tool ct ON ct.resource_node_id = s.shortcut_node_id
LEFT JOIN tool t ON t.id = ct.tool_id
WHERE LOWER(TRIM(s.title)) LIKE 'mobidico%'
   OR LOWER(TRIM(ct.title)) LIKE 'mobidico%'
   OR LOWER(TRIM(t.title)) = 'mobidico';
```

Expected: no rows.

```sql
SELECT ct.iid, ct.c_id, ct.title, t.title AS tool_title, ct.resource_node_id
FROM c_tool ct
INNER JOIN tool t ON t.id = ct.tool_id
WHERE ct.title = 'Mobidico';
```

Expected: one row per course where the tool is installed.


## Course tool synchronization note

Mobidico is registered as a normal Chamilo 2 course tool through the legacy Plugin API. The synchronization intentionally rebuilds the Mobidico `c_tool` rows and does not create `c_shortcut` rows. This avoids stale course-tool rows without resource links and keeps the icon behavior aligned with Zoom, BBB and Positioning.
