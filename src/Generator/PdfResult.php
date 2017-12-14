<?php

namespace Forci\Bundle\PdfGeneratorBundle\Generator;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Forci\Bundle\PdfGeneratorBundle\Generator\Exception\GenerationFailedException;

class PdfResult {

    const ON_ERROR_EMPTY_RESPONSE = 1,
        ON_ERROR_EXCEPTION = 2,
        ON_ERROR_500_RESPONSE = 3;

    /** @var File */
    protected $file;

    /** @var string */
    protected $path;

    /** @var Process */
    protected $process;

    public function __construct(string $path, Process $process) {
        $this->path = $path;
        $this->process = $process;
    }

    /**
     * @return string
     * @throws GenerationFailedException
     */
    public function realPath(): string {
        $this->wait();

        return $this->file->getRealPath();
    }

    /**
     * @return string
     * @throws GenerationFailedException
     */
    public function contents(): string {
        return file_get_contents($this->realPath());
    }

    /**
     * @param string $location
     *
     * @return $this
     * @throws GenerationFailedException
     */
    public function copy(string $location) {
        copy($this->realPath(), $location);

        return $this;
    }

    /**
     * @param string $filename
     * @param int    $onError
     *
     * @return Response
     * @throws GenerationFailedException
     */
    public function response(string $filename, $onError = self::ON_ERROR_EMPTY_RESPONSE): Response {
        try {
            $this->wait();
        } catch (GenerationFailedException $e) {
            switch ($onError) {
                case self::ON_ERROR_EMPTY_RESPONSE:
                    return new Response();
                    break;
                case self::ON_ERROR_EXCEPTION:
                    throw $e;
                    break;
                case self::ON_ERROR_500_RESPONSE;
                    return new Response(sprintf("wkhtmltopdf failed: \n\nError Output: \n\n%s\n\nOutput: \n\n%s", $e->getErrorOutput(), $e->getOutput()), Response::HTTP_INTERNAL_SERVER_ERROR);
                    break;
            }

            return new Response();
        }

        $response = new BinaryFileResponse($this->file);

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

        $this->file = new File($this->path);
    }

}