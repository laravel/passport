<?php

namespace Html5facil\Passport\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DataTypeCommandId extends Command
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
     * The configfile configuration.
     *
     * @var string
     */
    protected $configFile = array(
        'name' => 'config.json',
        'content' => array(
            'data_type_id' => ''
        )
    );

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
        $this->configFile['content']['data_type_id'] = $this->choice( 'What type of data used for the IDs? uuid or integer?', [ 'uuid' => 'uuid', 'integer' => 'integer'], 'integer');

        if( Storage::disk('local')->exists($this->configFile['name']) )
        {
            if ($this->confirm('The config file exist. Do you want overwrite? [y|N]', true)) {

                Storage::disk('local')->put($this->configFile['name'], json_encode($this->configFile['content']));

                $this->info('Config file overwrited successfully.');
            }
            else {
                $this->info('Failed overwrited the config file.');
            }
        }
        else
        {
            Storage::disk('local')->put($this->configFile['name'], json_encode($this->configFile['content']));

            $this->info('Config file created successfully.');
        }
    }
}
