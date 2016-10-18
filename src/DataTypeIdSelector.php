<?php

namespace Laravel\Passport;

use Illuminate\Support\Facades\Storage;

class DataTypeIdSelector
{
    /**
     * The configuration data type file.
     *
     * @var ConfigFile
     */
    protected static $configFile = "config.json";

    /**
     * Read a config file JSON
     *
     */
    public static function readConfigFile()
    {
        if(Storage::disk('local')->exists(static::$configFile))
        {
            $dataType = json_decode( Storage::disk('local')->get(static::$configFile), true);

            $dataType = $dataType['data_type_id'];
        }
        else
        {
            $dataType = "integer";
        }

        return $dataType;
    }
}
