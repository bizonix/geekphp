<?php
$sku = "MT000101_BL";
$list = array();
if (!empty($sku) && is_string($sku)) {
    $pdtArr = explode(',', $sku);
    if (!empty($pdtArr) && is_array($pdtArr)) {
        foreach ($pdtArr AS $aLine) {
            $product = explode('*', trim($aLine));
            if (!empty($product) && !empty($product[0]) && !empty($product[1])) {
                $list[] = array('sku' => trim($product[0]), 'quantity' => intval($product[1]));
            } else {
                $list[] = array('sku' => trim($product[0]), 'quantity' => 1);
            }
        }
    }
}
die(var_dump($list));
?>