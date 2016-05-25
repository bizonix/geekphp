<?php
/**
 * @description 生成excel方法
 * @param array $head_list 头的行名称
 * @param array $data_list 数据行的内容
 * @author lzj
 */
function get_excel($fileName, $head_list, $data_list, $style_list = null){
	require_once WEB_PATH.'lib/PHPExcel/PHPExcel.php';
	require_once WEB_PATH.'lib/PHPExcel/PHPExcel/Writer/Excel2007.php';
	require_once WEB_PATH.'lib/PHPExcel/PHPExcel/Writer/Excel5.php';
	include_once WEB_PATH.'lib/PHPExcel/PHPExcel/IOFactory.php';

	$objPHPExcel = new PHPExcel();

	$objActSheet = $objPHPExcel->getActiveSheet();
	$objPHPExcel->setActiveSheetIndex(0);
	//设置工作表名称
	$fileName = $fileName.date('Y-m-d');
	$objPHPExcel->getActiveSheet()->setTitle($fileName);

	//表第一行生成，注意ascii编码的大小，最长26个字母
	$header_key = ord("A");//设置列的起始位置
	$colum_value = '1';	//设置列的起始位置
	foreach($head_list as $list) {
		$header_name = chr($header_key);
		$objPHPExcel->setActiveSheetIndex()->setCellValue($header_name.$colum_value, $list);
		//如果行太长此处作判断处理
		$header_key ++;
	}

	//设置样式,例array('width' => array(5,6,7))代表1列2列3列的宽度
	if(!empty($style_list)) {
		foreach($style_list as $key => $list) {
			switch ($key) {
				case 'width':
					$style_key = ord("A");
					foreach($list as $ls) {
						$style_name = chr($style_key);
						$objPHPExcel->getActiveSheet()->getColumnDimension($style_name)->setWidth($ls);
						$style_key ++;
					}
				break;
				case 'type':
					$style_key = ord("A");
					foreach($list as $ls){
						$style_name = chr($style_key);
						if($ls == 'string') $objPHPExcel->getActiveSheet()->getStyle($style_name)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
						if($ls == 'money') $objPHPExcel->getActiveSheet()->getStyle($style_name)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
						$style_key ++;
					}
				break;
				case 'color':
					$style_key = ord("A");
					foreach($list as $ls){
						$style_name = chr($style_key);
						$objPHPExcel->getActiveSheet()->getStyle($style_name)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
						$objPHPExcel->getActiveSheet()->getStyle($style_name)->getFill()->getStartColor()->setARGB($ls);
						$style_key ++;
					}
				break;
			}

		}
	}
	
	//第二行开始内容生成
	$colum_value	= '2';
	foreach($data_list as $list) {
		$info_key	= ord("A");
		foreach($list as $k => $ls) {
			$info_name = chr($info_key);
			$objPHPExcel->setActiveSheetIndex()->setCellValue($info_name.$colum_value, $ls);
			$info_key ++;
		}
		$colum_value ++;
	}

	//设置头信息输出
	$fileName = iconv("utf-8", "gb2312", $fileName);
	header("Content-Type: application/force-download");
	header('Content-type: application/x-msexcel');
	header("Content-Disposition: attachment;filename=\"{$fileName}.xls\"");
	header('Cache-Control: max-age=0');
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
	header("Pragma: no-cache"); 

	// 创建文件格式写入对象实例, uncomment      
	$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);         
	//到文件      
	$objWriter->save("php://output");
	exit;
}