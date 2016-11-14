<?php
//Без этой директивы PHP не будет перехватывать сигналы
declare(ticks = 1);

namespace gambit\pcntl;

class Job
{

    /**
     * pid current job
     * @var type 
     */
    public $pid = null;

    /**
     * Stop process
     * @var boolean
     */
    public $isStop = false;

    /**
     * Name command
     * @var string
     */
    public $name = '';

    /**
     * 
     * @var type 
     */
    public $jobCommand;

    /**
     * Global runner command
     * @var type 
     */
    public $runner = 'php';

    public function run()
    {
        echo 'Run process pid: ' . $this->pid;
    }
}
