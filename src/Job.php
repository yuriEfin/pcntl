<?php
//Без этой директивы PHP не будет перехватывать сигналы
declare(ticks = 1);

namespace gambit\pcntl;

class JobErrorException extends \CException
{
    
}

class Job
{
    const TABLE_PARAMS        = 'crm_job_params';
    const TABLE_PARAMS_BY_TYP = 'crm_job_type_params';

    /**
     * ActiveRecord model
     * @var СActiveRecord
     */
    public $model;

    /**
     * Параметры запуска
     * @var type
     */
    public $id;
    public $job_type_id;
    public $task_name;
    public $command;
    public $priority;
    public $interval;
    public $last_time;
    public $is_running;
    public $is_disabled;
    public $is_start_web;
    public $paramsJobCommand;

    /**
     * pid current job
     * @var type 
     */
    public $pid       = null;
    public $pidDaemon = null;

    /**
     * Stop process
     * @var boolean
     */
    public $isStop = false;

    /**
     * @var CConsoleCommand
     */
    public $context = null;

    /**
     * path folder save pid file
     * @var string
     */
    public $pathPid     = 'application.runtime';
    public $pidFilename = 'crm_job_pid_{id}.pid';

    /**
     * Global runner command
     * @var type 
     */
    public $runner = 'php';

    public function updateDateStart(){
	return Yii::app()->db->createCommand('UPDATE crm_job_params SET param_value=:date WHERE job_id=:id AND param_id=2')->execute([
	  ':date' => date("Y-m-d",time()),
	  ':id' => $this->id,
	]);
    }
    public function setParams()
    {
        return $this->paramsJobCommand = $this->context->getJobParams($this->id);
    }

    public function getIsRunning()
    {
        return (bool) $this->is_running;
    }

    public function getIsDisabled()
    {
        return (bool) $this->is_disabled;
    }

    public function getPidFilename()
    {
        $this->pidFilename = strtr($this->pidFilename,
            [
            '{id}' => $this->id,
        ]);
        return $this->pidFilename;
    }

    public function setCommand()
    {
        foreach ($this->paramsJobCommand as $label => $v) {
            $this->command .= ' --'.$label.'='.$v.' ';
        }
    }

    public function setPid()
    {
        $this->pid = md5($this->command);
        file_put_contents($this->context->getPathPid().'/'.$this->getPidFilename(),
            $this->pid);
    }

    public function hasPid()
    {
        return file_exists($this->context->getPathPid().'/'.$this->getPidFilename());
    }

    public function setModel()
    {
        try {
            $this->model = \CrmJob::model()->findByPk($this->id);
            if (!$this->model) {
                throw new JobErrorException('Не удалось проинициализировать модель');
            }
        } catch (Exception $ex) {
            $this->context->logger->log($ex, $this->context);
        }
    }

    public function setActive()
    {

        $this->model->setAttributes([
            'last_time' => time(),
            'pid' => $this->pid,
            'is_running' => 1,
        ]);
        $this->model->update();
    }

    public function setInActive()
    {
        $this->model = \CrmJob::model()->findByPk($this->id);
        $this->model->setAttributes([
            'last_time' => time(),
            'pid' => null,
            'is_running' => 0,
        ]);
        $this->model->update();

        @unlink($this->context->getPathPid().'/'.$this->pidFilename);
    }

    /**
     * @param CConsoleCommand $context
     * @param string $command - name methode $context
     * @param array $params - params method $context
     */
    public function __construct(\CConsoleCommand $context = null, $config = [])
    {
        $this->context = $context;
        foreach ($config as $prop => $v) {
            $this->{$prop} = $v;
        }
        $this->setModel();
        $this->setParams();
        $this->setCommand();
        $this->setPid();
    }

    public function beforeRun()
    {
        return true;
    }

    public function run()
    {
        if ($this->beforeRun()) {
            usleep($this->interval);
            $this->context->runJob();
        }
    }
}
