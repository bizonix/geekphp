<?php
class InterfacePictureListModel extends InterfaceModel {
	
	public function findPictureBySPUs($spu) {
		$request = self::buildRequest(array('spu' => $spu, 'picType' => 'G'), __FUNCTION__);
		$result = callOpenSystem($request);
		$rs = json_decode($result, true);
		if (empty($rs['error_response'])) return $rs['data'];
		else return false;
	}
}
?>