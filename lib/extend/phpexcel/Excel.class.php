<?php
class Excel {

	private static $_instance;
	public $writexls;
	private $rowcount;

	public function __construct() {
		
    }

	public static function singleton() {
		if(!isset(self::$_instance)) {
			$c = __CLASS__;
			self::$_instance = new $c;
		}
		return self::$_instance;
	}
	public function test($num){
		$this->_PHPExcel = new PHPExcel();
		print_r($this->_str2Array('a-aa'));
		print_r($this->_str2Array('a,c,b,s,n'));
		print_r($this->_getColumnchar($num));
	}
	public function readExcel($inputFileName, $column='', $row=''){
		
		vendor("PHPExcel.PHPExcel.IOFactory");
		
		$excelData = array();
		$PHPExcel = PHPExcel_IOFactory::load($inputFileName);
		$Worksheet = $PHPExcel->getActiveSheet();
		if($column===''&&$row===''){
			$excelData = $Worksheet->toArray(null,true,true,true);
		}else if($column!==''&&$row===''){
			$highestRow = $Worksheet->getHighestRow();
			$columns = $this->_str2Array($column);
			for ($row=1; $row<=$highestRow; $row++) {
				foreach($columns AS $col){
					$excelData[$row][] = trim((string)$Worksheet->getCellByColumnAndRow($col, $row)->getValue());
				}
			}
		}else if($column===''&&$row!==''){
			$highestColumn = $Worksheet->getHighestColumn();
			$rows = $this->_str2Array($row);
			foreach($rows AS $r){
				for ($col=1; $col<=$highestColumn; $col++) {
					$excelData[$r][] = trim((string)$Worksheet->getCellByColumnAndRow($col, $r)->getValue());
				}
			}
		}else{
			$rows = $this->_str2Array($row);
			$columns = $this->_str2Array($column);
			foreach($rows AS $r){
				foreach($columns AS $col){
					$excelData[$r][] = trim((string)$Worksheet->getCellByColumnAndRow($col, $r)->getValue());
				}
			}
		}
		return $excelData;
	}
	public function writeExcelarray($datas, $row, $multi=false){

		$this->_getWritexls();

		$worksheet = $this->writexls->getActiveSheet();
		$writelists = $multi ? $datas : array($datas);
		$worksheet->fromArray($writelists, NULL, 'A'.$row);
	}
	public function writeExcelsingle($key, $data){

		$this->_getWritexls();
		
		$worksheet = $this->writexls->getActiveSheet();
		$worksheet->setCellValue($key, $data);
	}
	public function setExcelwidth($data){
		$columns = $this->_getColumnchar(count($data));
		foreach ($data AS $k=>$colwidth){
			$this->writexls->setActiveSheetIndex(0)->getColumnDimension($columns[$k])->setWidth($colwidth);
		}
		$endchar = array_pop($columns);
	}
	public function saveExcel($filename, $xls=true, $filepath=''){
		$title		= $filename.date('Y-m-d');
		$titlename		= $filename.date('Y-m-d').".xls";
		$this->writexls->getActiveSheet()->setTitle($title);
		$this->writexls->setActiveSheetIndex(0);

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header("Content-Disposition: attachment;filename={$titlename}");
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($this->writexls, 'Excel5');
		$objWriter->save('php://output');
		//$objWriter->save($filepath.$titlename);
		exit;
	}
	private function _getWritexls(){
		if(empty($this->writexls)){
			vendor("PHPExcel.PHPExcel");
			$this->writexls = new PHPExcel();
		}
	}
	private function _getColumnchar($num){
		
		$times = 1;
		$COLUMNS = array();
		for($i=0; $i<ceil($num/26); $i++){
			foreach (range('A', 'Z') as $letter) {	
				$char = $i==0 ? $letter : chr($i+96).$letter;
				array_push($COLUMNS, strtoupper($char));
				if ($times>=$num){
					break;
				}
				$times++;
			}
		}
		return $COLUMNS;
	}
	private function _str2Array($str){

		$result = array();
		$str = strtoupper($str);

		if(strpos($str, ',')!==false){
			$result = array_map('trim', array_filter(explode(',', $str)));
			sort($result);
			return $result;
		}else if(strpos($str, '-')!==false){
			$result = array_map('trim', explode('-', $str));
			sort($result);
			list($start, $end) = $result;
			return range($start, $end);
		}else{
			return array(trim($str));
		}
	}
}
?>
