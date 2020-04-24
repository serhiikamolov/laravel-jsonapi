# laravel-jsonapi

<p align="center">
    <a href="https://packagist.org/packages/serhiikamolov/laravel-jsonapi"><img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/serhiikamolov/laravel-jsonapi.svg?style=flat-square"></a>
    <a href="https://opensource.org/licenses/MIT"><img alt="License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square"></a>
</p>

### Custom error handler

Go to bootstrap/app.php and define the custom error handler 
instead of the default one
  
    $app->singleton(
       Illuminate\Contracts\Debug\ExceptionHandler::class,
       \serhiikamolov\Laravel\JsonApi\Exceptions\Handler::class
    );
     
     
### Request validation class
It's actually a simple extension of the FormRequest class that returns the validation errors in compatible form with the json API standard

    namespace App\Http\Requests\Auth;
    
    use \serhiikamolov\Laravel\JsonApi\Contracts\Request;

    class LoginRequest extends Request
    {
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

Usage of the request validation in the controller:

    namespace App\Http\Controllers;
    
    use serhiikamolov\Laravel\JsonApi\Response\Response;
    use App\Http\Requests\Auth\LoginRequest;
    use Illuminate\Http\JsonResponse;
    
    class AuthController extends Controller
    {
        /**
         * Request a JWT token
         *
         * @param LoginRequest $request
         * @param Response $response
         * @return JsonResponse
         */
        public function login(LoginRequest $request, Response  $response):JsonResponse
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

### Serializer

