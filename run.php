<?php

require_once('vendor/autoload.php');

const API_KEY_ENV_VAR = 'TINYPNG_API_KEY';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(API_KEY_ENV_VAR);

$apiKey = $_ENV[API_KEY_ENV_VAR];

try {

    Tinify\setKey($apiKey);
    Tinify\validate();

} catch(\Tinify\Exception $e) {

    return printError('API key validation failed', $e);

}

try {

    $sourceDir = './source';
    $scannedSourceDir = array_diff(scandir($sourceDir), ['..', '.', '.gitkeep']);
    $initialFolderSize = folderSize('./source');
    $initialFolderSizeReadable = formatBytes($initialFolderSize);
    $imageCount = count($scannedSourceDir);
    $imagesCompressed = 0;
    print('Initial folder size is ' . $initialFolderSizeReadable . PHP_EOL);
    print('There are ' . $imageCount . ' images to compress ' . PHP_EOL);

    clearDirectory('./output/', ['.gitkeep']);

    foreach ($scannedSourceDir as $imageToCompress) {

        $source = Tinify\fromFile('./source/' . $imageToCompress);
        $source->toFile('./output/' . $imageToCompress);
        $imagesCompressed++;
        echo progressBar($imagesCompressed, $imageCount);

    }

    $finalFolderSize = folderSize('./output');
    $finalFolderSizeReadable = formatBytes($finalFolderSize);
    print('Final folder size is ' . $finalFolderSizeReadable . PHP_EOL);

    clearDirectory('./source/', ['.gitkeep']);

} catch(Tinify\AccountException $e) {

    return printError('Verify your API key and account limit.', $e);

} catch(Tinify\ClientException $e) {

    return printError('Check your source image and request options', $e);

} catch(Tinify\ServerException $e) {

    return printError('Temporary issue with the Tinify API', $e);

} catch(Tinify\ConnectionException $e) {

    return printError('A network connection error occurred', $e);

} catch(Exception $e) {

    return printError('Something else went wrong, unrelated to the Tinify API.', $e);

}

/**
 * @param $e
 * @return int
 */
function printError($message, $e)
{
    print($message . PHP_EOL);
    print('The error message is: ' . $e->getMessage() . PHP_EOL);
    return 0;
}

/**
 * @param $dir
 * @return false|int
 */
function folderSize($dir)
{
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : folderSize($each);
    }
    return $size;
}

/**
 * @param $bytes
 * @param int $precision
 * @return string
 */
function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * @param $done
 * @param $total
 * @param string $info
 * @param int $width
 * @return string
 */
function progressBar($done, $total, $info='', $width=50)
{
    $perc = round(($done * 100) / $total);
    $bar = round(($width * $perc) / 100);
    return sprintf('%s%%[%s>%s]%s\r', $perc, str_repeat('=', $bar), str_repeat(' ', $width-$bar), $info);
}

/**
 * @param $dir
 * @param array $filesToKeep
 */
function clearDirectory($dir, $filesToKeep = [])
{
    foreach( glob('$dir/*') as $file ) {
        if( !in_array(basename($file), $filesToKeep) ){
            unlink($file);
        }
    }
}
