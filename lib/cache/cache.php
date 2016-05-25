<?php
class Cache
{
    private $mmc 	= null;
    private $group 	= null;
    private $version = 1;
    function __construct($group){
        if(!class_exists('Memcache')){
            $this->mmc = false;
            return;
        }
        $this->mmc = new Memcache();
        $cache_config	=	C("CACHE_CONFIG");
        foreach ($cache_config as $v){
        	//ip   port
        	$this->mmc->addServer($v[0], $v[1]);
        }
        //可以设置多组服务器
        //$this->mmc->addServer('192.168.1.6', 11211);
        $this->group = $group;
        $this->version = $this->mmc->get('version_'.$group);
    }
    function add($key, $var, $expire=3600){
        if(!$this->mmc)return;
        return $this->mmc->add($this->group.'_'.$this->version.'_'.$key, $var, false, $expire);
    }
    function replace($key, $var, $expire=3600){
        if(!$this->mmc)return;
        return $this->mmc->replace($this->group.'_'.$this->version.'_'.$key, $var, false, $expire);
    }
    function set($key, $var, $expire=3600){
        if(!$this->mmc)return;
        return $this->mmc->set($this->group.'_'.$this->version.'_'.$key, $var, 0, $expire);
    }
	function set_extral($key, $var, $expire=3600){
        if(!$this->mmc)return;
        return $this->mmc->set($key, $var, 0, $expire);
    }
    function get($key){
        if(!$this->mmc)return;
        return $this->mmc->get($this->group.'_'.$this->version.'_'.$key);
    }
	function get_extral($key){
        if(!$this->mmc)return;
        return $this->mmc->get($key);
    }
    function incr($key, $value=1){
        if(!$this->mmc)return;
        return $this->mmc->increment($this->group.'_'.$this->version.'_'.$key, $value);
    }
    function decr($key, $value=1){
        if(!$this->mmc)return;
        return $this->mmc->decrement($this->group.'_'.$this->version.'_'.$key, $value);
    }
    function delete($key){
        if(!$this->mmc)return;
        return $this->mmc->delete($this->group.'_'.$this->version.'_'.$key);
    }
    function flush(){
        if(!$this->mmc)return;
        ++$this->version;
        $this->mmc->set('version_'.$this->group, $this->version);
    }
}
?>