<?php

namespace Redot\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Console\ViewMakeCommand as Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Input\InputOption;

class ViewMakeCommand extends Command
{
    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws FileNotFoundException
     */
    protected function buildClass($name)
    {
        $contents = parent::buildClass($name);

        parse_str($this->option('params') ?: '', $params);

        $params = Arr::dot($params);

        $contents = str_replace(
            array_map(fn ($key) => "{{ $key }}", array_keys($params)),
            array_values($params),
            $contents,
        );

        return $contents;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($template = $this->option('template')) {
            return $this->resolveStubPath($template . '.stub');
        }

        return $this->resolveStubPath('/stubs/view.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        $stub = trim($stub, '/');
        $isFrameworkStub = str_starts_with($stub, 'stubs/');
        $customPath = base_path(($isFrameworkStub ? '' : 'stubs/') . $stub);
        $packagePath = __DIR__ . '/../../' . ($isFrameworkStub ? '' : 'stubs/') . $stub;

        if (file_exists($customPath)) {
            return $customPath;
        }

        if (file_exists($packagePath)) {
            return $packagePath;
        }

        return parent::resolveStubPath('/' . $stub);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['template', 't', InputOption::VALUE_OPTIONAL, 'The template to use, if none is provided the default template will be used'],
            ['params', 'p', InputOption::VALUE_OPTIONAL, 'The params to replace in the template'],
        ]);
    }
}
