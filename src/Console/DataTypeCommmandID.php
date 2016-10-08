<?php

namespace Html5facil\Passport\Console;

use Illuminate\Console\Command;

class DataTypeCommandID extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:data-type-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Define data type for all IDs on the migrations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->createConfigFile();
    }

    /**
     * Create or overwrite config file
     *
     * @param
     * @return void
     */
    protected function createConfigFile()
    {
        $configFile = array(
            'path' = '../../',
            'name' = 'config.json',
            'content' = array(
                'data_type_id' => ''
            )
        );


        $configFile['content']['data_type_id'] = $this->ask(
            'What type of data used for the IDs? Uuid or integer? (integer is default):'
        );

        if($configFile['content']['data_type_id'] != "uuid" or $configFile['content']['data_type_id'] != "integer" && $configFile['content']['data_type_id'] != "")
        {
            $configFile['content']['data_type_id'] = $this->ask(
                'Just can be uuid or integer. Choose one:'
            );
        }
        elseif($configFile['content']['data_type_id'] == "")
        {
            $configFile['content']['data_type_id'] = "integer";
        }

        if( file_exists( $configFile['path'].$configFile['name'] ) )
        {
            $configFileExist = (boolean)$this->ask(
                'The config file exist. Do you want overwrite? (true/false):'
            );

            if($configFileExist){
                file_put_contents($configFile['path'].$configFile['name'], json_encode($configFile['content']['data_type_id']));
            }

            $this->info('Config file overwrited successfully.');
        }
        else
        {
            file_put_contents($configFile['path'].$configFile['name'], json_encode($configFile['content']['data_type_id']));

            $this->info('Config file created successfully.');
        }
    }
}
