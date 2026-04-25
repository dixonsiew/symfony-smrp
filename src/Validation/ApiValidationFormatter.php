<?php

namespace App\Validation;

use Soleinjast\ValidationResponse\Formatter\FormatterInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ApiValidationFormatter implements FormatterInterface
{
    public function format(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors = [
                $violation->getMessage(),
            ];
        }

        return [
            'statusCode' => 400,
            'error' => 'Bad Request',
            'message' => $errors,
        ];
    }
}
