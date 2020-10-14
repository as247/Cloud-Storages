<?php


namespace As247\CloudStorages\Cache;

use Exception;
use PDO;

class SqliteCache
{
	protected $pdo;

	/**
	 * SqliteCache constructor.
	 * @param null $dataFile
	 * @throws Exception
	 */
	public function __construct($dataFile=null)
	{
		if($dataFile===null){
			$dataFile=sys_get_temp_dir().'/'.md5(static::class).'';
		}
		$isNewDB=!file_exists($dataFile);
		$this->pdo=new PDO('sqlite:' . $dataFile);
		if($isNewDB) {
			$this->createPathMapTable();
		}else{
			$this->checkMalformed();
		}
	}

	/**
	 * @throws Exception
	 */
	protected function checkMalformed(){
		$this->pdo->prepare("select 1 from cache where 0=1");
		$error = $this->pdo->errorInfo();
		if($error[0] !=='00000'){
			throw new Exception(sprintf("SQLSTATE[%s]: Error [%s] %s",$error[0],$error[1],$error[2]));
		}
	}
	protected function createPathMapTable(){
		$this->pdo->query("
			CREATE TABLE IF NOT EXISTS `cache` (
				`key` varchar(190) not null,
				`value` text not null,
				`expiration` integer(11),
				PRIMARY KEY (`key`)
			)
		");
	}
	public function get($key){
		$statement=$this->pdo->prepare("SELECT * FROM cache WHERE key=? limit 1");
		$statement->bindValue(1,$key);
		$statement->execute();
		$cache=$statement->fetch(PDO::FETCH_OBJ);
		if(!$cache){
			return null;
		}
		if ($this->currentTime() >= $cache->expiration) {
			$this->forget($key);
			return null;
		}
		return unserialize($cache->value);
	}

	public function put(string $key, $value, $seconds=3600){
		$value=serialize($value);
		$statement=$this->pdo->prepare(
		"insert into cache (`key`,`value`,`expiration`) values (?,?,?)"
		);

		$statement->bindValue(1,$key);
		$statement->bindValue(2,$value);
		$statement->bindValue(3,$this->currentTime()+$seconds);
		if(!$statement->execute()){
			$statement=$this->pdo->prepare("UPDATE cache SET value=:value,expiration=:expiration  WHERE key=:key");
			$statement->bindValue(':key',$key);
			$statement->bindValue(':value',$value);
			$statement->bindValue(':expiration',$this->currentTime()+$seconds);
			return $statement->execute();
		}else{
			return true;
		}
	}
	public function forget($key){
		$statement=$this->pdo->prepare("DELETE FROM cache WHERE key=?");
		$statement->bindValue(1,$key);
		$statement->execute();
		return true;
	}
	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string  $path
	 * @param  string  $id
	 * @return bool
	 */
	public function forever(string $path, string $id)
	{
		return $this->put($path, $id, 315360000);
	}

	public function flush(){
		$statement=$this->pdo->prepare("DELETE FROM cache");
		$statement->execute();
		return true;
	}
	public function cleanup(){
		$statement=$this->pdo->prepare("DELETE FROM cache WHERE expiration < ?");
		$statement->bindValue(1,$this->currentTime());
		$statement->execute();
	}
	/**
	 * Get the current system time as a UNIX timestamp.
	 *
	 * @return int
	 */
	protected function currentTime()
	{
		return time();
	}
}
