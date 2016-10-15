<?php

namespace Html5facil\Passport;

use Illuminate\Support\Facades\Storage;

class DataTypeIdSelector
{
    /**
     * The configuration data type file.
     *
     * @var ConfigFile
     */
    protected $configFile = "config.json";

    /**
     * The data type selection.
     *
     * @var DataType
     */
    public $dataType = "";

    /**
     * Create a DataType ID Selector factory instance.
     *
     * @param  Config  $config
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Read a config file JSON
     *
     */
    public function readConfigFile()
    {
        if(Storage::disk('local')->exists($configFile['name']))
        {
            $dataType = json_decode( Storage::disk('local')->get($configFile['name']) );
            $dataType = $dataType['data_type_id'];
        }
        else
        {
            $dataType = "integer";
        }

        return $dataType;
    }
}
