<?php
/**
 * 类名：OrderModel
 * 功能：订单主表
 * 版本：V1.0
 * 作者：wcx
 * 时间：2014-09-22
 */
class OrderModel extends CommonModel{
	public function __construct(){
		parent::__construct();
	}

    public static $errCode;
    public static $errMsg;
    
    /**
     * 获取订单列表(前台)
     * @param array $param
     * @param array $fields
     * @param number $pageSize
     * @author jbf
     */
    public function getList($param, $fields = array('id', 'record_no', 'create_time'), $pageSize = 10) {
        $sql  = 'SELECT '.self::buildFields($fields).' FROM  `dp_order` '.self::buildWhereForList($param);
        $list = $this->sql($sql)->sort("ORDER BY `create_time` DESC")->page($param['page'])->perpage($pageSize)->select(array('mysql'));
        
        $orderList = array();
        if (!empty($list)) {
            $idsArr = self::getIdsByList($list);
            if (!empty($idsArr)) {
                $detailList = self::getDetailsByIDs($idsArr);
                if (!empty($detailList)) {
                    foreach ($list AS $order) {
                        $address = !empty($detailList[$order['id']]) && !empty($detailList[$order['id']]['receiptAddress']) ? json_decode($detailList[$order['id']]['receiptAddress'], true) : array();
                        $order['country'] = empty($address) ? '' : $address['country'];
                        $orderList[] = $order;
                    }
                }
            }
        }
        
        return $orderList;
    }
    
    /**
     * 新获取订单列表(分销商前台) | 从本地系统获取
     * @param array $param
     * @param array $fields
     * @param number $pageSize
     * @return multitype:Ambigous <boolean, multitype:Ambigous <unknown, mixed> >
     * @author jbf
     */
    public function newGetList($param, $fields = array('id', 'dp_id', 'orderSys_id', 'sku_num_price', 'record_no', 'source', 'forecast_info', 'actual_info', 'status', 'create_time', 'split_status'), $pageSize = 10) {
        $sql  = 'SELECT '.self::buildFields($fields).' FROM '.(empty($param['database']) ? '' : '`'.$param['database'].'`.').'`dp_order` '.self::buildWhereForList($param);
        $list = $this->sql($sql)->sort("ORDER BY `create_time` DESC")->page($param['page'])->perpage($pageSize)->select(array('mysql'));
        $orderList = array();
        if (!empty($list)) {
            $idsArr = self::getIdsByList($list);
            if (!empty($idsArr)) {
                $detailList = self::getDetailsByIDs($idsArr, $param['database']);
                if (!empty($detailList)) {
                    foreach ($list AS $order) {
                        $detail = empty($detailList[$order['id']]) ? array() : $detailList[$order['id']];
                        $orderList[] = self::buildNewOrder($order, $detail);
                    }
                }
            }
        }
        
        return $orderList;
    }
    
    /**
     * 根据ID列表获取订单
     * @param unknown $ids
     * @param unknown $fields
     * @author jbf
     */
    public function getListByIDs($ids, $database = '', $fields = array('id', 'dp_id', 'orderSys_id', 'record_no', 'status', 'create_time')) {
        $sql  = 'SELECT '.self::buildFields($fields).' FROM '.(empty($database) ? '' : '`'.$database.'`.').'`dp_order` WHERE `id` IN ('.implode(',', $ids).")";
        return $this->sql($sql)->sort("ORDER BY `create_time` DESC") -> select(array( 'mysql'));
    }
    
    /**
     * 根据ID获取单个订单
     * @param int $id
     * @return boolean
     * @author jbf
     */
    public function getOrder($id, $fields = array('id', 'record_no', 'sku_num_price', 'source', 'forecast_info', 'actual_info', 'status', 'create_time')) {
        $id = empty($id) ? 0 : intval($id);
        
        if (!empty($id)) {
            $sql = "SELECT ".self::buildFields($fields)." FROM `"."dp_order` WHERE `id` = '".$id."'";
            return $this -> dbConn -> fetch_first($sql);
        }
        
        return false;
    }
    
