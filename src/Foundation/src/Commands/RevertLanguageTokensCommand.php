<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Redot\Jobs\RevertLanguageTokens;
use Redot\Models\Language;

use function Laravel\Prompts\info;

class RevertLanguageTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        lang:revert
        {language? : The language code of the language to revert the tokens for.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revert language tokens to their original values.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->argument('language')) {
            $language = Language::where('code', $this->argument('language'))->firstOrFail();
            RevertLanguageTokens::dispatchSync($language);
        } else {
            Language::all()->each(function (Language $language) {
                RevertLanguageTokens::dispatchSync($language);
            });
        }

        info('Language tokens reverted successfully.');
    }
}
