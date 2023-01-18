<?php

namespace Bigyohann\DtoBundle\ValueResolver;

use Bigyohann\DtoBundle\Dto\DtoInterface;
use Bigyohann\DtoBundle\Exception\DtoValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DtoValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface  $validator
    )
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();

        if (!is_subclass_of($argumentType, DtoInterface::class, true) || $request->getContentTypeFormat() !== 'json') {
            return [];
        }

        $data = $request->getContent();
        if (empty($data)) {
            throw new DtoValidationException('No body provided', []);
        }
        $objectDto = new ($argument->getType())();

        try {
            $data = $this->serializer->deserialize(
                $data,
                $argumentType,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $objectDto,
                    DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,]
            );
        } catch (PartialDenormalizationException $e) {
            $violations = new ConstraintViolationList();
            /** @var NotNormalizableValueException $exception */
            foreach ($e->getErrors() as $exception) {
                $message = sprintf('The type must be one of "%s" ("%s" given).',
                    implode(', ', $exception->getExpectedTypes()),
                    $exception->getCurrentType());
                $parameters = [];
                if ($exception->canUseMessageForUser()) {
                    $parameters['hint'] = $exception->getMessage();
                }
                $violations->add(new ConstraintViolation($message,
                    '',
                    $parameters,
                    null,
                    $exception->getPath(),
                    null));
            }
            throw new DtoValidationException('Error from request', $violations);
        }

        $errors = $this->validator->validate($data);
        if (count($errors) > 0) {
            throw new DtoValidationException('Error from request', $errors);
        }
        return [$objectDto];

    }

}