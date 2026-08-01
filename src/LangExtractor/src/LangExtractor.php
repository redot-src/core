<?php

namespace Redot\LangExtractor;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class LangExtractor
{
    /**
     * Pattern to match translation strings.
     */
    protected string $pattern;

    /**
     * Directories to search for blade files.
     */
    protected array $directories = [];

    /**
     * File extensions to search for.
     */
    protected array $extensions = [];

    /**
     * Translations extracted from blade files.
     */
    protected array $translations = [];

    /**
     * Language file groups of the fallback locale, resolved on demand.
     */
    protected ?array $groups = null;

    /**
     * The functions to extract translations from.
     */
    public static array $functions = ['__', 'trans', 'trans_choice', '@lang', '@choice', 'Lang::get', 'Lang::choice'];

    /**
     * Whitespace and comments allowed between the tokens of a translation call.
     */
    protected const GAP = '(?>(?:\s|\/\*.*?\*\/|\/\/[^\n]*)*)';

    /**
     * A single quoted string literal, escapes included.
     */
    protected const LITERAL = '(?:\'(?:\\\\.|[^\'\\\\])*+\'|"(?:\\\\.|[^"\\\\])*+")';

    /**
     * Escape sequences shared by double quoted strings and heredocs.
     */
    protected const ESCAPES = ['\\' => '\\', '"' => '"', 'n' => "\n", 'r' => "\r", 't' => "\t", 'v' => "\v", 'e' => "\e", 'f' => "\f", '$' => '$'];

    /**
     * Create a new instance.
     */
    public function __construct($directories = [], $extensions = [])
    {
        if (count($directories) > 0) $this->searchIn(...$directories);
        if (count($extensions) > 0) $this->withExtensions(...$extensions);

        $this->pattern = $this->generatePatternUsing(...self::$functions);
    }

    /**
     * Set the directories to search for blade files.
     */
    public function searchIn(string ...$directories): static
    {
        foreach ($directories as $directory) {
            if (! in_array($directory, $this->directories)) {
                $this->directories[] = $directory;
            }
        }

        return $this;
    }

    /**
     * Set the file extensions to search for.
     */
    public function withExtensions(string ...$extensions): static
    {
        foreach ($extensions as $extension) {
            $extension = ltrim(strtolower($extension), '.');

            if (! in_array($extension, $this->extensions)) {
                $this->extensions[] = $extension;
            }
        }

        return $this;
    }

    /**
     * Get the pattern to match translation strings.
     */
    protected function generatePatternUsing(string ...$functions): string
    {
        $functions = array_unique($functions);

        // Longest first so that helpers sharing a prefix, like trans and
        // trans_choice, are both reachable from the same alternation.
        usort($functions, fn ($a, $b) => strlen($b) <=> strlen($a));

        $calls = array_map(function (string $function) {
            $boundary = str_starts_with($function, '@') ? '(?<![A-Za-z0-9_@])' : '(?<![A-Za-z0-9_])';

            return $boundary . preg_quote($function, '/');
        }, $functions);

        $argument = '(?<argument>' . self::LITERAL . '(?:' . self::GAP . '[.+]' . self::GAP . self::LITERAL . ')*)';
        $heredoc = '<<<(?<quote>[\'"]?)(?<identifier>[A-Za-z_][A-Za-z0-9_]*)\k<quote>\R(?<body>.*?)\R(?<indent>[ \t]*)\k<identifier>(?![A-Za-z0-9_])';
        $named = '(?:[A-Za-z_][A-Za-z0-9_]*' . self::GAP . ':(?!:)' . self::GAP . ')?';
        $terminator = '(?=' . self::GAP . '[,)])';

        return '/(?:' . implode('|', $calls) . ')' . self::GAP . '\(' . self::GAP . $named . '(?:' . $argument . '|' . $heredoc . ')' . $terminator . '/s';
    }

    /**
     * Get all translations from blade files.
     */
    public function extract(): static
    {
        if (count($this->directories) === 0) {
            $this->searchIn(app_path(), resource_path(), base_path('routes'), database_path('seeders'));
        }

        $directories = array_values(array_filter($this->directories, fn ($directory) => is_dir($directory)));

        $translations = collect();

        if (count($directories) > 0) {
            $files = Finder::create()->files()->ignoreVCSIgnored(true);
            $files->in($directories)->name(array_map(fn ($extension) => '*.' . $extension, $this->extensions));

            foreach ($files as $file) {
                $translations = $translations->merge($this->extractMatchesFromString($file->getContents()));
            }
        }

        $this->translations = $this->formatTranslations($translations->all());

        return $this;
    }

    /**
     * Get all translations from the given string.
     */
    public function extractFromString(string $contents): array
    {
        return $this->formatTranslations($this->extractMatchesFromString($contents));
    }

