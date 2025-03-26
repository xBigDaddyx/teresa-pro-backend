<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeDDDCommand extends Command
{
    protected $signature = 'make:ddd {type : Tipe objek (entity, value-object, aggregate, dto, repository, service, event)} {context : Bounded context (misalnya Accuracy\CartonBox)} {name : Nama objek} {--force : Overwrite jika file sudah ada}';
    protected $description = 'Generate a DDD object with optional migration';

    public function handle()
    {
        $type = strtolower($this->argument('type'));
        $context = $this->argument('context'); // Tidak kapitalisasi otomatis di sini
        $name = ucfirst($this->argument('name'));

        // Validasi nama
        if (!$this->validateName($context) || !$this->validateName($name)) {
            $this->error("Nama context atau objek hanya boleh mengandung huruf, angka, dan backslash.");
            return;
        }

        $this->generateFile($type, $context, $name);

        if (in_array($type, ['entity', 'aggregate'])) {
            $this->generateMigration($context, $name);
        }
    }

    protected function validateName($name): bool
    {
        return preg_match('/^[a-zA-Z0-9\\\\]+$/', $name) === 1; // Izinkan backslash
    }

    protected function generateFile($type, $context, $name)
    {
        $namespaceMap = [
            'entity' => "App\\Domain\\{$context}\\Entities",
            'value-object' => "App\\Domain\\{$context}\\ValueObjects",
            'aggregate' => "App\\Domain\\{$context}\\Aggregates",
            'dto' => "App\\Domain\\{$context}\\Data",
            'repository' => "App\\Infrastructure\\Repositories",
            'service' => "App\\Application\\Services",
            'event' => "App\\Infrastructure\\Events",
        ];

        $pathMap = [
            'entity' => app_path("Domain/" . str_replace('\\', '/', $context) . "/Entities"),
            'value-object' => app_path("Domain/" . str_replace('\\', '/', $context) . "/ValueObjects"),
            'aggregate' => app_path("Domain/" . str_replace('\\', '/', $context) . "/Aggregates"),
            'dto' => app_path("Domain/" . str_replace('\\', '/', $context) . "/Data"),
            'repository' => app_path("Infrastructure/Repositories"),
            'service' => app_path("Application/Services"),
            'event' => app_path("Infrastructure/Events"),
        ];

        if (!array_key_exists($type, $namespaceMap)) {
            $this->error("Tipe '{$type}' tidak didukung. Gunakan: entity, value-object, aggregate, dto, repository, service, event.");
            return;
        }

        $namespace = $namespaceMap[$type];
        $path = $pathMap[$type];
        $filePath = "{$path}/{$name}.php";

        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
            $this->info("Direktori {$path} dibuat.");
        }

        if (File::exists($filePath) && !$this->option('force')) {
            $this->error("File {$filePath} sudah ada! Gunakan --force untuk overwrite.");
            return;
        }

        $stub = $this->getStub($type, $namespace, $name);
        File::put($filePath, $stub);
        $this->info("Berhasil membuat {$type}: {$filePath}");
    }

    protected function generateMigration($context, $name)
    {
        $tableName = Str::plural(strtolower($name));
        $migrationName = "create_{$tableName}_table";
        $timestamp = now()->format('Y_m_d_His');
        $migrationFile = database_path("migrations/{$timestamp}_{$migrationName}.php");

        if (File::exists($migrationFile)) {
            $this->warn("Migrasi untuk {$tableName} sudah ada, dilewati.");
            return;
        }

        $stub = $this->getMigrationStub($context, $name, $tableName);
        File::put($migrationFile, $stub);
        $this->info("Berhasil membuat migrasi: {$migrationFile}");
    }

    protected function getStub($type, $namespace, $name)
    {
        $stubPath = base_path("stubs/ddd.{$type}.stub");
        if (!File::exists($stubPath)) {
            $this->error("Stub untuk tipe '{$type}' tidak ditemukan di {$stubPath}.");
            return;
        }

        $stub = File::get($stubPath);
        return str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $name],
            $stub
        );
    }

    protected function getMigrationStub($context, $name, $tableName)
    {
        $stubPath = base_path("stubs/ddd.migration.stub");
        if (!File::exists($stubPath)) {
            $this->error("Stub migrasi tidak ditemukan di {$stubPath}.");
            return;
        }

        $stub = File::get($stubPath);
        return str_replace(
            ['{{table}}', '{{class}}'],
            [$tableName, $name],
            $stub
        );
    }
}
