<?php
namespace IMSGlobal\LTI;

class Redirect {

    private $location;
    private $referer_query;
    private static $CAN_302_COOKIE = 'LTI1p3_302_Redirect';

    public function __construct($location, $referer_query = null) {
        $this->location = $location;
        $this->referer_query = $referer_query;
    }

    public function do_redirect() {
        header('Location: ' . $this->location, true, 302);
        die;
    }

    public function do_hybrid_redirect(Cookie $cookie = null) {
        if ($cookie == null) {
            $cookie = new Cookie();
        }
        if (!empty($cookie->get_cookie(self::$CAN_302_COOKIE))) {
            return $this->do_redirect();
        }
        $cookie->set_cookie(self::$CAN_302_COOKIE, "true");
        $this->do_js_redirect();
    }

    public function get_redirect_url() {
        return $this->location;
    }

    public function do_js_redirect() {
        ?>
        <a id="try-again" target="_blank">If you are not automatically redirected, click here to continue</a>
        <script>

        document.getElementById('try-again').href=<?php
        if (empty($this->referer_query)) {
            echo 'window.location.href';
         } else {
            echo "window.location.origin + window.location.pathname + '?" . $this->referer_query . "'";
        }
        ?>;

        var canAccessCookies = function() {
            if (!navigator.cookieEnabled) {
                // We don't have access
                return false;
            }
            // Firefox returns true even if we don't actually have access
            try {
                if (!document.cookie || document.cookie == "" || document.cookie.indexOf('<?php echo self::$CAN_302_COOKIE; ?>') === -1) {
                    return false;
                }
            } catch (e) {
                return false;
            }
            return true;
        };

        if (canAccessCookies()) {
            // We have access, continue with redirect
            window.location = '<?php echo $this->location ?>';
        } else {
            // We don't have access, reopen flow in a new window.
            var opened = window.open(document.getElementById('try-again').href, '_blank');
            if (opened) {
                document.getElementById('try-again').innerText = "New window opened, click to reopen";
            } else {
                document.getElementById('try-again').innerText = "Popup blocked, click to open in a new window";
            }
        }

        </script>
        <?php
    }

}

?>