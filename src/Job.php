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
    public $name    = '';
    public $context = null;
    public $params  = [];

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

    /**
     * @param CConsoleCommand $context
     * @param string $command - name methode $context
     * @param array $params - params method $context
     */
    public function __construct(\CConsoleCommand $context=null, $command, $params)
    {
        $this->context    = $context;
        $this->jobCommand = $command;
        $this->params     = $params;
    }
}