<?php

namespace Redot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Redot\Models\Language;

class RevertLanguageTokens implements ShouldQueue
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
        $this->language->tokens()->modified()->update([
            'value' => DB::raw('original_translation'),
            'is_published' => false,
        ]);
    }
}
