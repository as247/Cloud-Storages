<?php

namespace As247\CloudStorages\Service;

use As247\AList\AListClient;
use As247\CloudStorages\Contracts\Storage\StorageContract;
use As247\CloudStorages\Exception\ApiException;
use As247\CloudStorages\Support\Path;
use As247\CloudStorages\Support\StorageAttributes;

class AListService
{
    protected $client;
    public function __construct($url, $options = [])
    {
        if(empty($options['token'])){
            throw new ApiException('AList token is required. Please check your configuration.');
        }
        $this->client=new AListClient($url,$options['token'],$options['client']??[]);
    }
    public function getClient(){
        return $this->client;
    }
    public function getDownloadUrl($path,$expiration=0)
    {
        $path=Path::clean($path);
        return $this->client->getDownloadUrl($path,$expiration);
    }
    function copy($srcDir, $dstDir, $names)
    {
        $response= $this->client->fsCopy([
            'src_dir'=>$srcDir,
            'dst_dir'=>$dstDir,
            'names'=>(array)$names,
        ]);
        $code=$response['code']??0;
        return $code==200;
    }
    function move($srcDir, $dstDir, $names)
    {
        $response = $this->client->fsMove([
            'src_dir'=>$srcDir,
            'dst_dir'=>$dstDir,
            'names'=>(array)$names,
        ]);
        $code=$response['code']??0;
        return $code==200;
    }
    function rename($path,$newName)
    {
        $response= $this->client->fsRename([
            'path'=>$path,
            'name'=>$newName,
        ]);
        $code=$response['code']??0;
        return $code==200;
    }
    function read($path)
    {
        $path=Path::clean($path);
        if(!$this->get($path)){
            return null;
        }
        $response=$this->client->fsLink(['path'=>$path]);
        $url=$response['data']['url']??'';
        if($url){
            $guzzleClient=$this->client->getGuzzleClient();
            return $guzzleClient->get($url)->getBody()->detach();
        }
        return null;
    }
    function remove($dir, $names)
    {
        $response= $this->client->fsRemove([
            'names'=>(array)$names,
            'dir'=>$dir,
        ]);
        $code=$response['code']??0;
        return $code==200;
    }
    function put($path,$contents){
        $contents=StreamWrapper::wrap($contents);
        $response= $this->client->fsPut($contents,[
            'File-Path'=>$path,
        ]);
        $code=$response['code']??0;
        if($code!==200){
            return false;
        }
        return true;
    }

    function get($path){
        $response= $this->client->fsGet(['path'=>$path]);
        return $response['data']??null;
    }
    function mkdir($path){
        $response= $this->client->fsMkdir(['path'=>$path]);
        $code=$response['code']??0;
        if($code!==200){
            return false;
        }
        return true;
    }
    /**
     * @param $path
     * @return \Generator
     *
     */
    function listContents($path)
    {
        $page=1; $per_page=100; $refresh=false;
        $args=compact('path','page','per_page','refresh');
        $response= $this->client->fsList($args);
        $total=$response['data']['total']??0;
        $maxPage=ceil($total/$per_page);
        $items=$response['data']['content']??[];
        yield from $items;
        while($page<$maxPage){
            $page++;
            $args['page']=$page;
            $response= $this->client->fsList($args);
            $items=$response['data']['content']??[];
            yield from $items;
        }
    }
    function normalizeMetadata($file,$path)
    {
        /**
         * $file Array sample
         * 'name' => 'a file',
         * 'size' => 0,
         * 'is_dir' => false,
         * 'modified' => '2024-01-31T02:25:18.328834701Z',
         * 'created' => '2024-01-31T02:25:18.327034177Z',
         * 'sign' => 'hjsV0hqWOu7VR0iETfO_1hRqhUXrZ6m0qKjMm21QUtQ=:2639787953',
         * 'thumb' => '',
         * 'type' => 0,
         * 'hashinfo' => 'null',
         * 'hash_info' => NULL,
         */
        $visibility = StorageContract::VISIBILITY_PRIVATE;
        return [
            StorageAttributes::ATTRIBUTE_PATH => $path,
            StorageAttributes::ATTRIBUTE_LAST_MODIFIED => strtotime($file['modified']),
            StorageAttributes::ATTRIBUTE_FILE_SIZE => $file['size'],
            StorageAttributes::ATTRIBUTE_TYPE => $file['is_dir'] ? 'dir' : 'file',
            StorageAttributes::ATTRIBUTE_MIME_TYPE => $this->guessMimeTypeFromFileName($path),
            StorageAttributes::ATTRIBUTE_VISIBILITY=>$visibility,
            '@id'=>'',
            '@link' => '',
            '@shareLink'=>'',
            '@downloadUrl' => '',
        ];
    }
    protected function guessMimeTypeFromFileName($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        //Use predefined mime types
        $mimes=[
            'txt'=>'text/plain',
            'html'=>'text/html',
            'htm'=>'text/html',
            'php'=>'text/html',
            'css'=>'text/css',
            'js'=>'application/javascript',
            'json'=>'application/json',
            'xml'=>'application/xml',
            'swf'=>'application/x-shockwave-flash',
            'flv'=>'video/x-flv',
            //Images
            'png'=>'image/png',
            'jpe'=>'image/jpeg',
            'jpeg'=>'image/jpeg',
            'jpg'=>'image/jpeg',
            'gif'=>'image/gif',
            'bmp'=>'image/bmp',
            'ico'=>'image/vnd.microsoft.icon',
            'tiff'=>'image/tiff',
            'tif'=>'image/tiff',
            'svg'=>'image/svg+xml',
            'svgz'=>'image/svg+xml',
            //Archives
            'zip'=>'application/zip',
            'rar'=>'application/x-rar-compressed',
            'exe'=>'application/x-msdownload',
            'msi'=>'application/x-msdownload',
            'cab'=>'application/vnd.ms-cab-compressed',
            //Audio/video
            'mp3'=>'audio/mpeg',
            'qt'=>'video/quicktime',
            'mov'=>'video/quicktime',
            //Adobe
            'pdf'=>'application/pdf',
            'psd'=>'image/vnd.adobe.photoshop',
            'ai'=>'application/postscript',
            'eps'=>'application/postscript',
            'ps'=>'application/postscript',
            //MS Office
            'doc'=>'application/msword',
            'rtf'=>'application/rtf',
            'xls'=>'application/vnd.ms-excel',
            'ppt'=>'application/vnd.ms-powerpoint',
            //Open Office
            'odt'=>'application/vnd.oasis.opendocument.text',
            'ods'=>'application/vnd.oasis.opendocument.spreadsheet',
        ];
        return $mimes[$ext]??'application/octet-stream';
    }
}