    /**
     * 根据ID获取单个订单
     * @param int $id
     * @return boolean
     * @author jbf
     */
    public function newGetOrder($id, $database = '', $fields = array('id', 'dp_id', 'orderSys_id', 'sku_num_price', 'record_no', 'source', 'forecast_info', 'actual_info', 'status', 'create_time', 'delivery_time','tracking_number')) {
        $id = empty($id) ? 0 : intval($id);    
        if (!empty($id)) {
            $sql = "SELECT ".self::buildFields($fields)." FROM ".(empty($database) ? '' : '`'.$database.'`.')."`dp_order` WHERE `id` = '".$id."'";
            $order = $this -> dbConn -> fetch_first($sql);
            if (!empty($order) && !empty($order['create_time'])) {
                $detail = self::getDetailsByID($id, date('Y_m', $order['create_time']), $database);
                return self::buildNewOrder($order, $detail);
            }
        }
    
        return false;
    }
    
    public function getFullOrder($id, $database) {
        $res = null;
        $order = null;
        $detail = null;
        $id = @intval($id);
        
        if (!empty($id)) {
            $orderSql = "SELECT * FROM ".(empty($database) ? '' : '`'.$database.'`.')."`dp_order` WHERE `id`='".$id."'";
            $order = $this->dbConn->fetch_first($orderSql);
            if (!empty($order) && !empty($order['create_time'])) {
                $detailSql = "SELECT * FROM ".(empty($database) ? '`' : '`'.$database.'`.`').C('DB_PREFIX')."order_details".date('_Y_m', $order['create_time'])."` WHERE `id`='".$id."'";
                $detail = $this->dbConn->fetch_first($detailSql);
                if (!empty($detail)) $res = array('order' => $order, 'detail' => $detail);
            }
        }
        
        return $res;
    }
    
    public function getMaxSuffix($recordNumber, $database) {
        $max = 0;
        
        if (!empty($recordNumber)) {
            $orderSql = "SELECT `record_no` FROM ".(empty($database) ? '' : '`'.$database.'`.')."`dp_order` WHERE `record_no` LIKE '".$recordNumber."%' ORDER BY `id` DESC";
            $list = $this->dbConn->fetch_all($orderSql);
            if (!empty($list)) {
                foreach ($list AS $rn) {
                    $rn = explode("##", $rn['record_no']);
                    if (!empty($rn) && !empty($rn[1])) {
                        $num = intval($rn[1]);
                        if ($num > $max) $max = $num;
                    }
                }
            }
        }
        
        return empty($max) ? 0 : ($max+1);
    }
    
    /**
     * 根据Record Number获取导入的文件信息
     * @param unknown $recordNumber
     * @return NULL
     * @author jbf
     */
    public function getFileByRN($recordNumber) {
        $res = null;
    
        if (!empty($recordNumber)) {
            $sql = "SELECT `f`.`id`, `f`.`name`, `f`.`original` FROM `".C('TABLES.UPLOADFILES')."` AS `f` LEFT JOIN `".C('TABLES.IMPORTLOGS')."` AS `l` ON `l`.`file_id` = `f`.`id` WHERE `l`.`order_id` = '".trim($recordNumber)."' AND `l`.`code` = '200' AND `f`.`type` = 'Order' ORDER BY `f`.`id` DESC";
            return $this -> dbConn -> fetch_first($sql);
        }
    
        return $res;
    }
    
    
    /**
     * 根据订单ID及创建时间，获取订单明细
     * @param unknown $id
     * @param unknown $createTime
     * @param unknown $fields
     * @return multitype:unknown mixed |boolean
     * @author jbf
     */
    public function getDetail($id, $dpId = 0, $createTime, $fields = array('id', 'gmtCreate', 'receiptAddress')) {
        if (!empty($id) && !empty($createTime)) {
            $sql = "SELECT ".self::buildFields($fields). " FROM ".(empty($dpId) ? '' : '`opensystem_'.$dpId.'`.')."`"."`dp_order_details_".date('Y_m', $createTime)."` WHERE `id` = '".$id."'";
            $detailTmp = $this -> dbConn -> fetch_first($sql);
            if (!empty($detailTmp)) {
                return array(
                        'id' => $id,
                        'gmtCreate'     => $detailTmp['gmtCreate'],
                        'address'       => json_decode($detailTmp['receiptAddress'], true)
                );
            }
        }
        
        return false;
    }
    
