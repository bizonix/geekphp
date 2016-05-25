<?php
require('common.php');
$url = 'http://www.aliexpress.com/item/Original-Smartphone-A806-MTK6595-Octa-Core-3G-5-0-inch-1080P-4GB-RAM-16GB-ROM-Dual/32344414889.html?s=p';
$url = 'http://www.aliexpress.com/item/2014-Fashion-Chinese-Dress-Style-Sexy-Flower-Scalloped-Neck-Sleeveless-Dress-party-elegant-Mini-Lace-Dress/32248945801.html';

M('goodsBasic')->getAliexProduct($url);