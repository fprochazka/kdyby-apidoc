<?php

// update deps
use Nette\Utils\Finder;

chdir(__DIR__);
//passthru('composer update');

// why not use then if we have them!
require_once __DIR__ . '/vendor/autoload.php';

$params = [
	'rootDir' => __DIR__,
	'vendorDir' => __DIR__ . '/vendor',
	'wwwDir' => __DIR__ . '/www_new',
];

$settings = \Nette\Neon\Neon::decode(file_get_contents(__DIR__ . '/apigen.template.neon'));
$settings = \Nette\DI\Helpers::expand($settings, $params);

$settings['exclude'][] = $params['vendorDir'] . '/bin';
$settings['exclude'][] = $params['vendorDir'] . '/composer';

/** @var \SplFileInfo $packageDir */
/** @var \SplFileInfo $excludeDir */
foreach (Finder::findDirectories('*')->in($params['vendorDir']) as $vendorDir) {
	foreach (Finder::findDirectories('*')->in($vendorDir) as $packageDir) {

		// exclude in package
		foreach (Finder::findDirectories('example*', 'style*', 'test*', 'doc*', 'tool*', '.git', 'bin', 'demo', 'benchmark')->in($packageDir) as $excludeDir) {
			$settings['exclude'][] = $excludeDir->getPathname();
		}

		// doctrine
		foreach (Finder::findDirectories('vendor')->limitDepth(1)->from($packageDir) as $excludeDir) {
			$settings['exclude'][] = $excludeDir->getPathname();
		}
	}
}

// symfony
foreach (Finder::findDirectories('Test*')->limitDepth(4)->from($params['vendorDir'] . '/symfony') as $excludeDir) {
	$settings['exclude'][] = $excludeDir->getPathname();
}

// psr
foreach (Finder::findDirectories('Test*')->limitDepth(3)->from($params['vendorDir'] . '/psr') as $excludeDir) {
	$settings['exclude'][] = $excludeDir->getPathname();
}

file_put_contents(__DIR__ . '/apigen.neon', \Nette\Neon\Neon::encode($settings, \Nette\Neon\Neon::BLOCK));


passthru(sprintf('/usr/local/bin/php -dmemory_limit=1024M /usr/local/bin/apigen generate --config %s', escapeshellarg($params['rootDir'] . '/apigen.neon')));

