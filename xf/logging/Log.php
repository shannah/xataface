<?php
namespace xf\logging;

define("XF_LOG_LEVEL_ERROR", 5);
define("XF_LOG_LEVEL_WARNING", 4);
define("XF_LOG_LEVEL_INFO", 3);
define("XF_LOG_LEVEL_DEBUG", 1);

// THe minimum level of a log messagse (see xf_log()) that will result 
// in a logged message.
//if (!defined('XF_MIN_LOG_LEVEL')) define("XF_MIN_LOG_LEVEL", 3);

function init_logging() {
    if (defined('XF_LOGGING_INITIALIZED')) {
        return;
    }
    define('XF_LOGGING_INITIALIZED', 1);
    if (!defined('XF_MIN_LOG_LEVEL')) {
        $app = \Dataface_Application::getInstance();
        if (@$app->_conf['_logging'] and @$app->_conf['_logging']['level']) {
            $level = $app->_conf['_logging']['level'];
            $levelNum = XF_LOG_LEVEL_INFO;
            switch (strtolower($level)) {
                case 'error': $levelNum = XF_LOG_LEVEL_ERROR; break;
                case 'warning': $levelNum = XF_LOG_LEVEL_WARNING; break;
                case 'debug': $levelNum = XF_LOG_LEVEL_DEBUG; break;
            }
            define('XF_MIN_LOG_LEVEL', $levelNum);
        
        } else {
            define('XF_MIN_LOG_LEVEL', 3);
            
        } 
    }
    
    
    
}

function xf_log($level, $message, $tags=null, $includeContext=false) {
    init_logging();
    if ($level < XF_MIN_LOG_LEVEL) {
        // Log message does not meet minimum threshold, so we do nothing here.
        return;
    }
	$app = \Dataface_Application::getInstance();
	if (!@$app->_conf['_logging']) {
		error_log($message);
		return;
	}
	$conf = $app->_conf['_logging'];
	if (@$conf['function']) {
		$func = $conf['function'];
		${func}($level, $message, $tags, $includeContext);
		return;
	}
	
	if (@$conf['table']) {
		
		$loggingTable = \Dataface_Table::loadTable($conf['table']);
		if (\PEAR::isError($loggingTable)) {
			error_log("Logging table ".$conf['table']." not found.  Check config in conf.ini [_logging] section.");
			error_log($message);
			return;
		}
		$fields = [
			'message' => $message,
			'context' => null,
			'log_level' => $level,
			'username' => null,
			'action' => null,
			'table' => null,
			'tags' => $tags
		];
	    $backtrace = null;
	    if ($includeContext instanceof \Exception) {
	        $backtrace = $includeContext->getTraceAsString();
	    } else if ($includeContext) {
	        $backtrace = json_encode(debug_backtrace());
	    }
		$fields['context'] = $backtrace;
		if (class_exists('Dataface_AuthenticationTool')) {
			$auth = \Dataface_AuthenticationTool::getInstance();
			$fields['username'] = $auth->getLoggedInUsername();
		}
		$query = $app->getQuery();
		$fields['action'] = $query['-action'];
		$fields['table'] = $query['-table'];
		$logRecord = new \Dataface_Record($loggingTable->tablename);
		$data = [
			'message' => $message,
		];
		foreach ($fields as $key=>$value) {
			$fld = $loggingTable->getField($key);
			if (!$fld) $fld = $loggingTable->getFieldWithTag('log.'.$key);
			if ($fld) {
				$logRecord->setValue($fld['name'], $value);
			}
		}
		try {
			$res = $logRecord->save();
			if (\PEAR::isError($res)) {
				error_log("Failed to log errors using xf_log.  Using error_log.  Check your _logging config in conf.ini:".$res->getMessage());
				error_log($message);
			}
		} catch (\Exception $ex) {
			error_log("Failed to log errors using xf_log.  Using error_log.  Check your _logging config in conf.ini:".$ex->getMessage());
			error_log($message);
		}
		return;
		
	}
	error_log($message);
    
}

function xf_error($message, $tags=null, $includeContext = false) {
	xf_log(XF_LOG_LEVEL_ERROR, $message, $tags, $includeContext);
}

function xf_warning($message, $tags=null, $includeContext = false) {
	xf_log(XF_LOG_LEVEL_WARNING, $message, $tags, $includeContext);
}

function xf_info($message, $tags=null, $includeContext = false) {
	xf_log(XF_LOG_LEVEL_INFO, $message, $tags, $includeContext);
}

function xf_debug($message, $tags=null, $includeContext = false) {
	xf_log(XF_LOG_LEVEL_DEBUG, $message, $tags, $includeContext);
}