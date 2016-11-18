<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Illuminate\Encryption\Encrypter;
use Laravel\Passport\ClientRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class HandlesGrantParameterInjections
{
    /**
     * The Client Repository instance.
     *
     * @var \Laravel\Passport\ClientRepository
     */
    protected $clients;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create a new middleware instance.
     *
     * @param  \Laravel\Passport\ClientRepository $clients
     * @param  \Illuminate\Encryption\Encrypter $encrypter
     * @return void
     */
    public function __construct(ClientRepository $clients, Encrypter $encrypter)
    {
        $this->clients = $clients;
        $this->encrypter = $encrypter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function handle($request, Closure $next)
    {
        if ($request->grant_type === 'password') {
            $client = $this->clients->find($request->client_id);

            if ($client === null) {
                throw (new ModelNotFoundException)->setModel(Client::class);
            }

            $request->request->add([
                'client_secret' => $client->secret,
            ]);
        }

        if ($request->grant_type === 'refresh_token') {
            $payload = $this->encrypter->decrypt($request->cookie(Passport::cookie()));

            $request->request->add([
                'refresh_token' => $payload['refresh_token'],
            ]);
        }

        return $next($request);
    }
}
