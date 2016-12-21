<?php
//Без этой директивы PHP не будет перехватывать сигналы
declare(ticks = 1);

namespace gambit\pcntl;

class Job
{
    const TABLE_PARAMS = 'crm_job_params';

    public $id;
    public $job_type_id;
    public $task_name;
    public $command;
    public $priority;
    public $interval;
    public $last_time;
    public $is_active;
    public $is_disabled;
    public $is_start_web;
    public $params = [];

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

    /**
     * 
     * @var type 
     */
    public $jobCommand;
    public $paramsJobCommand;

    /**
     * Global runner command
     * @var type 
     */
    public $runner = 'php';

    public function getParams()
    {
        $sql = 'SELECT * FROM '.self::TABLE_PARAMS.' WHERE `job_id`='.$this->id;
        return Yii::app()->db->createCommand()->queryAll();
    }

    /**
     * @param CConsoleCommand $context
     * @param string $command - name methode $context
     * @param array $params - params method $context
     */
    public function __construct(\CConsoleCommand $context = null, $config=[])
    {
        $this->context          = $context;
        foreach ($config as $prop => $v){
            $this->$prop = $v;
        }
    }
}