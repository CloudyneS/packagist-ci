<?php
require_once __DIR__ . '/vendor/autoload.php';

$api_key = $_SERVER['CMP_API_KEY'];
$api_token = $_SERVER['CMP_API_SECRET'];
$packageName = $_SERVER['CMP_PACKAGE_NAME'];
$packageVersion = $_SERVER['CMP_PACKAGE_VERSION'];
$packagePath = $_SERVER['CMP_PACKAGE_PATH'];

$zipfileName = str_replace('/', '-', $packageName) . '.zip';

echo "Creating client..." . PHP_EOL;
$client = new \PrivatePackagist\ApiClient\Client();
$client->authenticate($api_key, $api_token);

echo "Reading file..." . PHP_EOL;
$file = file_get_contents($packagePath);
$log = [];

echo "Uploading package artifact..." . PHP_EOL;
$log['pkgUpload'] = $client->packages()->artifacts()->create($file, 'application/zip', $zipfileName);
echo "Successfully uploaded package!" . PHP_EOL;

try {
    echo "Checking if the package already exists..." . PHP_EOL;
    $client->packages()->show($packageName);
    
    $log['artifactIDs'] = $client->packages()->artifacts()->showPackageArtifacts($packageName);

    echo "Package exists, trying to add artifact...";
    $idList = $log['artifactIDs'];

    if (substr($packageVersion, 0, 3) === 'dev') {
        $idList = array_filter($idList, function($item) use ($packageVersion) {
            return $item['version'] !== $packageVersion;
        });
    }

    $idList = array_column($idList, 'id');
    $idList[] = $log['pkgUpload']['id'];

    try {
        $log['addArtifact'] = $client->packages()->editArtifactPackage($packageName, $idList);
    }
    catch (PrivatePackagist\ApiClient\Exception\ErrorException $e) {
        throw new Exception(
            'Package version already exists and is not a development version. Upload failed.' 
        );

    }
}
catch (PrivatePackagist\ApiClient\Exception\ResourceNotFoundException $e) {
    echo "Creating package..." . PHP_EOL;
    $log['pkgCreate'] = $client->packages()->createArtifactPackage([$log['pkgUpload']['id']]);
    echo "Successfully created package!" . PHP_EOL;
}
print_r($log);

echo "All done!" . PHP_EOL;