<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;

class ClearUploadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all uploads from the uploads directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = collect(File::allFiles(public_path('uploads')));

        if ($files->isEmpty()) {
            error('Uploads directory is empty!');

            return 1;
        }

        if (confirm('Are you sure you want to delete all files in the uploads directory?')) {
            $this->deleteFiles($files);

            info('All files have been deleted!');
        } else {
            info('No files were deleted.');
        }
    }

    /**
     * Delete the files in the uploads directory.
     *
     * @param  Collection  $files
     * @return void
     */
    protected function deleteFiles($files)
    {
        progress(
            label: 'Deleting files',
            steps: count($files),
            callback: fn () => File::delete($files->shift()->getPathname())
        );
    }
}
