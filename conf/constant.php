<?php
if (!defined('WEB_PATH')) exit();

//日志及debug信息记录配置

return  array(
    'LOG_RECORD'	=>true,													// 开启日志记录
    'LOG_EXCEPTION_RECORD'  => true,										// 是否记录异常信息日志
    'LOG_LEVEL'		=>   'EMERG,ALERT,CRIT,ERR,WARN,NOTIC,INFO,DEBUG,SQL'	// 允许记录的日志级别
);

?>