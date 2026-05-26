<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class UploadedFileValidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UploadFilenamePolicy $policy
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Run early so every controller/action sees sanitized files.
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->files->count() <= 0) {
            return;
        }

        $files = $request->files->all();
        $sanitized = $this->sanitizeRecursive($files);

        $request->files->replace($sanitized);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function sanitizeRecursive($value)
    {
        if ($value instanceof UploadedFile) {
            return $this->sanitizeUploadedFile($value);
        }

        // FileBag may contain null for UPLOAD_ERR_NO_FILE after conversion.
        // We must not re-insert null back into FileBag::replace().
        if (null === $value) {
            return null;
        }

        if (\is_array($value)) {
            $out = [];

            foreach ($value as $k => $v) {
                $san = $this->sanitizeRecursive($v);

                // Drop nulls entirely, otherwise FileBag->replace() will throw.
                if (null === $san) {
                    continue;
                }

                $out[$k] = $san;
            }

            return $out;
        }

        // Anything else inside Request::files is invalid; drop it.
        return null;
    }

    private function sanitizeUploadedFile(UploadedFile $file): UploadedFile
    {
        // Keep invalid uploads untouched; controllers usually handle isValid()/getError().
        if (!$file->isValid()) {
            return $file;
        }

        $originalName = (string) $file->getClientOriginalName();
        $decision = $this->policy->filter($originalName);

        if (!($decision['allowed'] ?? false)) {
            throw new BadRequestHttpException('File upload rejected by extension policy.');
        }

        $safeName = (string) ($decision['filename'] ?? $originalName);
        if ($safeName === $originalName) {
            return $file;
        }

        // Preserve "test mode" semantics for synthetic UploadedFile objects.
        $path = $file->getPathname();
        $isRealUpload = @is_uploaded_file($path);
        $test = !$isRealUpload;

        $mimeType = $file->getMimeType();
        $error = $file->getError();

        return new UploadedFile($path, $safeName, $mimeType, $error, $test);
    }
}
