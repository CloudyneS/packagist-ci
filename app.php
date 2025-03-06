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

$log = [];

$log['zipfileName'] = str_replace('/', '-', $packageName) . '.zip';

echo "Creating client..." . PHP_EOL;
$client = new \PrivatePackagist\ApiClient\Client();
$client->authenticate($api_key, $api_token);

echo "Reading file {$packagePath}..." . PHP_EOL;
$file = file_get_contents($packagePath);

echo "Uploading package artifact..." . PHP_EOL;
$log['newArtifact'] = $client->packages()->artifacts()->create($file, 'application/zip', $log['zipfileName']);

echo "Checking if the package already exists..." . PHP_EOL;
try {
    $log['existingPackage'] = $client->packages()->show($packageName);

    echo "Package {$log['existingPackage']['name']} exists, checking versions" . PHP_EOL;

    $log['packageArtifacts'] = $client->packages()->artifacts()->showPackageArtifacts($packageName);
    $log['listOfArtifactIds'] = array_filter($log['packageArtifacts'], function($item) use ($packageVersion) {
        return $item['composerJson']['version'] !== $packageVersion;
    });

    $log['listOfArtifactIds'] = array_column($log['listOfArtifactIds'], 'id');

    $log['listOfArtifactIds'][] = $log['newArtifact']['id'];

    $client->packages()->editArtifactPackage($packageName, $log['listOfArtifactIds']);
    
    echo "Successfully updated package!" . PHP_EOL;
    echo "Available versions now:" . PHP_EOL;

    $log['newPackageArtifacts'] = $client->packages()->artifacts()->showPackageArtifacts($packageName);
    foreach ($log['newPackageArtifacts'] as $item) {
        echo $item['composerJson']['version'] . PHP_EOL;
    }
}
catch (PrivatePackagist\ApiClient\Exception\ResourceNotFoundException $e) {
    echo "Package does not exist, creating..." . PHP_EOL;
    $log['pkgCreate'] = $client->packages()->createArtifactPackage([$log['pkgUpload']['id']]);
    echo "Successfully created package!" . PHP_EOL;
}


print_r($log);

echo "All done!" . PHP_EOL;