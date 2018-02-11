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

use Forci\Bundle\PdfGenerator\Generator\Exception\GenerationFailedException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

class PdfResult {

    const RESPONSE_TYPE_NORMAL = 1,
        RESPONSE_TYPE_BINARY = 2,
        RESPONSE_ON_ERROR_EMPTY_RESPONSE = 4,
        RESPONSE_ON_ERROR_EXCEPTION = 8,
        RESPONSE_ON_ERROR_500_RESPONSE = 16;

    /** @var File */
    protected $file;

    /** @var string */
    protected $pdfFilePath;

    /** @var string */
    protected $htmlFilePath;

    /** @var Process */
    protected $process;

    public function __construct(string $pdfFilePath, string $htmlFilePath, Process $process) {
        $this->pdfFilePath = $pdfFilePath;
        $this->htmlFilePath = $htmlFilePath;
        $this->process = $process;
    }

    public function pdfPath(): string {
        return $this->pdfFilePath;
    }

    public function htmlPath(): string {
        return $this->htmlFilePath;
    }

    /**
     * @return string
     *
     * @throws GenerationFailedException
     */
    public function realPath(): string {
        $this->wait();

        return $this->file->getRealPath();
    }

    /**
     * @return string
     *
     * @throws GenerationFailedException
     */
    public function contents(): string {
        return file_get_contents($this->realPath());
    }

    /**
     * @param string $location
     *
     * @return $this
     *
     * @throws GenerationFailedException
     */
    public function copy(string $location) {
        copy($this->realPath(), $location);

        return $this;
    }

    /**
     * @param string $filename Downloaded file name
     * @param int    $flags any combination of RESPONSE_TYPE_* and RESPONSE_ON_* flags
     *
     * @return Response
     *
     * @throws GenerationFailedException
     */
    public function response(string $filename, int $flags = self::RESPONSE_ON_ERROR_EMPTY_RESPONSE): Response {
        try {
            $this->wait();
        } catch (GenerationFailedException $e) {
            if ($flags & self::RESPONSE_ON_ERROR_EMPTY_RESPONSE) {
                return new Response();
            }

            if ($flags & self::RESPONSE_ON_ERROR_EXCEPTION) {
                throw $e;
            }

            if ($flags & self::RESPONSE_ON_ERROR_500_RESPONSE) {
                return new Response(sprintf("wkhtmltopdf failed: \n\nError Output: \n\n%s\n\nOutput: \n\n%s", $e->getErrorOutput(), $e->getOutput()), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return new Response();
        }

        if ($flags & self::RESPONSE_TYPE_NORMAL) {
            $response = new Response(file_get_contents($this->realPath()));
        } else if ($flags & self::RESPONSE_TYPE_BINARY) {
            $response = new BinaryFileResponse($this->file);
        } else {
            $response = new BinaryFileResponse($this->file);
        }

        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }

    /**
     * @throws GenerationFailedException
     */
    private function wait() {
        $this->process->wait();

        if (!$this->process->isSuccessful()) {
            throw GenerationFailedException::create($this->process->getOutput(), $this->process->getErrorOutput());
        }

        $this->file = new File($this->pdfFilePath);
    }
}
