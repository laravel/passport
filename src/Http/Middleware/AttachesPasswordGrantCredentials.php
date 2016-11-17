<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AttachesPasswordGrantCredentials
{
    /**
     * The Client Repository instance.
     *
     * @var \Laravel\Passport\ClientRepository
     */
    protected $clients;

    /**
     * Create a new middleware instance.
     *
     * @param \Laravel\Passport\ClientRepository $clients
     * @return void
     */
    public function __construct(ClientRepository $clients)
    {
        $this->clients = $clients;
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

        return $next($request);
    }
}
