<?php

namespace Tests\Knp\DoctrineBehaviors\ORM;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

require_once 'EntityManagerProvider.php';

class TranslatableTest extends \PHPUnit_Framework_TestCase
{
    use EntityManagerProvider;

    protected function getUsedEntityFixtures()
    {
        return [
            'BehaviorFixtures\\ORM\\TranslatableEntity',
            'BehaviorFixtures\\ORM\\TranslatableEntityTranslation'
        ];
    }

    protected function getEventManager()
    {
        $em = new EventManager;

        $em->addEventSubscriber(new \Knp\DoctrineBehaviors\ORM\Translatable\TranslatableSubscriber(
            new ClassAnalyzer(),
            function()
            {
                return 'en';
            },
            function()
            {
                return 'en';
            },
            'Knp\DoctrineBehaviors\Model\Translatable\Translatable',
            'Knp\DoctrineBehaviors\Model\Translatable\Translation',
            'LAZY',
            'LAZY'
        ));

        return $em;
    }

    /**
     * @test
     */
    public function should_persist_translations()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TranslatableEntity();
        $entity->translate('fr')->setTitle('fabuleux');
        $entity->translate('en')->setTitle('awesome');
        $entity->translate('ru')->setTitle('удивительный');
        $entity->mergeNewTranslations();

        $em->persist($entity);
        $em->flush();
        $id = $entity->getId();
        $em->clear();

        $entity = $em
            ->getRepository('BehaviorFixtures\ORM\TranslatableEntity')
            ->find($id)
        ;

        $this->assertEquals(
            'fabuleux',
            $entity->translate('fr')->getTitle()
        );

        $this->assertEquals(
            'awesome',
            $entity->translate('en')->getTitle()
        );

