<?php
require('common.php');
$url = 'http://www.ebay.com/itm/Outdoor-Garden-Solar-Power-Powered-Light-Gutter-Fence-Yard-LED-Lamp-Wall-Roof-/251648289657?pt=LH_DefaultDomain_0&hash=item3a97682b79';

M('goodsBasic')->getEbayProduct($url);