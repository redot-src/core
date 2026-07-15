<?php

namespace Redot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Redot\Models\Language;

class PublishLanguageTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Language $language
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->publishJsonBasedTranslations();
        $this->publishFileBasedTranslations();

        // Trigger the build of the dependencies.
        trigger_dependencies_build();
    }

    /**
     * Publish the JSON based translations.
     */
    protected function publishJsonBasedTranslations()
    {
        $locale = strtolower($this->language->code);
        $query = $this->language->tokens()->fromJson();
        $tokens = (clone $query)->pluck('value', 'key')->sortKeys();

        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        File::put(lang_path($locale . '.json'), json_encode($tokens, $flags));

        $query->update(['is_published' => true]);
    }

    /**
     * Publish the file based translations.
     */
    protected function publishFileBasedTranslations()
    {
        $locale = strtolower($this->language->code);
        $translations = $this->language->tokens()->notFromJson()->unpublished()->get();

        $translations = $translations->mapToGroups(function ($item) {
            $parts = explode('.', $item->key);
            $filename = array_shift($parts);
            $exact_key = array_pop($parts);

            return [$filename => [
                'id' => $item->getKey(),
                'exact_key' => $exact_key,
                'full_key' => $item->key,
                'value' => $item->value,
            ]];
        });

        foreach ($translations as $filename => $items) {
            $path = lang_path($locale . DIRECTORY_SEPARATOR . $filename . '.php');
            $content = File::get($path);
            $published = [];

            foreach ($items as $item) {
                $key = preg_quote($item['exact_key'], '/');
                $current = (string) app('translator')->get($item['full_key'], [], $locale, false);

                // Match the raw value or its var_export() form, since published
                // values are written with quotes and backslashes escaped.
                $raw = preg_quote($current, '/');
                $exported = preg_quote(substr(var_export($current, true), 1, -1), '/');

                $content = preg_replace_callback(
                    "/(['\"]{$key}['\"]\s*=>\s*)(?:['\"]{$raw}['\"]|'{$exported}')/",
                    fn (array $matches): string => $matches[1] . var_export($item['value'], true),
                    $content,
                    -1,
                    $count,
                );

                if ($count > 0) {
                    $published[] = $item['id'];
                }
            }

            File::put($path, $content);

            // Drop the translator's in-memory cache so later publishes in the
            // same process (queue worker) read the freshly written file.
            app('translator')->setLoaded([]);

            $this->language->tokens()->whereKey($published)->update(['is_published' => true]);
        }
    }
}