    /**
     * 根据订单ID列表获取多个订单明细
     * @param unknown $idsArr
     * @param unknown $fields
     * @return multitype:unknown
     * @author jbf
     */
    protected function getDetailsByIDs($idsArr, $database = '', $fields = array('id', 'paymentType', 'orderMsgList', 'receiptAddress', 'buyerInfo', 'transportType', 'childOrderList', 'platform', 'paymentTime')) {
        $detailList = array();
        
        if (!empty($idsArr)) {
            foreach ($idsArr AS $key => $ids) {
                $sql = "SELECT ".self::buildFields($fields). " FROM ".(empty($database) ? '' : '`'.$database.'`.')."`dp_order_details_".$key."` WHERE `id` IN (".$ids.") AND `is_delete` = '0'";
                $list = $this -> sql($sql) -> sort("ORDER BY `id` ASC") -> select(array('mysql'));
                if (!empty($list)) {
                    foreach ($list AS $line) {
                        if (!empty($line) && !empty($line['id'])) $detailList[$line['id']] = $line;
                    }
                }
            }
        }
        
        return $detailList;
    }
    
    /**
     * 根据订单ID列表获取多个订单明细
     * @param unknown $idsArr
     * @param unknown $fields
     * @return multitype:unknown
     * @author jbf
     */
    protected function getDetailsByID($id, $tableExts, $database = '', $fields = array('id', 'paymentType', 'orderMsgList', 'receiptAddress', 'buyerInfo', 'transportType', 'childOrderList', 'platform', 'paymentTime', 'user_remarks')) {
        if (empty($id) || empty($tableExts)) return false;
    
        $sql = "SELECT ".self::buildFields($fields). " FROM ".(empty($database) ? '' : '`'.$database.'`.')."`dp_order_details_".$tableExts."` WHERE `id` = '".$id."' AND `is_delete` = '0'";
        return $this -> dbConn -> fetch_first($sql);
    }
    
    /**
     * 从订单列表中取出订单ID
     * @param unknown $list
     * @return multitype:string
     * @author jbf
     */
    protected function getIdsByList($list) {
        $ids = array();
        
        foreach ($list AS $aLine) {
            if (!empty($aLine['id']) && !empty($aLine['create_time'])) {
                if (isset($ids[date('Y_m', $aLine['create_time'])])) $ids[date('Y_m', $aLine['create_time'])] .= "'".$aLine['id']."',";
                else $ids[date('Y_m', $aLine['create_time'])] = "'".$aLine['id']."',";
            }
        }
        
        if (!empty($ids)) {
            foreach ($ids AS $key  => $value) {
                if (!empty($value)) $ids[$key] = substr($value, 0, -1);
            }
        }
        
        return $ids;
    }
    
    
    
    /**
     * 获取订单列表总数
     * @param array $param
     * @return number
     */
    public function getCountByList($param) {
        $sql = 'SELECT COUNT(*) AS `cnt` FROM '.(empty($param['database']) ? '' : '`'.$param['database'].'`.').' `dp_order` '.self::buildWhereForList($param);
        $cnt = $this -> dbConn -> fetch_first($sql);
        if (!empty($cnt) && !empty($cnt['cnt'])) return intval($cnt['cnt']);
        else return 0;
    }
    
    /**
     * 将数组查询条件格式化为查询条件
     * @param array $param
     * @return string
     */
    protected function buildWhereForList($param) {
        $where = "WHERE `is_delete`='0' ";
        
        if (!empty($param)) {
            if (!empty($param['dp_id'])) $where .= "AND `dp_id` = '".intval($param['dp_id'])."' ";
            if (!empty($param['recordnumber'])) $where .= "AND `record_no` LIKE '%".@mysql_escape_string(trim($param['recordnumber']))."%' ";
            if (!empty($param['orderstatus'])) $where .= "AND `status` = '".intval($param['orderstatus'])."' ";
        }
        
        return $where;
    }
    
    /**
     * 将数组字段格式化为字符串字段
     * @param array $fields
     * @return string
     */
    protected function buildFields($fields) {
        if (!empty($fields)) {
            if (is_array($fields)) {
                $fieldsString = '';
                foreach ($fields AS $field) {
                    $colume = explode('.', $field);
                    if (!empty($colume) && count($colume) == 2) {
                        $fieldsString .= " `".$colume[0]."`.`".$colume[1]."`,";
                    } else {
                        $fieldsString .= " `".$colume[0]."`,";
                    }
                    
                }
                
                if (!empty($fieldsString)) {
                    return substr($fieldsString, 0, -1);
                }
            }
        }
        
        if (empty($fields)) return "*";
    }
    
