<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Form\DataTransformer;

use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @template-implements DataTransformerInterface<object, int|string>
 */
final class ResourceToIdentifierTransformer implements DataTransformerInterface
{
    private ObjectRepository $repository;

    private string $identifier;

    public function __construct(ObjectRepository $repository, ?string $identifier = null)
    {
        $this->repository = $repository;
        $this->identifier = $identifier ?? 'id';
    }

    public function transform($value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (\is_object($value) && method_exists($value, 'getId')) {
            return $value;
        }

        if (is_numeric($value)) {
            return $this->repository->find($value);
        }

        return $value;
    }

    public function reverseTransform($value): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_object($value) && method_exists($value, 'getId')) {
            return $value;
        }

        $resource = $this->repository->find($value);
        if (null === $resource) {
            throw new TransformationFailedException(\sprintf('Object "%s" with identifier "%s" does not exist.', $this->repository->getClassName(), $value));
        }

        return $resource;
    }
}
