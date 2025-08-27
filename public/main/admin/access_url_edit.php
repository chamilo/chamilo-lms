<?php

/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_global_admin_script();

$httpRequest = HttpRequest::createFromGlobals();
$urlRepo = Container::getAccessUrlRepository();
$urlHelper = Container::getAccessUrlUtil();

$form = new FormValidator('add_url');

$form->addUrl('url', 'URL');
$form->addRule('url', get_lang('Required field'), 'required');
$form->addRule('url', '', 'maxlength', 254);
$form->addTextarea('description', get_lang('Description'));

// URL Images
$form->addFile('url_image_1', get_lang('Image'));
//$form->addElement('file', 'url_image_2', 'URL Image 2 (PNG)');
//$form->addElement('file', 'url_image_3', 'URL Image 3 (PNG)');

$defaults['url'] = 'http://';
$form->setDefaults($defaults);
if ($httpRequest->query->has('url_id')) {
    $url_id = $httpRequest->query->getInt('url_id');

    /** @var AccessUrl $url_data */
    $url_data = $urlRepo->find($url_id);

    if (!$url_data) {
        header('Location: access_urls.php');
        exit();
    }
    $form->addElement('hidden', 'id', $url_data->getId());
    // If we're still with localhost (should only happen at the very beginning)
    // offer the current URL by default. Once this has been saved, no more
    // magic will happen, ever.
    if ($url_data->getId() === 1 && $url_data->getUrl() === AccessUrl::DEFAULT_ACCESS_URL) {
        $https = api_is_https() ? 'https://' : 'http://';
        $url_data->setUrl($https.$_SERVER['HTTP_HOST'].'/');
    }
    $form->setDefaults([
        'id' => $url_data->getId(),
        'url' => $url_data->getUrl(),
        'description' => $url_data->getDescription(),
        'active' => $url_data->getActive(),
        'login_only' => $url_data->isLoginOnly(),
    ]);
}

$form->addHidden(
    'parentResourceNodeId',
    $urlHelper->getFirstAccessUrl()->resourceNode->getId()
);

//the first url with id = 1 will be always active
if ($httpRequest->query->has('url_id')) {
    if ($urlHelper->getFirstAccessUrl()?->getId() !== $httpRequest->query->getInt('url_id')) {
        $form->addElement('checkbox', 'active', null, get_lang('active'));
    }
}

$form->addCheckBox('login_only', get_lang('Login-only URL'), get_lang('Yes'));

$form->addButtonCreate(get_lang('Save'));

if ($form->validate()) {
    $check = Security::check_token('post');
    if ($check) {
        $url_array = $form->getSubmitValues();
        $url = Security::remove_XSS($url_array['url']);
        $description = Security::remove_XSS($url_array['description']);
        $active = isset($url_array['active']) ? (int) $url_array['active'] : 0;
        $url_id = isset($url_array['id']) ? (int) $url_array['id'] : 0;
        $isLoginOnly = isset($url_array['login_only']) && (bool) $url_array['login_only'];
        $url_to_go = 'access_urls.php';
        if (!empty($url_id)) {
            //we can't change the status of the url with id=1
            if (1 == $url_id) {
                $active = 1;
            }
            // Checking url
            if ('/' != substr($url, strlen($url) - 1, strlen($url))) {
                $url .= '/';
            }

            /** @var AccessUrl $accessUrl */
            $accessUrl = $urlRepo->find($url_id);

            $accessUrl
                ->setUrl($url)
                ->setDescription($description)
                ->setActive($active)
                ->setCreatedBy(api_get_user_id())
                ->setTms(api_get_utc_datetime(null, false, true))
                ->setIsLoginOnly($isLoginOnly)
            ;

            $url_to_go = 'access_urls.php';
            $message = get_lang('The URL has been edited');
        } else {
            try {
                $exists = $urlRepo->exists($url);
            } catch (NoResultException|NonUniqueResultException $e) {
                $exists = true;
            }

            $url_to_go = 'access_url_edit.php';
            $message = get_lang('This URL already exists, please select another URL');
            if (!$exists) {
                // checking url
                if ('/' != substr($url, strlen($url) - 1, strlen($url))) {
                    $url .= '/';
                }

                if ($isLoginOnly) {
                    $sameDomain = $urlHelper->isSameBaseDomain(
                        array_merge($urlRepo->getUrlList(), [$url])
                    );

                    if (!$sameDomain) {
                        Display::addFlash(
                            Display::return_message(
                                get_lang('To use the central login page feature, all URLs defined MUST use the same (root) domain name in order to limit security risks linked to sharing access tokens between URLs. URLs using a different domain name might not be taken into account for access sharing.')
                            )
                        );
                    }
                }

                $accessUrl = $urlRepo->findOneBy(['url' => $url]);

                if (!$accessUrl) {
                    $accessUrl = new AccessUrl();
                    $accessUrl
                        ->setDescription($description)
                        ->setActive($active)
                        ->setUrl($url)
                        ->setCreatedBy(api_get_user_id())
                        ->setIsLoginOnly($isLoginOnly)
                    ;

                    Database::getManager()->persist($accessUrl);

                    $message = get_lang('The URL has been added');
                    $url_to_go = 'access_urls.php';
                }
            }
        }

        Database::getManager()->flush();

        Security::clear_token();
        $tok = Security::get_token();
        Display::addFlash(Display::return_message($message));
        header('Location: '.$url_to_go.'?sec_token='.$tok);
        exit;
    }
} else {
    if (isset($_POST['submit'])) {
        Security::clear_token();
    }
    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(['sec_token' => $token]);
}

$tool_name =  isset($url_id) && $url_id > 0
    ? get_lang('Edit URL')
    : get_lang('Add URL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('Multiple access URL / Branding')];

Display::display_header($tool_name);
echo '<div class="flex gap-2 items-center mb-4 mt-4">';
echo Display::url(
    Display::getMdiIcon(
        ActionIcon::BACK,
        'ch-tool-icon',
        null, ICON_SIZE_MEDIUM,
        sprintf(get_lang('Back to %s'), get_lang('URL list'))
    ),
    api_get_path(WEB_CODE_PATH).'admin/access_urls.php'
);
echo '</div>';
echo '<h2 class="text-xl font-semibold text-gray-800 mt-6 mb-6">';
echo $tool_name;
echo '</h2>';
$form->display();
Display::display_footer();
