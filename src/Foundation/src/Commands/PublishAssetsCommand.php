<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\info;

class PublishAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redot:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the Redot assets and libraries to public/vendor/redot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Start from a clean directory so files removed from the package don't linger.
        File::deleteDirectory(public_path('vendor/redot'));

        $this->callSilently('vendor:publish', [
            '--tag' => 'redot::assets',
            '--force' => true,
        ]);

        info('Redot assets published successfully!');
    }
}
