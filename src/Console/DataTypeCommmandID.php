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
     *
     * Create or overwrite config file
     *
     * @return void
     */
    protected function createConfigFile()
    {
        $configFile = array(
            'path' => $_SERVER['DOCUMENT_ROOT'],
            'name' => 'config.json',
            'content' => array(
                'data_type_id' => ''
            )
        );

        $configFile['content']['data_type_id'] = $this->choice( 'What type of data used for the IDs? uuid or integer?', [ 'uuid' => 'uuid', 'integer' => 'integer'], 'integer');

        if( file_exists( $configFile['path'].$configFile['name'] ) )
        {
            if ($this->confirm('The config file exist. Do you want overwrite? [y|N]', true)) {
                file_put_contents($configFile['path'].$configFile['name'], json_encode($configFile['content']));
                $this->info('Config file overwrited successfully.');
            }
            else {
                $this->info('Failed overwrited the config file.');
            }
        }
        else
        {
            file_put_contents($configFile['path'].$configFile['name'], json_encode($configFile['content']));

            $this->info('Config file created successfully.');
        }
    }
}
