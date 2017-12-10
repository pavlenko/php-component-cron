# Cron component
### Installation
```sh
php composer.phar require pe/component-cron:^1.0
```
### Usage
```php
use PE\Component\Cron\Crontab;
use PE\Component\Cron\Job;

// Instantiate with default binary path
$crontab = new Crontab();

// Or instantiate with custom binary path
$crontab = new Crontab('/usr/sbin/crontab');

// Get all jobs
$crontab->all();// returns array of Job instances

// Create new job from string
$job = Job::fromString('*/5 * * * * command');

// Or create programmatically
$job = (new Job())->setMinute('*/5')->setCommand('command');

// Add job
$crontab->add($job);

// Get job by index
$job = $crontab->get(0);

// Remove job by index
$crontab->remove(0);
```