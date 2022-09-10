<?php

namespace Bigyohann\DtoBundle\ParamConverter;

use Bigyohann\DtoBundle\Dto\DtoInterface;
use Bigyohann\DtoBundle\Exception\DtoValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DtoParamConverter implements ParamConverterInterface
{


    public function __construct(private readonly SerializerInterface $serializer, private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @inheritDoc
     * @throws DtoValidationException
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        if ($request->getContentType() !== 'json') {
            return false;
        }

        $data = $request->getContent();
        if (empty($data)) {
            throw new DtoValidationException('No body provided', []);
        }
        $objectDto = new ($configuration->getClass())();

        try {
            $data = $this->serializer->deserialize(
                $data,
                $configuration->getClass(),
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
        $request->attributes->set($configuration->getName(), $objectDto);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function supports(ParamConverter $configuration): bool
    {
        return is_subclass_of($configuration->getClass(), DtoInterface::class);
    }

}