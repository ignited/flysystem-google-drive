<?php
namespace Ignited\Flysystem\GoogleDrive;

use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive_FileList;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\Flysystem\Util;

class GoogleDriveAdapter extends AbstractAdapter
{

    protected $service;
    protected $baseFolderId = null;

    public function __construct(Google_Service_Drive $service, $prefix = null)
    {
        $this->service = $service;

        if($prefix !== null)
        {
            $this->setPathPrefix($prefix);
            $this->baseFolderId = $this->getDirectory($this->getParentFolder($prefix));
        }
        else
        {
            $this->setPathPrefix('root');
        }
    }

    public function setPathPrefix($prefix)
    {
        $this->pathPrefix = $prefix;
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
        $directories = explode('/', $dirname);

        $previousParentId = null;

        foreach($directories as $directory)
        {
            $result = $this->createDirectory($directory, $previousParentId);
            $previousParentId = $result->id;
        }
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

    protected function getParentFolder($path)
    {
        $parts = explode('/', trim($path, '/'));
        $folderId = $this->baseFolderId;
        $currentPath = [];
        foreach ($parts as $name) {
            $currentPath[] = $name;
            $q = 'mimeType="application/vnd.google-apps.folder" and title contains "'.$name.'" and trashed = false';
            if ($folderId) {
                $q .= sprintf(' and "%s" in parents', $folderId);
            }
            $folders = $this->service->files->listFiles(array(
                    'q' => $q,
                ))->getItems();
            if (count($folders) == 0) {
                $folder = $this->createDirectory($name, $folderId);
                $folderId = $folder->id;
            } else {
                $folderId = $folders[0]->id;
            }
        }
        if (!$folderId) {
            return;
        }
        
        return $folderId;
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

        $location = $this->applyPathPrefix($path);

        $parentId = $this->getParentFolder($pathInfo['dirname']);

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