<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

class ModelPopulateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        model:populate
        {--model= : The model class to populate.}
        {--count= : The number of records to create.}
    ';

    /**
     * The models to exclude from the command.
     *
     * @var array<string>
     */
    public static array $execlude = [
        // ...
    ];

    /**
     * The models to include in the command.
     *
     * @var array<string>
     */
    public static array $include = [
        // ...
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a specific factory to populate the database with fake data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = collect(scandir(app_path('Models')))
            ->filter(fn ($file) => ! in_array($file, ['.', '..']))
            ->map(fn ($file) => $this->laravel->getNamespace() . 'Models\\' . str_replace('.php', '', $file))
            ->merge($this->include)
            ->filter(fn ($class) => class_exists($class) && method_exists($class, 'factory') && ! in_array($class, $this->execlude));

        $model = $this->option('model');

        // Use different input methods based on the OS because of the known
        // issue with the search for non-existing classes on Windows.
        if ($model === null && strtolower(PHP_OS_FAMILY) === 'windows') {
            $model = $this->choice(
                question: 'Which model class do you want to populate?',
                choices: array_values($models->toArray()),
            );
        } elseif ($model === null && strtolower(PHP_OS_FAMILY) !== 'windows') {
            $model = search(
                label: 'Which model class do you want to populate?',
                options: fn ($term) => $models->filter(fn ($class) => str_contains(strtolower($class), strtolower($term)))->values()->toArray(),
            );
        }

        $count = $this->option('count') ?? text(
            label: 'How many records do you want to create?',
            default: 10,
            validate: fn ($value) => match (true) {
                $value < 1 => 'The number of records must be greater than 0.',
                default => null,
            },
        );

        // Set hash rounds to 4 for the duration of the command.
        Hash::setRounds(4);

        $factory = $model::factory();

        progress(
            label: "Populating the model class '$model' with $count records...",
            steps: $count,
            callback: fn () => $factory->create(),
        );

        // Reset hash rounds to the default value.
        Hash::setRounds(env('BCRYPT_ROUNDS', 12));

        info("The model class '$model' has been populated with $count records.");
    }
}
