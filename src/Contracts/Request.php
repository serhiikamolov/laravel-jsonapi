<?php
namespace JsonAPI\Contracts;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use JsonAPI\Response\Response;

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
     * Get the values of the validated fields only
     * @return array
     */
    public function values():array
    {
        return Arr::only($this->input(), array_keys($this->rules()));
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
