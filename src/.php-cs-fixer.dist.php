<?php declare(strict_types=1);

use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['vendor', 'node_modules', 'storage', 'bootstrap/cache', 'public/build'])
    ->notPath('_ide_helper.php');

$config = (new PhpCsFixer\Config())
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setFinder($finder);

(new PhpCsFixerCodingStandard())->applyTo($config, [
    'final_public_method_for_abstract_class' => false,
]);

return $config;
