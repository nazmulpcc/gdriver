<?php

namespace App\Helpers;

use \Google_Client;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/**
 * Google Drive Helper
 * Created with the help of https://github.com/J05HI/google-drive-direct-remote-upload/blob/master/index.php
 */
class GDriver
{
    /**
     * Google Api Client
     *
     * @var \Google_Client
     */
    private $client;

    /**
     * GuzzleHttp Client
     *
     * @var \GuzzleHttp\Client
     */
    private $http = false;

    /**
     * Google Api Service
     *
     * @var \Google_Service_Drive
     */
    private $service;

    /**
     * The Google API Access Token
     *
     * @var array
     */
    private $token;

    /**
     * Unique Identifier for each user, stored in sessions.
     *
     * @var string
     */
    private $user;

    public function __construct(Google_Client $client)
    {
        $this->client = $client;

        if(!$this->user = session('user', false)){
            $this->user = Str::random(8);
            session()->put('user', $this->user);
        }else{
            $this->token = Cache::get($this->user, false);
            if($this->token){
                $this->client->setAccessToken($this->token);
                if ($this->client->isAccessTokenExpired()) {
                    $this->token = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    Cache::put($this->user, $this->token, 999);
                }
                $this->service = new \Google_Service_Drive($this->client);
            }
        }
    }

    /**
     * Get Authentication URL
     *
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Set the authentication code, obtained from callback URL
     *
     * @param string $code
     * @return void
     */
    public function setAuthCode($code = false)
    {
        if(!$code){
            if(request()->has('code')){
                $code = request()->input('code');
            }else{
                throw new Exception("Auth Code not supplied");
            }
        }
        $this->token = $this->client->fetchAccessTokenWithAuthCode($code);
        if(!isset($this->token['access_token'])){
            throw new Exception("Invalid Auth code");
        }
        Cache::put($this->user, $this->token, 999);
    }

    /**
     * Upload a file in Google Drive from a URL
     *
     * @param string $url
     * @param string $filename
     * @return boolean
     */
    public function remoteUpload($url, $filename = false)
    {
        if(!$filename){
            $filename = $this->getNameFromUrl($url);
        }
        $file = new \Google_Service_Drive_DriveFile();
        $file->name = $filename;

        $fileSize = (int)$this->remoteFileSize($url);
        $mimeType = $this->remoteMimeType($url);
        $chunkSizeBytes = $this->getChunkSize($url);
        
        # Call the API with the media upload, defer so it doesn't immediately return.
        $this->client->setDefer(true);
        $fileRequest = $this->service->files->create($file);
        $media = new \Google_Http_MediaFileUpload(
            $this->client,
            $fileRequest,
            $mimeType,
            null,
            true,
            $chunkSizeBytes
        );
        $media->setFileSize($fileSize);
        # Upload the chunks. Status will be false until the process is complete.
        $status = false;
        $sizeUploaded = 0;
        $handle = fopen($url, 'rb');
        while (!$status && !feof($handle)) {
            # Read until you get $chunkSizeBytes from the file
            $chunk = $this->readFileChunk($handle, $chunkSizeBytes);
            $chunkSizee = strlen($chunk);
            $sizeUploaded += $chunkSizee;
            $sizeMissing = $fileSize - $sizeUploaded;
            $status = $media->nextChunk($chunk);
        }
        fclose($handle);
        # The final value of $status will be the data from the API for the object that has been uploaded.
        return $status;
    }
    
    /**
     * Get the file name from a url
     *
     * @param string $url
     * @return string
     */
    public function getNameFromUrl($url)
    {
        $path = data_get(parse_url($url), 'path', config('app_name'));
        return basename($path);
    }
    /**
     * Detect If we don't have a valid token.
     *
     * @return void
     */
    public function needsToken()
    {
        return !$this->token;
    }

    /**
     * Get remote mime type.
     *
     * @param string $url
     *
     * @return string
     */
    function remoteMimeType($url) {
        $headers = $this->getHeaders($url);
        if (isset($headers['Content-Type'])) {
            return $headers['Content-Type'];
        }
        return 'application/octet-stream';
    }

    public function remoteFileSize($url)
    {
        $headers = $this->getHeaders($url);
        if(isset($headers['Content-Length'])){
            return is_array($headers['Content-Length']) ? $headers['Content-Length'][0] : $headers['Content-Length'];
        }else{
            return 0;
        }
    }

    /**
     * Get the headers of a URL
     *
     * @param string $url
     * @return array
     */
    public function getHeaders($url, $cacheBust = false)
    {
        if($this->http == false){
            $this->http = new HttpClient;
        }
        if(isset($this->_headers[md5($url)]) && $cacheBust == false){
            return $this->_headers[md5($url)];
        }
        return $this->_headers[md5($url)] = $this->http->head($url)->getHeaders();
    }

    /**
     * Get the suitable chunk size for a url.
     * The smaller between remote file size and chunkSize set in config is returned.
     *
     * @param string $url
     * @return integer
     */
    public function getChunkSize($url = false)
    {
        $chunkSize = config('gdrive.chunkSize');
        $fileSize = $url ? $this->remoteFileSize($url) : $chunkSize+1;
        return min($fileSize, $chunkSize);
    }

    /**
     * Get a chunk of a file.
     *
     * @param resource $handle
     * @param int      $chunkSize
     *
     * @return string
     */
    private function readFileChunk($handle, int $chunkSize) {
        $byteCount = 0;
        $giantChunk = '';
        while (!feof($handle)) {
            $chunk = fread($handle, min($chunkSize - $byteCount, 8192));
            $byteCount += strlen($chunk);
            $giantChunk .= $chunk;
            if ($byteCount >= $chunkSize) {
                return $giantChunk;
            }
        }
        return $giantChunk;
    }
}
