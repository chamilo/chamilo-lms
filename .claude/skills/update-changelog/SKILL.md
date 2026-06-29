---
name: update-changelog
description: >
  Update public/documentation/changelog.html with commits for a new Chamilo release.
  Applies the filtering and formatting rules from tests/scripts/packaging/gitlog.php,
  classifies commits into changelog categories, and reports a line-count summary per
  category. Use when the user wants to update the changelog, prepare a release entry,
  add a version section, or runs /update-changelog.
---

# Update Changelog

Update `public/documentation/changelog.html` with the commits for a new Chamilo
release. The changelog is **progressive**: a version section may be created and
updated multiple times before the release tag is actually set. Handle both cases.

---

## Step 1: Determine the target version

Ask the user: **"Which version number are we adding or updating? (e.g. 2.0.4)"**

Once you have the version, check whether a section for it already exists in
`public/documentation/changelog.html` by looking for `<a id="X.Y.Z">`.

---

## Step 2: Determine the commit range

The range is **"commits not yet listed in the changelog for this version"**. How to
find it depends on whether the section already exists:

**If the section already exists (progressive update):**
- Extract every commit SHA already present in that version's `<li>` entries.
- Run `git log --pretty=format:'%H' HEAD` and skip every SHA that appears (by
  its 8-char or 12-char prefix) in the existing section.
- The range to process is everything since the newest already-listed commit.
  Concretely: find the most recent commit in the section, then use
  `git log --pretty=... <that-SHA>..HEAD` as the range.
- If the section exists but has no `<li>` entries yet, fall back to the
  previous-release tag as the start point (see below).

**If the section does not yet exist:**
- Identify the most recent existing release tag on this branch via
  `git tag --merged HEAD --sort=-version:refname | grep -E '^v[0-9]'`.
- The most recent tag is the start of the range: `git log v2.0.X..HEAD`.
- If the tag name is ambiguous (e.g. `v2.0.2` vs `2.0.2`), ask the user to
  confirm before running the log command.

---

## Step 3: Apply the gitlog.php filtering rules

Read `tests/scripts/packaging/gitlog.php` to confirm the rules are unchanged
before processing. Then apply them in this order to each commit's subject line:

### 3a. Hard-skip entire commits whose subject starts with any of:
- `Update language terms`
- `Update language vars`
- `Update lang vars`
- `Merge` (also lowercase `merge`)
- `Scrutinizer Auto-Fixes`
- `Update changelog`
- `Fix PHP Warning`

Also skip any commit whose subject starts with `Minor` (case-insensitive,
first 5 characters — matches `Minor`, `MINOR`, `minor:`, etc.).

> **Note:** `gitlog.php` defines a `$skipTechnicalPrefixes` array (QA, Internal,
> Display, Fix, Refactor, Migration, UI, …) but **never uses it** — it is dead
> code. Do NOT filter on those prefixes; the existing changelog includes
> `Internal:`, `Display:`, and `Fix:` entries.

### 3b. Normalise separator punctuation (applied in this exact order):
1. `^(\w+) - (.*)` → `$1: $2`
2. `^(\w+ \w+) - (.*)` → `$1: $2`
3. `^(\w+) : (.*)` → `$1: $2`

### 3c. Rename known prefix mistakes via sanitizeCategory():
Apply the full substitution table below (case-insensitive prefix match,
replace prefix only, keep the rest of the message):

| Wrong prefix | Correct prefix |
|---|---|
| Quiz / Exercises | Exercise |
| LP / Learning Paths / LearningPath / Learnpaths | Learnpath |
| Documents | Document |
| Announcements | Announcement |
| RemedialCourse | Plugin: RemedialCourse |
| Groups / [usergroup] | Group |
| Survey report / Survey list export | Survey |
| Learnpath report | Learnpath |
| TopLink / TopLinks | Plugin: TopLinks |
| Sessions | Session |
| Cas | Authentication: CAS |
| Webservices / WebService / Web services | Webservice |
| BBB | Plugin: BigBlueButton |
| My Progress / My Progres / Reports / Reporting | Tracking |
| Courses | Display |
| [LP] | Learnpath |
| Student follow page | Tracking: Student follow-up |
| REST | Webservice: REST |
| Import CSV / ImportCSV / Import_csv.php | Admin: CSV import |
| [Minor] | Minor: |
| [admin] | Admin |
| MySpace | Tracking |
| Career diagram / Careers | Career |
| Users | User |
| Style: | Display: |
| Course Announcement | Announcement |
| Testing / CI | QA |
| Blogs | Blog |
| Gradebook eval | Gradebook |
| Survey test | QA: Survey |
| Editor | WYSIWYG |
| Global | Internal |
| Extra field | Extra Fields |
| Settings | Admin |
| Changelog | Documentation |
| Session import | Admin: Session import |
| XAPI | xAPI |
| CourseCopy / Course Copy | Maintenance |
| Course Backup | Maintenance |
| SSO | Authentication: Single Sign On |
| Skills | Skill |
| Messages | Message |
| Security fixes - | Security: |
| Work / Works / Pending works | Assignment |
| Improve code | Internal: Improve code |
| Thematic / Thematic advance | Course Progress |
| Agenda | Calendar |
| Course import | Maintenance |
| Student publication / Student publications | Assignment |

