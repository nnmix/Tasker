<?php
/**
 * Class Results
 *
 * @author: Jiří Šifalda <sifalda.jiri@gmail.com>
 * @date: 28.08.13
 */
namespace Tasker;

use Tasker\Output\IWriter;
use Tasker\Output\Writer;

class ResultSet
{

	/** @var bool  */
	public $verbose = true;

	/** @var array  */
	private $results = array();

	/**
	 * @param array $results
	 */
	function __construct(array $results = array())
	{
		$this->results = $results;
	}

	/**
	 * @return array
	 */
	public function getResults()
	{
		return $this->results;
	}

	/**
	 * @param boolean $verbose
	 * @return $this
	 */
	public function setVerboseMode($verbose)
	{
		$this->verbose = (bool) $verbose;
		return $this;
	}

	/**
	 * @param $result
	 * @param $type
	 * @return $this
	 */
	public function addResult($result, $type = Writer::SUCCESS)
	{
		$result = array($result, $type);
		if($this->verbose === true) {
			$this->printResult($result);
		}

		$this->results[] = $result;
		return $this;
	}

	/**
	 * @throws \ErrorException
	 */
	public function dump()
	{
		if($this->verbose === false) {
			if(!count($this->results)) {
				$this->results[] = array('NO TASKS EXECUTED', Writer::INFO);
			}

			foreach ($this->results as $result) {
				if(!is_array($result)) {
					$result = array($result, Writer::SUCCESS);
				}
				$this->printResult($result);
			}
		}
	}

	/**
	 * @param array $result
	 */
	protected function printResult(array $result)
	{
		list($message, $type) = $result;
		if($message instanceof \Exception) {
			Writer::writeException($message);
		}else{
			Writer::writeLn($message, $type);
		}
	}
}