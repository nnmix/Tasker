<?php
/**
 * Class ThreadsRunner
 *
 * @author: Jiří Šifalda <sifalda.jiri@gmail.com>
 * @date: 27.08.13
 */
namespace Tasker\Threading;

use Tasker\Configuration\ISetting;
use Tasker\IResultSet;
use Tasker\Output\IWriter;
use Tasker\Tasks\ITask;
use Tasker\Object;
use Tasker\IRunner;

class ThreadsRunner extends Object implements IRunner
{

	/** @var \Tasker\Configuration\ISetting  */
	private $setting;

	/** @var \Tasker\ResultSet  */
	private $resultSet;

	/** @var array|Thread[] */
	private $threads = array();

	/** @var \Tasker\Threading\ResultStorage  */
	private $resultStorage;

	/**
	 * @param ISetting $setting
	 * @param IResultSet $resultSet
	 */
	function __construct(ISetting $setting, IResultSet $resultSet)
	{
		$this->setting = $setting;
		$this->resultSet = $resultSet;
		$this->resultStorage = new ResultStorage($setting->getThreadsResultStorage());
	}

	/**
	 * @param \Tasker\Tasks\ITask[]|array $tasks
	 * @return \Tasker\IResultSet
	 */
	public function run(array $tasks)
	{
		$this->processTasks($tasks);

		while(!empty($this->threads)) {
			foreach($this->threads as $name => $thread) {
				if(!$thread->isAlive()) {
					unset($this->threads[$name]);
					$this->processTaskResult($name);
				}
			}

			$this->processTasks($tasks);
			$this->pause();
		}

		return $this->resultSet;
	}

	/**
	 * @param ITask $task
	 */
	public function runTask(ITask $task)
	{
		try {
			$result = $task->run($this->setting->getContainer()->getConfig($task->getSectionName()));

			if($result !== null) {
				$this->resultStorage->writeSuccess($task->getName(), $result);
			}
		}catch (\Exception $ex) {
			$this->resultStorage->writeError($task->getName(), $ex->getMessage());
		}
	}

	/**
	 * @param array $tasks
	 */
	protected function processTasks(array &$tasks)
	{
		$diff = count($this->threads) - $this->setting->getThreadsLimit();
		if($diff < 0) {
			foreach ($tasks as $name => $task) {
				if($diff >= 0) {
					break;
				}

				$this->startThread($task);
				unset($tasks[$name]);
				$diff++;
			}
		}
	}

	/**
	 * @param ITask $task
	 */
	protected function startThread(ITask $task)
	{
		$this->resultSet->addResult('Running task "' . $task->getName() . '"', IWriter::INFO);
		$thread = new Thread(array($this, 'runTask'));
		$thread->start($task);
		$this->threads[$task->getName()] = $thread;
	}

	/**
	 * @return $this
	 */
	private function pause()
	{
		// let the CPU do its work
		sleep($this->setting->getThreadsSleepTime());
		return $this;
	}

	/**
	 * @param $taskName
	 */
	private function processTaskResult($taskName)
	{
		$result = $this->resultStorage->read($taskName);
		if($result[1] !== null) {
			$this->resultSet->addResult('Task "'. $taskName . '" completed with result:', IWriter::INFO);
			if(is_array($result[1])) {
				$this->resultSet->addResults($result[1]);
			}else{
				$this->resultSet->addResult($result[1], $result[0]);
			}
		}else{
			$this->resultSet->addResult('Task "'. $taskName . '" completed!', IWriter::SUCCESS);
		}
	}
}