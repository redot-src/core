<?php

namespace Redot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Redot\Models\Language;
use Symfony\Component\Finder\Finder;

class SyncLanguageTokens implements ShouldQueue
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
        $locale = $this->language->code;

        // Delete all tokens for the language
        $this->language->tokens()->delete();

        $path = lang_path($locale . '.json');
        $this->syncTokens($this->language, json_decode(file_get_contents($path) ?: '{}', true), true);

        $translations = [];
        foreach (Finder::create()->files()->in(lang_path($locale)) as $file) {
            $basename = $file->getBasename('.php');
            $translations[$basename] = require $file->getRealPath();
        }

        // Use Dot notation for the translations
        $translations = Arr::dot($translations);
        $this->syncTokens($this->language, $translations, false);
    }

    /**
     * Seed language tokens.
     */
    protected function syncTokens(Language $language, array $tokens, bool $jsonKey = false): void
    {
        foreach ($tokens as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $language->tokens()->updateOrCreate([
                'key' => $key,
            ], [
                'value' => $value,
                'original_translation' => $value,
                'from_json' => $jsonKey,
                'is_published' => true,
            ]);
        }
    }
}
