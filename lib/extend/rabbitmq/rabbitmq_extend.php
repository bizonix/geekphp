<?php
/*
 * rabbitMQ封装函数
 * exchange_declare
	name: $exchange
	type: fanout
	passive: false //don't check is an exchange with the same name exists
	durable: false //the exchange won't survive server restarts
	auto_delete: true //the exchange will be deleted once the channel is closed.
 *
 * @modify lzx, date 20140610
 */
define('AMQP_PASSIVE', true);
define('AMQP_DEBUG', false);
include_once WEB_PATH."lib/extend/rabbitmq/vendor/autoload.php";
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ{

	private $ch;
	private $msg;
	private $conn;
	private $consumer_tag;


	public function __construct($system=''){
		if (!empty($system)){
			$this->connection($system);
		}
	}

	public function __destruct(){
		if (is_object($this->ch)) $this->ch->close();
		if (is_object($this->conn)) $this->conn->close();
	}

	/**
	 * 实例化后rabbitMQ连接
	 * @param string $system  系统名称
	 * @return bool
	 * @author lzx
	 */
	public function connection($system){
		if (empty($this->conn)){
			return $this->resetConnection($system);
		}
		return true;
	}

	/**
	 * 强制重置rabbitMQ连接
	 * @param string $system  系统名称
	 * @return bool
	 * @author lzx
	 */
	public function resetConnection($system){
		$rmqconfig = C('RMQ_CONFIG');
		if (isset($rmqconfig[$system])){
			list($host, $user, $password, $port, $vhost) = $rmqconfig[$system];
			$this->conn = new AMQPConnection($host, $port, $user, $password, $vhost);
			$this->consumer_tag = 'consumer'. getmypid();
			$this->ch	= $this->conn->channel();
			return true;
		}
		return false;
	}

	/**
	 * 基础模型之消息发布
	 * @param string $exchange		交换器名称
	 * @param string|array $msg		发布内容
	 * @param string $mqtype		发布消息的类型
	 * @return bool
	 * @author lzx
	 */
	public function basicPublish($exchange, $msg, $mqtype='direct'){
		$this->ch->exchange_declare($exchange, $mqtype, false, false, false);
		$tosend = new AMQPMessage(is_array($msg) ? json_encode($msg) : $msg, array('content_type'=>'text/plain', 'delivery_mode'=>2));
		$this->ch->basic_publish($tosend, $exchange);
	}

	/**
	 * 基础模型之消息接受
	 * @param string $exchange
	 * @param string $queue
	 * @param array $callback
	 * @param string $mqtype
	 * @return string
	 * @author lzx
	 */
	public function basicReceive($exchange, $queue, $callback=array('RabbitMQ', 'process_message'), $mqtype='direct'){
		$this->ch->queue_declare($queue, false, true, false, false);
		$this->ch->exchange_declare($exchange, $mqtype, false, false, false);
		$this->ch->queue_bind($queue, $exchange);
		$this->ch->basic_consume($queue, $this->consumer_tag, false, false, false, false, $callback);
		while (count($this->ch->callbacks)) {
			$this->ch->wait();
		}
	}

	/**
	 * Pub/Sub 之消息发布
	 * @param string $exchange		交换器名称
	 * @param string|array $msg		发布内容
	 * @param string $mqtype		发布消息的类型
	 * @return bool
	 * @author lzx
	 */
	public function queuePublish($exchange, $msg, $mqtype='fanout'){
		$this->ch->exchange_declare($exchange, $mqtype, false, false, false);
		$tosend = new AMQPMessage(json_encode($msg), array('content_type'=>'text/plain', 'delivery_mode'=>2));
		$this->ch->basic_publish($tosend, $exchange);
	}

	/**
	 * Pub/Sub 之消息接受
	 * @param string $exchange		交换器名称
	 * @param string $queue			队列名称
	 * @param string $callback		注册回调函数
	 * @param string|array $msg		发布内容
	 * @param string $mqtype		发布消息的类型
	 * @return bool
	 * @author lzx
	 */
	public function queueSubscribe($exchange, $queue, $callback=array('RabbitMQ', 'process_message'), $mqtype = 'fanout'){
		$this->ch->queue_declare($queue, false, true, false, false);
		$this->ch->queue_bind($queue, $exchange);
		$this->ch->basic_consume($queue, $this->consumer_tag, false, false, false, false, $callback);
		while (count($this->ch->callbacks)) {
			$this->ch->wait();
		}
	}

	/**
	 * Pub/Sub 之批量消息接受，默认接受200条数据
	 * @param string $exchange		交换器名称
	 * @param string $queue			队列名称
	 * @param int $limit			返回条数
	 * @param bool $extral			返回数据类型， true为json_decode， false为json
	 * @return array
	 * @author lzx
	 */
	public function queueSubscribeLimit($exchange, $queue, $limit=200, $extral=true, $mqtype = 'fanout'){

		$messageCount = $this->ch->queue_declare($queue, false, true, false, false);
		$this->ch->queue_bind($queue, $exchange);
		$i = 0;
		$max = $limit<200 ? $limit : 200;
		$orderids = array();
		while ($i<$messageCount[1] && $i<$max) {
			$this->msg = $this->ch->basic_get($queue);
			$this->ch->basic_ack($this->msg->delivery_info['delivery_tag']);
			if($extral === false){
				array_push($orderids, $this->msg->body);
			}else{
				array_push($orderids, json_decode($this->msg->body, true));
			}
			$i++;
		}
		return $orderids;
	}

	/**
	 * 销毁队列中的数据
	 * @return bool
	 * @author lzx
	 */
	public function basicAck(){
		$this->ch->basic_ack($this->msg->delivery_info['delivery_tag']);
	}

	/**
	 * 默认回调函数
	 * @param object $msg
	 * @return bool
	 * @author lzx
	 */
	public function process_message($msg){
		//回调函数，已经废弃使用
		echo "\n--------{$msg->body}----".__FILE__."----Missing registration function in class <RabbitMQ>------\n";
	}
}
