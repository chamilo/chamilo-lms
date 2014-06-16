<?php

namespace ChamiloLMS\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Model\PageInterface;

use Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\Doctrine\Orm\ContentRepositoryTest;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPageData extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
{
    private $container;

    public function getOrder()
    {
        return 3;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $site = $this->createSite();
        $this->createGlobalPage($site);

        // app/console sonata:page:update-core-routes --site=all
        // app/console sonata:page:create-snapshots --site=all

        $this->createHomePage($site);

        /*
       $this->create404ErrorPage($site);
       $this->create500ErrorPage($site);
       $this->createBlogIndex($site);
       $this->createGalleryIndex($site);
       $this->createMediaPage($site);
       $this->createProductPage($site);
       $this->createBasketPage($site);
       $this->createUserPage($site);
       $this->createApiPage($site);
       $this->createLegalNotesPage($site);
       $this->createTermsPage($site);

       // Create footer pages
       $this->createWhoWeArePage($site);
       $this->createClientTestimonialsPage($site);
       $this->createPressPage($site);
       $this->createFAQPage($site);
       $this->createContactUsPage($site);
       $this->createBundlesPage($site);

       $this->createSubSite();*/
    }

    /**
     * @return SiteInterface $site
     */
    public function createSite()
    {
        $site = $this->getSiteManager()->create();

        $site->setHost('localhost');
        $site->setEnabled(true);
        $site->setName('localhost');
        $site->setEnabledFrom(new \DateTime('now'));
        $site->setEnabledTo(new \DateTime('+20 years'));
        $site->setRelativePath("");
        $site->setIsDefault(true);

        $this->getSiteManager()->save($site);

        return $site;
    }

    public function createSubSite()
    {
        $site = $this->getSiteManager()->create();

        $site->setHost('localhost');
        $site->setEnabled(true);
        $site->setName('sub site');
        $site->setEnabledFrom(new \DateTime('now'));
        $site->setEnabledTo(new \DateTime('+10 years'));
        $site->setRelativePath("/sub-site");
        $site->setIsDefault(false);

        $this->getSiteManager()->save($site);

        return $site;
    }

    /**
     * @param SiteInterface $site
     */
    public function createBlogIndex(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();

        $blogIndex = $pageManager->create();
        $blogIndex->setSlug('blog');
        $blogIndex->setUrl('/blog');
        $blogIndex->setName('News');
        $blogIndex->setTitle('News');
        $blogIndex->setEnabled(true);
        $blogIndex->setDecorate(1);
        $blogIndex->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $blogIndex->setTemplateCode('default');
        $blogIndex->setRouteName('sonata_news_home');
        $blogIndex->setParent($this->getReference('page-homepage'));
        $blogIndex->setSite($site);

        $pageManager->save($blogIndex);
    }

    /**
     * @param SiteInterface $site
     */
    public function createGalleryIndex(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $galleryIndex = $pageManager->create();
        $galleryIndex->setSlug('gallery');
        $galleryIndex->setUrl('/media/gallery');
        $galleryIndex->setName('Gallery');
        $galleryIndex->setTitle('Gallery');
        $galleryIndex->setEnabled(true);
        $galleryIndex->setDecorate(1);
        $galleryIndex->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $galleryIndex->setTemplateCode('default');
        $galleryIndex->setRouteName('sonata_media_gallery_index');
        $galleryIndex->setParent($this->getReference('page-homepage'));
        $galleryIndex->setSite($site);

        // CREATE A HEADER BLOCK
        $galleryIndex->addBlocks($content = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $galleryIndex,
            'code' => 'content_top',
        )));

        $content->setName('The content_top container');

        // add the breadcrumb
        $content->addChildren($breadcrumb = $blockManager->create());
        $breadcrumb->setType('sonata.page.block.breadcrumb');
        $breadcrumb->setPosition(0);
        $breadcrumb->setEnabled(true);
        $breadcrumb->setPage($galleryIndex);

        // add a block text
        $content->addChildren($text = $blockManager->create());
        $text->setType('sonata.block.service.text');
        $text->setSetting('content', <<<CONTENT

<h1>Gallery List</h1>

<p>
    This current text is defined in a <code>text block</code> linked to a custom symfony action <code>GalleryController::indexAction</code>
    the SonataPageBundle can encapsulate an action into a dedicated template. <br /><br />

    If you are connected as an admin you can click on <code>Show Zone</code> to see the different editable areas. Once
    areas are displayed, just double click on one to edit it.
</p>

CONTENT
        );
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($galleryIndex);

        $pageManager->save($galleryIndex);
    }

    /**
     * @param SiteInterface $site
     */
    public function createTermsPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $terms = $pageManager->create();
        $terms->setSlug('shop-payment-terms-and-conditions');
        $terms->setUrl('/shop/payment/terms-and-conditions');
        $terms->setName('Terms and conditions');
        $terms->setTitle('Terms and conditions');
        $terms->setEnabled(true);
        $terms->setDecorate(1);
        $terms->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $terms->setTemplateCode('default');
        $terms->setRouteName('sonata_payment_terms');
        $terms->setParent($this->getReference('page-homepage'));
        $terms->setSite($site);

        // CREATE A HEADER BLOCK
        $terms->addBlocks($content = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $terms,
            'code' => 'content_top',
        )));
        $content->setName('The content_top container');

        // add the breadcrumb
        $content->addChildren($breadcrumb = $blockManager->create());
        $breadcrumb->setType('sonata.page.block.breadcrumb');
        $breadcrumb->setPosition(0);
        $breadcrumb->setEnabled(true);
        $breadcrumb->setPage($terms);

        $pageManager->save($terms);
    }

    /**
     * @param SiteInterface $site
     */
    public function createHomePage(SiteInterface $site)
    {
        return;
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $this->addReference('page-homepage', $homepage = $pageManager->create());
        $homepage->setSlug('/');
        $homepage->setUrl('/');
        $homepage->setName('Home');
        $homepage->setTitle('Homepage');
        $homepage->setEnabled(true);
        $homepage->setDecorate(0);
        $homepage->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $homepage->setTemplateCode('2columns');
        $homepage->setRouteName('homepage');
        $homepage->setSite($site);

        $pageManager->save($homepage);

        // CREATE A HEADER BLOCK
        $homepage->addBlocks($contentTop = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $homepage,
            'code' => 'content_top',
        )));

        $contentTop->setName('The container top container');

        $blockManager->save($contentTop);

        // add a block text
        $contentTop->addChildren($text = $blockManager->create());
        $text->setType('sonata.block.service.text');
        $text->setSetting('content', <<<CONTENT
<div class="col-md-3 welcome"><h2>Welcome</h2></div>
<div class="col-md-9">
    <p>
        This page is a demo.
    </p>

    <p>
        Pages.
    </p>
</div>
CONTENT
        );
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($homepage);


        $homepage->addBlocks($content = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $homepage,
            'code' => 'content',
        )));
        $content->setName('The content container');
        $blockManager->save($content);

        /*
        // Add media gallery block
        $content->addChildren($gallery = $blockManager->create());
        $gallery->setType('sonata.media.block.gallery');
        $gallery->setSetting('galleryId', $this->getReference('media-gallery')->getId());
        $gallery->setSetting('context', 'default');
        $gallery->setSetting('format', 'big');
        $gallery->setPosition(1);
        $gallery->setEnabled(true);
        $gallery->setPage($homepage);

        // Add recent products block
        $content->addChildren($newProductsBlock = $blockManager->create());
        $newProductsBlock->setType('sonata.product.block.recent_products');
        $newProductsBlock->setSetting('number', 4);
        $newProductsBlock->setSetting('title', 'New products');
        $newProductsBlock->setPosition(2);
        $newProductsBlock->setEnabled(true);
        $newProductsBlock->setPage($homepage);

        // Add homepage bottom container
        $homepage->addBlocks($bottom = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $homepage,
            'code'    => 'content_bottom',
        ), function ($container) {
            $container->setSetting('layout', '{{ CONTENT }}');
        }));
        $bottom->setName('The bottom content container');

        // Add homepage newsletter container
        $bottom->addChildren($bottomNewsletter = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $homepage,
            'code'    => 'bottom_newsletter',
        ), function ($container) {
            $container->setSetting('layout', '<div class="block-newsletter col-sm-6 well">{{ CONTENT }}</div>');
        }));
        $bottomNewsletter->setName('The bottom newsetter container');
        $bottomNewsletter->addChildren($newsletter = $blockManager->create());
        $newsletter->setType('sonata.demo.block.newsletter');
        $newsletter->setPosition(1);
        $newsletter->setEnabled(true);
        $newsletter->setPage($homepage);

        // Add homepage embed tweet container
        $bottom->addChildren($bottomEmbed = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $homepage,
            'code'    => 'bottom_embed',
        ), function ($container) {
            $container->setSetting('layout', '<div class="col-sm-6">{{ CONTENT }}</div>');
        }));
        $bottomEmbed->setName('The bottom embedded tweet container');
        $bottomEmbed->addChildren($embedded = $blockManager->create());
        $embedded->setType('sonata.seo.block.twitter.embed');
        $embedded->setPosition(1);
        $embedded->setEnabled(true);
        $embedded->setSetting('tweet', "https://twitter.com/dunglas/statuses/438337742565826560");
        $embedded->setSetting('lang', "en");
        $embedded->setPage($homepage);

        $pageManager->save($homepage);*/
    }

    /**
     * @param SiteInterface $site
     */
    public function createProductPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();

        $category = $pageManager->create();

        $category->setSlug('shop-category');
        $category->setUrl('/shop/category');
        $category->setName('Shop');
        $category->setTitle('Shop');
        $category->setEnabled(true);
        $category->setDecorate(1);
        $category->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $category->setTemplateCode('default');
        $category->setRouteName('sonata_catalog_index');
        $category->setSite($site);
        $category->setParent($this->getReference('page-homepage'));

        $pageManager->save($category);
    }

    /**
     * @param SiteInterface $site
     */
    public function createBasketPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();

        $basket = $pageManager->create();

        $basket->setSlug('shop-basket');
        $basket->setUrl('/shop/basket');
        $basket->setName('Basket');
        $basket->setEnabled(true);
        $basket->setDecorate(1);
        $basket->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $basket->setTemplateCode('default');
        $basket->setRouteName('sonata_basket_index');
        $basket->setSite($site);
        $basket->setParent($this->getReference('page-homepage'));

        $pageManager->save($basket);
    }

    /**
     * @param SiteInterface $site
     */
    public function createMediaPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $this->addReference('page-media', $media = $pageManager->create());
        $media->setSlug('/media');
        $media->setUrl('/media');
        $media->setName('Media & Seo');
        $media->setTitle('Media & Seo');
        $media->setEnabled(true);
        $media->setDecorate(1);
        $media->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $media->setTemplateCode('default');
        $media->setRouteName('sonata_demo_media');
        $media->setSite($site);
        $media->setParent($this->getReference('page-homepage'));

        // CREATE A HEADER BLOCK
        $media->addBlocks($content = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $media,
            'code' => 'content_top',
        )));

        $content->setName('The content_top container');

        // add the breadcrumb
        $content->addChildren($breadcrumb = $blockManager->create());
        $breadcrumb->setType('sonata.page.block.breadcrumb');
        $breadcrumb->setPosition(0);
        $breadcrumb->setEnabled(true);
        $breadcrumb->setPage($media);

        $pageManager->save($media);
    }

    /**
     * @param SiteInterface $site
     */
    public function createUserPage(SiteInterface $site)
    {
        $this->createTextContentPage($site, 'user', 'Admin', <<<CONTENT
<div>

    <h3>Available accounts</h3>
    You can connect to the <a href="/admin/dashboard">admin section</a> by using two different accounts:<br>

    <ul>
        <li><em>Standard</em> user:
            <ul>
                <li> Login - <strong>johndoe</strong></li>
                <li> Password - <strong>johndoe</strong></li>
            </ul>
        </li>
        <li><em>Admin</em> user:
            <ul>
                <li> Login - <strong>admin</strong></li>
                <li> Password - <strong>admin</strong></li>
            </ul>
        </li>
        <li><em>Two-step Verification admin</em> user:
            <ul>
                <li> Login - <strong>secure</strong></li>
                <li> Password - <strong>secure</strong></li>
                <li> Key - <strong>4YU4QGYPB63HDN2C</strong></li>
            </ul>
        </li>
    </ul>

    <h3>Two-Step Verification</h3>
    The <strong>secure</strong> account is a demo of the Two-Step Verification provided by
    the <a href="http://sonata-project.org/bundles/user/2-0/doc/reference/two_step_validation.html">Sonata User Bundle</a>

    <br />
    <br />
    <center>
        <img src="/bundles/sonatademo/images/secure_qr_code.png" class="img-polaroid" />
        <br />
        <em>Take a shot of this QR Code with <a href="https://support.google.com/accounts/bin/answer.py?hl=en&answer=1066447">Google Authenticator</a></em>
    </center>

</div>

CONTENT
        );
    }

    /**
     * @param SiteInterface $site
     */
    public function createApiPage(SiteInterface $site)
    {
        $this->createTextContentPage($site, 'api-landing', 'API', <<<CONTENT
<div>

    <h3>Available account</h3>
    You can connect to the <a href="/api/doc">api documentation</a> by using the following account:<br>

    <ul>
        <li> Login - <strong>admin</strong></li>
        <li> Password - <strong>admin</strong></li>
    </ul>

    <br />
    <br />
    <center>
        <img src="/bundles/sonatademo/images/api.png" class="img-rounded" />
    </center>

</div>

CONTENT
        );
    }

    /**
     * @param SiteInterface $site
     */
    public function createLegalNotesPage(SiteInterface $site)
    {
        $this->createTextContentPage($site, 'legal-notes', 'Legal notes', <<<CONTENT
<p>The Sonata framework is built with many great open source libraries / tools.</p>
<section>
    <h3>Backend</h3>
    <ul>
        <li><a href="http://symfony.com" title="Symfony, PHP framework official website">Symfony 2</a>, the PHP framework for web projects (Code licensed under MIT),</li>
        <li><a href="http://twig.sensiolabs.org" title="Twig, PHP template engine">Twig</a>, the PHP template engine (Code licensed under the new BSD license),</li>
        <li><a href="http://www.doctrine-project.org" title="Doctrine, PHP ORM">Doctrine</a>, the PHP ORM.</li>
    </ul>
</section>
<section>
    <h3>Frontend</h3>
    <ul>
        <li><a href="http://jquery.com" title="jQuery javascript library">jQuery</a>, a cross-platform JavaScript library (Code licensed under MIT),</li>
        <li><a href="http://getbootstrap.com" title="Bootstrap front-end framework">Bootstrap</a>, the front-end framework (Code licensed under MIT),</li>
        <li><a href="http://glyphicons.com" title="GLYPHICONS icons">GLYPHICONS</a>, library included in the Bootstrap framework (same license as Bootstrap).</li>
    </ul>
</section>
<section>
    <h3>Other miscellaneous tools</h3>
    <ul>
        <li><a href="https://www.github.com" title="Github, code distribution tool">Github</a></li>
        <li><a href="http://getcomposer.org" title="Composer, dependency management tool">Composer</a></li>
        <li><a href="https://packagist.org" title="Packagist, PHP packages repository">Packagist</a></li>
        <li><a href="https://travis-ci.org" title="Travis CI, continuous integration tool">Travis CI</a></li>
        <li><a href="http://phpunit.de" title="PHPUnit, PHP unit testing library">PHPUnit</a></li>
        <li><a href="http://behat.org" title="Behat, test driven development tool">Behat</a></li>
    </ul>
</section>
CONTENT
        );
    }

    /**
     * Creates the "Who we are" content page (link available in footer)
     *
     * @param SiteInterface $site
     *
     * @return void
     */
    public function createWhoWeArePage(SiteInterface $site)
    {
        $this->createTextContentPage($site, 'who-we-are', 'Who we are', <<<CONTENT
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut quis sapien gravida, eleifend diam id, vehicula erat. Aenean ultrices facilisis tellus. Vivamus vitae molestie diam. Donec quis mi porttitor, lobortis ipsum quis, fermentum dui. Donec nec nibh nec risus porttitor pretium et et lorem. Nullam mauris sapien, rutrum sed neque et, convallis ullamcorper lacus. Nullam vehicula a lectus vel suscipit. Nam gravida faucibus fermentum.</p>
<p>Pellentesque dapibus eu nisi quis adipiscing. Phasellus adipiscing turpis nunc, sed interdum ante porta eu. Ut tempus, purus posuere molestie cursus, quam nisi fermentum est, dictum gravida nulla turpis vel nunc. Maecenas eget sem quam. Nam condimentum mi id lectus venenatis, sit amet semper purus convallis. Nunc ullamcorper magna mi, non adipiscing velit semper quis. Duis vel justo libero. Suspendisse laoreet hendrerit augue cursus congue. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;</p>
<p>Nullam dignissim sapien vestibulum erat lobortis, sed imperdiet elit varius. Fusce nisi eros, feugiat commodo scelerisque a, lacinia et quam. In neque risus, dignissim non magna non, ultricies faucibus elit. Vivamus in facilisis enim, porttitor volutpat justo. Praesent placerat feugiat nibh et fermentum. Vivamus eu fermentum metus. Sed mattis volutpat quam a suscipit. Donec blandit sagittis est, ac tristique arcu venenatis sed. Fusce vel libero id lectus aliquet sollicitudin. Fusce ultrices porta est, non pellentesque lorem accumsan eget. Fusce id libero sit amet nulla venenatis dapibus. Maecenas fermentum tellus eu magna mollis gravida. Nam non nibh magna.</p>
CONTENT
        );
    }

    /**
     * Creates the "Client testimonials" content page (link available in footer)
     *
     * @param SiteInterface $site
     *
     * @return void
     */
    public function createClientTestimonialsPage(SiteInterface $site)
    {
        $this->createTextContentPage($site, 'client-testimonials', 'Client testimonials', <<<CONTENT
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut quis sapien gravida, eleifend diam id, vehicula erat. Aenean ultrices facilisis tellus. Vivamus vitae molestie diam. Donec quis mi porttitor, lobortis ipsum quis, fermentum dui. Donec nec nibh nec risus porttitor pretium et et lorem. Nullam mauris sapien, rutrum sed neque et, convallis ullamcorper lacus. Nullam vehicula a lectus vel suscipit. Nam gravida faucibus fermentum.</p>
<p>Pellentesque dapibus eu nisi quis adipiscing. Phasellus adipiscing turpis nunc, sed interdum ante porta eu. Ut tempus, purus posuere molestie cursus, quam nisi fermentum est, dictum gravida nulla turpis vel nunc. Maecenas eget sem quam. Nam condimentum mi id lectus venenatis, sit amet semper purus convallis. Nunc ullamcorper magna mi, non adipiscing velit semper quis. Duis vel justo libero. Suspendisse laoreet hendrerit augue cursus congue. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;</p>
<p>Nullam dignissim sapien vestibulum erat lobortis, sed imperdiet elit varius. Fusce nisi eros, feugiat commodo scelerisque a, lacinia et quam. In neque risus, dignissim non magna non, ultricies faucibus elit. Vivamus in facilisis enim, porttitor volutpat justo. Praesent placerat feugiat nibh et fermentum. Vivamus eu fermentum metus. Sed mattis volutpat quam a suscipit. Donec blandit sagittis est, ac tristique arcu venenatis sed. Fusce vel libero id lectus aliquet sollicitudin. Fusce ultrices porta est, non pellentesque lorem accumsan eget. Fusce id libero sit amet nulla venenatis dapibus. Maecenas fermentum tellus eu magna mollis gravida. Nam non nibh magna.</p>
CONTENT
        );
    }

    /**
     * Creates the "Press" content page (link available in footer)
     *
     * @param SiteInterface $site
     *
     * @return void
     */
    public function createPressPage(SiteInterface $site)
    {
        $this->createTextContentPage($site, 'press', 'Press', <<<CONTENT
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut quis sapien gravida, eleifend diam id, vehicula erat. Aenean ultrices facilisis tellus. Vivamus vitae molestie diam. Donec quis mi porttitor, lobortis ipsum quis, fermentum dui. Donec nec nibh nec risus porttitor pretium et et lorem. Nullam mauris sapien, rutrum sed neque et, convallis ullamcorper lacus. Nullam vehicula a lectus vel suscipit. Nam gravida faucibus fermentum.</p>
<p>Pellentesque dapibus eu nisi quis adipiscing. Phasellus adipiscing turpis nunc, sed interdum ante porta eu. Ut tempus, purus posuere molestie cursus, quam nisi fermentum est, dictum gravida nulla turpis vel nunc. Maecenas eget sem quam. Nam condimentum mi id lectus venenatis, sit amet semper purus convallis. Nunc ullamcorper magna mi, non adipiscing velit semper quis. Duis vel justo libero. Suspendisse laoreet hendrerit augue cursus congue. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;</p>
<p>Nullam dignissim sapien vestibulum erat lobortis, sed imperdiet elit varius. Fusce nisi eros, feugiat commodo scelerisque a, lacinia et quam. In neque risus, dignissim non magna non, ultricies faucibus elit. Vivamus in facilisis enim, porttitor volutpat justo. Praesent placerat feugiat nibh et fermentum. Vivamus eu fermentum metus. Sed mattis volutpat quam a suscipit. Donec blandit sagittis est, ac tristique arcu venenatis sed. Fusce vel libero id lectus aliquet sollicitudin. Fusce ultrices porta est, non pellentesque lorem accumsan eget. Fusce id libero sit amet nulla venenatis dapibus. Maecenas fermentum tellus eu magna mollis gravida. Nam non nibh magna.</p>
CONTENT
        );
    }

    /**
     * Creates the "FAQ" content page (link available in footer)
     *
     * @param SiteInterface $site
     *
     * @return void
     */
    public function createFAQPage(SiteInterface $site)
    {
        $this->createTextContentPage($site, 'faq', 'FAQ', <<<CONTENT
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut quis sapien gravida, eleifend diam id, vehicula erat. Aenean ultrices facilisis tellus. Vivamus vitae molestie diam. Donec quis mi porttitor, lobortis ipsum quis, fermentum dui. Donec nec nibh nec risus porttitor pretium et et lorem. Nullam mauris sapien, rutrum sed neque et, convallis ullamcorper lacus. Nullam vehicula a lectus vel suscipit. Nam gravida faucibus fermentum.</p>
<p>Pellentesque dapibus eu nisi quis adipiscing. Phasellus adipiscing turpis nunc, sed interdum ante porta eu. Ut tempus, purus posuere molestie cursus, quam nisi fermentum est, dictum gravida nulla turpis vel nunc. Maecenas eget sem quam. Nam condimentum mi id lectus venenatis, sit amet semper purus convallis. Nunc ullamcorper magna mi, non adipiscing velit semper quis. Duis vel justo libero. Suspendisse laoreet hendrerit augue cursus congue. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;</p>
<p>Nullam dignissim sapien vestibulum erat lobortis, sed imperdiet elit varius. Fusce nisi eros, feugiat commodo scelerisque a, lacinia et quam. In neque risus, dignissim non magna non, ultricies faucibus elit. Vivamus in facilisis enim, porttitor volutpat justo. Praesent placerat feugiat nibh et fermentum. Vivamus eu fermentum metus. Sed mattis volutpat quam a suscipit. Donec blandit sagittis est, ac tristique arcu venenatis sed. Fusce vel libero id lectus aliquet sollicitudin. Fusce ultrices porta est, non pellentesque lorem accumsan eget. Fusce id libero sit amet nulla venenatis dapibus. Maecenas fermentum tellus eu magna mollis gravida. Nam non nibh magna.</p>
CONTENT
        );
    }

    /**
     * Creates the "Contact us" content page (link available in footer)
     *
     * @param SiteInterface $site
     *
     * @return void
     */
    public function createContactUsPage(SiteInterface $site)
    {
        $this->createTextContentPage($site, 'contact-us', 'Contact us', <<<CONTENT
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut quis sapien gravida, eleifend diam id, vehicula erat. Aenean ultrices facilisis tellus. Vivamus vitae molestie diam. Donec quis mi porttitor, lobortis ipsum quis, fermentum dui. Donec nec nibh nec risus porttitor pretium et et lorem. Nullam mauris sapien, rutrum sed neque et, convallis ullamcorper lacus. Nullam vehicula a lectus vel suscipit. Nam gravida faucibus fermentum.</p>
<p>Pellentesque dapibus eu nisi quis adipiscing. Phasellus adipiscing turpis nunc, sed interdum ante porta eu. Ut tempus, purus posuere molestie cursus, quam nisi fermentum est, dictum gravida nulla turpis vel nunc. Maecenas eget sem quam. Nam condimentum mi id lectus venenatis, sit amet semper purus convallis. Nunc ullamcorper magna mi, non adipiscing velit semper quis. Duis vel justo libero. Suspendisse laoreet hendrerit augue cursus congue. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;</p>
<p>Nullam dignissim sapien vestibulum erat lobortis, sed imperdiet elit varius. Fusce nisi eros, feugiat commodo scelerisque a, lacinia et quam. In neque risus, dignissim non magna non, ultricies faucibus elit. Vivamus in facilisis enim, porttitor volutpat justo. Praesent placerat feugiat nibh et fermentum. Vivamus eu fermentum metus. Sed mattis volutpat quam a suscipit. Donec blandit sagittis est, ac tristique arcu venenatis sed. Fusce vel libero id lectus aliquet sollicitudin. Fusce ultrices porta est, non pellentesque lorem accumsan eget. Fusce id libero sit amet nulla venenatis dapibus. Maecenas fermentum tellus eu magna mollis gravida. Nam non nibh magna.</p>
CONTENT
        );
    }

    public function createBundlesPage(SiteInterface $site)
    {
        $this->createTextContentPage($site, 'bundles', 'Sonata Bundles', <<<CONTENT
<div class="row">
<div class="col-md-6">

    <div class="panel panel-success">
      <div class="panel-heading">
        <h3 class="panel-title">Admin bundles</h3>
      </div>
      <div class="panel-body">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut quis sapien gravida, eleifend diam id, vehicula erat.
      </div>
      <ul class="list-group">
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/admin">Admin</a></h4>
              The missing Symfony2 Admin Generator.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/doctrine-orm-admin">Doctrine2 ORM Admin</a></h4>
              Integrates the Doctrine2 ORM into the Admin Bundle.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/propel-admin">Propel Admin</a></h4>
              Integrates the Propel into the Admin Bundle.
            </div>
          </div>
        </li>
      </ul>
    </div>

    <div class="panel panel-info">
      <div class="panel-heading">
        <h3 class="panel-title">Foundation bundles</h3>
      </div>
      <div class="panel-body">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut quis sapien gravida, eleifend diam id, vehicula erat.
      </div>
      <ul class="list-group">
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/core">Core</a></h4>
              Provides base classes used by Sonata's Bundles.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/notification">Notification</a></h4>
              Message Queue Solution with Abstracted Backends.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/formatter">Formatter</a></h4>
              Add text helpers.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/intl">Internationalization (i18n)</a></h4>
              Integrate the PHP Intl extension into a Symfony2 Project.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/cache">Cache</a></h4>
              Cache handlers&nbsp;: ESI, Memcached, APC and more…
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/seo">SEO</a></h4>
              Integrates a shareable object to handle all SEO requirements&nbsp;: title, meta, Open Graph and more…
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/easy-extends">EasyExtends</a></h4>
              EasyExtends is a tool for generating a valid bundle structure from a Vendor Bundle.
            </div>
          </div>
        </li>
      </ul>
    </div>

</div>
<div class="col-md-6">

    <div class="panel panel-warning">
      <div class="panel-heading">
        <h3 class="panel-title">E-commerce bundles</h3>
      </div>
      <div class="panel-body">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut quis sapien gravida, eleifend diam id, vehicula erat.
      </div>
      <ul class="list-group">
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/ecommerce">Ecommerce</a></h4>
              Implements base tools for integrated e-commerce features
            </div>
          </div>
        </li>
      </ul>
    </div>

    <div class="panel panel-danger">
      <div class="panel-heading">
        <h3 class="panel-title">Features bundles</h3>
      </div>
      <div class="panel-body">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut quis sapien gravida, eleifend diam id, vehicula erat.
      </div>
      <ul class="list-group">
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/page">Page</a></h4>
              A Symfony2 friendly CMS.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/media">Media</a></h4>
              Media management bundle on steroid for Symfony2.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/news">News</a></h4>
              A simple blog/news platform.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/user">User</a></h4>
              FOS/User integration.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/block">Block</a></h4>
              Handle rendering of block element. A block is a small unit with its own logic and templates. A block can be inserted anywhere in a current template.
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="media">
            <a class="pull-left" href="#">
              <img class="media-object" src="/bundles/sonatademo/images/sonata-logo.png">
            </a>
            <div class="media-body">
              <h4 class="media-heading"><a href="http://sonata-project.org/bundles/timeline">Timeline</a></h4>
              Integrates SpyTimelineBundle into Sonata's bundles.
            </div>
          </div>
        </li>
      </ul>
    </div>

</div>
</div>
CONTENT
        );
    }

    /**
     * Creates simple content pages
     *
     * @param SiteInterface $site    A Site entity instance
     * @param string        $url     A page URL
     * @param string        $title   A page title
     * @param string        $content A text content
     *
     * @return void
     */
    public function createTextContentPage(SiteInterface $site, $url, $title, $content)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $page = $pageManager->create();
        $page->setSlug(sprintf('/%s', $url));
        $page->setUrl(sprintf('/%s', $url));
        $page->setName($title);
        $page->setTitle($title);
        $page->setEnabled(true);
        $page->setDecorate(1);
        $page->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $page->setTemplateCode('default');
        $page->setRouteName('page_slug');
        $page->setSite($site);
        $page->setParent($this->getReference('page-homepage'));

        $page->addBlocks($block = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $page,
            'code'    => 'content_top',
        )));

        // add the breadcrumb
        $block->addChildren($breadcrumb = $blockManager->create());
        $breadcrumb->setType('sonata.page.block.breadcrumb');
        $breadcrumb->setPosition(0);
        $breadcrumb->setEnabled(true);
        $breadcrumb->setPage($page);

        // Add text content block
        $block->addChildren($text = $blockManager->create());
        $text->setType('sonata.block.service.text');
        $text->setSetting('content', sprintf('<h2>%s</h2><div>%s</div>', $title, $content));
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($page);

        $pageManager->save($page);
    }

    public function create404ErrorPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $page = $pageManager->create();
        $page->setName('_page_internal_error_not_found');
        $page->setTitle('Error 404');
        $page->setEnabled(true);
        $page->setDecorate(1);
        $page->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $page->setTemplateCode('default');
        $page->setRouteName('_page_internal_error_not_found');
        $page->setSite($site);

        $page->addBlocks($block = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $page,
            'code'    => 'content_top',
        )));

        // add the breadcrumb
        $block->addChildren($breadcrumb = $blockManager->create());
        $breadcrumb->setType('sonata.page.block.breadcrumb');
        $breadcrumb->setPosition(0);
        $breadcrumb->setEnabled(true);
        $breadcrumb->setPage($page);

        // Add text content block
        $block->addChildren($text = $blockManager->create());
        $text->setType('sonata.block.service.text');
        $text->setSetting('content', '<h2>Error 404</h2><div>Page not found.</div>');
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($page);

        $pageManager->save($page);
    }

    public function create500ErrorPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $page = $pageManager->create();
        $page->setName('_page_internal_error_fatal');
        $page->setTitle('Error 500');
        $page->setEnabled(true);
        $page->setDecorate(1);
        $page->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $page->setTemplateCode('default');
        $page->setRouteName('_page_internal_error_fatal');
        $page->setSite($site);

        $page->addBlocks($block = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $page,
            'code'    => 'content_top',
        )));

        // add the breadcrumb
        $block->addChildren($breadcrumb = $blockManager->create());
        $breadcrumb->setType('sonata.page.block.breadcrumb');
        $breadcrumb->setPosition(0);
        $breadcrumb->setEnabled(true);
        $breadcrumb->setPage($page);

        // Add text content block
        $block->addChildren($text = $blockManager->create());
        $text->setType('sonata.block.service.text');
        $text->setSetting('content', '<h2>Error 500</h2><div>Internal error.</div>');
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($page);

        $pageManager->save($page);
    }

    /**
     * @param SiteInterface $site
     */
    public function createGlobalPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $global = $pageManager->create();
        $global->setName('global');
        $global->setRouteName('_page_internal_global');
        $global->setSite($site);

        $pageManager->save($global);

        // CREATE A HEADER BLOCK
        $global->addBlocks($header = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $global,
            'code' => 'header',
        )));

        $header->setName('The header container');

        $header->addChildren($text = $blockManager->create());

        $text->setType('sonata.block.service.text');
        $text->setSetting('content', '<h2><a href="/">Chamilo Demo</a></h2>');
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($global);

        $global->addBlocks($headerTop = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $global,
            'code' => 'header-top',
        ), function ($container) {
            $container->setSetting('layout', '<div class="pull-right">{{ CONTENT }}</div>');
        }));

        $headerTop->setPosition(1);

        $header->addChildren($headerTop);

        $headerTop->addChildren($account = $blockManager->create());

        $account->setType('sonata.user.block.account');
        $account->setPosition(1);
        $account->setEnabled(true);
        $account->setPage($global);

        $global->addBlocks($headerMenu = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $global,
            'code' => 'header-menu',
        )));

        $headerMenu->setPosition(2);

        $header->addChildren($headerMenu);

        $headerMenu->setName('The header menu container');
        $headerMenu->setPosition(3);
        $headerMenu->addChildren($menu = $blockManager->create());

        $menu->setType('sonata.block.service.menu');
        $menu->setSetting('menu_name', "ChamiloLMSCoreBundle:SimpleMenuBuilder:mainMenu");
        $menu->setSetting('safe_labels', true);
        $menu->setPosition(3);
        $menu->setEnabled(true);
        $menu->setPage($global);

        $global->addBlocks($footer = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $global,
            'code'    => 'footer'
        ), function ($container) {
            $container->setSetting('layout', '<div class="row page-footer well">{{ CONTENT }}</div>');
        }));

        $footer->setName('The footer container');

        // Footer : add 3 children block containers (left, center, right)
        $footer->addChildren($footerLeft = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $global,
            'code'    => 'content'
        ), function ($container) {
            $container->setSetting('layout', '<div class="col-sm-3">{{ CONTENT }}</div>');
        }));

        $footer->addChildren($footerLinksLeft = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $global,
            'code'    => 'content',
        ), function ($container) {
            $container->setSetting('layout', '<div class="col-sm-2 col-sm-offset-3">{{ CONTENT }}</div>');
        }));

        $footer->addChildren($footerLinksCenter = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $global,
            'code'    => 'content'
        ), function ($container) {
            $container->setSetting('layout', '<div class="col-sm-2">{{ CONTENT }}</div>');
        }));

        $footer->addChildren($footerLinksRight = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $global,
            'code'    => 'content'
        ), function ($container) {
            $container->setSetting('layout', '<div class="col-sm-2">{{ CONTENT }}</div>');
        }));

        // Footer left: add a simple text block
        $footerLeft->addChildren($text = $blockManager->create());

        $text->setType('sonata.block.service.text');
        $text->setSetting('content', '<h2>Sonata Demo</h2><p class="handcraft">HANDCRAFTED IN PARIS<br />WITH MIXED HERITAGE</p><p><a href="http://twitter.com/sonataproject" target="_blank">Follow Sonata on Twitter</a></p>');

        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($global);

        // Footer left links
        $footerLinksLeft->addChildren($text = $blockManager->create());

        $text->setType('sonata.block.service.text');
        $text->setSetting('content', <<<CONTENT
<h4>PRODUCT</h4>
<ul class="links">
    <li><a href="/bundles">Sonata</a></li>
    <li><a href="/api-landing">API</a></li>
    <li><a href="/faq">FAQ</a></li>
</ul>
CONTENT
        );

        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($global);

        // Footer middle links
        $footerLinksCenter->addChildren($text = $blockManager->create());

        $text->setType('sonata.block.service.text');
        $text->setSetting('content', <<<CONTENT
<h4>ABOUT</h4>
<ul class="links">
    <li><a href="http://www.sonata-project.org/about" target="_blank">About Sonata</a></li>
    <li><a href="/legal-notes">Legal notes</a></li>
    <li><a href="/shop/payment/terms-and-conditions">Terms</a></li>
</ul>
CONTENT
        );

        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($global);

        // Footer right links
        $footerLinksRight->addChildren($text = $blockManager->create());

        $text->setType('sonata.block.service.text');
        $text->setSetting('content', <<<CONTENT
<h4>COMMUNITY</h4>
<ul class="links">
    <li><a href="/blog">Blog</a></li>
    <li><a href="http://www.github.com/sonata-project" target="_blank">Github</a></li>
    <li><a href="/contact-us">Contact us</a></li>
</ul>
CONTENT
        );

        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($global);

        $pageManager->save($global);
    }

    /**
     * @return \Sonata\PageBundle\Model\SiteManagerInterface
     */
    public function getSiteManager()
    {
        return $this->container->get('sonata.page.manager.site');
    }

    /**
     * @return \Sonata\PageBundle\Model\PageManagerInterface
     */
    public function getPageManager()
    {
        return $this->container->get('sonata.page.manager.page');
    }

    /**
     * @return \Sonata\BlockBundle\Model\BlockManagerInterface
     */
    public function getBlockManager()
    {
        return $this->container->get('sonata.page.manager.block');
    }

    /**
     * @return \Faker\Generator
     */
    public function getFaker()
    {
        return $this->container->get('faker.generator');
    }

    /**
     * @return \Sonata\PageBundle\Entity\BlockInteractor
     */
    public function getBlockInteractor()
    {
        return $this->container->get('sonata.page.block_interactor');
    }
}
