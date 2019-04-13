<?php

$env = require './secret.php';

// Merge config with triggers commands
function mergeConfig($env)
{
    $user = $env['user'];
    $pass = $env['pass'];
    $host = $env['host'];
    $ssh = "ssh -A -tt $user@$host";

    $baseFolder = $env['path'];

    $config = [
        'remote' => 'sftp://' . $host . $baseFolder,
        'user' => $user,
        'password' => $pass,
        'local' => '../',
        'test' => false,
        'color' => true,
        'ignore' => '
			/deployment.*
			/log
			temp/*
			!temp/.htaccess
			*/tests
			.env
			/vendor/
			/storage/logs/
			/storage/framework/
			/storage/framework/
			README.md
			/docs/
			/deploy/
		',
        'allowDelete' => true,
        'after' => [
            function (Deployment\Server $server, Deployment\Logger $logger, Deployment\Deployer $deployer) use ($baseFolder, $ssh) {
                $logger->log('>> After');

                // Composer
                $logger->log('   Running composer install...');
                $command = "$ssh 'cd {$baseFolder} && php composer.phar install'";
                echo shell_exec($command);
                $logger->log(' ');

                // Migration
                $logger->log('   Running artisan migrate and dumping autoload...');
                $command = "$ssh 'cd {$baseFolder} && php artisan migrate && php composer.phar dump-autoload'";
                echo shell_exec($command);
                $logger->log(' ');
            }
        ]
    ];

    return $config;
}


return [
    'dev' => mergeConfig($env),

    'log' => __DIR__ . '/logs/deploy.log',
    'tempDir' => __DIR__ . '/temp',
    'colors' => true,
];
