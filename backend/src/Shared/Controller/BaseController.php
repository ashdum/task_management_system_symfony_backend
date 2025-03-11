<?php
namespace App\Shared\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseController
{
    protected SerializerInterface $serializer;
    protected ValidatorInterface $validator;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    protected function deserializeAndValidate(Request $request, string $dtoClass): object
    {
        $dto = $this->serializer->deserialize($request->getContent(), $dtoClass, 'json');
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException((string) $errors);
        }

        return $dto;
    }

    protected function jsonResponse($data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status);
    }
}