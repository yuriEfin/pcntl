<?php
declare(ticks = 1); //Без этой директивы PHP не будет перехватывать сигналы

namespace gambit\pcntl;

use Yii;
use gambit\pcntl\Job;

/**
 * @source http://leonid.shevtsov.me/post/mnogoprocessovye-demony-na-php/
 * @author Gambit
 * @email php.gambit@yandex.ru
 */
class Daemon
{
    public static $stop_server = false;

    /**
     * Max count child process
     * Default 10
     * @var integer 
     */
    public $maxChildProcess = 15;

    /**
     * Sleep php
     * @var integer 
     */
    public $someDelay = 1;

    /**
     * path directiry save pid file
     * @var string 
     */
    public $pathPid = null;

    /**
     * Default path directiry save pid file
     * @var string 
     */
    private $pathPidDefault = 'runtime.daemonJob';

    /**
     * pid current fork
     * @var integer 
     */
    public $pid;

    /**
     * singleton
     * @var type 
     */
    private static $_instance = null;

    /**
     * Stack result data Job
     * @var array
     */
    public static $result = [];

    /**
     * stack process
     * @var array
     */
    public static $child_processes = [];

    public function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function createPathPid()
    {
        $pathPid = $this->getPathPid();
        @mkdir($pathPid, 0777, true);
    }

    public function getDefaultPathPid()
    {
        return $this->getAlias($this->pathPidDefault);
    }

    public function getPathPid()
    {
        if (is_null($this->pathPid)) {
            $this->pathPid = $this->getDefaultPathPid();
        } else {
            $this->setPathPid($this->pathPid);
        }
        return $this->pathPid;
    }

    public function setPathPid($value)
    {
        if ((is_dir($value) && file_exists($value) !== false) && is_writable($value)) {
            if (stripos($value, '@') !== false || substr_count($value, '.') != 0) {
                $value = $this->getAlias($value);
            }
            $this->pathPid = $value;
        } else {
            throw new Exception('Failed path "'.$value.'" or is not writable');
        }
    }

    public function getAlias($alias)
    {
        return Yii::getPathOfAlias($alias);
    }

    public function init()
    {
        $this->createPathPid();
        //регистрируем обработчик
        pcntl_signal(SIGTERM,
            function ($signo) {
            switch ($signo) {
                case SIGTERM:
                    self::$stop_server = true;
                    // handle shutdown tasks
                    echo "Caught SIGTERM...\n";
                    exit;
                    break;
                case SIGHUP:
                    // handle restart tasks
                    echo "Caught SIGHUP...\n";
                    break;
                case SIGUSR1:
                    echo "Caught SIGUSR1...\n";
                    break;
                default: {
                        //все остальные сигналы
                    }
            }
        });

        return $this;
    }

    public function setMaxChild($countProcess)
    {
        $this->maxChildProcess = $countProcess;
        return $this;
    }

    public function getMaxChild()
    {
        return $this->maxChildProcess;
    }

    public function runJob($jobs)
    {
        if (!is_array($jobs)) {
            $jobs = [$jobs];
        }
        self::$child_processes = array();
        while (!self::$stop_server) {
            $countChild = count(self::$child_processes);
            if (!self::$stop_server && ($countChild <= $this->getMaxChild())) {
                //TODO: получаем задачу
                //плодим дочерний процесс
                $this->pid = pcntl_fork();
                if ($this->pid == -1) {
                    exit('Не удалось создать форк');
                } elseif ($this->pid) {
                    exit(1);
                } else {
                    foreach ($jobs as $job) {
                        //процесс создан
                        self::$child_processes[$this->pid] = true;
                        $status                            = 0;
                        //TODO: дочерний процесс - тут рабочая нагрузка
                        $this->pid                         = $job->pidDaemon                    = getmypid();
                        // job item
                        if ($job instanceof Job) {
                            try {
                                $job->context->job = $job;
                                echo $job->pidDaemon.PHP_EOL;
                                $job->run();
                            } catch (Exception $ex) {
                                dump($ex->getMessage());
                            }
                        }
                    }
                    return;
                }
            } else {
                //чтоб не гонять цикл вхолостую
                sleep($this->someDelay);
            }
            //проверяем, умер ли один из детей
            while ($signaled_pid = pcntl_waitpid(-1, $status, WNOHANG)) {
                if ($signaled_pid == -1) {
                    //детей не осталось
                    self::$child_processes = array();
                    break;
                } else {
                    unset(self::$child_processes[$signaled_pid]);
                }
            }
        }
    }

    public function isDaemonActive($pid_file)
    {
        if (is_file($pid_file)) {
            $this->pid = file_get_contents($pid_file);
            //проверяем на наличие процесса
            if (posix_kill($pid, 0)) {
                //демон уже запущен
                return true;
            } else {
                //pid-файл есть, но процесса нет
                if (!unlink($pid_file)) {
                    //не могу уничтожить pid-файл. ошибка
                    exit(-1);
                }
            }
        }
        return false;
    }
}