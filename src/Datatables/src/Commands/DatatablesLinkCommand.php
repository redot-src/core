<?php

namespace Redot\Datatables\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Redot\Datatables\DatatablesServiceProvider;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class DatatablesLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        datatables:link
        {--relative : Create the symbolic link using relative paths}
        {--force : Recreate the symbolic link if it already exists}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a symbolic link from public/vendor/datatables to the package assets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $paths = ServiceProvider::pathsToPublish(DatatablesServiceProvider::class, 'datatables::assets');
        $source = array_key_first($paths);

        $link = $paths[$source];
        $target = realpath($source);

        if ($target === false) {
            error('The datatables public assets directory does not exist.');

            return self::FAILURE;
        }

        if (file_exists($link) && ! $this->isRemovableSymlink($link, $this->option('force'))) {
            error("The [$link] link already exists.");

            return self::FAILURE;
        }

        if (is_link($link)) File::delete($link);
        if (! is_dir(dirname($link))) File::makeDirectory(dirname($link), 0755, true);

        if ($this->option('relative')) {
            File::relativeLink($target, $link);
        } else {
            File::link($target, $link);
        }

        info('Datatables assets linked successfully.');

        return self::SUCCESS;
    }

    /**
     * Determine if the provided path is a symlink that can be removed.
     */
    protected function isRemovableSymlink(string $link, bool $force): bool
    {
        return is_link($link) && $force;
    }
}
