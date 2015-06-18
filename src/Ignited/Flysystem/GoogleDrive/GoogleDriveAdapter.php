<?php
namespace Ignited\Flysystem\GoogleDrive;

use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive_FileList;
use Google_Http_Request;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\Flysystem\Util;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FileExistsException;

class GoogleDriveAdapter extends AbstractAdapter
{

    protected $service;
    protected $baseFolderId = null;

    public function __construct(Google_Service_Drive $service, $prefix = null)
    {
        $this->service = $service;

        $this->setPathPrefix($prefix);
    }

    public function setPathPrefix($prefix)
    {
        if($prefix !== null)
        {
            $this->prefix = $prefix;
            $this->baseFolderId = $this->getDirectory($prefix);
        }
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {

    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {

    }

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {

    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {

    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {

    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {

    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        $folderId = $this->getDirectory($dirname, false);

        if($folderId == null)
        {
            throw new FileNotFoundException($dirname);
        }

        /*
            Need to create config as to whether to 'delete' or 'trash'
         */
        return $this->service->files->delete($folderId);
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        $folderId = $this->getDirectory($dirname, false);

        if($folderId !== null)
        {
            throw new FileExistsException($dirname);
        }

        return $this->getDirectory($dirname);
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {

    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        return $this->getFileId($path) !== null;
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        return $this->getFile($path);
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {

    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {

    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {

    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {

    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {

    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {

    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {

    }

    protected function getDirectory($path, $create=true)
    {
        $parts = explode('/', trim($path, '/'));
        $folderId = $this->baseFolderId;
        $parentFolderId = $this->baseFolderId;

        foreach ($parts as $name)
        {
            $folderId = $this->getDirectoryId($name, $folderId);

            if (!$folderId) {
                if($create)
                {
                    $folder = $this->createDirectory($name, $parentFolderId);
                    $folderId = $folder->id;
                }
                else
                {
                    return;
                }
            }

            $parentFolderId = $folderId;
        }

        if (!$folderId) {
            return;
        }
        
        return $folderId;
    }

    protected function getDirectoryId($name, $parentId=null)
    {
        if(is_null($parentId) && $this->baseFolderId !== null)
        {
            $parentId = $this->baseFolderId;
        }

        $q = 'mimeType="application/vnd.google-apps.folder" and title = "'.$name.'" and trashed = false';

        if(!is_null($parentId))
        {
            $q .= sprintf(' and "%s" in parents', $parentId);
        }

        $folders = $this->service->files->listFiles(array(
                'q' => $q,
            ))->getItems();

        if (count($folders) == 0) {
            return null;
        } else {
            return $folders[0]->id;
        }
    }

    protected function getFileId($path)
    {
        $paths = explode('/', $path);
        $fileName = array_pop($paths);
        $pathInfo = pathinfo($path);

        if(!empty($pathInfo['dirname']))
        {
            $parentId = $this->getDirectory($pathInfo['dirname'], false);
        }

        if(is_null($parentId) && $this->baseFolderId !== null)
        {
            $parentId = $this->baseFolderId;
        }

        $q = 'title = "'.$fileName.'" and trashed = false';
        $q .= sprintf(' and "%s" in parents', $parentId);

        $files = $this->service->files->listFiles(array(
            'q' => $q,
        ))->getItems();

        if (count($files) == 0) {
            return null;
        } else {
            return $files[0]->id;
        }
    }

    protected function getFile($path)
    {
        $fileId = $this->getFileId($path);

        $file = $this->service->files->get($fileId);

        return ['contents'=>$this->downloadFile($file)];
    }

    protected function downloadFile($file)
    {
      $downloadUrl = $file->getDownloadUrl();
      
      if ($downloadUrl)
      {
        $request = new Google_Http_Request($downloadUrl, 'GET', null, null);

        $httpRequest = $this->service->getClient()->getAuth()->authenticatedRequest($request);

        if ($httpRequest->getResponseHttpCode() == 200)
        {
          return $httpRequest->getResponseBody();
        }
        else
        {
          // An error occurred.
          return null;
        }
      }
      else
      {
        // The file doesn't have any content stored on Drive.
        return null;
      }
    }

    protected function createDirectory($name, $parentId=null)
    {
        $file = new Google_Service_Drive_DriveFile();
        $file->setTitle($name);
        $file->setParents([
            [
                'id' => $parentId
            ]
        ]);
        $file->setMimeType('application/vnd.google-apps.folder');

        return $this->service->files->insert($file);
    }

    protected function upload($path, $contents)
    {
        $paths = explode('/', $path);
        $fileName = array_pop($paths);
        $pathInfo = pathinfo($path);

        $parentId = $this->getDirectory($pathInfo['dirname']);

        $file = new Google_Service_Drive_DriveFile();
        $file->setTitle($fileName);
        $file->setParents([
            [
                'kind' => 'drive#fileLink',
                'id' => $parentId
            ]
        ]);

        $result = $this->service->files->insert($file, array(
          'data' => $contents,
          'uploadType' => 'media',
        ));

        return $this->normalizeResponse($result, $path);
    }

    protected function normalizeResponse($response, $path = null)
    {
        return $response;
    }

}