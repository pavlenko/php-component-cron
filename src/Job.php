<?php

namespace PE\Component\Cron;

use PE\Component\Cron\Exception\InvalidArgumentException;
use PE\Component\Cron\Exception\RuntimeException;

class Job
{
    const STATUS_UNKNOWN = 'unknown';
    const STATUS_ERROR   = 'error';
    const STATUS_SUCCESS = 'success';

    /**
     * @var string
     */
    protected $minute = '*';

    /**
     * @var string
     */
    protected $hour = '*';

    /**
     * @var string
     */
    protected $dayOfMonth = '*';

    /**
     * @var string
     */
    protected $month = '*';

    /**
     * @var string
     */
    protected $dayOfWeek = '*';

    /**
     * @var string
     */
    protected $command;

    /**
     * @var string
     */
    protected $logFile;

    /**
     * @var string
     */
    protected $errorFile;

    /**
     * @var \DateTime|null
     */
    protected $lastRunTime;

    /**
     * @var string
     */
    protected $status = self::STATUS_UNKNOWN;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var bool
     */
    protected $suspended = false;

    /**
     * @param string $string
     *
     * @return static
     *
     * @throws InvalidArgumentException
     */
    public static function fromString($string)
    {
        if (!is_string($string) || '' === $string) {
            throw new InvalidArgumentException(sprintf(
                'Cron job must be a non empty string. Got %s',
                is_object($string)
                    ? get_class($string)
                    : is_scalar($string) ? $string : gettype($string)
            ));
        }

        $suspended = false;
        if (0 === strpos($string, '#suspended: ')) {
            $string = substr($string, 12);
            $suspended = true;
        }

        $parts = explode(' ', $string);
        if (count($parts) < 6) {
            throw new InvalidArgumentException(sprintf(
                'Cron job must have minimal format: * * * * * command. Got %s',
                is_object($string) ? get_class($string) : $string
            ));
        }

        $command = implode(' ', array_slice($parts, 5));

        // extract comment
        $comment = '';
        if (false !== strpos($command, '#')) {
            list($command, $comment) = explode('#', $command);
        }

        // extract error file
        $errorFile = '';
        if (false !== strpos($command, '2>')) {
            list($command, $errorFile) = explode('2>', $command);
        }

        // extract log file
        $logFile = '';
        if (false !== strpos($command, '>')) {
            list($command, $logFile) = explode('>', $command);
        }

        $job = new static();
        $job->setCommand(trim($command));
        $job->setSuspended($suspended);
        $job->setComment(trim($comment));
        $job->setErrorFile(trim($errorFile));
        $job->setLogFile(trim($logFile));

        // detect last run time, and file size
        $lastRunTime = null;
        $logSize     = null;
        $errorSize   = null;

        if (($file = $job->getLogFile()) && file_exists($file)) {
            $lastRunTime = filemtime($file);
            $logSize     = filesize($file);
        }

        if (($file = $job->getErrorFile()) && file_exists($file)) {
            $lastRunTime = filemtime($file);
            $errorSize   = filesize($file);
        }

        if (is_int($lastRunTime)) {
            $job->setLastRunTime(new \DateTime('@' . $lastRunTime));
        }

        // detect status
        if ($logSize) {
            $job->setStatus(!$errorSize ? static::STATUS_SUCCESS : static::STATUS_ERROR);
        }

        // fill interval
        $job->setMinute($parts[0]);
        $job->setHour($parts[1]);
        $job->setDayOfMonth($parts[2]);
        $job->setMonth($parts[3]);
        $job->setDayOfWeek($parts[4]);

        return $job;
    }

    /**
     * @param string $command
     *
     * @return $this
     */
    public function setCommand($command)
    {
        $this->command = (string) $command;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $dayOfMonth
     *
     * @return $this
     */
    public function setDayOfMonth($dayOfMonth)
    {
        $this->dayOfMonth = (string) $dayOfMonth;
        return $this;
    }

    /**
     * @return string
     */
    public function getDayOfMonth()
    {
        return $this->dayOfMonth;
    }

    /**
     * @param string $dayOfWeek
     *
     * @return $this
     */
    public function setDayOfWeek($dayOfWeek)
    {
        $this->dayOfWeek = (string) $dayOfWeek;
        return $this;
    }

    /**
     * @return string
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * @param string $hour
     *
     * @return $this
     */
    public function setHour($hour)
    {
        $this->hour = (string) $hour;
        return $this;
    }

    /**
     * @return string
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * @param string $minute
     *
     * @return $this
     */
    public function setMinute($minute)
    {
        $this->minute = (string) $minute;
        return $this;
    }

    /**
     * @return string
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * @param string $month
     *
     * @return $this
     */
    public function setMonth($month)
    {
        $this->month = (string) $month;
        return $this;
    }

    /**
     * @return string
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @param string $comment
     *
     * @return $this
     */
    public function setComment($comment)
    {
        $this->comment = (string) $comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $logFile
     *
     * @return $this
     */
    public function setLogFile($logFile)
    {
        $this->logFile = (string) $logFile;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     * @param string $errorFile
     *
     * @return $this
     */
    public function setErrorFile($errorFile)
    {
        $this->errorFile = (string) $errorFile;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorFile()
    {
        return $this->errorFile;
    }

    /**
     * @param \DateTime|null $lastRunTime
     *
     * @return $this
     */
    public function setLastRunTime(\DateTime $lastRunTime = null)
    {
        $this->lastRunTime = $lastRunTime;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastRunTime()
    {
        return $this->lastRunTime;
    }

    /**
     * @param string $status
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setStatus($status)
    {
        $statuses = [static::STATUS_ERROR, static::STATUS_SUCCESS, static::STATUS_UNKNOWN];
        if (!in_array($status, $statuses, true)) {
            throw new InvalidArgumentException(sprintf(
                'Status can be only one of: %s. Got %s',
                implode(', ', $statuses),
                is_object($status)
                    ? get_class($status)
                    : is_scalar($status) ? $status : gettype($status)
            ));
        }

        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return boolean
     */
    public function isSuspended()
    {
        return $this->suspended;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function setSuspended($flag = true)
    {
        $this->suspended = (bool) $flag;
        return $this;
    }

    /**
     * @return string
     *
     * @throws RuntimeException
     */
    public function toString()
    {
        $parts = [];

        if ($this->suspended) {
            $parts[] = '#suspended:';
        }

        $parts[] = $this->minute;
        $parts[] = $this->hour;
        $parts[] = $this->dayOfMonth;
        $parts[] = $this->month;
        $parts[] = $this->dayOfWeek;

        if ('' === (string) $this->command) {
            throw new RuntimeException('Command must be a non empty string');
        }

        $parts[] = $this->command;

        if ($this->logFile) {
            $parts[] = '> ' . $this->logFile;
        }

        if ($this->errorFile) {
            $parts[] = '2> ' . $this->errorFile;
        }

        if ($this->comment) {
            $parts[] = '#' . $this->comment;
        }

        return implode(' ', $parts);
    }
}