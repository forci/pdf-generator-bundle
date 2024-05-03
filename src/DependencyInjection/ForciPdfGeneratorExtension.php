<?php declare(strict_types=1);
/*
 * This file is part of the ForciPdfGeneratorBundle package.
 *
 * Copyright (c) Forci Web Consulting Ltd.
 *
 * Author Martin Kirilov <martin@forci.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Forci\Bundle\PdfGenerator\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ForciPdfGeneratorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = [];
        foreach ($configs as $subConfig) {
            $config = array_merge($config, $subConfig);
        }
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $container->setParameter('forci_pdf_generator.flags', []);

        $loader->load('services.xml');
    }

    public function getXsdValidationBasePath(): string
    {
        return __DIR__ . '/../Resources/config/';
    }

    public function getNamespace(): string
    {
        return 'http://www.example.com/symfony/schema/';
    }
}
