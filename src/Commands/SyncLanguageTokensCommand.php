<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Redot\Jobs\SyncLanguageTokens;
use Redot\Models\Language;

use function Laravel\Prompts\info;

class SyncLanguageTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        lang:sync
        {language? : The language code of the language to sync the tokens for.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the language tokens with the language files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->argument('language')) {
            $language = Language::where('code', $this->argument('language'))->firstOrFail();
            SyncLanguageTokens::dispatchSync($language);
        } else {
            Language::all()->each(function (Language $language) {
                SyncLanguageTokens::dispatchSync($language);
            });
        }

        info('Language tokens synced successfully.');
    }
}
