<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Redot\Jobs\ExtractLanguageTokens;
use Redot\Models\Language;

use function Laravel\Prompts\info;

class ExtractLanguageTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        lang:extract
        {language? : The language code of the language to extract the tokens for.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract language tokens from the source code.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->argument('language')) {
            $language = Language::where('code', $this->argument('language'))->firstOrFail();
            ExtractLanguageTokens::dispatchSync($language);
        } else {
            Language::all()->each(function (Language $language) {
                ExtractLanguageTokens::dispatchSync($language);
            });
        }

        info('Language tokens extracted successfully.');
    }
}
