<?php

declare(strict_types=1);

use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCsFixer\Fixer\FixerInterface;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;
use Rector\Core\Configuration\Option;
use Rector\DowngradePhp72\Rector\ClassMethod\DowngradeParameterTypeWideningRector;
use Rector\DowngradePhp80\Rector\Class_\DowngradeAttributeToAnnotationRector;
use Rector\DowngradePhp80\ValueObject\DowngradeAttributeToAnnotation;
use Rector\Set\ValueObject\DowngradeLevelSetList;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Contracts\Service\Attribute\Required;
use Symplify\RuleDocGenerator\Contract\Category\CategoryInfererInterface;
use Symplify\RuleDocGenerator\Contract\RuleCodeSamplePrinterInterface;
use Symplify\SimplePhpDocParser\Contract\PhpDocNodeVisitorInterface;
use Symplify\SymfonyPhpConfig\ValueObjectInliner;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_71);

    $services = $containerConfigurator->services();

    $services->set(DowngradeParameterTypeWideningRector::class)
        ->call('configure', [[
            DowngradeParameterTypeWideningRector::SAFE_TYPES => [
                Sniff::class,
                FixerInterface::class,
                OutputInterface::class,
                StyleInterface::class,
                PhpDocNodeVisitorInterface::class,
                // phpstan
                Parser::class,
                RuleCodeSamplePrinterInterface::class,
                CategoryInfererInterface::class,
                PrettyPrinterAbstract::class,
            ],
            DowngradeParameterTypeWideningRector::SAFE_TYPES_TO_METHODS => [
                ContainerInterface::class => ['setParameter', 'getParameter', 'hasParameter'],
            ],
        ]]);

    $services->set(DowngradeAttributeToAnnotationRector::class)
        ->call('configure', [[
            DowngradeAttributeToAnnotationRector::ATTRIBUTE_TO_ANNOTATION => ValueObjectInliner::inline([
                new DowngradeAttributeToAnnotation(Required::class, 'required'),
            ]),
        ]]);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::SKIP, [
        '*/Tests/*',
        '*/tests/*',
        __DIR__ . '/../../tests',
        # missing "optional" dependency and never used here
        '*/symfony/framework-bundle/KernelBrowser.php',
    ]);
};
