<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Component\Mpdf\SafeMpdfHttpClient;
use Chamilo\CoreBundle\Entity\Transcript;
use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Writer\PngWriter;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\Environment;

/**
 * Renders transcripts as PDF. Every image (logo, signature, QR code) is embedded as a data URI,
 * so mPDF fetches nothing.
 */
final readonly class TranscriptPdfRenderer
{
    public function __construct(
        private Environment $twig,
        private ThemeHelper $themeHelper,
        private TranscriptSignatureImage $signatureImage,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $transcript the data of TranscriptBuilder::build()
     */
    public function renderUnofficial(array $transcript): string
    {
        return $this->render([
            'transcript' => $transcript,
            'official' => false,
        ]);
    }

    public function renderOfficial(Transcript $transcript, string $verificationUrl): string
    {
        return $this->render([
            'transcript' => $transcript->getPayload(),
            'official' => true,
            'revoked' => $transcript->isRevoked(),
            'signatureImage' => $this->signatureImage->getDataUri($transcript->getUrl()),
            'verificationCode' => $transcript->getToken(),
            'verificationUrl' => $verificationUrl,
            'qrCode' => $this->buildQrCode($verificationUrl),
        ]);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(array $context): string
    {
        $html = $this->twig->render('@ChamiloCore/Transcript/transcript_pdf.html.twig', [
            ...$context,
            'logo' => $this->getLogo(),
        ]);

        $mpdf = new Mpdf([
            'format' => 'A4',
            'mode' => 'utf-8',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'tempDir' => api_get_path(SYS_ARCHIVE_PATH).'mpdf/',
        ], SafeMpdfHttpClient::container());
        $mpdf->SetTitle((string) ($context['transcript']['student']['fullName'] ?? 'Transcript'));
        if (!$context['official'] || ($context['revoked'] ?? false)) {
            $mpdf->SetWatermarkText($context['official'] ? 'REVOKED' : 'UNOFFICIAL', 0.08);
            $mpdf->showWatermarkText = true;
        }
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function getLogo(): string
    {
        // mPDF renders PNG more reliably than SVG.
        foreach (['images/email-logo.png', 'images/header-logo.png', 'images/header-logo.svg'] as $path) {
            $logo = $this->themeHelper->getAssetBase64Encoded($path);
            if ('' !== $logo) {
                return $logo;
            }
        }

        return '';
    }

    private function buildQrCode(string $url): string
    {
        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($url)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(180)
                ->margin(5)
                ->build()
            ;

            return 'data:image/png;base64,'.base64_encode($result->getString());
        } catch (Throwable $exception) {
            $this->logger->warning('Unable to generate the transcript QR code.', ['exception' => $exception]);

            return '';
        }
    }
}
