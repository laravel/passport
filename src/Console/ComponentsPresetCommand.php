<?php


namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ComponentsPresetCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:components
                    { type=vue : The preset type (vue) }
                    {--views : Only scaffold the authentication views}
                    {--force : Overwrite existing views by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Passport Components';

    protected $views = [
        'AuthorizedClients.vue' => 'components/AuthorizedClients.vue',
        'Clients.vue' => 'components/Clients.vue',
        'PersonalAccessTokens.vue' => 'components/PersonalAccessTokens.vue',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (static::hasMacro($this->argument('type'))) {
            return call_user_func(static::$macros[$this->argument('type')], $this);
        }

        if (!in_array($this->argument('type'), ['vue'])) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        $this->ensureDirectoriesExist();

        $this->exportComponents();

        $this->info('Passport components generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function ensureDirectoriesExist()
    {
        if (!is_dir($directory = resource_path('js/components/'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    private function exportComponents()
    {
        foreach ($this->views as $key => $value) {
            File::copy(
                __DIR__ . '/../../resources/js/components/' . $key,
                resource_path('js/' . $value)
            );
        }
    }
}
