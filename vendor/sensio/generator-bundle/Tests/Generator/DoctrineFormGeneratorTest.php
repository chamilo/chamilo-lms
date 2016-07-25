<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Tests\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\DoctrineFormGenerator;

class DoctrineFormGeneratorTest extends GeneratorTest
{
    public function testGenerate()
    {
        $this->generateForm(false);

        $this->assertTrue(file_exists($this->tmpDir.'/Form/PostType.php'));

        $content = file_get_contents($this->tmpDir.'/Form/PostType.php');
        $this->assertContains('->add(\'title\')', $content);
        $this->assertContains('->add(\'createdAt\', \'date\')', $content);
        $this->assertContains('->add(\'publishedAt\', \'time\')', $content);
        $this->assertContains('->add(\'updatedAt\', \'datetime\')', $content);
        $this->assertContains('class PostType extends AbstractType', $content);
        $this->assertContains("'data_class' => 'Foo\BarBundle\Entity\Post'", $content);

        if (!method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $this->assertContains('getName', $content);
            $this->assertContains("'foo_barbundle_post'", $content);
        } else {
            $this->assertNotContains('getName', $content);
            $this->assertNotContains("'foo_barbundle_post'", $content);
        }
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp: Unable to generate the PostType form class as it already exists under the .* file
     */
    public function testNonOverwrittenForm()
    {
        $this->generateForm(false);
        $this->generateForm(false);
    }

    public function testOverwrittenForm()
    {
        $this->generateForm(false);
        $this->generateForm(true);

        $this->assertTrue(file_exists($this->tmpDir.'/Form/PostType.php'));
    }

    private function generateForm($overwrite)
    {
        $generator = new DoctrineFormGenerator($this->filesystem);
        $generator->setSkeletonDirs(__DIR__.'/../../Resources/skeleton');

        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())->method('getPath')->will($this->returnValue($this->tmpDir));
        $bundle->expects($this->any())->method('getNamespace')->will($this->returnValue('Foo\BarBundle'));

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')->disableOriginalConstructor()->getMock();
        $metadata->identifier = array('id');
        $metadata->fieldMappings = array(
            'title' => array('type' => 'string'),
            'createdAt' => array('type' => 'date'),
            'publishedAt' => array('type' => 'time'),
            'updatedAt' => array('type' => 'datetime'),
        );
        $metadata->associationMappings = $metadata->fieldMappings;

        $generator->generate($bundle, 'Post', $metadata, $overwrite);
    }
}
