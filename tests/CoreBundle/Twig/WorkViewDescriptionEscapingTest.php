<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Twig;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

/**
 * Regression test for the assignment review page.
 *
 * The review page turns autoescape off for its whole body and shows the description written by the
 * learner who sent the work. That description is learner input: course staff opening it must not run
 * its active content, while the plain text and the permitted formatting must still be shown.
 */
final class WorkViewDescriptionEscapingTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testLearnerDescriptionStaysInertOnTheReviewPage(): void
    {
        $html = $this->renderReviewPage(
            '<p>Plain <strong>text</strong></p>'
            .'<script>document.documentElement.dataset.x = 1</script>'
            .'<img src="x" onerror="alert(1)">'
            .'<a href="javascript:alert(2)">link</a>'
        );

        $this->assertStringContainsString('Plain <strong>text</strong>', $html, 'The permitted formatting is kept.');
        $this->assertStringNotContainsString('<script>document.documentElement', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    private function renderReviewPage(string $description): string
    {
        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());

        // The legacy helpers used by the filters read the language from the session of the request.
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);

        $work = (new CStudentPublication())
            ->setTitle('Submission')
            ->setDescription($description)
        ;

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        return $twig->render('@ChamiloCore/Work/view.html.twig', [
            'work' => $work,
            'score_label' => '-',
            'is_reviewed' => false,
            'download_url' => '',
            'correction_url' => '',
            'delete_correction_url' => '',
        ]);
    }
}
