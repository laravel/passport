<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Http\Request;

class ProviderSelector
{
    public function getProvider(Request $request)
    {
        return config('auth.guards.api.provider');
    }

    public function getModel(Request $request)
    {
        return config('auth.providers.'.$this.getProvider().'.model');
    }
}
