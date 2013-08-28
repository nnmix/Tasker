<?php
/**
 * Class JsonConfig
 *
 * @author: Jiří Šifalda <sifalda.jiri@gmail.com>
 * @date: 27.08.13
 */
namespace Tasker\Config;

use Tasker\Utils\Arrays;

class JsonConfig extends Config
{

	const EXTENSION = 'json';

	/**
	 * @return array
	 */
	public function getConfig()
	{
		return Arrays::objectToArray(json_decode(parent::getConfig()));
	}
}