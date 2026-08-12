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
- Extract every commit SHA already present in that version's `<li>` entries
  **across every category in the section**, not just the first one. Category
  `<h3>` blocks are each sorted newest-first independently, so the top entry of
  whichever section happens to appear first in the file (usually "Security
  fixes") is **not necessarily** the most-recently-added commit overall — a
  later section (e.g. "Changed") can easily have a newer top entry. Picking
  the wrong "most recent" boundary silently re-processes commits that are
  already listed elsewhere in the section.
- Run `git log --pretty=format:'%H' HEAD` and skip every SHA that appears (by
  its 8-char or 12-char prefix) in the existing section.
- The range to process is everything since the newest already-listed commit.
  Concretely: find the most recent commit in the section, then use
  `git log --pretty=... <that-SHA>..HEAD` as the range.
- If the section exists but has no `<li>` entries yet, fall back to the
  previous-release tag as the start point (see below).
- **Don't trust a single computed "boundary commit" blindly.** Before writing
  anything, take the final candidate list of commits you intend to add and
  grep each one's SHA directly against the *entire* existing version section
  (all categories, not just the one you think is the boundary). Any hit means
  that commit is already listed and must be dropped from the new batch — this
  simple per-commit membership check is cheap and catches boundary-detection
  mistakes before they turn into duplicate `<li>` entries.
- **If the user hands you a pre-curated commit list directly** (e.g. a file
  with already-formatted `<li>` lines they generated themselves), treat it as
  the authoritative "what to add" source, but still: (a) run the per-commit
  membership check above — a curated list can still overlap with what's
  already in the file; (b) de-duplicate exact-repeated messages inside the
  list itself (different SHAs with identical text happen after rebases or
  duplicate merges — keep only the first occurrence); (c) still apply the
  full Step 3 / Step 5 cleanup below, since a pre-generated list is normally
  raw `gitlog.php` output and needs the same trimming.

**If the section does not yet exist:**
- Identify the most recent existing release tag on this branch via
  `git tag --merged HEAD --sort=-version:refname | grep -E '^v[0-9]'`.
- The most recent tag is the start of the range: `git log v2.0.X..HEAD`.
- If the tag name is ambiguous (e.g. `v2.0.2` vs `2.0.2`), ask the user to
  confirm before running the log command.

### ⚠️ RTK / token-proxy hooks can silently corrupt `git log` output

If this environment has an `rtk` (or similar token-savings) hook rewriting
`git` commands transparently, **do not trust its output for changelog work**.
It has been observed to both truncate long lines (e.g. cutting a subject at
~80 chars and appending `...`, even when output is redirected to a file, not
just displayed) **and** to silently drop commits from the result set — in one
session, `git log <sha>..HEAD | wc -l` returned `50` through the hook and `168`
through an unfiltered call for the exact same range. A truncated message is
merely annoying (it gets caught during cleanup); a silently dropped commit
means a real change never makes it into the changelog at all, with nothing to
signal the omission.

**Mitigation:** for every `git log` call used to build the commit range or
inspect messages, use `rtk proxy git log ...` (or whatever the local
token-proxy's raw/debug passthrough is — check the tool's own docs, e.g. a
`CLAUDE.md`/`RTK.md` reference) instead of the plain `git log ...` the hook
would intercept. If you're unsure whether such a hook is active, run the same
`git log` count both ways once at the start of the session and compare —
mismatched counts confirm the hook is interfering.

---

## Step 3: Apply the gitlog.php filtering rules

Read `tests/scripts/packaging/gitlog.php` to confirm the rules are unchanged
before processing. Then apply them in this order to each commit's subject line:

> **Prefer running the real script over hand-simulating it.** `gitlog.php`
> requires a `php-git/src/Git.php` dependency in the same directory
> (`tests/scripts/packaging/php-git/`) — it may be present in the working tree
> even if untracked by git (check with `ls`). When it's available, run it
> directly instead of manually replaying steps 3a-3e in your head across dozens
> of commits — that manual replay is error-prone (mis-tracking which known
> mistake applies, mis-computing issue-reference stripping, etc.):
> ```
> cd tests/scripts/packaging && php gitlog.php <boundary-commit-sha>
> ```
> This stops at `<boundary-commit-sha>` (printing it too — drop that last line
> since it's already in the changelog) and echoes real `<li>` HTML using the
> exact same logic this skill documents, eliminating transcription errors.
>
> **Caveat:** the script's output can still include full commit-body text
> appended after the subject (e.g. `See advisory GHSA-xxxx-xxxx-xxxx`, trailing
> `Author: ... <email>` trailers, or multi-sentence rationale paragraphs). This
> is not part of the clean subject line — strip it during Step 5 cleanup the
> same as a typo.

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

> **Known false positive: `REST` / `CI` and other short prefixes.**
> `sanitizeCategory()` matches these terms as a plain string prefix
> (`strncasecmp`), with no word-boundary check. `REST` (4 chars) matches the
> first 4 letters of any word starting "Rest…", so a commit titled `Restore
> the language switch...` gets mangled into `Webservice: RESTore the language
> switch...`. This has already happened repeatedly and gone uncorrected in the
> existing changelog (search for `RESTore` — multiple pre-existing entries).
> When you hit this on a **new** commit you're adding, fix it for that entry
> (strip the bogus `Webservice: REST` prefix, restore the plain subject) and
> note the correction in the Step 8 report — but don't retroactively edit old,
> already-published entries with the same bug (surgical changes only; flag it
> to the user instead as a possible upstream fix to `sanitizeCategory()`, e.g.
> requiring a word boundary or capitalization check after the matched term).
> The same risk applies to any other short 2-4 letter term in the table above
> (e.g. `CI`, `Cas`, `LP`) — double check any prefix match shorter than ~5
> characters against what it actually matched before trusting it.

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

### 4a. "Add" verb fallback (Changed → Added only)

A commit whose own prefix doesn't match Security fixes / Fixed / Added / Removed
(so it would otherwise land in the **Changed** catch-all) should be reclassified
into **Added** if the first word after the prefix is `add` or `Add`. To find that
word: strip the first `Category:` prefix, then keep stripping any further
`Subcategory:` prefixes (e.g. `Plugin: BuyCourses: Add country info in sales
detail` — strip `Plugin:`, then `BuyCourses:`, leaving `Add country info...`).
If the resulting first word is `add`/`Add`, use Added instead of Changed.

This fallback only ever moves a commit out of **Changed** — it never overrides
Security fixes, Fixed, or Removed, even when their message also starts with
"Add" right after the colon:

- `Security: Add native FIM feature` → stays **Security fixes**
- `Language: Add missing translation for Back to account` → stays **Fixed**
  (`Language` already matches the Fixed prefix list)
- `Learnpath: Add support for post-unload event Chrome...` → **Changed → Added**
- `User: Add invitation to course/session...` → **Changed → Added**
- `Plugin: CStudio: Add AI-assisted content generation` → **Changed → Added**
  (nested prefix stripped: `Plugin:` then `CStudio:`)

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

**Additional cleanup learned in practice:**
- If the raw message ends with body-only content that leaked into the subject
  (advisory IDs like `See advisory GHSA-xxxx-xxxx-xxxx`, `Author: Name
  <email>` trailers, or multi-sentence rationale paragraphs), strip all of it
  — keep only the clean subject clause. This is common when running the real
  `gitlog.php` script (see Step 3 note) against squash-merged commits whose
  body carries PR metadata.
- If an issue number appears in the message text in parentheses (e.g.
  `...reorder course tools (#8868)`) **and** a separate BT#/GH# link is also
  being added per this step, drop the redundant `(#NNNN)` from the visible
  text — showing the same issue number twice (once inline, once as a link) is
  noise. `gitlog.php`'s own ref-stripping regexes don't catch this case (they
  only strip text following specific separator patterns like ` - refs `, not
  a bare parenthetical), so this needs a manual pass.

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
