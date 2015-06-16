# flysystem-google-drive
### Google Drive Adaptor for Flysystem
WORK IN PROGRESS not for production

#### My Testing Scripts
    $client = new \Google_Client();
    $client->setClientId('xxxx');
    $client->setClientSecret('xxxx');
    $client->setAccessToken('{
      "access_token":"xxxx",
      "expires_in":3920,
      "token_type":"Bearer",
      "created":'.time().'
    }');
    
    $service = new \Google_Service_Drive($client);
    $adapter = new \Ignited\Flysystem\GoogleDrive\GoogleDriveAdapter($service);
    
    $filesystem = new Filesystem($adapter);
