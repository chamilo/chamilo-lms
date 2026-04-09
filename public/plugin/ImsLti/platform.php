<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\LtiBundle\Entity\Platform;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

$pluginEntity = Container::getPluginRepository()->findOneByTitle('ImsLti');
$currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
$pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isPluginEnabled = $pluginEntity
    && $pluginEntity->isInstalled()
    && $pluginConfiguration
    && $pluginConfiguration->isActive();

if (!$isPluginEnabled) {
    api_not_allowed(true);
}

/** @var Platform|null $platform */
$platform = $plugin->ensurePlatformKeys();

$kid = $platform ? htmlspecialchars((string) $platform->getKid(), ENT_QUOTES) : '';
$publicKey = $platform ? htmlspecialchars((string) $platform->publicKey, ENT_QUOTES) : '';
$privateKey = $platform ? htmlspecialchars((string) $platform->getPrivateKey(), ENT_QUOTES) : '';

$backUrl = api_get_path(WEB_PLUGIN_PATH).'ImsLti/admin.php';
$pageTitle = $plugin->get_lang('PlatformKeys');
$pageDescription = 'Review the generated key pair used by the IMS/LTI client plugin platform configuration.';

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('PlatformAdmin'),
];
$interbreadcrumb[] = [
    'url' => $backUrl,
    'name' => $plugin->get_title(),
];

$template = new Template($pageTitle);

$content = '
<div>
    <div class="mx-auto w-full max-w-7xl px-4 py-8 lg:px-6">
        <a
            href="'.$backUrl.'"
            class="mb-6 inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary"
        >
            <i class="mdi mdi-arrow-left" aria-hidden="true"></i>
            <span>Back to tools</span>
        </a>

        <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-xl">
            <div class="border-b border-gray-25 bg-support-2 px-6 py-5 lg:px-8">
                <h1 class="text-2xl font-semibold text-gray-90">'.$pageTitle.'</h1>
                <p class="mt-2 max-w-3xl text-body-2 text-gray-50">'.$pageDescription.'</p>
            </div>

            <div class="p-6 lg:p-8">
                <div class="mb-6 rounded-2xl border border-gray-25 bg-gray-10 p-5">
                    <div class="mb-2 text-caption font-semibold uppercase tracking-wide text-gray-50">Key ID</div>
                    <div class="text-body-2 font-semibold text-gray-90">'.($kid ?: '—').'</div>
                </div>';

if (empty($publicKey) && empty($privateKey)) {
    $content .= '
                <div class="rounded-2xl border border-warning bg-support-6 p-5 text-body-2 text-gray-90">
                    No platform keys are available.
                </div>';
} else {
    $content .= '
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-25 bg-support-2 px-5 py-4">
                            <h2 class="text-body-1 font-semibold text-gray-90">'.$plugin->get_lang('PublicKey').'</h2>
                            <button
                                type="button"
                                class="js-copy-key inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary"
                                data-copy-target="imslti-public-key"
                            >
                                <i class="mdi mdi-content-copy" aria-hidden="true"></i>
                                <span>Copy</span>
                            </button>
                        </div>
                        <div class="p-5">
                            <pre id="imslti-public-key" class="overflow-x-auto whitespace-pre-wrap break-all rounded-xl border border-gray-25 bg-gray-10 p-4 font-mono text-tiny text-gray-90">'.$publicKey.'</pre>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-25 bg-support-2 px-5 py-4">
                            <h2 class="text-body-1 font-semibold text-gray-90">'.$plugin->get_lang('PrivateKey').'</h2>
                            <button
                                type="button"
                                class="js-copy-key inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary"
                                data-copy-target="imslti-private-key"
                            >
                                <i class="mdi mdi-content-copy" aria-hidden="true"></i>
                                <span>Copy</span>
                            </button>
                        </div>
                        <div class="p-5">
                            <pre id="imslti-private-key" class="overflow-x-auto whitespace-pre-wrap break-all rounded-xl border border-gray-25 bg-gray-10 p-4 font-mono text-tiny text-gray-90">'.$privateKey.'</pre>
                        </div>
                    </div>
                </div>';
}

$content .= '
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".js-copy-key");

    buttons.forEach((button) => {
        button.addEventListener("click", async function () {
            const targetId = button.getAttribute("data-copy-target");
            const target = document.getElementById(targetId);

            if (!target) {
                return;
            }

            const originalText = button.querySelector("span")?.textContent || "Copy";

            try {
                await navigator.clipboard.writeText(target.textContent || "");
                if (button.querySelector("span")) {
                    button.querySelector("span").textContent = "Copied";
                }
            } catch (error) {
                if (button.querySelector("span")) {
                    button.querySelector("span").textContent = "Error";
                }
            }

            window.setTimeout(function () {
                if (button.querySelector("span")) {
                    button.querySelector("span").textContent = originalText;
                }
            }, 1600);
        });
    });
});
</script>';

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();
