<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Console;

use Illuminate\Console\Command;
use KalimeroMK\FilamentPageBuilder\Database\Seeders\StarterContentSeeder;

class InstallCommand extends Command
{
    protected $signature = 'fpb:install
        {--without-content : Skip starter categories, posts and News page}
        {--force : Force migrations and seeding in production}';

    protected $description = 'Install Filament Page Builder and optionally seed starter content';

    public function handle(): int
    {
        $this->components->info('Installing Filament Page Builder');

        $this->call('vendor:publish', ['--tag' => 'filament-page-builder-config']);
        $this->call('vendor:publish', ['--tag' => 'filament-page-builder-migrations']);
        $this->call('migrate', ['--force' => (bool) $this->option('force')]);

        if (! file_exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        if (! $this->option('without-content')) {
            $this->call('db:seed', [
                '--class' => StarterContentSeeder::class,
                '--force' => (bool) $this->option('force'),
            ]);
        }

        $this->components->info('Filament Page Builder is ready.');

        return self::SUCCESS;
    }
}
