<?php

namespace App\Libs;

use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class PdfToCairo
{
    public static $bin = 'pdftocairo';

    public static $timeout = 69; // 69 seconds

    /** @var Process */
    protected $process;

    protected $command;

    protected $tmp;

    public function __construct()
    {

    }

    protected function getProcess($command, $timeout)
    {
        $process = new Process($command);
        $process->setTimeout($timeout);

        return $process;
    }

    public function convert($path, $page = 1, $width = '-1', $ext = 'png')
    {
        $input = fopen($path, 'r+');

        return $this->convertStream($input, $page, $width, $ext);
    }

    /**
     * @param  string  $ext  png/jpeg/tiff
     * @return string
     */
    public function convertStream($input, int $page = 1, string $width = '-1', string $ext = 'png')
    {
        $command = [self::$bin,
            '-'.$ext,
            '-f',
            $page,
            '-scale-to-y',
            '-1',
            '-scale-to-x',
            $width,
            '-singlefile',
            '-',
            '-',
        ];
        $_input = new InputStream();
        $this->process = new Process($command);
        $this->process->setTimeout(self::$timeout);
        $this->process->setInput($_input);
        $this->process->start();
        if (is_string($input)) {
            $_input->write($input);
            $_input->close();
        } elseif (is_resource($input)) {
            while (! feof($input)) {
                $_input->write(fread($input, 8192));
            }
            $_input->close();
        }
        $this->process->wait();

        return $this->output();
    }

    public function __destruct()
    {
        @unlink($this->tmp);
    }

    protected function run($command)
    {
        $this->command = $command;
        $this->process = $this->getProcess($this->command, self::$timeout);
        $this->process->run();
        $this->validateRun();

        return $this;
    }

    protected function validateRun()
    {
        $status = $this->process->getExitCode();
        $error = $this->process->getErrorOutput();

        if ($status !== 0) {
            throw new \RuntimeException(
                sprintf(
                    "The exit status code %s says something went wrong:\n stderr: %s\n stdout: %s\ncommand: %s.",
                    $status,
                    $error,
                    $this->process->getOutput(),
                    $this->process->getCommandLine()
                )
            );
        }
    }

    protected function output()
    {
        return $this->process->getOutput();
    }

    public function array_insert_after(array $array, $insert_value, $new_value)
    {
        $new = [];
        foreach ($array as $value) {
            $new[] = $value;
            if ($value == $insert_value) {
                $new[] = $new_value;
            }
        }

        return $new;
    }
}
