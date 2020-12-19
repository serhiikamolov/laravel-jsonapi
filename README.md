# Laravel JsonAPI

A set of interfaces and classes to facilitate the construction of an efficient JSON:API application with the Laravel framework.

<p align="left">
    <a href="https://packagist.org/packages/serhiikamolov/laravel-jsonapi"><img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/serhiikamolov/laravel-jsonapi.svg?style=flat-square"></a>
    <a href="https://opensource.org/licenses/MIT"><img alt="License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square"></a>
</p>

#### Table of contents
- [Installation](#installation)
- [Custom error handler](#custom-error-handler)
- [Request validation class](#request-validation-class)
- [Response class](#response-class)
- [Serializer class](#serializer-class)
    - [Declaring Serializers](#declaring-serializers)
    - [Using Model Serializers](#using-model-serializers)
    - [Using Field Modifiers](#using-field-modifiers)
- [Expand Response with the Queries Log](#expand-response-with-the-queries-log)
- [Testing API Responses](#testing-api-responses)

## Installation
This package is very easy to set up via Composer, just run from your project's root folder:
```bash
composer require serhiikamolov/laravel-jsonapi
```

## Custom error handler

In order to make errors output compatible with json API format go to `bootstrap/app.php` and register the custom error handler.
```php  
$app->singleton(
   Illuminate\Contracts\Debug\ExceptionHandler::class,
   \JsonAPI\Exceptions\Handler::class
);
```
Or just extend default `App\Exceptions\Handler` from `JsonAPI\Exceptions\Handler` class.

Now, in case of an exception you will get such a response:
```json
{
    "links": {
        "self": "http://127.0.0.1/api/v1/auth/login"
    },
    "errors": {
        "exception": [
            "Some internal exception"
        ]
    },
    "debug": {
        "message": "Some internal exception",
        "exception": "Exception",
        "file": "/code/app/Http/Controllers/AuthController.php",
        "line": 29,
        "trace": [...]
    }
}
```
     
## Request validation class
`\JsonAPI\Contracts\Request` is a simple extension of the `FormRequest` class that returns the validation errors in compatible with the json API form

```php
namespace App\Http\Requests\Auth;

use \JsonAPI\Contracts\Request;

class LoginRequest extends Request
{

    public function messages()
    {
        return [
            'email.required'  => 'Значення e-mail не може бути порожнім',
            'email.email'   => 'Значення e-mail не відповідає формату електронної пошти',
            'email.max'     => 'Значення e-mail не має бути таким довгим',
        ];
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules():array
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|string',
        ];
    }

}
```

Example of a controller with request validation:

```php
namespace App\Http\Controllers;

use JsonAPI\Response\Response;
use App\Http\Requests\Auth\LoginRequest;

class AuthController extends Controller
{
    /**
     * Request a JWT token
     *
     * @param LoginRequest $request
     * @param Response $response
     * @return JsonResponse
     */
    public function login(LoginRequest $request, Response  $response):Response
    {
        // validation is passed, you can check the user credentials now
        // and generate a JWT token 
    }      
}
```
        
Response with validation errors:
```json
{
    "links": {
        "self": "http://127.0.0.1/api/v1/auth/login"
    },
    "errors": {
        "email": [
            "The email field is required."
        ]
    }
}
```

## Response class
`JsonAPI\Response\Response` is an exension of the `JsonResponse` class with some additional methods.

**Add additional values to the links object of the response**
```php
$response->links($array)
```

**Return error with the specific code in the response**
```php
$response->error($statusCode, $message)  
```

**Return response with JWT token**
```php
$response->token($token, $type = 'bearer', $expires = null)
```

**Return a custom data object in the response**
```php
$response->data($array)
```

**Attach a field to the response's data object**
```php
$response->attach($key, $value)
```

**Add a debug information to the response object**
```php
$response->debug($array)
```

**Add a meta data to the response object**
```php
$response->meta($array, $key = 'meta')
```

**Serialize an eloquent collection or a data model**
```php
$response->serialize($collection, $serializer = new Serializer())
```

**Paginate a data array in the response**
```php
$response->serialize($collection)->paginate()
```

**Add a specific status code to the response**
```php
$response->code($statusCode)
```

`Response` class is an implementation of the Builder pattern thus you can use diffrent methods in a row:

```php
public function login(LoginRequest $request, Response $response): Response
{
    ...
    return $response
        ->token((string)$token)
        ->attach('uuid', Auth::guard('api')->user()->uuid);
}
``` 

Response result:
```json
{
    "links": {
        "self": "http://127.0.0.1/api/v1/auth/login"
    },
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjFcL2FwaVwvdjFcL2F1dGhcL2xvZ2luIiwiaWF0IjoxNjA4Mzc0NTAyLCJleHAiOjE2MDgzNzQ1NjIsIm5iZiI6MTYwODM3NDUwMiwianRpIjoiUXRZWnpUeEhYajJyaGxHaCIsInN1YiI6MiwicHJ2IjoiY2U2ZTY0NDI4OTkwYjk4NWJjZTQ2OGZjNTlmZTUxYzNiZTljN2ZhZCJ9.PNF2-5nswNqaWS4WS1D_gemBD3IyPJVKXJohNF8mMUY",
        "token_type": "bearer",
        "expires_in": 60,
        "uuid": "40e831349e594d8e944478a243ff463f"
    }
}
```

## Serializer class
Serializers allow complex data such as Eloquent Collections and Model instances to be converted to a simple array that can be easily rendered into JSON.
Serializer class gives you a powerful, generic way to control the output of your responses.
  
#### Declaring Serializers
Let's create a simple serializer class extended from the `JsonAPI\Response\Serializer` class.
There is a public method `fields()` which returns an array, and define a set of fields which will be retrieved from the given Collection or Model 
and put to the response.
```php
namespace App\Http\Serializers;

use JsonAPI\Response\Serializer;

class UserSerializer extends Serializer
{
    public function fields(): array
    {
        return [
            'id',    // take data from $user->id
            'name',  // take data from $user->name
            'email', // take data from $user->email
            'uuid'   // take data from the public method defined below
        ];
    }

    /**
    * Define a custom field 
    */
    public function uuid(Model $item): string
    {
        return md5($item->id);
    }
}
```
Here you can notice that `uuid` is not taken from the database but generated from the model data.
In such a way you can define any new field you need in the response or even override an existing field value.  

#### Using Model Serializers
There is `serialize` method in the `JsonAPI\Response\Response` class which accept a Serializer instance as a second parameter.

```php
class UsersController extends Controller
{
    /**
     * Get list of all users.
     *
     * @param Response $response
     * @return Response
     */
    public function read(Response $response): Response
    {
        $users = User::all();
        return $response->serialize($users, new UserSerializer());
    }
}
```
Response result
```json
{
    "links": {
        "self": "http://127.0.0.1/api/v1/users"
    },
    "data": [
        {
            "id": 1,
            "name": "admin",
            "email": "user@email.com",
            "uuid": "40e831349e594d8e944478a243ff463f"
        }
    ]
}
```

#### Using Field Modifiers  
Field modifiers can be applied to every field defined in serializer. Although, there are a few predefined modifiers: `timestamp`, `number`, `trim`
you can define your own modifier by creating a `protected` method with the `modifier` prefix in its name.
Also you can use another serializing class as a modifier, it can be useful when you have some related data to the original model.  

```php
class UserSerializer extends Serializer
{
    public function fields(): array
    {
        return [
            'id' => 'md5'                       // use custom modifier
            ...
            'created_at' => 'timestamp',        // use default modifier which 
                                                // transforms a Carbon date object 
                                                // into the unix timestamp number
            'roles' => RoleSerializer::class    // use a serializing class as a modifier
                                                // for the related data
        ];
    }

     /**
     * Define custom modifier which transforms user id to md5 hash.
     * @param int|null $value
     * @return int
     */
    protected function modifierMd5(?int $value): string
    {
        return md5($value);
    }
}
```

## Expand Response with the Queries Log
Enable `JsonAPI\Http\Middleware\QueryDebug` middleware and expand the `debug` section of a response with the information from the queries log.  

```php
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareGroups = [
        ...
        'api' => [
            ...
            \JsonAPI\Http\Middleware\QueryDebug::class                  
        ],
        ...
    ];
}
```

Response with the queries log.

```json
{
  ...
  "debug": {
    "queries": {
        "total": 2,
        "list": [
            {
                "query": "select * from `users` where `id` = ? limit 1",
                "bindings": [
                    2
                ],
                "time": 20.81
            },
            {
                "query": "select `roles`.*, `role_user`.`user_id` as `pivot_user_id`, `role_user`.`role_id` as `pivot_role_id`, `role_user`.`created_at` as `pivot_created_at`, `role_user`.`updated_at` as `pivot_updated_at` from `roles` inner join `role_user` on `roles`.`id` = `role_user`.`role_id` where `role_user`.`user_id` = ? and `roles`.`deleted_at` is null",
                "bindings": [
                    2
                ],
                "time": 73.97
            }
        ]
    }
  }
}
```

## Testing API Responses
Add `JsonAPI\Traits\Tests\JsonApiAsserts` trait to your default `TestCase` class and expand your tests with some useful asserts methods for testing API responses.

```php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use JsonAPI\Traits\Tests\JsonApiAsserts;

abstract class TestCase extends BaseTestCase
{
    use JsonApiAsserts;
}
```

Example of using additional asserts:

```php
    /**
     * Testing GET /api/v1/entries/<id>
     */
    public function test_read()
    {
        $response = $this->get("/api/v1/entries/1");

        // expecting to get response in JSON:API format and 
        // find "id", "value", "type", "active" fields within 
        // a response's data
        $this->assertJsonApiResponse($response, [
            "id",
            "value",
            "type",
            "active",
        ]);
    }
```