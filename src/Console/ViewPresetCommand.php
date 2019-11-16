<?php


namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ViewPresetCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:views
                    { type=bootstrap : The preset type (bootstrap) }
                    {--views : Only scaffold the authentication views}
                    {--force : Overwrite existing views by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Passport Authorize View';

    protected $views = [
        'views/authorize.blade.php' => 'passport/authorize.blade.php',
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

        if (!in_array($this->argument('type'), ['bootstrap'])) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        $this->ensureDirectoriesExist();

        $this->exportViews();

        $this->info('Passport authorize view generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function ensureDirectoriesExist()
    {
        if (!is_dir($directory = resource_path('views/vendor/passport/'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    private function exportViews()
    {
        foreach ($this->views as $key => $value) {
            File::copy(
                __DIR__ . '/../../resources/' . $key,
                resource_path('views/vendor/' . $value)
            );
        }
    }
}