### 3d. Strip issue references from the message text:
If the subject matches `((BT)?#\d{2,5})`, remove the first match and anything
after these patterns: ` see ISSUE`, ` - ref`, ` -refs `, ` - refs `, ` ISSUE`.

### 3e. Finalise:
Apply `ucfirst()` (capitalise the first character of the message).

---

## Step 4: Classify commits into categories

Use the **normalised message prefix** (the part before the first `:`) to assign
each commit to a category. Apply in priority order — stop at the first match:

| Category | Message prefix matches (case-insensitive) |
|---|---|
| **Security fixes** | `Security` |
| **Fixed** | `Fix`, `Bug`, `Install`, `Language`, `Hotfix` |
| **Added** | `Add`, `New`, `Enable`, `Feature`, `Implement`, `Create`, `Include`, `Introduce` |
| **Removed** | `Remove`, `Delete`, `Drop`, `Deprecate` |
| **Changed** | Everything else (Internal, Display, Refactor, Plugin, Auth, Learnpath, Exercise, …) |

Omit a category section entirely if it has zero entries.

---

## Step 5: Build each `<li>` line

Format (newest-first, matching existing changelog style):

```html
<li>[YYYY-MM-DD] (<a href="https://github.com/chamilo/chamilo-lms/commit/SHA12">SHA8</a>) Message</li>
```

When a BT# or GH# issue link exists, add it between the commit link and the
closing `)`:

```html
<li>[YYYY-MM-DD] (<a href="https://github.com/chamilo/chamilo-lms/commit/SHA12">SHA8</a> - <a href="https://task.beeznest.com/issues/NUM">BT#NUM</a>) Message</li>
```

Where `SHA12` = first 12 chars of the full SHA, `SHA8` = first 8 chars.

Correct any obvious typos in commit messages when formatting for publication
(the HTML is user-facing; the git history is not changed). Note corrections in
the final report.

---

## Step 6: Ask for the release codename

Ask: **"What codename should I use for version X.Y.Z? (Leave blank to insert a placeholder)"**

- If provided, use it: `Chamilo X.Y.Z - Codename, YYYY-MM-DD`
- If blank, use: `Chamilo X.Y.Z - [TBD], YYYY-MM-DD`
- Use today's date as the release date unless the user specifies another.
- Historical context: codenames have been small cities/towns/villages in the
  Somerset/Cheddar region of England (Cadbury, Blackford, Little Weston, Axbridge).

---

## Step 7: Write or update the changelog

**If the section does not yet exist:** insert it above the previous release's
`<a id="...">` anchor. Also add an entry to the `<div class="toc">` `<ul>` at
the top, above the previous release's TOC line.

**If the section already exists (progressive update):** append the new `<li>`
entries into the correct category `<ul>`. If a commit's category `<h3>` block
does not exist yet in that section, create it in the correct order:
Security fixes → Added → Changed → Fixed → Removed → Known issues.

The release summary `<p>` should be written or updated to reflect the nature of
the commits in this update. If the section is new, write a brief paragraph. If
updating an existing section, revise the summary if the new commits materially
change its character.

---

## Step 8: Report

Print a summary table:

```
Changelog updated for Chamilo X.Y.Z - Codename

Category         | Lines added
-----------------|------------
Security fixes   |  N
Added            |  N
Changed          |  N
Fixed            |  N
Removed          |  N
Total            |  N

Filtered out (not included):
  - M merge commits
  - K minor/noise commits

Typo corrections applied:
  - <SHA8>: "<original>" → "<corrected>"  (or "none")
```

---

## Guidelines

- Always read `tests/scripts/packaging/gitlog.php` at the start to check for
  rule changes before applying the filter logic above.
- The changelog is the user-facing record. Prefer clarity over verbatim
  faithfulness to commit messages.
- Never invent commits. Only include SHAs that appear in the git log output.
- Do not modify any section other than the target version.
- If the user provides a specific date, use that; otherwise use today's date.
