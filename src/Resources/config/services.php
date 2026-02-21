<?php declare(strict_types=1);

use Forci\Bundle\PdfGenerator\Generator\PdfGenerator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();
    $services = $container->services();

    $parameters->set('forci_pdf_generator.binary', '%kernel.project_dir%/bin/wkhtmltopdf-i386');

    $services->set(PdfGenerator::class)
        ->public()
        ->args([
            '%kernel.cache_dir%',
            '%kernel.project_dir%',
            '%forci_pdf_generator.binary%',
            '%forci_pdf_generator.flags%',
            service('event_dispatcher'),
            service('request_stack'),
            service('router.request_context')->nullOnInvalid(),
        ]);
};
