<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Application;

/**
 * Generates the data protection keys. Run this locally (you do not need server
 * access) and store the values as secrets in your deployment platform:
 * DATA_PROTECTION_KEY (encryption) and DATA_PROTECTION_INDEX_KEY (blind index).
 */
class GenerateDataProtectionKey extends Command
{
    protected $signature = 'data-protection:key
        {--write : Write the generated keys to the local .env file}
        {--force : Allow running in production and overwrite existing keys in .env}';

    protected $description = 'Generate the DATA_PROTECTION_KEY and DATA_PROTECTION_INDEX_KEY used to protect sensitive data';

    /**
     * @var list<string>
     */
    private array $variables = ['DATA_PROTECTION_KEY', 'DATA_PROTECTION_INDEX_KEY'];

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to generate data protection keys in production.');
            $this->line('Generate the keys locally and set them as secrets in your deployment platform.');
            $this->line('If you genuinely intend to run this on production, re-run with --force.');

            return self::FAILURE;
        }

        $values = [
            'DATA_PROTECTION_KEY' => $this->generateKey(),
            'DATA_PROTECTION_INDEX_KEY' => $this->generateKey(),
        ];

        if ($this->option('write')) {
            $this->writeToEnvironmentFile($values);
        }

        $this->newLine();
        $this->line('Store these as environment variables / secrets:');
        $this->newLine();

        foreach ($values as $name => $value) {
            $this->line($name.'='.$value);
        }

        $this->newLine();

        return self::SUCCESS;
    }

    private function generateKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    /**
     * @param  array<string, string>  $values
     */
    private function writeToEnvironmentFile(array $values): void
    {
        /** @var Application $app */
        $app = app();
        $path = $app->environmentFilePath();

        if (! is_file($path)) {
            $this->warn('.env file not found; the keys were not written.');

            return;
        }

        $contents = (string) file_get_contents($path);

        $existing = array_values(array_filter(
            $this->variables,
            fn (string $variable): bool => $this->hasExistingValue($contents, $variable)
        ));

        if ($existing !== [] && ! $this->option('force')) {
            $this->error(implode(' and ', $existing).' already exist in .env. Overwriting would make existing encrypted data or lookups unreadable.');
            $this->line('Add the old key(s) to DATA_PROTECTION_PREVIOUS_KEYS first, or pass --force.');

            return;
        }

        foreach ($values as $name => $value) {
            $contents = $this->setEnvironmentValue($contents, $name, $value);
        }

        file_put_contents($path, $contents);

        $this->info('Data protection keys written to .env');
    }

    private function hasExistingValue(string $contents, string $variable): bool
    {
        return preg_match('/^'.$variable.'=(.+)$/m', $contents, $matches) === 1
            && trim($matches[1]) !== '';
    }

    private function setEnvironmentValue(string $contents, string $variable, string $value): string
    {
        $line = $variable.'='.$value;

        if (preg_match('/^'.$variable.'=.*$/m', $contents) === 1) {
            return (string) preg_replace('/^'.$variable.'=.*$/m', $line, $contents);
        }

        return rtrim($contents)."\n".$line."\n";
    }
}
