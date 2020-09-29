<?php


namespace As247\CloudStorages\Storage;


use As247\CloudStorages\Contracts\Storage\StorageContract;
use As247\CloudStorages\Exception\FileNotFoundException;
use As247\CloudStorages\Exception\InvalidVisibilityProvided;
use As247\CloudStorages\Exception\UnableToCreateDirectory;
use As247\CloudStorages\Exception\UnableToDeleteDirectory;
use As247\CloudStorages\Exception\UnableToDeleteFile;
use As247\CloudStorages\Exception\UnableToReadFile;
use As247\CloudStorages\Exception\UnableToRetrieveMetadata;
use As247\CloudStorages\Exception\UnableToWriteFile;
use As247\CloudStorages\Service\GoogleDrive;
use As247\CloudStorages\Support\Config;
use As247\CloudStorages\Support\FileAttributes;
use GuzzleHttp\Exception\ClientException;
use Microsoft\Graph\Exception\GraphException;
use As247\CloudStorages\Service\OneDrive as OneDriveService;
use Microsoft\Graph\Graph;
use Throwable;

class OneDrive implements StorageContract
{
	/** @var Graph */
	protected $service;

	public function __construct(Graph $graph,$options=[])
	{
		$this->service = new OneDriveService($graph,$options);
	}

	public function getService()
	{
		return $this->service;
	}

	/**
	 * @param string $directory
	 * @param bool $recursive
	 * @return \Generator
	 * @throws GraphException
	 */
	public function listContents(string $directory = '', bool $recursive = false): iterable
	{
		try {
			$results = $this->service->listChildren($directory);
			foreach ($results as $id => $result) {
				$result = $this->service->normalizeMetadata($result, $directory . '/' . $result['name']);
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
		}catch (UnableToDeleteFile $e){
			throw UnableToDeleteDirectory::atLocation($e->location(),$e->reason(),$e->getPrevious());
		}
	}

	public function createDirectory(string $path, Config $config): void
	{
		try {
			$response = $this->service->createDirectory($path);
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
		if ($visibility === StorageContract::VISIBILITY_PUBLIC) {
			$this->service->publish($path);
		} elseif ($visibility === StorageContract::VISIBILITY_PRIVATE) {
			$this->service->unPublish($path);
		} else {
			throw InvalidVisibilityProvided::withVisibility($visibility, join(' or ', [StorageContract::VISIBILITY_PUBLIC, StorageContract::VISIBILITY_PRIVATE]));
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
	}


	/**
	 * @param $path
	 * @return FileAttributes
	 * @throws FileNotFoundException
	 */
	public function getMetadata($path): FileAttributes
	{
		try {
			$meta = $this->service->getItem($path, ['expand' => 'permissions']);
			$attributes = $this->service->normalizeMetadata($meta, $path);
			return FileAttributes::fromArray($attributes);
		} catch (ClientException $e) {
			if ($e->getResponse()->getStatusCode() === 404) {
				throw new FileNotFoundException($path, 0, $e);
			}
			throw UnableToRetrieveMetadata::create($path, 'metadata', '', $e);
		} catch (Throwable $e) {
			throw UnableToRetrieveMetadata::create($path, 'metadata', '', $e);
		}
	}


}
