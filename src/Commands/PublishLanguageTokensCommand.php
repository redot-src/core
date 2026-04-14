<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Redot\Jobs\PublishLanguageTokens;
use Redot\Models\Language;

use function Laravel\Prompts\info;

class PublishLanguageTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        lang:publish
        {language? : The language code of the language to publish the tokens for.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish language tokens to the language files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->argument('language')) {
            $language = Language::where('code', $this->argument('language'))->firstOrFail();
            PublishLanguageTokens::dispatchSync($language);
        } else {
            Language::all()->each(function (Language $language) {
                PublishLanguageTokens::dispatchSync($language);
            });
        }

        info('Language tokens published successfully.');
    }
}
