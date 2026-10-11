<?php

namespace Deployer;

require 'recipe/laravel.php';

// Driftsättning till den egna servern. Körs av GitHub Actions (.github/workflows/deploy.yml)
// efter att testerna gått igenom på main. Se docs/DRIFT.md.

set('application', 'NPF-familjen');
set('repository', 'https://github.com/MrGullman/adhd.git');
set('keep_releases', 5);

// PHP-FPM kör som användaren deploy, så filerna behöver inga särskilda rättigheter.
set('writable_mode', 'skip');

host('npf-familjen.duckdns.org')
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/npf-familjen')
    ->set('bin/php', '/usr/bin/php8.4');

// Frontend byggs i GitHub Actions och laddas upp, så servern behöver inte Node.
desc('Laddar upp den byggda frontend-koden');
task('deploy:upload_assets', function () {
    upload('public/build/', '{{release_path}}/public/build/');
});

after('deploy:vendors', 'deploy:upload_assets');
after('deploy:failed', 'deploy:unlock');
