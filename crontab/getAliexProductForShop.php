<?php
require('common.php');
$url = 'http://www.aliexpress.com/store/sale-items/614016.html?promotionType=fixed';

M('goodsBasic')->getAliexProductForShop($url);