<?php

namespace Forci\Bundle\PdfGenerator\Twig;

class ReadAssetExtension extends \Twig_Extension {

    const ASSETS_DIR = __DIR__ . '/../Resources/public';

    public function getFilters() {
        return [
            new \Twig_SimpleFilter('readPdfGeneratorAsset', [$this, 'readAsset'])
        ];
    }

    public function readAsset(string $path) {
        return file_get_contents(sprintf('%s/%s', self::ASSETS_DIR, $path));
    }

}