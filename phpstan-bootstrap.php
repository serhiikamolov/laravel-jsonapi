<?php

namespace {
    use Illuminate\Contracts\Routing\UrlGenerator;
    use Illuminate\Http\Request;

    if (!function_exists('config')) {
        function config(array|string|null $key = null, mixed $default = null): mixed
        {
            return $default;
        }
    }

    if (!function_exists('request')) {
        function request(array|string|null $key = null, mixed $default = null): mixed
        {
            return $default;
        }
    }

    if (!function_exists('url')) {
        function url(?string $path = null, mixed $parameters = [], ?bool $secure = null): UrlGenerator|string
        {
            return '';
        }
    }
}

namespace Illuminate\Foundation\Http {
    use Illuminate\Http\Request;
    use Illuminate\Contracts\Validation\Validator;

    abstract class FormRequest extends Request
    {
        abstract public function rules(): array;

        protected function failedValidation(Validator $validator): void
        {
        }
    }
}

namespace Illuminate\Foundation\Exceptions {
    use Illuminate\Http\JsonResponse;
    use Throwable;

    class Handler
    {
        public function report(Throwable $exception): void
        {
        }

        protected function isHttpException(Throwable $exception): bool
        {
            return false;
        }

        protected function convertExceptionToArray(Throwable $exception): array
        {
            return [];
        }

        protected function prepareJsonResponse($request, Throwable $e): JsonResponse
        {
            return new JsonResponse();
        }
    }
}
