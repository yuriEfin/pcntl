<?php 


defined('MAX_CHILD_PROCESSES') or define('MAX_CHILD_PROCESSES',10);
defined('SOME_DELAY') or define('SOME_DELAY',1);

$stop_server = false;
$child_processes = array();

while (!$stop_server) {
    if (!$stop_server and (count($child_processes) < MAX_CHILD_PROCESSES)) {
        //TODO: получаем задачу
        //плодим дочерний процесс
        $pid = pcntl_fork();
        if ($pid == -1) {
            //TODO: ошибка - не смогли создать процесс
        } elseif ($pid) {
            //процесс создан
            $child_processes[$pid] = true;
	    var_dump($child_processes,'ch_procs');
        } else {
            $pid = getmypid();
            //TODO: дочерний процесс - тут рабочая нагрузка
            exit;
        }
    } else {
        //чтоб не гонять цикл вхолостую
        sleep(SOME_DELAY); 
    }
    //проверяем, умер ли один из детей
    while ($signaled_pid = pcntl_waitpid(-1, $status, WNOHANG)) {
        if ($signaled_pid == -1) {
            //детей не осталось
            $child_processes = array();
           	echo "Детей не осталось\n";
		 break; 
        } else {
            unset($child_processes[$signaled_pid]);
        }
    }
} 

var_dump($child_processes);
