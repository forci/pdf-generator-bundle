<?php

namespace Forci\Bundle\PdfGenerator\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ReadAssetExtension extends AbstractExtension {

    const ASSETS_DIR = __DIR__ . '/../Resources/public';

    public function getFilters() {
        return [
            new TwigFilter('readPdfGeneratorAsset', [$this, 'readAsset'])
        ];
    }

    public function readAsset(string $path) {
        return file_get_contents(sprintf('%s/%s', self::ASSETS_DIR, $path));
    }

}