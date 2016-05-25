<?php
/*
 *事件方法类
 *@add by : junny zou ,date : 20150414
 */
class EventAct extends CommonAct{

	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
	}

	/**
	 * 注册事件
	 * @param 
	 */
	public function registerEvent ($title,$type,$param,$function){
		//事件数据
		$insertData = array(
			"event_title" 	=> $title,
			"event_type"	=> $type,
			"event_content"	=> json_encode($param),
			"call_function"	=> $function,
			"add_time"		=> time()
		);
		return M("Event")->insertData($insertData);
	}

	/**
	 * 执行事件
	 */
	public function runEvent ($where=""){
		if($where) $where = "where ".$where;
		$events = MC("select * from ".C("DB_PREFIX")."event {$where} limit 0,200");
		if(!empty($events)){
			foreach ($events as $k => $v) {
				list($vclass, $vfun) = explode(':', $v["call_function"]);
				$vmethod = ucfirst($vclass."Act");
				if (class_exists($vmethod) && method_exists($vmethod, $vfun)){
					//验证数据
					if (!A($vclass)->$vfun(json_decode($v['event_content'],true))){
						log::writeLog(json_encode(A($vclass)->act_getErrorMsg())." \r\n event = ".json_encode($v),"event/error","error","d");
					}else{
						log::writeLog("{$v['event_title']} | {$v['event_type']}","event/success","success","d");
					}
				}
				$this->deleteEvent(array($v["id"]));
			}
			
		}
		
	}

	/**
	 * 删除事件
	 */
	protected function deleteEvent ($events){
		$eventStr = implode(",", $events);
		return MC("delete from ".C("DB_PREFIX")."event where id IN ({$eventStr})");
	}

	/*
	 * 同步订单的状态
	 */
	public function synOrderStatus($params){
		$sysOrderId 	= $params['id'];
		$orderId 		= $params['order_id'];
		$companyId 		= $params['company_id'];
		$deliveryFrom 	= $params['delivery_from'];
		$comeFrom 		= $params['come_from'];
		$sourcePlatfrom = $params['source_platform'];
		$status 		= $params['handle_status'];
		$note			= $params['note'];
		$trackingNumber = isset($params['tracking_number']) && !empty($params['tracking_number']) ? $params['tracking_number'] : '';
		$deliveryTime 	= isset($params['delivery_time']) && !empty($params['delivery_time']) ? $params['delivery_time'] : '';
		$simpleDetail  	= isset($params['simple_detail']) && !empty($params['simple_detail']) ? $params['simple_detail'] : '';
		$newOrderSysId  = isset($params['new_order_sys_id']) && !empty($params['new_order_sys_id']) ? $params['new_order_sys_id'] : '';

		if($companyId != $comeFrom){
			$updateData = array(
				"handle_status"	=> $status,
				"update_time"	=> time()
			);
			if(!empty($note)){
			    $updateData['note'] = $note;
			}
			if(!empty($trackingNumber)){
			    $updateData['tracking_number'] = $trackingNumber;
			}
			if(!empty($deliveryTime)){
			    $updateData['delivery_time'] = $deliveryTime;
			}
			if(!empty($simpleDetail)){
			    $updateData['simple_detail'] = $simpleDetail;
			}
			$whereData = array(
				"order_id"	=> $orderId,
				"company_id" => $comeFrom,
				"source_platform" => $sourcePlatfrom
			);
			if(!empty($newOrderSysId)){
				$whereData['id'] = $newOrderSysId;
			}

			$orders = M("Order")->getSingleData("id,order_id,company_id,source_platform,come_from,delivery_from,handle_status,note,delivery_time,tracking_number,simple_detail,new_order_sys_id",$whereData);
			if(!empty($orders)){
				$ret = M("Order")->updateDataWhere($updateData,$whereData);
				if($ret){
				    //收集已经发货的订单，一边统计费用
				    if($orders['handle_status'] == 12){
				        A('Statistics')->importStatics(array($orders['id']));
				    }
					if($orders["company_id"] != $orders["come_from"]){
						$orders['handle_status'] 	= $status;
						$orders['note']				= $note;
						$this->registerEvent("Order","handleStatus",$orders,"Event:synOrderStatus");
					}
					return true;
				}
			}
		}
		return false;
	}

}
?>