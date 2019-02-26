<?php
/**
 * Rpc Log 配置
 */
namespace xb\rpclog;

class Config {
	/*
	 * 最大执行时间，单位秒，超过该时间以红色标注执行时间
	 */
	const MAX_EXEC_TIME = 3;

	/*
	 * 最小执行时间，单位秒，小于该时间，以绿色标注执行时间
	 */
	const MIN_EXEC_TIME = 0.09;

	/*
	 * RPC log类型
	 */
	const RPC_LOG_TYPE_CURL = 'curl';
	const RPC_LOG_TYPE_WECHAT = 'wechat';
	const RPC_LOG_TYPE_MYSQL = 'mysql';
	const RPC_LOG_TYPE_REDIS = 'redis';
	const RPC_LOG_TYPE_MEMCACHE = 'memcache';
	const RPC_LOG_TYPE_MODULES = 'modules';
	const RPC_LOG_TYPE_SOCKET = 'socket';
	const RPC_LOG_TYPE_DAO = 'dao';
	const RPC_LOG_TYPE_EXCEPTION = 'exception';

	public $server = [
        'host' => '127.0.0.1',
        'port' => 19001,
    ];
}