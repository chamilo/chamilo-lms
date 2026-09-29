# Chamilo 2.0 — project security rules

Codebase-specific rules for the LLM diff reviewer. They supplement (do not replace) the built-in
web-vulnerability checks. Stack: Symfony 6.4 + API Platform 3/4 + Doctrine + Vue 3, plus legacy PHP
in `public/main/`.

## Authorization (API Platform & controllers)
- Role checks MUST use `$security->isGranted('ROLE_ADMIN')` (respects the role hierarchy). Flag any
  `$user->isAdmin()`, `$user->hasRole(...)`, or manual role-array inspection used for authorization.
- The current user MUST come from `UserHelper::getCurrent()`. Flag `$security->getUser()` in
  controllers/services — EXCEPT inside a `QueryCollectionExtensionInterface`, where
  `$this->security->getUser()` is the correct, established idiom.
- Course/session/group-scoped API operations authorize via contextual roles:
  `ROLE_CURRENT_COURSE_STUDENT|TEACHER`, `ROLE_CURRENT_COURSE_SESSION_STUDENT|TEACHER`,
  `ROLE_CURRENT_COURSE_GROUP_STUDENT|TEACHER`. Read/collection ops gate on the `_STUDENT` roles;
  write/Post ops on the `_TEACHER` roles. Item ops on `AbstractResource` entities should prefer
  object-level checks: `is_granted('VIEW'|'EDIT'|'DELETE', object.resourceNode)`.
- A Voter MUST NEVER call `$user->addRole('ROLE_CURRENT_COURSE_*')`. Those roles are published only by
  `CourseContextRoleListener`. Flag any Voter that mutates the user's roles (removed in #8486).
- `#[ApiResource]` `security:` expressions MUST NOT express per-row ownership as
  `"... and object.getUser() == user"` — that returns 403 (leaks row existence) and silently blocks
  admins. Ownership belongs in a Voter (item ops) + a `QueryCollectionExtension` WHERE (collections)
  + a Processor forcing the owner on create.
- `#[IsGranted]` on a migrated controller must match the legacy page's access checks. If the legacy
  page allowed admins AND session admins, use the `Expression` form, re-check the role inside each
  destructive action, and filter actionable entity IDs to only those the current non-admin manages.

## Injection & mass assignment
- No user input interpolated into DQL/SQL. Require bound parameters (`:name` + `setParameter()`).
  `orderBy()` sort fields MUST come from an allowlist map, never a raw request value.
- Client array params (e.g. `sessionIds[]`) MUST be cast, e.g. `array_map('intval', ...)`. A non-admin
  must not be able to pass IDs of entities they do not own/manage.
- On create (Post), force ownership server-side (`$data->setUser($currentUser)` in a Processor). Never
  trust an owner/user field coming from the client body.

## Serialization (mass-assignment defense)
- Owner/user relations → read-only serialization group (never settable by the client).
- Secrets (API keys, tokens, VAPID/private keys, `plainKey`) → write-only group so they are never
  echoed back in responses. Flag any secret exposed in a read group.

## CSRF
- POST/PUT/DELETE controllers doing destructive/sensitive actions (delete, copy, anonymize, restore,
  toggle) MUST validate `$this->isCsrfTokenValid('intent', $token)`. The token is generated with
  `CsrfTokenManagerInterface`, returned in the data JSON, and submitted as a hidden `_token` field from
  the Vue form.
- Legacy PHP (`public/main/`): `FormValidator` checks the CSRF token in `validate()` ONLY if
  `protect()` was called — a form without `protect()` has NO token check (trap). For raw `$_POST`/`$_GET`
  handlers without `validate()`, the idiom is a manual `Security::check_token()`.

## XSS
- User-supplied HTML is sanitized in the API OUTPUT Normalizer (server side), NOT on write. DOMPurify in
  Vue is an optional second barrier. Do NOT flag stored raw HTML as XSS when it is sanitized in the
  output Normalizer.
- Vue `{{ }}` auto-escapes. Flag `v-html` bound to user-supplied data. Dynamic `:href` must interpolate
  only integer IDs or known-safe strings.

## Open redirects
- Redirect URLs built from user/DB values (e.g. a username) MUST `urlencode()` those values before
  embedding them.
- Vue must not push user-controlled query-param values into `window.location.href` unsanitized.

## Course-context contract
- `CidReqListener` reads `cid`/`sid`/`gid` from query params and route attributes only — NOT from the
  request body. An endpoint that reads `cid` only from the JSON body is NOT gated; require a `cid` query
  param or validate in a StateProcessor.

## Known intentional patterns — do NOT flag as vulnerabilities
- `COURSEMANAGERLOWSECURITY` is intentional Chamilo design, not a vulnerability. Do not report it as an
  XSS or access-control issue.
- `api_protect_course_script()` validates COURSE CONTEXT, not authentication. It is compatible with
  `$use_anonymous` on OPEN courses — its presence alongside anonymous access is not an auth bypass.
- SSRF egress control is centralized via `NoPrivateNetworkHttpClient` (symfony/http-client). Code using
  it is the approved pattern; do not demand hand-rolled curl IP filters where it is already in place.