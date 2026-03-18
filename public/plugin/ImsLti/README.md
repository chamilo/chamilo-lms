# IMS/LTI Client Plugin

Version 2.x

This plugin adds **IMS/LTI client** capabilities to Chamilo 2.

It allows Chamilo to register and launch **external LTI tools** from other platforms, providers, or services. In this setup, **Chamilo acts as the LTI platform/client**, while the remote service acts as the **LTI tool/provider**.

Depending on the tool configuration and supported protocol version, the plugin can be used to integrate external learning activities into Chamilo courses.

---

## What this plugin does

Once installed and enabled, the plugin allows administrators to:

1. Create and manage external LTI tools
2. Configure platform keys for LTI 1.3 flows
3. Register launch endpoints and client credentials for remote tools
4. Add an external tool to one or more courses
5. Launch external activities from Chamilo course home
6. Reuse a global external tool across multiple courses through course shortcuts

This means Chamilo can consume external LTI tools and expose them inside course pages as direct course access items.

---

## Installation and activation

In Chamilo 2, the plugin lifecycle is managed from the **Plugins** administration page.

### Typical steps

1. Go to **Administration > Configuration settings > Plugins**
2. Install the plugin
3. Enable the plugin
4. Open the plugin administration page
5. Configure platform keys if needed
6. Create one or more external tools
7. Add each external tool to one or more courses

> No manual database table creation is required.
> No manual SQL upgrade instructions are needed in the README for normal Chamilo 2 usage.

---

## Current administration flow

The plugin administration page provides actions such as:

- **Platform keys**
- **Add external tool**
- list of registered external tools
- **Add in courses**
- **Edit**
- **Delete**

### Platform keys

This section is used for the platform-side key material required by LTI 1.3 integrations.

These keys identify Chamilo as the client/platform when communicating with external LTI 1.3 tools.

### Add external tool

This form is used to register a remote LTI tool in Chamilo.

An external tool can later be added to one or more courses.

### Add in courses

This action allows assigning an existing external tool to one or more courses.

In the current Chamilo 2 model, a global external tool can be reused across multiple courses without duplicating the base tool definition.

---

## Typical external tool data

When creating or editing an external tool, the exact fields may vary depending on the selected LTI version and enabled features.

Common values include:

### Name
Administrative and visible name of the tool inside Chamilo.

Example:

```text
Demo LTI Provider
```

### Launch URL
Main launch endpoint of the remote tool/provider.

Example:

```text
<REMOTE_TOOL_LAUNCH_URL>
```

For example:

```text
https://provider.example.com/lti/launch
```

### Client ID
Client identifier assigned to Chamilo by the remote LTI 1.3 provider.

Example:

```text
<REMOTE_TOOL_CLIENT_ID>
```

### Login URL
OIDC login initiation endpoint of the remote provider.

Example:

```text
<REMOTE_TOOL_LOGIN_URL>
```

### Redirect URL
Redirect or callback URL used in the LTI authentication flow.

Example:

```text
<CHAMILO_REDIRECT_URL>
```

### Public key / platform keys
Key material used for LTI 1.3 signed exchanges.

This is configured through the plugin platform key management flow.

### Version
Protocol version used by the external tool.

Typical examples may include legacy LTI versions or LTI 1.3, depending on the tool and current implementation.

### Privacy / launch parameters / presentation settings
Some tools may expose additional launch behavior options such as:

- user data sharing level
- custom launch parameters
- iframe or new window presentation
- deep linking behavior
- grading or outcome-related options

The exact available options depend on the implementation and version of the tool.

---

## Example external tool registration

The following example uses placeholders instead of real values.

| Field | Example value |
|---|---|
| Name | `Demo LTI Provider` |
| Launch URL | `<REMOTE_TOOL_LAUNCH_URL>` |
| Client ID | `<REMOTE_TOOL_CLIENT_ID>` |
| Login URL | `<REMOTE_TOOL_LOGIN_URL>` |
| Redirect URL | `<CHAMILO_REDIRECT_URL>` |
| Version | `LTI 1.3` |

Example:

```text
Name:         Demo LTI Provider
Launch URL:   https://provider.example.com/lti/launch
Client ID:    client-id-generated-by-provider
Login URL:    https://provider.example.com/lti/login
Redirect URL: https://your-chamilo.example.com/plugin/ImsLti/redirect.php
Version:      LTI 1.3
```

> Replace these values with the real values supplied by the external provider.

---

## Course assignment model

In the current Chamilo 2 implementation, an external tool can be defined once and then assigned to multiple courses.

This means:

- the base external tool definition remains global,
- course visibility is handled per course,
- and adding or removing the tool from a course affects the course shortcut/access entry rather than duplicating the original tool definition.

This makes administration cleaner and avoids maintaining duplicate tool records for each course.

---

## Typical usage flow

A normal setup flow looks like this:

1. Install and enable the plugin from the **Plugins** page
2. Open the IMS/LTI administration page
3. Configure platform keys if the tool uses LTI 1.3
4. Create a new external tool
5. Save the tool
6. Use **Add in courses** to assign it to one or more courses
7. Open the course and launch the external tool from the course home or corresponding shortcut

---

## Notes about launch behavior

Depending on the tool configuration, an external tool may be launched:

- embedded in an iframe
- or in a new window/tab

Some external platforms require specific browser or server behavior for cookies and cross-site authentication.

If an LTI launch fails in iframe mode, verify the hosting environment and browser restrictions related to secure cookies and cross-site requests.

---

## Notes

- This plugin is used to consume **external LTI tools from Chamilo**
- Chamilo acts as the **platform/client**
- The remote service acts as the **tool/provider**
- Available options depend on the selected LTI version and current implementation
- Some advanced behaviors such as deep linking, grading, launch presentation, or replacements depend on the tool definition and platform compatibility

---

## Summary

This plugin turns Chamilo 2 into an **IMS/LTI client/platform** for external tools.

It allows administrators to:

- register external LTI tools,
- configure LTI 1.3 platform keys,
- assign tools to one or more courses,
- and make those tools available from Chamilo course pages.
