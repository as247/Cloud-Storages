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
			$this->createTable();
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
	protected function createTable(){
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
		if($seconds===-1){
			$expire=2147483647;
		}else{
			$expire=$this->currentTime()+$seconds;
		}
		$statement->bindValue(1,$key);
		$statement->bindValue(2,$value);
		$statement->bindValue(3,$expire);
		if(!$statement->execute()){
			$statement=$this->pdo->prepare("UPDATE cache SET value=:value,expiration=:expiration  WHERE key=:key");
			$statement->bindValue(':key',$key);
			$statement->bindValue(':value',$value);
			$statement->bindValue(':expiration',$expire);
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
	 * @param  mixed  $id
	 * @return bool
	 */
	public function forever(string $path, $value)
	{
		return $this->put($path, $value, -1);
	}

	public function flush(){
		$statement=$this->pdo->prepare("DELETE FROM cache");
		$statement->execute();
		return true;
	}
	public function clearExpires(){
		$statement=$this->pdo->prepare("DELETE FROM cache WHERE expiration < ?");
		$statement->bindValue(1,$this->currentTime());
		$statement->execute();
	}
	public function getPdo(){
		return $this->pdo;
	}
	public function keyStartedWith($key){
		$statement=$this->pdo->prepare("SELECT * FROM cache WHERE key like ?");
		$key=$key.'%';
		$statement->bindValue(1,$key);
		$statement->execute();
		$allRecords=$statement->fetchAll(PDO::FETCH_OBJ);
		if(!$allRecords){
			return [];
		}
		$results=[];
		foreach ($allRecords as $cache) {
			if ($this->currentTime() >= $cache->expiration) {
				$this->forget($key);
			}else{
				$results[$cache->key]=unserialize($cache->value);
			}
		}
		return $results;
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
