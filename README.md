# laravel-jsonapi

### Override default error handler

Go to bootstrap/app.php and define the custom error handler 
instead of the default one
  
       $app->singleton(
           Illuminate\Contracts\Debug\ExceptionHandler::class,
           \serhiikamolov\Laravel\JsonApi\Exceptions\Handler::class
      );
      
