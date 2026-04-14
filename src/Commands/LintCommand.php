<?php

namespace Redot\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\info;

class LintCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        lint
        {--with-js : Run JavaScript linting}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lint the application';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        info('Running PHP linting using Laravel Pint');
        passthru(base_path('vendor/bin/pint'));

        if (! $this->option('with-js')) {
            return;
        }

        // Check if `npm` is installed
        exec('npm -v', $npmVersionOutput, $npmVersionCode);

        if ($npmVersionCode === 0) {
            info('Running JavaScript linting using prettier');
            passthru('npx prettier --write ' . base_path());
        } else {
            info('Skipping JavaScript linting because npm is not installed');
        }
    }
}
