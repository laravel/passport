<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Exception;
use Throwable;
use Illuminate\Http\Response;
use Zend\Diactoros\Response as Psr7Response;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Config\Repository as Config;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Laravel\Passport\Http\Controllers\ConvertsPsrResponses;

class HandleOAuthErrors
{
    use ConvertsPsrResponses;

    /**
     * The configuration repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The exception handler instance.
     *
     * @var \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected $exceptionHandler;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $exceptionHandler
     * @return void
     */
    public function __construct(Config $config, ExceptionHandler $exceptionHandler)
    {
        $this->config = $config;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (OAuthServerException $e) {
            $this->exceptionHandler->report($e);

            return $this->convertResponse(
                $e->generateHttpResponse(new Psr7Response)
            );
        } catch (Exception $e) {
            $this->exceptionHandler->report($e);

            return new Response($this->config->get('app.debug') ? $e->getMessage() : 'Error.', 500);
        } catch (Throwable $e) {
            $this->exceptionHandler->report(new FatalThrowableError($e));

            return new Response($this->config->get('app.debug') ? $e->getMessage() : 'Error.', 500);
        }
    }
}
