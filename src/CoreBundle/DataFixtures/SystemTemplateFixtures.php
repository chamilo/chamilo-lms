<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\SystemTemplate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class SystemTemplateFixtures extends Fixture
{
    /**
     * @return array<int, array{title: string, comment: string, content: string, language: string}>
     */
    public static function getDefaultTemplates(): array
    {
        return [
            [
                'title' => SystemTemplate::DEFAULT_CERTIFICATE_TITLE,
                'comment' => 'Modern default Gradebook certificate using the Chamilo 3 visual language.',
                'content' => self::getBundledCertificateTemplate(),
                'language' => '',
            ],
            [
                'title' => 'Simple document',
                'comment' => 'Clean page with a title, introduction, section and bullet list.',
                'content' => <<<'HTML'
<div style="max-width:960px;margin:0 auto;color:#444;font-family:Arial,Helvetica,sans-serif;">
    <div style="padding:28px 32px;background:#f7f9fc;border-left:6px solid #f26722;">
        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#203e61;font-weight:700;">Document</div>
        <h1 style="margin:8px 0 0;color:#203e61;font-size:34px;line-height:1.2;">Write your title here</h1>
        <p style="margin:12px 0 0;color:#6b7280;font-size:17px;line-height:1.6;">Use this space for a short introduction to the document.</p>
    </div>

    <div style="padding:32px;">
        <h2 style="margin:0 0 14px;color:#203e61;font-size:24px;">Section title</h2>
        <p style="margin:0 0 18px;line-height:1.7;">Replace this text with the main content of your document. Keep paragraphs short and use headings to make the information easier to scan.</p>

        <ul style="margin:0;padding-left:24px;line-height:1.8;">
            <li>First key idea</li>
            <li>Second key idea</li>
            <li>Third key idea</li>
        </ul>
    </div>
</div>
HTML,
                'language' => '',
            ],
            [
                'title' => 'Text with image',
                'comment' => 'Balanced two-column layout for explanatory text and an image.',
                'content' => <<<'HTML'
<div style="max-width:960px;margin:0 auto;color:#444;font-family:Arial,Helvetica,sans-serif;">
    <div style="padding:24px 28px;background:#203e61;color:#fff;">
        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#f4a06f;font-weight:700;">Topic</div>
        <h1 style="margin:8px 0 0;font-size:32px;line-height:1.2;color:#fff;">Write your title here</h1>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-top:24px;border-collapse:separate;border-spacing:18px;">
        <tr>
            <td style="width:55%;vertical-align:top;padding:6px;">
                <h2 style="margin:0 0 12px;color:#203e61;font-size:23px;">Section title</h2>
                <p style="margin:0;line-height:1.7;">Write the explanatory text here. This layout works well for examples, product explanations, course concepts and visual summaries.</p>
                <p style="margin:16px 0 0;line-height:1.7;">Add another paragraph when more context is needed.</p>
            </td>
            <td style="width:45%;vertical-align:middle;">
                <div style="min-height:260px;padding:32px;border:2px dashed #cbd5df;background:#f7f9fc;text-align:center;color:#7b8794;">
                    <div style="margin-top:72px;font-size:13px;letter-spacing:1px;text-transform:uppercase;font-weight:700;">Insert image here</div>
                    <div style="margin-top:8px;font-size:13px;">Use the editor image tool to replace this placeholder.</div>
                </div>
            </td>
        </tr>
    </table>
</div>
HTML,
                'language' => '',
            ],
            [
                'title' => 'Two-column document',
                'comment' => 'Two equal columns for comparisons, parallel topics or compact summaries.',
                'content' => <<<'HTML'
<div style="max-width:960px;margin:0 auto;color:#444;font-family:Arial,Helvetica,sans-serif;">
    <div style="padding:24px 28px;border-bottom:4px solid #f26722;">
        <h1 style="margin:0;color:#203e61;font-size:32px;line-height:1.2;">Write your title here</h1>
        <p style="margin:10px 0 0;color:#6b7280;line-height:1.6;">Introduce the two topics or perspectives below.</p>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-top:24px;border-collapse:separate;border-spacing:18px;">
        <tr>
            <td style="width:50%;vertical-align:top;padding:24px;background:#f7f9fc;border-top:4px solid #203e61;">
                <h2 style="margin:0 0 12px;color:#203e61;font-size:22px;">First column</h2>
                <p style="margin:0;line-height:1.7;">Write the first block of content here.</p>
            </td>
            <td style="width:50%;vertical-align:top;padding:24px;background:#f7f9fc;border-top:4px solid #f26722;">
                <h2 style="margin:0 0 12px;color:#203e61;font-size:22px;">Second column</h2>
                <p style="margin:0;line-height:1.7;">Write the second block of content here.</p>
            </td>
        </tr>
    </table>
</div>
HTML,
                'language' => '',
            ],
            [
                'title' => 'Key points',
                'comment' => 'Structured page for objectives, takeaways, steps or important concepts.',
                'content' => <<<'HTML'
<div style="max-width:960px;margin:0 auto;color:#444;font-family:Arial,Helvetica,sans-serif;">
    <div style="padding:24px 28px;background:#f7f9fc;">
        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#f26722;font-weight:700;">Key points</div>
        <h1 style="margin:8px 0 0;color:#203e61;font-size:32px;">What should the learner remember?</h1>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-top:22px;border-collapse:separate;border-spacing:0 14px;">
        <tr>
            <td style="width:64px;vertical-align:top;padding:18px;text-align:center;background:#203e61;color:#fff;font-size:22px;font-weight:700;">1</td>
            <td style="padding:18px 22px;background:#f7f9fc;">
                <strong style="color:#203e61;">First key point</strong>
                <div style="margin-top:6px;line-height:1.6;">Explain the idea in one or two concise sentences.</div>
            </td>
        </tr>
        <tr>
            <td style="width:64px;vertical-align:top;padding:18px;text-align:center;background:#f26722;color:#fff;font-size:22px;font-weight:700;">2</td>
            <td style="padding:18px 22px;background:#f7f9fc;">
                <strong style="color:#203e61;">Second key point</strong>
                <div style="margin-top:6px;line-height:1.6;">Use this area for another important concept or step.</div>
            </td>
        </tr>
        <tr>
            <td style="width:64px;vertical-align:top;padding:18px;text-align:center;background:#203e61;color:#fff;font-size:22px;font-weight:700;">3</td>
            <td style="padding:18px 22px;background:#f7f9fc;">
                <strong style="color:#203e61;">Third key point</strong>
                <div style="margin-top:6px;line-height:1.6;">Finish with the final takeaway or recommended action.</div>
            </td>
        </tr>
    </table>
</div>
HTML,
                'language' => '',
            ],
            [
                'title' => 'Media with text',
                'comment' => 'Layout for a video or audio block followed by supporting text.',
                'content' => <<<'HTML'
<div style="max-width:960px;margin:0 auto;color:#444;font-family:Arial,Helvetica,sans-serif;">
    <div style="padding:24px 28px;background:#203e61;color:#fff;">
        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#f4a06f;font-weight:700;">Media lesson</div>
        <h1 style="margin:8px 0 0;font-size:32px;color:#fff;">Write your title here</h1>
    </div>

    <div style="margin-top:24px;min-height:320px;padding:40px;border:2px dashed #b8c4d1;background:#f7f9fc;text-align:center;color:#6b7280;">
        <div style="margin-top:92px;font-size:14px;letter-spacing:1.4px;text-transform:uppercase;color:#203e61;font-weight:700;">Insert audio or video here</div>
        <div style="margin-top:8px;font-size:13px;">Use the editor media tool to replace this placeholder.</div>
    </div>

    <div style="padding:24px 4px 0;">
        <h2 style="margin:0 0 10px;color:#203e61;font-size:22px;">Context or summary</h2>
        <p style="margin:0;line-height:1.7;">Add instructions, a transcript summary, reflection questions or supporting information for the media content.</p>
    </div>
</div>
HTML,
                'language' => '',
            ],
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->installDefaultTemplates($manager);
    }

    public function installDefaultTemplates(ObjectManager $manager): int
    {
        $repository = $manager->getRepository(SystemTemplate::class);
        $created = 0;

        foreach (self::getDefaultTemplates() as $definition) {
            $existing = $repository->findOneBy(['title' => $definition['title']]);
            if ($existing instanceof SystemTemplate) {
                continue;
            }

            $template = (new SystemTemplate())
                ->setTitle($definition['title'])
                ->setComment($definition['comment'])
                ->setContent($definition['content'])
                ->setLanguage($definition['language'])
            ;

            $manager->persist($template);
            ++$created;
        }

        if ($created > 0) {
            $manager->flush();
        }

        return $created;
    }

    private static function getBundledCertificateTemplate(): string
    {
        $path = dirname(__DIR__, 3).'/public/main/gradebook/certificate_template/template.html';
        $content = file_get_contents($path);

        if (false === $content || '' === trim($content)) {
            throw new RuntimeException('The bundled Gradebook certificate template could not be loaded.');
        }

        return $content;
    }
}
