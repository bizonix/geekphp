<?php
require_once(WEB_PATH."lib/PHPExcel/PHPExcel.php");
require_once(WEB_PATH."lib/PHPExcel/PHPExcel/IOFactory.php");
require_once(WEB_PATH."lib/PHPExcel/PHPExcel/Reader/Excel5.php");
require_once(WEB_PATH."lib/PHPExcel/PHPExcel/Reader/Excel2007.php");

class ReadExcel {
    private $rows;
    private $column;
    private $sheet;
    
    public function buildToArray($file) {
        $excelObj = self::buildObj($file);
        if (!empty($excelObj)) {
            $objWorksheet= $excelObj->getActiveSheet();
            $highestRow= $objWorksheet->getHighestRow();
            $highestColumn = $objWorksheet->getHighestColumn();
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);     //总列数
            
//             for($cols =0 ;$cols<=$highestColumnIndex;$cols++){
//                 $headtitle[$cols] =(string)$objWorksheet->getCellByColumnAndRow($cols, 1)->getValue();
//             }
//             if(empty($headtitle[0])){
//                 for($cols =0 ;$cols<=$highestColumnIndex;$cols++){
//                     $headtitle[$cols] =(string)$objWorksheet->getCellByColumnAndRow($cols, 2)->getValue();
//                 }
//             }

            $datas = array();
            /*第二行开始读取*/
            for ($row =2;$row <= $highestRow;$row++){
                for($cols =0 ;$cols<=$highestColumnIndex;$cols++){
                    $datas[$row][$cols] = $objWorksheet->getCellByColumnAndRow($cols, $row)->getValue();
                }
            }
            
            return self::trimColumn($datas);
        } else {
            Log::write("\nNone Support!");
        }
        
        return false;
    }
    
    protected function trimColumn($datas) {
        if (empty($datas) || !is_array($datas)) return false;
        
        $newDatas = array();
        foreach ($datas AS $line) {
            $have = false;
            foreach ($line AS $cloumn) {
                if (!empty($cloumn)) $have = true;
            }
            
            if($have) {
                $newDatas[] = $line;
            }
        }
        
        return $newDatas;
    }
    
    protected  function buildObj($file) {
        if (empty($file)) return false;
        
        $objReader = PHPExcel_IOFactory::createReaderForFile($file);
        if(empty($objReader)) return false;
        
        try {
            $load = $objReader->load($file);
            return $load;
        } catch (PHPExcel_Reader_Exception $e) {
            var_dump($e);
        }
    }
}
?>