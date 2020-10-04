<?php


namespace As247\CloudStorages\Service;


class Logger
{
	protected $logDir;
	protected $enabled=true;
	protected $num_queries=0;
	public function __construct($logDir='')
    {
        $this->logDir=$logDir;
        if(is_dir($logDir)){
            $this->enabled=true;
        }
    }

    function log($message, $level='debug'){
	    if(!$this->enabled){
	        return $this;
        }
	    if(is_array($message) || is_object($message)){
	        $message=json_encode($message,JSON_PRETTY_PRINT);
        }
		$this->write("[$level] $message",'debug');
	    return $this;
	}

	function query($cmd, $query){
	    if(!$this->enabled){
	        return $this;
        }
        $query=json_encode($query,JSON_PRETTY_PRINT);
        $this->write("{$cmd} $query",'query');
        $this->num_queries++;
		return $this;
	}
	public function getNumQueries(){
		return $this->num_queries;
	}
	protected function write($line,$file){
	    if(!$this->enabled || !$this->logDir){
	        return ;
        }
	    $time=date('Y-m-d h:i:s');
	    file_put_contents($this->logDir."/$file.log",
			$time.' '.
				$line.
				'. Trace:'.
			$this->debugBacktraceSummary().PHP_EOL,FILE_APPEND);
    }
    public function enable($flag=true){
	    $previous= $this->enabled;
	    $this->enabled=$flag;
	    return $previous;
    }

	protected function debugBacktraceSummary( $ignore_class = null, $skip_frames = 0, $pretty = true ) {
		static $truncate_paths;

		$trace       = debug_backtrace( false );
		$caller      = array();
		$check_class = ! is_null( $ignore_class );
		$skip_frames++; // Skip this function.

		if ( ! isset( $truncate_paths ) ) {
			$truncate_paths = array(

			);
		}

		foreach ( $trace as $call ) {
			if ( $skip_frames > 0 ) {
				$skip_frames--;
			} elseif ( isset( $call['class'] ) ) {
				if ( $check_class && $ignore_class == $call['class'] ) {
					continue; // Filter out calls.
				}

				$caller[] = "{$call['class']}{$call['type']}{$call['function']}";
			} else {
				if ( in_array( $call['function'], array( 'do_action', 'apply_filters', 'do_action_ref_array', 'apply_filters_ref_array' ), true ) ) {
					$caller[] = "{$call['function']}('{$call['args'][0]}')";
				} elseif ( in_array( $call['function'], array( 'include', 'include_once', 'require', 'require_once' ), true ) ) {
					$filename = isset( $call['args'][0] ) ? $call['args'][0] : '';
					$caller[] = $call['function'] . "('" . str_replace( $truncate_paths, '', ( $filename ) ) . "')";
				} else {
					$caller[] = $call['function'];
				}
			}
		}
		if ( $pretty ) {
			return join( ', ', array_reverse( $caller ) );
		} else {
			return $caller;
		}
	}
}
