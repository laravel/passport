<?php

namespace Laravel\Passport\Http\Responses;

use Laravel\Passport\Contracts\DeviceCodeViewResponse as DeviceCodeViewResponseContract;

class DeviceCodeViewResponse implements DeviceCodeViewResponseContract
{
    use ViewResponsable;
}
