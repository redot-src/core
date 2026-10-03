<?php

namespace Redot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Redot\Models\Language;
use Symfony\Component\Finder\Finder;
use UnexpectedValueException;

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
        $locale = strtolower($this->language->code);

        $path = lang_path($locale . '.json');
        $jsonTokens = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($jsonTokens)) {
            throw new UnexpectedValueException("The translation catalog [$path] must contain a JSON object or array.");
        }

        $translations = [];
        foreach (Finder::create()->files()->in(lang_path($locale)) as $file) {
            $tokens = require $file->getRealPath();

            if (! is_array($tokens)) {
                throw new UnexpectedValueException("The translation file [{$file->getRealPath()}] must return an array.");
            }

            $basename = $file->getBasename('.php');
            $translations[$basename] = $tokens;
        }

        $translations = Arr::dot($translations);

        $this->language->getConnection()->transaction(function () use ($jsonTokens, $translations) {
            $this->language->tokens()->delete();

            $this->syncTokens($this->language, $jsonTokens, true);
            $this->syncTokens($this->language, $translations, false);
        });
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
