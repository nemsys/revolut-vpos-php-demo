<?php

namespace App;

use Dotenv\Dotenv;

/**
 * Application configuration loaded from .env file.
 */
class Config
{
    private static ?Config $instance = null;
    private array $config;

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();
        $dotenv->required([
            'REVOLUT_API_URL',
            'REVOLUT_API_PUBLIC_KEY',
            'REVOLUT_API_SECRET_KEY',
            'REVOLUT_ENVIRONMENT',
        ]);

        $this->config = [
            'api_url' => $_ENV['REVOLUT_API_URL'],
            'public_key' => $_ENV['REVOLUT_API_PUBLIC_KEY'],
            'secret_key' => $_ENV['REVOLUT_API_SECRET_KEY'],
            'webhook_secret' => $_ENV['REVOLUT_WEBHOOK_SECRET'] ?? '',
            'environment' => $_ENV['REVOLUT_ENVIRONMENT'],
            'server_port' => $_ENV['SERVER_PORT'] ?? '8080',
        ];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key): string
    {
        return $this->config[$key] ?? '';
    }
}
