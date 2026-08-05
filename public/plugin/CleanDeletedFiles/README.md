CleanDeletedFiles maintenance plugin
===

This plugin helps a platform administrator remove physical files that are safe to clean from local Chamilo 2 storage.

Chamilo 2 compatibility notes
---

Chamilo 2 does not manage modern document files like Chamilo 1.11.x. Current files are managed through Symfony/API Platform entities and Vich/Flysystem storage:

- resource files: `var/upload/resource`, tracked by the `resource_file` table.
- asset files: `var/upload/assets`, tracked by the `asset` table.

Obsolete Chamilo 1.x paths such as `app/courses`, `app/upload`, `public/courses` and `public/upload` are not scanned by this version because they are not part of a normal Chamilo 2 storage layout.

Documents in the Vue/Symfony interface must be deleted through the normal Chamilo 2 Documents tool or API. This allows Doctrine, security voters, ResourceNode/ResourceLink handling and Vich/Flysystem cleanup to run.

This plugin does **not** delete:

- `CDocument` rows.
- `ResourceNode` rows.
- `ResourceLink` rows.
- `ResourceFile` rows.
- `Asset` rows.
- Files that are still referenced by `resource_file` or `asset`.
- Unknown files outside `var/upload/resource` and `var/upload/assets`.

What it can delete
---

The plugin lists and allows deleting only these physical files:

1. **Orphan resource files**
   - Located under `var/upload/resource`.
   - Not referenced by any `resource_file.title`.
   - Path resolution follows Chamilo 2's `resources` Vich mapping using `SubdirDirectoryNamer` with `chars_per_dir=1` and `dirs=3`.

2. **Orphan asset files**
   - Located under `var/upload/assets`.
   - Not referenced by any `asset.title` + `asset.category`.
   - Path resolution follows `Chamilo\CoreBundle\Component\VichUploader\AssetDirectoryNamer`.

Expected empty result
---

If the page shows **0 cleanable physical files**, that is usually a good result. It means the scanner did not find local files that are outside the `resource_file` or `asset` metadata tables.

To test the UI safely on a local development installation, create temporary orphan files and remove them afterwards:

```bash
mkdir -p var/upload/resource/t/e/s
echo "temporary test" > var/upload/resource/t/e/s/test-clean-deleted-files-orphan.txt

mkdir -p var/upload/assets/test-clean-deleted-files
echo "temporary test" > var/upload/assets/test-clean-deleted-files/test-clean-deleted-files-orphan.txt
```

Both files should appear as cleanable. Do not create test files on production unless you plan to remove them immediately.

How to use
---

1. Enable the plugin.
2. Add it to the `menu_administrator` region.
3. Open it from the administration page.
4. Click **Run limited scan**.
5. Review the scan result.
6. Select only files that are safe to permanently remove.
7. Click **Delete selected files**.

Recommended production workflow
---

Run a backup before deleting files permanently.

On a production site, first review the list without selecting anything. If a file does not appear, it is either still referenced by Chamilo metadata, outside the allowed local storage directories, or on a non-local Flysystem backend.

This plugin is intentionally not a generic "delete everything not visible in the UI" tool. Chamilo 2 may use resources and assets from multiple tools. When in doubt, delete content through the normal tool interface first.

Performance note
---

The administration page does not scan `var/upload` automatically. Large installations can contain many files, so the UI uses a manual limited scan to avoid long page loads.


## Delete flow note

The main admin page uses a regular CSRF-protected POST to delete selected orphan files. AJAX is not required for the normal cleanup flow.


Path filter behavior
---

For large installations, always use **Path filter** when validating a specific folder. If the filter matches an existing folder or file under `var/upload/resource` or `var/upload/assets`, the plugin scans that target directly before applying the scan limit.

Example filter:

```text
clean-deleted-files-test
```

This is safer than scanning thousands of files and helps verify deletion with test files.
