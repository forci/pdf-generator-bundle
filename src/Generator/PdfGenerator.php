<?php

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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RequestContext;
use Forci\Bundle\PdfGenerator\Generator\Exception\CouldNotDetermineSchemeAndHostException;

class PdfGenerator {

    /** @var string */
    protected $cacheDir;

    /** @var string */
    protected $rootDir;

    /** @var string */
    protected $binary;

    /** @var \Twig_Environment */
    protected $twig;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var RequestStack */
    protected $requestStack;

    /** @var RequestContext|null */
    protected $requestContext;

    public function __construct(string $cacheDir, string $rootDir, string $binary,
                                \Twig_Environment $twig, EventDispatcherInterface $eventDispatcher,
                                RequestStack $requestStack, RequestContext $requestContext = null) {
        $this->cacheDir = $cacheDir;
        $this->rootDir = $rootDir;
        $this->binary = $binary;
        $this->twig = $twig;
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
    }

    public function bootstrap(string $html, bool $cleanupOnTerminate = true): PdfResult {
        return $this->wkPrint($this->layoutBootstrap($html), $cleanupOnTerminate);
    }

    public function wkPrint(string $html, bool $cleanupOnTerminate = true): PdfResult {
        $html = $this->replaceUrlsWithFilesystemPath($html);

        $cwd = sprintf('%s/wkhtmltopdf', $this->cacheDir);

        if (!is_dir($cwd)) {
            mkdir($cwd, 0755, true);
        }

        $tempName = uniqid();
        $htmlName = sprintf('%s.html', $tempName);
        $pdfName = sprintf('%s.pdf', $tempName);
        $htmlFile = sprintf('%s/%s', $cwd, $htmlName);
        $pdfFile = sprintf('%s/%s', $cwd, $pdfName);

        file_put_contents($htmlFile, $html);
        $command = sprintf('cd %s && xvfb-run -a %s %s %s', escapeshellarg($cwd), escapeshellarg($this->binary), $htmlName, $pdfName);

        $process = new Process($command);
        $process->start();

        $result = $this->createResult($pdfFile, $htmlFile, $process);

        if ($cleanupOnTerminate) {
            $this->eventDispatcher->addListener(KernelEvents::TERMINATE, function () use ($result) {
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

    protected function layoutBootstrap(string $html): string {
        $data = [
            'html' => $html
        ];

        return $this->twig->render('@ForciPdfGenerator/layout_bootstrap.html.twig', $data);
    }

    protected function replaceUrlsWithFilesystemPath($html): string {
        try {
            $schemeAndHost = $this->getSchemeAndHttpHost();
        } catch (CouldNotDetermineSchemeAndHostException $e) {
            return $html;
        }

        $find = sprintf('%s/bundles', $schemeAndHost);

        $replace = sprintf('file://%s/../web/bundles', $this->rootDir);

        $html = str_replace('src="/bundles', 'src="'.$replace, $html);

        return str_replace($find, $replace, $html);
    }

    protected function getSchemeAndHttpHost(): string {
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

    protected function createResult(string $pdfFile, string $htmlFile, Process $process) {
        return new PdfResult($pdfFile, $htmlFile, $process);
    }
}
