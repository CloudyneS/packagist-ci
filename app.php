<?php
require_once __DIR__ . '/vendor/autoload.php';

$cfg = $_SERVER;

if (!isset($_SERVER) || !isset($_SERVER['CMP_API_KEY'])) {
    if (isset($_ENV) && isset($_ENV['CMP_API_KEY'])) {
        $cfg = $_ENV;
    } else if (file_exists('.env')) {
        $cfg = parse_ini_file(".env");
        if ($cfg === false || !is_array($cfg) || !isset($cfg['CMP_API_KEY'])) {
            throw new Exception("No configuration found in .env file");
        }
    } else {
        throw new Exception("No configuration found");
    }
}

$api_key = $cfg['CMP_API_KEY'];
$api_token = $cfg['CMP_API_SECRET'];
$packageName = $cfg['CMP_PACKAGE_NAME'];
$packageVersion = $cfg['CMP_PACKAGE_VERSION'];
$packagePath = $cfg['CMP_PACKAGE_PATH'];

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
    $test = $client->packages()->show($packageName);

    echo "Package exists, trying to add artifact...";
    $log['artifactIDs'] = $client->packages()->artifacts()->showPackageArtifacts($packageName);
    $idList = $log['artifactIDs'];

    if (substr($packageVersion, 0, 3) === 'dev') {
        $idList = array_filter($idList, function($item) use ($packageVersion) {
            return $item['version'] !== $packageVersion;
        });
    }

    $idList = array_column($idList, 'id');
    
    $idList[] = $log['pkgUpload']['id'];
    echo "IDList: " . implode(", ", $idList).PHP_EOL;

    # Replace artifacts entirely
    try {
        $log['addArtifact'] = $client->packages()->editArtifactPackage($packageName, $idList);
    }
    catch (PrivatePackagist\ApiClient\Exception\ErrorException $e) {
        print_r($log['addArtifact']);
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