<?php


namespace As247\CloudStorages\Storage;

use As247\CloudStorages\Exception\FileNotFoundException;
use As247\CloudStorages\Exception\InvalidVisibilityProvided;
use As247\CloudStorages\Exception\UnableToCreateDirectory;
use As247\CloudStorages\Exception\UnableToDeleteDirectory;
use As247\CloudStorages\Exception\UnableToDeleteFile;
use As247\CloudStorages\Exception\UnableToReadFile;
use As247\CloudStorages\Exception\UnableToRetrieveMetadata;
use As247\CloudStorages\Exception\UnableToWriteFile;
use As247\CloudStorages\Support\Config;
use As247\CloudStorages\Support\FileAttributes;
use As247\CloudStorages\Support\Path;
use Generator;
use GuzzleHttp\Exception\ClientException;
use Microsoft\Graph\Exception\GraphException;
use As247\CloudStorages\Service\OneDrive as OneDriveService;
use Microsoft\Graph\Graph;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Throwable;
use Traversable;

class OneDrive extends Storage
{
	/** @var Graph */
	protected $service;

	public function __construct(Graph $graph,$options=[])
	{
		$this->service = new OneDriveService($graph,$options);
		$this->setLogger($this->service->getLogger());
		$this->setupCache($options);
	}

	public function getService()
	{
		return $this->service;
	}

	/**
	 * @param string $directory
	 * @param bool $recursive
	 * @return Generator
	 * @throws GraphException
	 */
	public function listContents(string $directory = '', bool $recursive = false): Traversable
	{
		try {
			$results = $this->service->listChildren($directory);
			foreach ($results as $id => $result) {
				$result = $this->service->normalizeMetadata($result, rtrim($directory,'\/') . '/' . $result['name']);
				yield $id => $result;
				if ($recursive && $result['type'] === 'dir') {
					yield from $this->listContents($result['path'], $recursive);
				}
			}
		} catch (ClientException $e) {
			if ($e->getResponse()->getStatusCode() === 404) {
				yield from [];
			}
		}
	}

	public function writeStream(string $path, $contents, Config $config): void
	{
		try {
			$this->service->upload($path, $contents);
			$this->getCache()->forget($path,true);
			if ($config && $visibility = $config->get('visibility')) {
				$this->setVisibility($path, $visibility);
			}
		} catch (ClientException $e) {
			throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
		} catch (GraphException $e) {
			throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
		}
	}

	public function readStream(string $path)
	{
		try {
			return $this->service->download($path);
		} catch (ClientException $e) {
			throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
		} catch (GraphException $e) {
			throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
		}
	}

	public function delete(string $path): void
	{
		try {
			$this->service->delete($path);
			$this->getCache()->delete($path);
		} catch (ClientException $e) {
			if ($e->getResponse()->getStatusCode() === 404) {
				throw FileNotFoundException::create($path);
			}
			throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
		} catch (GraphException $e) {
			throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
		}
	}

	public function deleteDirectory(string $path): void
	{
		try {
			$this->delete($path);
			$this->getCache()->deleteDir($path);
		}catch (UnableToDeleteFile $e){
			throw UnableToDeleteDirectory::atLocation($e->location(),$e->reason(),$e->getPrevious());
		}
	}

	public function createDirectory(string $path, Config $config): void
	{
		try {
			$response = $this->service->createDirectory($path);
			$this->getCache()->forget($path,true);

			$file = FileAttributes::fromArray($this->service->normalizeMetadata($response, $path));
			if (!$file->isDir()) {
				throw UnableToCreateDirectory::atLocation($path, 'File already exists');
			}
		} catch (GraphException $e) {
			throw UnableToCreateDirectory::atLocation($path, $e->getMessage());
		} catch (ClientException $e) {
			throw UnableToCreateDirectory::atLocation($path, $e->getMessage());
		}
	}

	/**
	 * @param string $path
	 * @param mixed $visibility
	 * @throws GraphException
	 */
	public function setVisibility(string $path, $visibility): void
	{
		if ($visibility === Storage::VISIBILITY_PUBLIC) {
			$this->service->publish($path);
			$this->getCache()->forget($path);
		} elseif ($visibility === Storage::VISIBILITY_PRIVATE) {
			$this->service->unPublish($path);
			$this->getCache()->forget($path);
		} else {
			throw InvalidVisibilityProvided::withVisibility($visibility, join(' or ', [Storage::VISIBILITY_PUBLIC, Storage::VISIBILITY_PRIVATE]));
		}
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param Config $config
	 * @throws GraphException
	 */
	public function move(string $source, string $destination, Config $config): void
	{
		$this->service->move($source, $destination);
		$this->getCache()->move($source,$destination);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param Config $config
	 * @throws GraphException
	 */
	public function copy(string $source, string $destination, Config $config): void
	{
		$this->service->copy($source, $destination);
		$this->getCache()->forget($destination,true);
	}


	/**
	 * @param $path
	 * @return FileAttributes
	 * @throws FileNotFoundException
	 */
	public function getMetadata($path): FileAttributes
	{
		try {
			//echo PHP_EOL.'metadata: '.$path.PHP_EOL;
			if($this->cache->has($path)){
				//echo 'From Cache: '.$path.PHP_EOL;
				$meta=$this->cache->get($path);
				if(!$meta){
					throw new FileNotFoundException($path);
				}
			}
			if(!isset($meta)) {
				$meta = $this->service->getItem($path, ['expand' => 'permissions']);
				$this->cache->put($path,$meta);
			}
			$attributes = $this->service->normalizeMetadata($meta, $path);
			return FileAttributes::fromArray($attributes);
		} catch (ClientException $e) {
			if ($e->getResponse()->getStatusCode() === 404) {
				$this->cache->put($path,false);
				throw new FileNotFoundException($path, 0, $e);
			}
			throw UnableToRetrieveMetadata::create($path, 'metadata', '', $e);
		} catch (FileNotFoundException $e){
			throw $e;
		}catch (Throwable $e) {
			throw UnableToRetrieveMetadata::create($path, 'metadata', '', $e);
		}
	}


}
