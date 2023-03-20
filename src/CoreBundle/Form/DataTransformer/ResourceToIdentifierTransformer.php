<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Form\DataTransformer;

use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Webmozart\Assert\Assert;

final class ResourceToIdentifierTransformer implements DataTransformerInterface
{
    private ObjectRepository $repository;

    private string $identifier;

    public function __construct(ObjectRepository $repository, ?string $identifier = null)
    {
        $this->repository = $repository;
        $this->identifier = $identifier ?? 'id';
    }

    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        /* @psalm-suppress ArgumentTypeCoercion */
        Assert::isInstanceOf($value, $this->repository->getClassName());

        return PropertyAccess::createPropertyAccessor()->getValue($value, $this->identifier);
    }

    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        $resource = $this->repository->findOneBy([
            $this->identifier => $value,
        ]);
        if (null === $resource) {
            throw new TransformationFailedException(sprintf('Object "%s" with identifier "%s"="%s" does not exist.', $this->repository->getClassName(), $this->identifier, $value));
        }

        return $resource;
    }
}
