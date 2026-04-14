<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\error;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

class EntityMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        make:entity
        {name? : The name of the resource}
        {--features= : The features to scaffold}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a new resource';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name') ?: text(
            label: 'Enter the name of the resource',
            placeholder: 'e.g. Post',
            required: true,
        );

        // Check if the name is valid
        if (! preg_match('/^[a-zA-Z]+$/', $name)) {
            error('The name must only contain alphabetic characters');

            return 1;
        }

        // Normalize the name
        $name = Str::studly($name);
        $plural = Str::plural($name);

        // Check if there is a model with the same name
        if (file_exists(app_path("Models/$name.php"))) {
            error("A model with the name $name already exists");

            return 1;
        }

        $features = $this->option('features') ? parse_csv($this->option('features')) : multiselect(
            label: 'Select the features you want to scaffold',
            options: [
                'factory' => 'Factory',
                'migration' => 'Migration',
                'seeder' => 'Seeder',
                'controller' => 'Controller',
                'request' => 'Request',
                'views' => 'Views',
                'datatable' => 'Datatable',
                'test' => 'Test',
            ],
            default: [
                'factory',
                'migration',
                'controller',
                'views',
                'datatable',
                'test',
            ],
        );

        $this->call('make:model', [
            'name' => $name,
            '-f' => in_array('factory', $features),
            '-m' => in_array('migration', $features),
            '-s' => in_array('seeder', $features),
        ]);

        if (in_array('controller', $features)) {
            $this->call('make:controller', [
                'name' => "Dashboard/{$name}Controller",
                '--model' => $name,
                '--requests' => in_array('request', $features),
            ]);
        }

        if (in_array('views', $features)) {
            $entity = Str::camel($name);
            $resource = strtolower(Str::kebab($plural));
            $datatable = Str::snake($plural, '-');

            foreach (['index', 'create', 'edit', 'show'] as $view) {
                $template = $view === 'index' && in_array('datatable', $features) ? 'index-datatable' : $view;

                $this->call('make:view', [
                    'name' => "views/dashboard/$resource/$view",
                    '--template' => "dashboard.$template",
                    '--params' => "resource=$resource&entity=$entity&datatable=$datatable",
                ]);
            }
        }

        if (in_array('datatable', $features)) {
            $this->call('make:datatable', [
                'name' => $plural,
                '--model' => $name,
            ]);
        }

        if (in_array('test', $features)) {
            $this->call('make:test', [
                'name' => "Http/Controllers/Dashboard/{$name}ControllerTest",
            ]);
        }
    }
}
