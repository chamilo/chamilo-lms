# Security Policy

## Supported Versions
The following versions are currently being supported with security updates.

| Version | Supported          |
| ------- | ------------------ |
| 3.0.*   | :white_check_mark: |
| 2.0.*   | :x: |
| 1.11.44 | (to be released) |
| 1.11.42 | :white_check_mark: |
| 1.11.40 | :white_check_mark: |
| 1.11.38 | :white_check_mark: |
| 1.11.36 | :x: |
| 1.11.34 | :x: |
| 1.11.32 | :x: |
| <=1.11.30 | :x: |

## Threat model: who is trusted with what

Chamilo is a multi-role platform, so whether something is a vulnerability depends on the role of the
person who can trigger it. The default trust levels are:

| Role | Trust | Notes |
| ---- | ----- | ----- |
| Platform administrator | Full | Can do anything on the platform, including running code through plugins and themes. |
| Other administrators | Lower than administrators, higher than teachers | Can take care of some limited administrative tasks on the platform, like manage sessions, questions, or users (for HRMs). They are usually not meant to create content, though (in contrast with teachers) |
| Teacher / course manager (and session coach where they may edit) | Lower than administrators, higher than learners | Trusted to author course content and manage users in their courses. Not trusted to administer the platform or to reach data of courses they do not manage. By default, they can author and edit content, including HTML with CSS and JS. |
| Learner, invitee, anonymous visitor | Untrusted | Their input must always be filtered. |

### Teacher-authored HTML and JavaScript (by default)

Teachers are expected to publish rich content, so by default **a teacher can store HTML, including
JavaScript, in the content they author** (for example course descriptions, documents, portfolio and
wiki content). The relevant code passes the `COURSEMANAGERLOWSECURITY` status to
`Security::remove_XSS()`, which returns the content **unfiltered**. This is the intended default
behaviour. It is not a setting that an administrator has to turn on, and the content is stored and
served as submitted.

Consequence: script written by a teacher runs in the browser of every user who opens that content,
with that user's session. This includes administrators who browse the course. Install Chamilo in
contexts where you know who your teachers are, or reduce this exposure as described below.

A report that only shows that a teacher can store active HTML or JavaScript in their own course content
is therefore treated as **a hardening request, not as a vulnerability**. The following are still
vulnerabilities:

- the same result for a role that is not meant to author unfiltered HTML (learners, invitees, anonymous
  users, or a teacher editing a course they do not manage);
- script execution in a place that is not teacher content, or content that bypasses the access checks of
  the course;
- any filtering that is documented as being active but does not work.

### Reducing the exposure

There is no platform setting that switches off unfiltered teacher HTML. It is decided in the code, where
`COURSEMANAGERLOWSECURITY` is passed to `Security::remove_XSS()`. To filter teacher content through
HTMLPurifier, replace it with `COURSEMANAGER` (or pass no status, so that the role of the current user
selects the profile) in the tools you want to restrict. Expect the editor to lose some content, such as
scripts, event-handler attributes and some embeds.

The administration settings below limit what the editor offers, but they do not filter stored content:

- `editor.enable_iframe_inclusion` (Allow iframes in HTML Editor)
- `editor.enabled_insertHtml` (Allow insertion of widgets)
- `editor.enabled_support_svg` (SVG uploads)
- `editor.htmlpurifier_wiki` (HTMLPurifier in Wiki)

## Reporting a Vulnerability

Please follow the instructions at https://github.com/chamilo/chamilo-lms/wiki/security-issues to report vulnerabilities.
