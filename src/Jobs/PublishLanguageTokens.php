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

        // Mark the tokens as published.
        $this->language->tokens()->update(['is_published' => true]);

        // Trigger the build of the dependencies.
        trigger_dependencies_build();
    }

    /**
     * Publish the JSON based translations.
     */
    protected function publishJsonBasedTranslations()
    {
        $locale = strtolower($this->language->code);
        $tokens = $this->language->tokens()->fromJson()->pluck('value', 'key')->sortKeys();

        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        File::put(lang_path($locale . '.json'), json_encode($tokens, $flags));
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
                'exact_key' => $exact_key,
                'full_key' => $item->key,
                'value' => $item->value,
            ]];
        });

        foreach ($translations as $filename => $items) {
            $path = lang_path($locale . DIRECTORY_SEPARATOR . $filename . '.php');
            $content = File::get($path);

            foreach ($items as $item) {
                $current = preg_quote(__($item['full_key']), '/');

                $content = preg_replace(
                    "/(['\"]{$item['exact_key']}['\"]\s*=>\s*)['\"]{$current}['\"]/",
                    "$1'" . addslashes($item['value']) . "'",
                    $content,
                );
            }

            File::put($path, $content);
        }
    }
}