    /**
     * Match raw translation strings from the given string.
     */
    protected function extractMatchesFromString(string $contents): array
    {
        if (! preg_match_all($this->pattern, $contents, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $translations = array_map(function (array $match) {
            if (($match['argument'] ?? '') !== '') {
                return $this->parseLiterals($match['argument']);
            }

            return $this->parseHeredoc($match['body'] ?? '', $match['indent'] ?? '', ($match['quote'] ?? '') === "'");
        }, $matches);

        return array_values(array_filter($translations, fn ($translation) => $translation !== null));
    }

    /**
     * Resolve a chain of concatenated string literals into a single value.
     */
    protected function parseLiterals(string $expression): ?string
    {
        $translation = '';
        $offset = 0;
        $first = true;

        while ($offset < strlen($expression)) {
            if (! $first) {
                if (! preg_match('/\G' . self::GAP . '[.+]' . self::GAP . '/s', $expression, $separator, 0, $offset)) {
                    return null;
                }

                $offset += strlen($separator[0]);
            }

            if (! preg_match('/\G' . self::LITERAL . '/s', $expression, $literal, 0, $offset)) {
                return null;
            }

            $quote = $literal[0][0];
            $value = substr($literal[0], 1, -1);

            if ($quote === '"' && $this->containsInterpolation($value)) {
                return null;
            }

            $translation .= $this->unescape($value, $quote);
            $offset += strlen($literal[0]);
            $first = false;
        }

        return $translation;
    }

    /**
     * Resolve a heredoc or nowdoc body into its runtime value.
     */
    protected function parseHeredoc(string $body, string $indent, bool $nowdoc): ?string
    {
        if ($indent !== '') {
            $body = preg_replace('/^' . preg_quote($indent, '/') . '/m', '', $body) ?? $body;
        }

        if (! $nowdoc && $this->containsInterpolation($body)) {
            return null;
        }

        return $nowdoc ? $body : $this->unescape($body, '"');
    }

    /**
     * Determine whether a double quoted value contains PHP interpolation.
     */
    protected function containsInterpolation(string $value): bool
    {
        for ($index = 0; $index < strlen($value); $index++) {
            if ($value[$index] !== '$') {
                continue;
            }

            $backslashes = 0;

            for ($cursor = $index - 1; $cursor >= 0 && $value[$cursor] === '\\'; $cursor--) {
                $backslashes++;
            }

            $next = $value[$index + 1] ?? '';

            if ($backslashes % 2 === 0 && preg_match('/[A-Za-z_\x80-\xff{]/', $next)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the escape sequences of a string literal.
     */
    protected function unescape(string $value, string $quote): string
    {
        if ($quote === "'") {
            return strtr($value, ['\\\\' => '\\', "\\'" => "'"]);
        }

        $unescaped = preg_replace_callback('/\\\\(\\\\|["nrtvef$]|[0-7]{1,3}|x[0-9A-Fa-f]{1,2}|u\{[0-9A-Fa-f]+\})/', function (array $match) {
            $escape = $match[1];

            return match (true) {
                isset(self::ESCAPES[$escape]) => self::ESCAPES[$escape],
                $escape[0] === 'x' => chr(hexdec(substr($escape, 1))),
                $escape[0] === 'u' => mb_chr(hexdec(substr($escape, 2, -1)), 'UTF-8') ?: '',
                default => chr(octdec($escape)),
            };
        }, $value);

        return $unescaped ?? $value;
    }

    /**
     * Determine whether the translation belongs to a php language file.
     */
    protected function shouldIgnore(string $translation): bool
    {
        if (preg_match('/\s/', $translation)) {
            return false;
        }

        foreach ($this->languageGroups() as $group) {
            if (str_starts_with($translation, $group . '.') && strlen($translation) > strlen($group) + 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the language file groups of the fallback locale.
     */
    protected function languageGroups(): array
    {
        if (is_array($this->groups)) {
            return $this->groups;
        }

        $files = glob(lang_path((string) config('app.fallback_locale')) . '/*.php') ?: [];

        return $this->groups = array_map(fn ($file) => basename($file, '.php'), $files);
    }

    /**
     * Format matched translations into language file entries.
     */
    protected function formatTranslations(array $translations): array
    {
        $translations = collect($translations)
            ->reject(fn ($translation) => $translation === '' || $this->shouldIgnore($translation))
            ->unique(strict: true)
            ->values()
            ->all();

        return array_combine($translations, $translations) ?: [];
    }

    /**
     * Merge translations with existing translations.
     */
    public function mergeWithFile(string $path): static
    {
        $old = File::exists($path) ? json_decode(File::get($path), true) ?? [] : [];

        if (count($old) > 0) {
            $this->translations = array_merge($this->translations, $old);
            ksort($this->translations);
        }

        return $this;
    }

    /**
     * Merge translations with existing translations.
     */
    public function mergeWithArray(array $translations): static
    {
        $this->translations = array_merge($this->translations, $translations);
        ksort($this->translations);

        return $this;
    }

    /**
     * Get all translations.
     */
    public function all(): array
    {
        return $this->translations;
    }

    /**
     * Save translations to a language file.
     */
    public function save(string $path, bool $force = false): int|bool
    {
        if (File::exists($path) && ! $force) {
            return false;
        }

        File::delete($path);

        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        return File::put($path, json_encode($this->translations, $flags));
    }
}
