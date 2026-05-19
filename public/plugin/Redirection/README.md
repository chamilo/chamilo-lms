# Redirection plugin

Redirects specific users to an administrator-defined URL immediately after login.

## Chamilo 2 behavior

Chamilo 2 authenticates through `/login_json` and returns a JSON response that may include a `redirect` key. The plugin updates that login JSON response instead of using `header('Location: ...')`, because the frontend otherwise continues with the default navigation, usually `/course`.

## Usage

1. Enable the plugin in Administration > Plugins.
2. Open the plugin administration page.
3. Select a user.
4. Add an internal path such as `/search/ui` or an absolute `http://` / `https://` URL.
5. Log in with that user.

If the user must complete mandatory workflows such as 2FA, terms and conditions, or password renewal, the plugin does not override those redirects.

## Notes

The EventSubscriber is intentionally not namespaced because the current Chamilo plugin compiler pass registers plugin subscribers by their file basename, matching existing working plugins.
