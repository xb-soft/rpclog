<?php
/**
 * 简单的RPCLOG客户端，目前为了快速的检测一些运行问题，由于时间的关系木有一些功能
 *
 * 2015.2.5 9:12
 * @author enze.wei <[enzewei@gmail.com]>
 */
namespace xb\rpclog;

/**
 * 使用UDP方式连接Rpc Log Server
 *
 * @example
 * 
 * //get microtime
 * RpcLog::getmicroTime();
 * //记录日志
 * RpcLog::log($message, $startTime, $endTime, $logType);
 * **************************************************************
 * $logType格式													*
 * RPC_LOG_TYPE_CURL		=> curl								*
 * RPC_LOG_TYPE_WECHAT 		=> wechat							*
 * RPC_LOG_TYPE_MYSQL 		=> mysql							*
 * RPC_LOG_TYPE_REDIS 		=> redis							*
 * RPC_LOG_TYPE_MEMCACHE 	=> memcache							*
 * RPC_LOG_TYPE_MODULES	 	=> modules							*
 * **************************************************************
 */

class RpcLog {

	/**
	 * 需要记录的日志信息
	 * @var string
	 */
	static private $_message = '';

	/**
	 * 结束换行符
	 * @var string
	 */
	static private $_endLine = "\n";

	static private $_udpHost = '';
	static private $_udpPort = '';

	private function __construct() {
		//empty
	}

	/**
	 * 记录日志
	 * @param  [float] $execTime 微秒
	 * @return [void]
	 */
	static private function _sendLog($execTime) {
		/*
		 * 创建UDP连接
		 */
		$fp = stream_socket_client('udp://' . self::$_udpHost . ':' . self::$_udpPort, $errno, $errstr);
		if (!$fp) {
			echo "ERROR: $errno - $errstr<br />\n";
		} else {
			/*
			 * 简单发送日志信息
			 */
			fwrite($fp, self::$_message);
			fclose($fp);
		}
		return;
	}

	/*************************************************************************************************/

	/**
	 * 实现环境配置加载接口
	 * @param  [string] $env 环境
	 * @return [void]
	 */
	static public function init() {
		$server = Loader::loadConfig('rpclog');
		self::$_udpHost = $server['host'];
		self::$_udpPort = $server['port'];
	}

	/**
	 * 静态魔术方法，主要起到自动识别当前环境
	 * 
	 * @param  [string] $method 调用的方法名
	 * @param  [mix] $args   $method方法需要的参数
	 * @return [mix]         $method返回值，或方法不存在
	 */
	static public function __callStatic($method, $args) {
		$method = '_' . $method;
		if (true === method_exists(get_called_class(), $method)) {
			self::init();
			return call_user_func_array(array(get_called_class(), $method), $args);
		} else {
			return false;
		}
	}


	/****************************************** API *******************************/
	
	/**
	 * 记录日志
	 * @param  [string] $message   日志信息
	 * @param  [float] $startTime [开始执行时间]
	 * @param  [float] $endTime   [执行结束时间]
	 * @param  [string] $logType   [日志类型]
	 * @return [void]
	 */
	static private function _log($message, $startTime, $endTime, $logType) {
		$execTime = number_format((float)($endTime - $startTime), 8, '.', '');

		/*
		 * 针对三种情况，高亮显示执行时间
		 */
		if ($execTime >= Config::MAX_EXEC_TIME) {
			$execTime = "\033[41m" . $execTime . "s\033[0m";
			$execState = "\033[41mSlow\033[0m";
		} else if ($execTime <= Config::MIN_EXEC_TIME) {
			$execTime = "\033[32m" . $execTime . "s\033[0m";
			$execState = "\033[32mFast\033[0m";
		} else {
			$execTime = "\033[36m" . $execTime . "s\033[0m";
			$execState = "\033[36mNormal\033[0m";
		}

		$debugTrace = debug_backtrace();

		/*
		 * 剔除魔术方法对应的调用信息
		 */
		$debugTrace = array_slice($debugTrace, 3);

		$errorType = '';

		if (preg_match('/errcode|error/i', $message)) {
			$errorType = "[\033[41mError\033[0m] ";
			if (strstr($message, '"errcode":0', true)) {
				$errorType = '';
			}
			if (strstr($message, '"errcode": 0', true)) {
				$errorType = '';
			}
		}

		self::$_message = $errorType . "[\033[36m" . $logType . "\033[0m] [" . $execState . "] <" . $execTime . '> ' . $message;
		self::$_message .= ' [' . $debugTrace[0]['file'] . ':' . $debugTrace[0]['line'] . '] <';
		self::$_message .= join(' => ', array_map(function ($item) {
				if (true === isset($item['class'])) {
					return $item['class'] . '::' . $item['function'];
				}
			}, $debugTrace));

		self::$_message .= '>' . self::$_endLine;
		
		self::_sendLog($execTime);
	}

	/**
	 * 获取微秒
	 * @return [float] 微秒
	 */
	static public function getMicrotime() {
		list($msec, $sec) = explode(' ', microtime());
		return ((float)$msec + (float)$sec);
	}
}