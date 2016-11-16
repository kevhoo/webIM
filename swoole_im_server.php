<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 2016/11/16
 * Time: 下午6:08
 */

$port       = isset($argv[1]) && is_numeric($argv[1]) ? $argv[1] : 9051;
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$redis->select(0);

// 1. create websocket server
$server     = new swoole_websocket_server("0.0.0.0", $port);

$server->set(array(
	'worker_num' => 2,
));

$server->on('workerstart', function($serv, $worker_id) use ($redis){

});

// 2. listen open event
$server->on('open', function(swoole_websocket_server $server, $request){
	// save user info to online_user_list
	$onlineUsers    = apcu_fetch('online_user');
	if ($onlineUsers === false || !is_array($onlineUsers)){
		$onlineUsers    = array();
	}
	$onlineUsers[$request->fd]  = array('nickname'=>'' , 'message'=> '', 'info'=>$server->connection_info($request->fd));
	apcu_store('online_user', $onlineUsers);
});

// 3. listen message receive
$server->on('message', function(swoole_websocket_server $server, $frame) use ($redis){
	$data   = json_decode($frame->data, true);
	foreach ($server->connections as $fd) {
		$redis->publish('news', json_encode(array('nickname' => $data['nickname'], 'message' => $data['message'])));
		$server->push($fd, json_encode(array('nickname' => $data['nickname'], 'message' => $data['message'])));
	}
});

// 4. listen close event
$server->on('close', function($server, $fd){
	// pop user from online_user_list
	$onlineUsers    = apcu_fetch('online_user');
	unset($onlineUsers[$fd]);
	apcu_store($onlineUsers);
});

// 5. start server
$server->start();


