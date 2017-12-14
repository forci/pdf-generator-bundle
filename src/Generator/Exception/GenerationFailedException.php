<?php

namespace Forci\Bundle\PdfGeneratorBundle\Generator\Exception;

class GenerationFailedException extends \Exception {

    /** @var string */
    protected $output;

    /** @var string */
    protected $errorOutput;

    public static function create(string $output, string $errorOutput) {
        $instance = new static();
        $instance->message = $errorOutput;
        $instance->setOutput($output);
        $instance->setErrorOutput($errorOutput);

        return $instance;
    }

    /**
     * @return string
     */
    public function getOutput(): string {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput(string $output) {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getErrorOutput(): string {
        return $this->errorOutput;
    }

    /**
     * @param string $errorOutput
     */
    public function setErrorOutput(string $errorOutput) {
        $this->errorOutput = $errorOutput;
    }

}