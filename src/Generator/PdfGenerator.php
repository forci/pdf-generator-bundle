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

namespace Forci\Bundle\PdfGenerator\Generator;

use Forci\Bundle\PdfGenerator\Generator\Exception\CouldNotDetermineSchemeAndHostException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RequestContext;

readonly class PdfGenerator
{
    public function __construct(
        private string $cacheDir,
        private string $projectDir,
        private string $binary,
        /** @var string[] */
        private array $flags,
        private EventDispatcherInterface $eventDispatcher,
        private RequestStack $requestStack,
        private ?RequestContext $requestContext = null
    ) {
    }

    public function wkPrint(string $html, bool $cleanupOnTerminate = true): PdfResult
    {
        $html = $this->replaceUrlsWithFilesystemPath($html);

        $cwd = sprintf('%s/wkhtmltopdf', $this->cacheDir);

        if (!is_dir($cwd) && !mkdir($cwd, 0755, true) && !is_dir($cwd)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $cwd));
        }

        $tempName = uniqid('', true);
        $htmlName = sprintf('%s.html', $tempName);
        $pdfName = sprintf('%s.pdf', $tempName);
        $htmlFile = sprintf('%s/%s', $cwd, $htmlName);
        $pdfFile = sprintf('%s/%s', $cwd, $pdfName);

        file_put_contents($htmlFile, $html);

        $process = new Process([
            'xvfb-run',
            '-a',
            $this->binary,
            ...$this->flags,
            $htmlName,
            $pdfName,
        ], $cwd);
        $process->start();

        $result = new PdfResult($pdfFile, $htmlFile, $process);

        if ($cleanupOnTerminate) {
            $this->eventDispatcher->addListener(KernelEvents::TERMINATE, static function () use ($result) {
                if (file_exists($result->htmlPath())) {
                    unlink($result->htmlPath());
                }

                if (file_exists($result->pdfPath())) {
                    unlink($result->pdfPath());
                }
            }, 255);
        }

        return $result;
    }

    protected function replaceUrlsWithFilesystemPath(string $html): string
    {
        try {
            $schemeAndHost = $this->getSchemeAndHttpHost();
        } catch (CouldNotDetermineSchemeAndHostException) {
            return $html;
        }

        $find = sprintf('%s/bundles', $schemeAndHost);

        $replace = sprintf('file://%s/web/bundles', $this->projectDir);

        $html = str_replace('src="/bundles', 'src="' . $replace, $html);

        return str_replace($find, $replace, $html);
    }

    /**
     * @throws CouldNotDetermineSchemeAndHostException
     */
    protected function getSchemeAndHttpHost(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            return $request->getSchemeAndHttpHost();
        }

        if ($this->requestContext) {
            $scheme = $this->requestContext->getScheme();
            $host = $this->requestContext->getHost();

            if ($scheme && $host) {
                return sprintf('%s://%s', $scheme, $host);
            }
        }

        throw new CouldNotDetermineSchemeAndHostException();
    }
}