        $this->assertEquals(
            'удивительный',
            $entity->translate('ru')->getTitle()
        );
    }

    /**
     * @test
     */
    public function should_fallback_country_locale_to_language_only_translation()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TranslatableEntity();
        $entity->translate('en', false)->setTitle('plastic bag');
        $entity->translate('fr', false)->setTitle('sac plastique');
        $entity->translate('fr_CH', false)->setTitle('cornet');
        $entity->mergeNewTranslations();

        $em->persist($entity);
        $em->flush();
        $id = $entity->getId();
        $em->clear();

        $entity = $em
            ->getRepository('BehaviorFixtures\ORM\TranslatableEntity')
            ->find($id)
        ;

        $this->assertEquals(
            'plastic bag',
            $entity->translate('de')->getTitle()
        );

        $this->assertEquals(
            'sac plastique',
            $entity->translate('fr_FR')->getTitle()
        );

        $this->assertEquals(
            'cornet',
            $entity->translate('fr_CH')->getTitle()
        );
    }

    /**
     * @test
     */
    public function should_update_and_add_new_translations()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TranslatableEntity();
        $entity->translate('en')->setTitle('awesome');
        $entity->translate('ru')->setTitle('удивительный');
        $entity->mergeNewTranslations();

        $em->persist($entity);
        $em->flush();
        $id = $entity->getId();
        $em->clear();

        $entity = $em
            ->getRepository('BehaviorFixtures\ORM\TranslatableEntity')
            ->find($id)
        ;

        $this->assertEquals(
            'awesome',
            $entity->translate('en')->getTitle()
        );

        $this->assertEquals(
            'удивительный',
            $entity->translate('ru')->getTitle()
        );

        $entity->translate('en')->setTitle('great');
        $entity->translate('fr', false)->setTitle('fabuleux');
        $entity->mergeNewTranslations();

        $em->persist($entity);
        $em->flush();
        $em->clear();

        $entity = $em
            ->getRepository('BehaviorFixtures\ORM\TranslatableEntity')
            ->find($id)
        ;

        $this->assertEquals(
            'great',
            $entity->translate('en')->getTitle()
        );

        $this->assertEquals(
            'fabuleux',
            $entity->translate('fr')->getTitle()
        );

        $this->assertEquals(
            'удивительный',
            $entity->translate('ru')->getTitle()
        );
    }

    /**
     * @test
     */
    public function translate_method_should_always_return_translation_object()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TranslatableEntity();

        $this->assertInstanceOf(
            'BehaviorFixtures\ORM\TranslatableEntityTranslation',
            $entity->translate('fr')
        );
    }

    /**
     * @test
     */
    public function subscriber_should_configure_entity_with_current_locale()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TranslatableEntity();
        $entity->setTitle('test'); // magic method
        $entity->mergeNewTranslations();
        $em->persist($entity);
        $em->flush();
        $id = $entity->getId();
        $em->clear();

        $entity = $em->getRepository('BehaviorFixtures\ORM\TranslatableEntity')->find($id);

        $this->assertEquals('en', $entity->getCurrentLocale());
        $this->assertEquals('test', $entity->getTitle());
        $this->assertEquals('test', $entity->translate($entity->getCurrentLocale())->getTitle());
    }

    /**
     * @test
     */
    public function subscriber_should_configure_entity_with_default_locale()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TranslatableEntity();
        $entity->setTitle('test'); // magic method
        $entity->mergeNewTranslations();
        $em->persist($entity);
        $em->flush();
        $id = $entity->getId();
        $em->clear();

        $entity = $em->getRepository('BehaviorFixtures\ORM\TranslatableEntity')->find($id);

        $this->assertEquals('en', $entity->getDefaultLocale());
        $this->assertEquals('test', $entity->getTitle());
        $this->assertEquals('test', $entity->translate($entity->getDefaultLocale())->getTitle());
        $this->assertEquals('test', $entity->translate('fr')->getTitle());
    }

    /**
     * @test
     */
    public function should_have_oneToMany_relation()
    {
        $this->assertTranslationsOneToManyMapped(
            'BehaviorFixtures\ORM\TranslatableEntity',
            'BehaviorFixtures\ORM\TranslatableEntityTranslation'
        );
    }

    /**
     * @test
     */
    public function should_have_oneToMany_relation_when_translation_class_name_is_custom()
    {
        $this->assertTranslationsOneToManyMapped(
            'BehaviorFixtures\ORM\TranslatableCustomizedEntity',
            'BehaviorFixtures\ORM\Translation\TranslatableCustomizedEntityTranslation'
        );
    }

    /**
     * @test
     */
    public function should_create_only_one_time_the_same_translation()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TranslatableEntity();
        $translation = $entity->translate('fr');
        $translation->setTitle('fabuleux');
        $entity->translate('fr')->setTitle('fabuleux2');
        $entity->translate('fr')->setTitle('fabuleux3');

        $this->assertEquals('fabuleux3', $entity->translate('fr')->getTitle());
        $this->assertEquals(spl_object_hash($entity->translate('fr')), spl_object_hash($translation));
    }

    /**
     * @test
     */
    public function should_remove_translation()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TranslatableEntity();
        $entity->translate('en')->setTitle('Hello');
        $entity->translate('nl')->setTitle('Hallo');
        $entity->mergeNewTranslations();
        $em->persist($entity);
        $em->flush();

        $nlTranslation = $entity->translate('nl');
        $entity->removeTranslation($nlTranslation);
        $em->flush();

        $em->refresh($entity);
        $this->assertNotEquals('Hallo', $entity->translate('nl')->getTitle());
    }

    /**
     * Asserts that the one to many relationship between translatable and translations is mapped correctly.
     *
     * @param string $translatableClass The class name of the translatable entity
     * @param string $translationClass  The class name of the translation entity
     */
    private function assertTranslationsOneToManyMapped($translatableClass, $translationClass)
    {
        $em = $this->getEntityManager();

        $meta = $em->getClassMetadata($translationClass);
        $this->assertEquals($translatableClass, $meta->getAssociationTargetClass('translatable'));

        $meta = $em->getClassMetadata($translatableClass);
        $this->assertEquals($translationClass, $meta->getAssociationTargetClass('translations'));

        $this->assertTrue($meta->isAssociationInverseSide('translations'));

        $this->assertEquals(
            ClassMetadataInfo::ONE_TO_MANY,
            $meta->getAssociationMapping('translations')['type']
        );
    }
}
