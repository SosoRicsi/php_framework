<?php

declare(strict_types=1);

use Radiant\Core\Config;

beforeEach(function () {
    // Ideiglenes konfigurációs fájl létrehozása
    $this->configPath = __DIR__.'/temp_config';
    mkdir($this->configPath);
    file_put_contents($this->configPath.'/app.php', '<?php return ["name" => "Aurora", "debug" => true];');
    file_put_contents($this->configPath.'/db.php', '<?php return ["host" => "localhost", "port" => 3306];');

    $this->config = new Config($this->configPath);
});

afterEach(function () {
    // Takarítás
    array_map('unlink', glob($this->configPath.'/*.php'));
    rmdir($this->configPath);
});

test('it loads config values by dot notation', function () {
    expect($this->config->get('app.name'))->toBe('Aurora')
        ->and($this->config->get('app.debug'))->toBeTrue();
});

test('it returns default value for missing keys', function () {
    expect($this->config->get('app.env', 'production'))->toBe('production');
});

test('it returns all loaded configuration', function () {
    $this->config->get('db.host');
    expect($this->config->all())->toHaveKey('db');
});

test('it returns default for missing file', function () {
    expect($this->config->get('missing.key', 'fallback'))->toBe('fallback');
});
