<?php

namespace Redot\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class PublicLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        public:link
        {--name=public_html : The name of the symbolic link}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a symbolic link from public to public_html folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name');

        $publicPath = public_path();
        $publicHtmlPath = base_path($name);

        if (file_exists($publicHtmlPath)) {
            error("The '$name' directory already exists.");

            return 1;
        }

        symlink($publicPath, $publicHtmlPath);

        info('Symbolic link created successfully.');
    }
}
