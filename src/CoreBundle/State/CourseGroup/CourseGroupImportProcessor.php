<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupImport;
use Import;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<mixed, CourseGroupImport>
 */
final readonly class CourseGroupImportProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseGroupImport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        $file = $request->files->get('file');
        if (null === $file || !$file->isValid()) {
            throw new BadRequestHttpException('A valid CSV file is required.');
        }
        $rows = Import::csv_reader($file->getPathname());
        if (!\is_array($rows)) {
            throw new BadRequestHttpException('The CSV file is invalid.');
        }
        $result = $this->manager->import($rows, $request->request->getBoolean('deleteMissing'));
        $resource = new CourseGroupImport();
        $resource->success = true;
        $resource->message = 'Import completed';
        $resource->result = $result;

        return $resource;
    }
}
