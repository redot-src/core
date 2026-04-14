<?php

namespace Redot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Redot\LangExtractor\LangExtractor;
use Redot\Models\Language;

class ExtractLanguageTokens implements ShouldQueue
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
        $directories = [
            app_path(),
            public_path('assets'),
            resource_path('views'),
        ];

        $extractor = new LangExtractor;
        $extractor->searchIn(...$directories)->withExtensions('php', 'js');
        $translations = $extractor->extract()->all();

        foreach ($translations as $key => $value) {
            $this->language->tokens()->firstOrCreate([
                'key' => $key,
            ], [
                'value' => $value,
                'original_translation' => $value,
                'from_json' => true,
            ]);
        }
    }
}
