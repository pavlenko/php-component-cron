<?php

namespace PE\Component\Cron;

use PE\Component\Cron\Exception\InvalidArgumentException;
use PE\Component\Cron\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
class Crontab
{
    /**
     * @var Job[]
     */
    protected $jobs = [];

    /**
     * @var string
     */
    protected $binary;

    /**
     * @param string|null $binary Path to crontab executable
     *
     * @throws RuntimeException
     */
    public function __construct($binary = null)
    {
        $this->binary = (string) $binary ?: '/usr/bin/crontab';

        $process = new Process($this->binary . ' -l');
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput() ?: 'Execution error');
        }

        $lines = explode(PHP_EOL, $process->getOutput());
        $lines = array_filter($lines, function($line){
            return (
                '' !== trim($line) &&
                (0 === strpos($line, '#suspended: ') || 0 !== strpos($line, '#')) &&
                !preg_match('/^\s*[a-z0-9_]+\s*=/i', $line)
            );
        });

        foreach ($lines as $index => $line) {
            $this->jobs['l' . $index] = Job::fromString($line);
        }
    }

    /**
     * @return Job[]
     */
    public function all()
    {
        return $this->jobs;
    }

    /**
     * @param Job $job
     *
     * @throws \RuntimeException
     */
    public function add(Job $job)
    {
        $this->jobs[] = $job;
        $this->write();
    }

    /**
     * @param int|string $index
     *
     * @return Job
     *
     * @throws InvalidArgumentException
     */
    public function get($index)
    {
        if (!array_key_exists($index, $this->jobs)) {
            throw new InvalidArgumentException('Undefined job with index ' . $index);
        }

        return $this->jobs[$index];
    }

    /**
     * @param int|string $index
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function remove($index)
    {
        if (!array_key_exists($index, $this->jobs)) {
            throw new InvalidArgumentException('Undefined job with index ' . $index);
        }

        unset($this->jobs[$index]);
        $this->write();
    }

    /**
     * @throws RuntimeException
     */
    protected function write()
    {
        $file = tempnam(sys_get_temp_dir(), 'cron');

        $lines = array_map(function(Job $job){
            return $job->toString();
        }, $this->jobs);

        file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);

        $process = new Process($this->binary . ' ' . $file);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Cannot save jobs');
        }
    }
}