    /**
     * 根据订单及订单明细，生成新的订单对象
     * @param unknown $order
     * @param unknown $orderDetail
     * @return multitype:Ambigous <unknown, mixed> |boolean
     * @author jbf
     */
    protected function buildNewOrder($order, $orderDetail) {
        if (!empty($order)) {
            $newOrder = array();
            foreach ($order AS $key => $value) {
                $decode = json_decode($value, true);
                $newOrder[$key] = empty($decode) ? $value : $decode;
            }
            
            if (!empty($orderDetail)) {
                foreach ($orderDetail AS $key => $value) {
                    $decode = @json_decode($value, true);
                    $newOrder[$key] = empty($decode) ? $value : $decode;
                }
            }
            
            return $newOrder;
        }
        
        return false;
    }
    
    /**
     * 根据Record Number列表，获取对应订单ID
     * @param array $rnList
     * @return multitype:number
     * @author jbf
     */
    public function getIDsByRNList($rnList) {
        $results = array();
        Log::write("\nRecord Number List:".json_encode($rnList));
        if (!empty($rnList)) {
            $devMod = M('Developer');
            foreach ($rnList AS $account => $list) {
                $dpId = $devMod->getIdByERPAccount($account);
                if (!empty($dpId)) {
                    $database = $devMod->getDPDatabase($dpId);
                    $rns = self::buildINString(array_unique($list));
                    if (!empty($rns)) {
                        $sql = "SELECT `id`, `dp_id`, `record_no`, `create_time` FROM `".(empty($database) ? '' :$database."`.`").$this->getTableName()."` WHERE `record_no` IN (".$rns.") AND `dp_id` = '".$dpId."' AND `is_delete`='0'";
                        $odrList = $this->dbConn->fetch_all($sql);
                        if (!empty($odrList)) {
                            foreach ($odrList AS $odr) {
                                if (!empty($odr) && !empty($odr['id']) && !empty($odr['record_no']) && !empty($odr['create_time'])) {
                                    $sql = "SELECT `transportType` FROM `".(empty($database) ? '' :$database."`.`").C('DB_PREFIX')."order_details_".date('Y_m')."` WHERE `id` = ".$odr['id'];
                                    $detail = $this->dbConn->fetch_first($sql);
                                    if (!empty($detail) && !empty($detail['transportType'])) $results[$odr['record_no']] = array('id' => intval($odr['id']), 'dp_id' => $odr['dp_id'], 'recordNumber' => $odr['record_no'], 'transportType' => $detail['transportType'], 'database' => $database);
                                }
                            }
                        }
                    }
                    unset($database); unset($rns);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * 获取对应国家简码
     * @param unknown $country
     * @param string $column
     * @return boolean|unknown
     */
    public function baseFindCountryCode($country) {
        if (empty($country)) return false;
    
        $sql = "SELECT `abbreviation` AS `code` FROM `country_code` WHERE `full_name` = '".$country."' OR `chinese_name` = '".$country."' OR `abbreviation` = '".$country."'";
        $countryCodeArr = $this -> dbConn -> fetch_first($sql);
        if (empty($countryCodeArr) || empty($countryCodeArr['code'])) return $country;
        else {
            return $countryCodeArr['code'];
        }
    }
    
    public function getCountryList() {
        $countryList = array();
        
        $sql = "SELECT `abbreviation` AS `code`, `full_name` AS `en_name`, `chinese_name` AS `zh_name` FROM `country_code` WHERE `is_delete` = 0";
        $allList = $this->dbConn->fetch_all($sql);
        if (!empty($allList)) {
            foreach ($allList AS $country) {
                if (!empty($country) && !empty($country['code'])) $countryList[$country['code']] = array('code' => $country['code'], 'en_name' => $country['en_name'], 'zh_name' => $country['zh_name']);
            }
        }
        
        return $countryList;
    }
    
    /**
     * 根据id更新信息
     * @param array $data
     * @author lzx
     */
    public function updateByParam($param, $data, $database = ''){
        $this->initDbPrefix();
        $key = isset($param['key']) ? trim($param['key']) : 'id';
        $value = isset($param['value']) ? trim($param['value']) : '';
        if (empty($key) || empty($value)){
            self::$errMsg[10110] = get_promptmsg(10110,'更新');
            return false;
        }
    
        $fdata = $this->formatUpdateField($this->getTableName(), $data);
        if ($fdata===false){
            self::$errMsg = $this->validatemsg;
            return false;
        }
        $sql = "UPDATE ".(empty($database) ? "" : ($database.".")).$this->getTableName()." SET ".array2sql($fdata)." WHERE `".$key."` = '".$value."'";
        Log::write("\nSql:".$sql);
        return $this->sql($sql)->update();
    }
    
}
?>