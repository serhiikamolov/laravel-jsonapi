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

## Installation
This package is very easy to set up via Composer, just run from your project's root folder:
```
composer require serhiikamolov/laravel-jsonapi
```

## Custom error handler

In order to get the errors list in json API format go to bootstrap/app.php and redefine the custom error handler 
instead of the default one.
```  
    $app->singleton(
       Illuminate\Contracts\Debug\ExceptionHandler::class,
       \JsonAPI\Exceptions\Handler::class
    );
```
Example of a response with an error:
```
{
    "jsonapi": {
        "version": "1.0"
    },
    "links": {
        "self": "http://127.0.0.1/api/v1/auth/login"
    },
    "errors": [
        "Some internal exception"
    ],
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

Example of a controller with request validation:

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
        
Validation result:

    {
        "jsonapi": {
            "version": "1.0"
        },
        "links": {
            "self": "http://127.0.0.1/api/v1/auth/login"
        },
        "errors": {
            "email": [
                "The email field is required."
            ]
        }
    }

## Response class
`JsonAPI\Response\Response` is an exension of the `JsonResponse` class with some additional methods.

**Add additional values to the links object of the response**
```
$response->links($array)
```

**Return error with the specific code in the response**
```
$response->error($statusCode, $message)  
```

**Return response with JWT token**
```
$response->token($token, $type = 'bearer', $expires = null)
```

**Return a custom data object in the response**
```
$response->data($array)
```

**Attach a field to the response's data object**
```
$response->attach($key, $value)
```

**Add a debug information to the response object**
```
$response->debug($array)
```

**Add a meta data to the response object**
```
$response->meta($array, $key = 'meta')
```

**Serialize an eloquent collection or a data model**
```
$response->serialize($collection, $serializer = new Serializer())
```

**Paginate a data array in the response**
```
$response->serialize($collection)->paginate()
```

**Add a specific status code to the response**
```
$response->code($statusCode)
```

`Response` class is an implementation of the Builder pattern thus you can use diffrent methods in a row:

```
public function login(LoginRequest $request, Response $response): Response
{
    ...
    return $response
        ->token((string)$token)
        ->attach('uuid', Auth::guard('api')->user()->uuid);
}
``` 

Response result:
```
{
    "jsonapi": {
        "version": "1.0"
    },
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
  
#### Declaring serializers
Let's create a simple serializer class extended from the `JsonAPI\Response\Serializer` class.
There is a public method `fields()` which returns an array, and define a set of fields which will be retrieved from the given Collection or Model 
and put to the response.
```
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

#### Using serializer in Controller
There is `serialize` method in the `JsonAPI\Response\Response` class which accept a Serializer instance as a second parameter.

```
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
```
{
    "jsonapi": {
        "version": "1.0"
    },
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
```

#### Field modifiers  
Field modifiers can be applied to every field defined in serializer. Although, there are a few predefined modifiers: `timestamp`, `number`, `trim`
you can define your own modifier by creating a `protected` method with the `modifier` prefix in its name.
Also you can use another serializing class as a modifier, it can be useful when you have some related data to the original model.  
    
```
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
     * Define custom modifier which will transform user id from number to md5 hash.
     * @param int|null $value
     * @return int
     */
    protected function modifierMd5(?int $value): string
    {
        return md5($value);
    }
}
```