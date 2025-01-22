<?php
namespace Deployer;

require 'recipe/laravel.php';
require 'contrib/npm.php';

// Config

set('repository', 'https://github.com/samharvey44/strava-dsw-insights.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('***REMOVED***')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/strava-dsw-insights');

// Tasks

desc('Build static assets');
task('npm:build', function () {
    run("cd {{release_path}} && {{bin/npm}} run build");
});

// Hooks

after('deploy:update_code', 'npm:install');
after('npm:install', 'npm:build');

after('deploy:symlink', 'artisan:queue:restart');

after('deploy:failed', 'deploy:unlock');
