<?php
namespace JsonApi\Contracts;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\App;
use JsonApi\Response\Response;

abstract class Request extends \Illuminate\Foundation\Http\FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize():bool
    {
        return true;
    }

    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator) {
        $response = App::make(Response::class);
        throw new HttpResponseException(
            $response->error(
                Response::HTTP_BAD_REQUEST,
                $validator->errors()->getMessages()
            )
        );
    }
}
