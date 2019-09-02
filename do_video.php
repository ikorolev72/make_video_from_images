<?php

require_once __DIR__ . '/lib/common_processing.php';
include_once __DIR__ . "/lib/FfmpegEffects.php";

$today = date("Y-m-d H:i:s");
$dt = date("U");
$basedir = dirname(__FILE__);
$binDir = "$basedir/bin";
$logDir = "$basedir/logs";
$dataDir = "$basedir/data";
$configFile = "$dataDir/config.json";
$tmpDir = "$basedir/tmp";
$tmpFiles=array(); // files need be removed



$shortopts = "";
$longopts = array(
  "config:",
  "debug::",
);
$options = getopt($shortopts, $longopts);
$configFile = isset($options['config']) ? $options['config'] : $configFile;
$debug = isset($options['debug']) ? true : false;

if (!file_exists($configFile)) {
    help("File '$configFile' do not exists ");
    exit(1);
}


$processing=new FfmpegEffects();
$config = $processing->readJson($configFile);
if (!$config) {
    $processing->writeToLog("Cannot get required parameters from config file");
    exit(1);
}

$width=isset( $config["video"]["width"] ) ? $config["mysql"]["width"] : 1920;
$height=isset( $config["video"]["height"] )  ? $config["mysql"]["height"]  :1080;

$db=new Common_db(
    $config["mysql"]["servername"],
    $config["mysql"]["database"],
    $config["mysql"]["username"],
    $config["mysql"]["password"]
);
$mysqli= $db->dbConnect();
if (!$mysqli) {
    $processing->writeToLog("Cannot connect to database: ".$db->error);
    exit(1);
}


$video_elements=$db->getRecords($mysqli);
if ($video_elements === false) {
    $processing->writeToLog("Cannot get records from database: ".$db->error);
    exit(1);
}


// main loop
foreach ($video_elements as $k=>$element) {
    $textRecord=array();
    //$photoRecord=array();
    $videoRecord=array();
    $audioRecord=null;
    $temporaryAssFile=getTempioraryFile($tmpDir, "ass", $tmpFiles);

    switch ($element['content_type']) {
            case 'video':
                $videoRecord[] =$element;
                break;
            case 'photo':
                $videoRecord[] =$element;
            break;
            case 'text':
                $textRecord[] = $element;
                break;
            case 'audio':
                $audioRecord = $element;
                break;
        }

    if (! $processing->prepareSubtitles($textRecord, $temporaryAssFile, $width, $height)) {
        $processing->writeToLog("Cannot prepare subtitles file for record with video_id=".$element['video_id'] .". Error: ".$processing->getLastError());
        // go to next record
        continue;
    }
    $videosFromPhotos=array();
    if (! $cmd=$processing->prepareVideoFromPhotos($videoRecord, $temporaryAssFile, $output, $width, $height)) {
        $processing->writeToLog("Cannot generate video from prohos for video_id=".$element['video_id'].". Error: ".$processing->getLastError());
        // go to next record
        continue;
    }
        echo $cmd;

/*
    $updateVideoRecord=$db->updateVideoRecord($mysqli, $video_elements['video_id']) ;
    if ($updateVideoRecord === false) {
        $processing->writeToLog("Cannot update record in database: ".$db->error);
        exit(1);
    }
    */
}

$processing->removeTempioraryFiles($tmpFiles);
$db->dbDisconnect($mysqli);
exit(0);


function help($msg)
{
    $script = basename(__FILE__);

    $message=
        "$msg
	Usage: php $script [--config /path_to_config/config.json]
	where:
	--config config.json - config file 
  --debug  show additional debug info

	Example: $script --config /path_to_config/config.json\n";
    $stderr = fopen('php://stderr', 'w');
    fwrite($stderr, "$date   $message" . PHP_EOL);
    fclose($stderr);
    exit(-1);
}
