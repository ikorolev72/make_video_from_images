<?php

require_once __DIR__ . '/lib/common_processing.php';
require_once __DIR__ . "/lib/FfmpegEffects.php";
require_once __DIR__ . "/lib/common_db.php";

$today = date("Y-m-d H:i:s");
$dt = date("U");
$basedir = dirname(__FILE__);
$binDir = "$basedir/bin";
$logDir = "$basedir/logs";
$dataDir = "$basedir/data";
$configFile = "$dataDir/config.json";
$tmpDir = "$basedir/tmp";
$log="$logDir/processing.log" ;


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


$processing=new FfmpegEffects($log, false);
$processing->writeToLog("Info: Script started");

$config = $processing->readJson($configFile);
if (!$config) {
    $processing->writeToLog("Cannot get required parameters from config file");
    exit(1);
}
$processing->writeToLog("Info: Config '$configFile' read");

$width=isset($config["video"]["width"]) ? $config["video"]["width"] : 1920;
$height=isset($config["video"]["height"])  ? $config["video"]["height"]  :1080;

$processing->setGeneralSettings(
    array(
        'ffmpeg'=> $config["general"]["ffmpeg"],
        'ffmpegLogLevel'=>$config["general"]["ffmpegLogLevel"],
        )
);


$db=new Common_db(
    $config["mysql"]["servername"],
    $config["mysql"]["database"],
    $config["mysql"]["username"],
    $config["mysql"]["password"]
);





$mysqli= $db->dbConnect();
if (!$mysqli) {
    $processing->writeToLog("Error: Cannot connect to database: ".$db->getLastError());
    exit(1);
}
$processing->writeToLog("Info: Connected to database");


$video_messages= $db->getMessageRecords($mysqli) ;
if ($video_messages===false) {
    $processing->writeToLog("Error: Cannot read messages records: ".$db->getLastError());
    exit(1);
}

foreach ($video_messages as $m=>$messagesValue) {
    if( ! is_dir($messagesValue["completed_video_directory"])  || ! is_writable($messagesValue["completed_video_directory"])) {
        $processing->writeToLog("Warning: Completed video derectory '".$messagesValue['completed_video_directory']."' for video_id=".$messagesValue['video_id']." do not exists or haven't write permission. Will go to next record."); 
        continue;
    }
    $tmpFiles=array(); // files need be removed
    $video_elements=$db->getElementRecords($mysqli, $messagesValue["video_id"]);

    if ($video_elements === false) {
        $processing->writeToLog("Error: Cannot get element records from database: ".$db->getLastError());
        exit(1);
    }

    $videosFromPhotos=array();
    foreach ($video_elements as $k=>$elementValue) {
        switch ($elementValue["content_type"]) {
            case 'video':
                break;
            case 'photo':
                break;
            case 'audio':
                $audioFile=$processing->fixPath($elementValue["audio_directory"]."/".$elementValue["file_name"]);
                $audioDuration=$elementValue["audio_duration"];
                continue 2;
                break;
            default:
                continue 2;
                break;
            }

        $processing->writeToLog("Info: Prepare video for video_id=".$messagesValue['video_id']." and object_id=".$elementValue['object_id']);
        $temporaryVideoFile=$processing->getTemporaryFile($tmpDir, "ts", $tmpFiles);
        if (!$cmd=$processing->prepareVideoFromPhotoFast($elementValue, $temporaryVideoFile, $width, $height)) {
            $processing->writeToLog("Error: Cannot generate video from proho for video_id=".$messagesValue['video_id']." and object_id=".$elementValue['object_id']." Error: ".$processing->getLastError());
            // go to next record
            continue;
        }
        $processing->writeToLog("Info: Execute command :$cmd");
        if (!$processing->doExec($cmd)) {
            $processing->writeToLog("Error: Error executing command '$cmd': ".$db->getLastError());
            // go to next record
            continue;
        }
        $videosFromPhotos[]=$temporaryVideoFile;
    }


    $processing->writeToLog("Info: Prepare subtitles file for record with video_id=".$messagesValue['video_id']);
    $temporaryAssFile=$processing->getTemporaryFile($tmpDir, "ass", $tmpFiles);
    if (!$processing->prepareSubtitles($video_elements, $temporaryAssFile, $width, $height)) {
        $processing->writeToLog("Cannot prepare subtitles file for record with video_id=".$messagesValue['video_id'] .". Error: ".$processing->getLastError());
        // go to next record
        continue;
    }

    $processing->writeToLog("Info: Prepareprepare final video command for record with video_id=".$messagesValue['video_id']);
    $output=$processing->fixPath($messagesValue["completed_video_directory"]."/".$messagesValue["date"].".mp4");
    if (!$cmd=$processing->collectFinalVideo($videosFromPhotos, $temporaryAssFile, $audioFile, $audioDuration, $output)) {
        $processing->writeToLog("Cannot prepare final video command for record with video_id=".$messagesValue['video_id'] .". Error: ".$processing->getLastError());
        // go to next record
        continue;
    }
    $processing->writeToLog("Info: Execute command :$cmd");

    if (!$processing->doExec($cmd)) {
        $processing->writeToLog("Error: Error executing command '$cmd': ".$db->getLastError());
        // go to next record
        continue;
    }


    if (!empty($config["mysql"]["updateMessageRecord"])) {
        $processing->writeToLog("Info: update record ( set video completed ) in database for record with video_id=".$messagesValue['video_id']);
        $updateVideoRecord=$db->updateVideoRecord($mysqli, $video_elements['video_id']) ;
        if ($updateVideoRecord === false) {
            $processing->writeToLog("Cannot update record in database: ".$db->getLastError());
            exit(1);
        }
        $processing->removeTemporaryFiles($tmpFiles);
    }

}

$processing->writeToLog("Info: Disconnect database");
$db->dbDisconnect($mysqli);
$processing->writeToLog("Info: Processing finished");
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
