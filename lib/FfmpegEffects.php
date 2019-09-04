<?php
/**
 *
 *
 * This class is the wrapper for ffmpeg ( http://ffmpeg.org )
 * and have several function for effects, like
 * transitions, mix audio, etc
 * @author korolev-ia [at] yandex.ru
 * @version 3.0.15
 */

require_once __DIR__ . '/common_processing.php';
class FfmpegEffects extends Common_processing
{
    private $ffmpegSettings = array();
    private $error; # last error

    public function __construct($log=null, $debug = false)
    {
        parent::__construct($log, $debug);
        # GENERAL settinds
        $this->ffmpegSettings['general'] = array();
        $this->ffmpegSettings['general']['showCommand'] = true;
        $this->ffmpegSettings['general']['ffmpegLogLevel'] = 'info'; # info warning error fatal panic verbose debug trace
        $this->ffmpegSettings['general']['ffmpeg'] = "ffmpeg";
        $this->ffmpegSettings['general']['ffprobe'] = "ffprobe";

        # AUDIO settinds
        $this->ffmpegSettings['audio'] = array();

        ################# direct audio settings #################
        # you can use 'direct audio settings' string for audio settings,
        # in this case all other audio settings will be ignored
        //$this->ffmpegSettings['audio']['direct']=" -c:a aac -b:a 160k -ac 1 ";
        ################# end of direct audio settings #################

        ################# copy settings #################
        // $this->ffmpegSettings['audio']['codec']="copy" ;        # copy existing audio cahnnels to output file, without transcoding ( -c:a copy )
        ################# end of copy settings #################

        $this->ffmpegSettings['audio']['channels'] = 2; # stereo # https://trac.ffmpeg.org/wiki/AudioChannelManipulation
        ################# aac settings #################
        $this->ffmpegSettings['audio']['codec'] = "aac"; # https://trac.ffmpeg.org/wiki/Encode/AAC                                                            # used native encoder/decoder
        $this->ffmpegSettings['audio']['bitrate_mode'] = "cbr"; # Constant Bit Rate (CBR) mode
        $this->ffmpegSettings['audio']['bitrate'] = "160k"; # hi quality ( -c:a aac -b:a 484k )
        ################# end of aac settings #################

        ################# mp3 settings #################
        //$this->ffmpegSettings['audio']['codec']="mp3";        # https://trac.ffmpeg.org/wiki/Encode/MP3
        //$this->ffmpegSettings['audio']['bitrate_mode']="cbr";    # Constant Bit Rate (CBR) mode
        //$this->ffmpegSettings['audio']['bitrate']="320k";        # hi quality ( -c:a mp3 -b:a 320k )
        // please select cbr or vbr mode
        ////$this->ffmpegSettings['audio']['bitrate_mode']="vbr";# Variable Bit Rate (VBR) mode
        ////$this->ffmpegSettings['audio']['qscale']="1";        # hi quality ( -c:a mp3 -q:a 1 )
        ################# end of mp3 settings #################

        # VIDEO settinds
        $this->ffmpegSettings['video'] = array();
        ################# direct video settings #################
        # you can use 'direct video settings' string for video settings,
        # in this case all other video settings will be ignored
        //$this->ffmpegSettings['video']['direct']=" -c:v libx264 -pix_fmt yuv420p -f mp4 ";
        ################# end of direct video settings #################

        ################# copy settings #################
        //$this->ffmpegSettings['video']['codec']="copy";        # copy video stream to output withou transcoding ( -c:v copy )
        ################# end of copy settings #################

        $this->ffmpegSettings['video']['framerate'] = 25;
        $this->ffmpegSettings['video']['format'] = "mp4";
        $this->ffmpegSettings['video']['pix_fmt'] = "yuv420p";
        $this->ffmpegSettings['video']['faststart'] = true; # -movflags +faststart
        ################# libx264 settings #################
        $this->ffmpegSettings['video']['codec'] = "libx264"; # https://trac.ffmpeg.org/wiki/Encode/H.264
        $this->ffmpegSettings['video']['preset'] = "veryfast"; # Speed of processing: ultrafast,superfast, veryfast, faster, fast, medium, slow, slower, veryslow, placebo
        $this->ffmpegSettings['video']['crf'] = "23"; # Constant Rate Factor: 0-51: where 0 is lossless, 23 is default, and 51 is worst possible.
        //$this->ffmpegSettings['video']['profile']="main";        # limit the output to a specific H.264 profile: baseline, main, high, high10, high422, high444 ( for old devices set to:  'baseline -level 3.0' )
        ################# end of libx264 settings #################
        $this->error = null;
    }

    /**
     * getAudioOutSettingsString
     * return the string for audio out settings for ffmpeg
     *
     * @return    string
     */
    private function getAudioOutSettingsString()
    {
        if (isset($this->ffmpegSettings['audio']['direct'])) {
            return ($this->ffmpegSettings['audio']['direct']);
        }
        $str = '';
        if (isset($this->ffmpegSettings['audio']['codec'])) {
            $str .= " -strict -2 -c:a " . $this->ffmpegSettings['audio']['codec'];
        }
        if (isset($this->ffmpegSettings['audio']['bitrate_mode']) && $this->ffmpegSettings['audio']['bitrate_mode'] == 'cbr') {
            if ($this->ffmpegSettings['audio']['bitrate']) {
                $str .= " -b:a " . $this->ffmpegSettings['audio']['bitrate'];
            }
        }
        if (isset($this->ffmpegSettings['audio']['bitrate_mode']) && $this->ffmpegSettings['audio']['bitrate_mode'] == 'vbr') {
            if ($this->ffmpegSettings['audio']['qscale']) {
                $str .= " -q:a " . $this->ffmpegSettings['audio']['qscale'];
            }
        }
        if (isset($this->ffmpegSettings['audio']['channels'])) {
            $str .= " -ac " . $this->ffmpegSettings['audio']['channels'];
        }

        return ($str);
    }

    /**
     * getVideoOutSettingsString
     * return the string for video out settings for ffmpeg
     *
     * @return    string
     */
    private function getVideoOutSettingsString()
    {
        if (isset($this->ffmpegSettings['video']['direct'])) {
            return ($this->ffmpegSettings['video']['direct']);
        }
        $str = '';
        if (isset($this->ffmpegSettings['video']['codec'])) {
            $str .= " -c:v " . $this->ffmpegSettings['video']['codec'];
        }
        if (isset($this->ffmpegSettings['video']['preset'])) {
            $str .= " -preset " . $this->ffmpegSettings['video']['preset'];
        }
        if (isset($this->ffmpegSettings['video']['crf'])) {
            $str .= " -crf " . $this->ffmpegSettings['video']['crf'];
        }
        if (isset($this->ffmpegSettings['video']['profile'])) {
            $str .= " -profile:v " . $this->ffmpegSettings['video']['profile'];
        }

        if (isset($this->ffmpegSettings['video']['pix_fmt'])) {
            $str .= " -pix_fmt " . $this->ffmpegSettings['video']['pix_fmt'];
        }
        if (isset($this->ffmpegSettings['video']['faststart'])) {
            $str .= " -movflags +faststart";
        }
        if (isset($this->ffmpegSettings['video']['format'])) {
            $str .= " -f " . $this->ffmpegSettings['video']['format'];
        }
        return ($str);
    }


    /**
     * getFfmpegSettings
     * return the current value of ffmpeg settings
     *
     * @param    string  $section ( 'general' ,'audio' or 'video' )
     * @param    string  $key
     * @return    string
     */
    public function getFfmpegSettings($section, $key)
    {
        $value = isset($this->ffmpegSettings[$section][$key]) ? $this->ffmpegSettings[$section][$key] : null;
        return $value;
    }

    /**
     * setFfmpegSettings
     * set new value to ffmpeg output settings
     *
     * @param    string  $section ( 'general' ,'audio' or 'video' )
     * @param    string  $key
     * @param    string  $value
     * @return    true
     */
    public function setFfmpegSettings($section, $key, $value)
    {
        $this->ffmpegSettings[$section][$key] = $value;
        return (true);
    }

    /**
     * setGeneralSettings
     * return the current value of general ffmpeg settings
     *
     * @param    array  with key=>value of audio settings
     * @param    string  $value
     * @return    true
     */
    public function setGeneralSettings($arr)
    {
        $this->ffmpegSettings['general'] = array_replace($this->ffmpegSettings['general'], $arr);
        return (true);
    }

    /**
     * getGeneralSettings
     * return the current value of general ffmpeg settings
     *
     * @param    array  with key=>value of audio settings
     * @return    true
     */
    public function getGeneralSettings()
    {
        return ($this->ffmpegSettings['general']);
    }

    /**
     * setAudioOutputSettings
     * return the current value of ffmpeg settings
     *
     * @param    array  with key=>value of audio settings
     * @return    true
     */
    public function setAudioOutputSettings($arr)
    {
        $this->ffmpegSettings['audio'] = array_replace($this->ffmpegSettings['audio'], $arr);
        return (true);
    }

    /**
     * setVideoOutputSettings
     * return the current value of ffmpeg settings
     *
     * @param    array  with key=>value of video settings
     * @return    true
     */
    public function setVideoOutputSettings($arr)
    {
        $this->ffmpegSettings['video'] = array_replace($this->ffmpegSettings['video'], $arr);
        return (true);
    }

    /**
     * getAudioOutputSettings
     * return the current value output audio ffmpeg settings
     *
     * @return array with key=>value of audio settings
     */
    public function getAudioOutputSettings()
    {
        return ($this->ffmpegSettings['audio']);
    }

    /**
     * getVideoOutputSettings
     * return the current value output video ffmpeg settings
     *
     * @return array with key=>value of audio settings
     */
    public function getVideoOutputSettings()
    {
        return ($this->ffmpegSettings['video']);
    }

    /**
     * formatTime
     * return time in hour:minute:
     *
     * @param    integer $t
     * @param    string  $f
     * @return    string
     */
    private function formatTime($t, $f = ':') // t = seconds, f = separator
    {
        return sprintf("%01d%s%02d%s%02.2f", floor($t / 3600), $f, ($t / 60) % 60, $f, $t % 60);
    }



    /**
     * getStreamInfo
     * function get info about video or audio stream in the file
     *
     * @param    string $fileName
     * @param    string $streamType    must be  'audio' or 'video'
     * @param    array &$data          return data
     * @return    integer 1 for success, 0 for any error
     */
    public function getStreamInfo($fileName, $streamType, &$data)
    {
        # parameter - 'audio' or 'video'
        $ffprobe = $this->getFfmpegSettings('general', 'ffprobe');

        if (!$probeJson = json_decode(`"$ffprobe" $fileName -v quiet -hide_banner -show_streams -of json`, true)) {
            $this->writeToLog("Cannot get info about file $fileName");
            return 0;
        }
        if (empty($probeJson["streams"])) {
            $this->writeToLog("Cannot get info about streams in file $fileName");
            return 0;
        }
        foreach ($probeJson["streams"] as $stream) {
            if ($stream["codec_type"] == $streamType) {
                $data = $stream;
                break;
            }
        }

        if (empty($data)) {
            $this->writeToLog("File $fileName :  stream not found");
            return 0;
        }
        if ('video' == $streamType) {
            if (empty($data["height"]) || !intval($data["height"]) || empty($data["width"]) || !intval($data["width"])) {
                $this->writeToLog("File $fileName : invalid or corrupt dimensions");
                return 0;
            }
        }

        return 1;
    }

    /**
     * time2float
     * this function translate time in format 00:00:00.00 to seconds
     *
     * @param    string $t
     * @return    float
     */

    public function time2float($t)
    {
        $matches = preg_split("/:/", $t, 3);
        if (array_key_exists(2, $matches)) {
            list($h, $m, $s) = $matches;
            return ($s + 60 * $m + 3600 * $h);
        }
        $h = 0;
        list($m, $s) = $matches;
        return ($s + 60 * $m);
    }

    /**
     * float2time
     * this function translate time from seconds to format 00:00:00.00
     *
     * @param    float $i
     * @return    string
     */
    public function float2time($i)
    {
        $h = intval($i / 3600);
        $m = intval(($i - 3600 * $h) / 60);
        $s = $i - 60 * floatval($m) - 3600 * floatval($h);
        return sprintf("%01d:%02d:%05.2f", $h, $m, $s);
    }



    /**
     * transcodeSubtitlesToAss
     * @param    string    $input subtitles file ( can be vtt or src )
     * @param    string    $output ass file
     * @return string  Command ffmpeg
     */
    public function transcodeSubtitlesToAss($input, $output)
    {
        $ffmpeg = $this->getFfmpegSettings('general', 'ffmpeg');
        $ffmpegLogLevel = $this->getFfmpegSettings('general', 'ffmpegLogLevel');
        if (!file_exists($input)) {
            $this->setLastError("File $input do not exists");
            return '';
        }
        $cmd = join(
            " ",
            [
            "$ffmpeg -loglevel $ffmpegLogLevel  -y  ",
            " -i $input $output"]
        );
        if ($this->getFfmpegSettings('general', 'showCommand')) {
            echo "$cmd\n";
        }
        return $cmd;
    }

    /**
     * addSubtitlesToVideo
     *
     * @param    string    $input
     * @param    string    $temporaryAssFile
     * @param    string    $output
     * @return string  Command ffmpeg
     */

    public function addSubtitlesToVideo(
        $input,
        $temporaryAssFile,
        $output
    ) {
        $ffmpeg = $this->getFfmpegSettings('general', 'ffmpeg');
        $ffmpegLogLevel = $this->getFfmpegSettings('general', 'ffmpegLogLevel');
        $videoOutSettingsString = $this->getVideoOutSettingsString();
        $audioOutSettingsString = $this->getAudioOutSettingsString();
        $data = null;
        if (!file_exists($input)) {
            $this->setLastError("File $input do not exists");
            return '';
        }
        /*
        if (!$this->getStreamInfo($input, 'video', $data)) {
        $this->setLastError("Cannot get info about video stream in file $input");
        return '';
        }
         */
        $cmd = join(
            " ",
            [
            "$ffmpeg -loglevel $ffmpegLogLevel  -y  ",
            "-i $input",
            " -filter_complex \"",
            "ass='$temporaryAssFile'",
            "[v]\"",
            " -map \"[v]\" -map \"a:0?\" $audioOutSettingsString $videoOutSettingsString $output",
        ]
        );
        if ($this->getFfmpegSettings('general', 'showCommand')) {
            echo "$cmd\n";
        }
        return $cmd;
    }





    public function getWidthOfTextInPixel($fontSize, $font, $text)
    {
        $tmp_bbox = $this->getSizeOfTextInPixel($fontSize, $font, $text);
        return ($tmp_bbox['width']);
    }

    public function getHeightOfTextInPixel($fontSize, $font, $text)
    {
        $tmp_bbox = $this->getSizeOfTextInPixel($fontSize, $font, $text);
        return ($tmp_bbox['height']);
    }

    private function getSizeOfTextInPixel($fontSize, $font, $text)
    {
        $bbox = imagettfbbox($fontSize, 0, $font, $text);
        $xcorr = 0 - $bbox[6]; //northwest X
        $ycorr = 0 - $bbox[7]; //northwest Y
        $tmp_bbox['left'] = $bbox[6] + $xcorr;
        $tmp_bbox['top'] = $bbox[7] + $ycorr;
        $tmp_bbox['width'] = $bbox[2] + $xcorr;
        $tmp_bbox['height'] = $bbox[3] + $ycorr;
        return ($tmp_bbox);
    }

    public function reWrapText($text, $output_width, $fontSize, $font)
    {
        $text = preg_replace('/\s*$/', '', $text);
        $text = preg_replace('/\\\N/', ' ', $text);
        //$text = preg_replace('/\s*\n\s*/', ' ', $text);
        $text = preg_replace("/\s*\n/", "\n", $text);

        $lines = preg_split('/\n/', $text);
        $maxLen = 0;
        foreach ($lines as $line) {
            if (strlen($line) > $maxLen) {
                $maxLen = strlen($line);
            }
        }
        $textWidth = $this->getWidthOfTextInPixel($fontSize, $font, $text);
        if (0 === $textWidth) {
            $textWidth = 1;
        }
        $requiredTextLen = round(1.35 * $maxLen * $output_width / $textWidth);
        //echo " $requiredTextLen = round( $maxLen * $output_width / $textWidth);\n";
        $text = wordwrap($text, $requiredTextLen, PHP_EOL, true);
        //echo "<pre>### $font\n$output_width\n$textWidth\n$strLen\n$requiredTextLen\n$text ####</pre>\n";
        return $text;
    }

    public function getFontProperties($fontFile, $defaultFamily = 'Sans')
    {
        $cmd = "/usr/bin/fc-list | /bin/grep $fontFile | /usr/bin/head -1 2>/dev/null";
        $fontProperties = array();
        $fontProperties['family'] = $defaultFamily;
        $fontProperties['bold'] = 0;
        $fontProperties['italic'] = 0;
        $data = ` $cmd `;
        if ($data) {
            if (preg_match('/\s*(.+)\s*:\s*(.+)\s*:style=.*Bold/', $data, $matches)) {
                $fontProperties['family'] = $matches[2];
                $fontProperties['bold'] = -1;
            }
            if (preg_match('/\s*(.+)\s*:\s*(.+)\s*:style=.*Italic/', $data, $matches)) {
                $fontProperties['family'] = $matches[2];
                $fontProperties['italic'] = -1;
            }
        }
        return ($fontProperties);
    }

    // RRGGBB to AABBGGRR
    public function getKlmColor($htmlColor, $alpha = '00')
    {
        $color = strtoupper("&H${alpha}" . substr($htmlColor, 4, 2) . substr($htmlColor, 2, 2) . substr($htmlColor, 0, 2));
        return ($color);
    }


    public function getWidthOfTextinPx($fontSize, $font, $text)
    {
        $bbox = imagettfbbox($fontSize, 0, $font, $text);
        $xcorr = 0 - $bbox[6]; //northwest X
        $ycorr = 0 - $bbox[7]; //northwest Y
        $tmp_bbox['left'] = $bbox[6] + $xcorr;
        $tmp_bbox['top'] = $bbox[7] + $ycorr;
        $tmp_bbox['width'] = $bbox[2] + $xcorr;
        $tmp_bbox['height'] = $bbox[3] + $ycorr;
        return ($tmp_bbox['width']);
    }


    public function prepareSubtitles($textRecord, $temporaryAssFile, $width=1920, $height=1080)
    {
        $styles="";
        $dialog="";
        foreach ($textRecord as $key=>$value) {
            if ($value["content_type"]!='text') {
                continue;
            }
            // prepare styles
            $styleName="Style_$key";

            $fontFile=$this->fixPath($value["font_directory"]."/".$value["text_font"]);
            $assFontProperties = $this->getFontProperties($fontFile);
            $font = $assFontProperties['family'];
            $styleBold = $assFontProperties['bold'] ? -1 : 0;
            $styleItalic = $assFontProperties['italic'] ? -1 : 0;

            $fontColor = $this->getKlmColor($value["text_color"]);
            $shadowColor = $this->getKlmColor($value["text_shadow_color"]);
            $shadow=1;
            $outLine=0;
            $marginL=$value["location_x"];
            $marginR=$width- $value["location_x"] - $value["width"];
            $marginV=$value["location_y"];
            $fontSize=$value["text_size"];
            $alingment=7; // top left
            switch ($value["text_align"]) {
                case 'left':
                $alingment=7;
                break;
                case 'right':
                $alingment=9;
                break;
                case 'center':
                $alingment=8;
                break;
            }

            $styles .= "Style: $styleName,$font,$fontSize,$fontColor,&H000000FF,&H000000FF,$shadowColor,$styleBold,$styleItalic,0,0,100,100,0,0,1,$outLine,$shadow,$alingment,$marginL,$marginR,$marginV,1" . PHP_EOL;

            // prepare dialogs
            $dialogStart = $this->float2time($value["fade_in"]);
            $dialogEnd = $this->float2time($value["zero_alpha"]);
            $layer=$value["layer"];
            $fadeIn= floatval($value["full_alpha"] - $value["fade_in"])*1000;
            $fadeOut= floatval($value["zero_alpha"] - $value["fade_out"])*1000;
            $fixedText= $value["text_content"] ;
            $shadowOffsetX= $value["text_shadow_offset_x"] ;
            $shadowOffsetY= $value["text_shadow_offset_y"] ;

            $dialog .="Dialogue: $layer,$dialogStart,$dialogEnd,$styleName,,0,0,0,,{\\shad$shadowOffsetX\\yshad$shadowOffsetY\\fad($fadeIn, $fadeOut)}$fixedText" . PHP_EOL;
        }
        $content = "[Script Info]
; Aegisub 3.2.2
; http://www.aegisub.org/
; FfmpegEffects php lib
; korolev-ia [at] yandex.ru
ScriptType: v4.00+
PlayResX: $width
PlayResY: $height
WrapStyle: 0
YCbCr Matrix: TV.601


[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
$styles

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
$dialog";

        if (! $writenBytes=file_put_contents($temporaryAssFile, $content)) {
            $this->setLastError("Error: Cannot save temporary subtitles file '$temporaryAssFile'");
            return (false);
        }
        return (true);
    }

   

    public function prepareVideoFromPhotoFast($value, $output, $width=1920, $height=1080)
    {
        $ffmpeg = $this->getFfmpegSettings('general', 'ffmpeg');
        $ffmpegLogLevel = $this->getFfmpegSettings('general', 'ffmpegLogLevel');
        $videoOutSettingsString = $this->getVideoOutSettingsString();
        $audioOutSettingsString = $this->getAudioOutSettingsString();

        $key=0;
        //$value=$videoRecord;
        $input=array();
        $fadeFilter=array();
        $concatFilter='';
                    
        $fadeInDuration= floatval($value["full_alpha"] - $value["fade_in"]);
        $fadeOutStart= floatval($value["fade_out"]- $value["fade_in"]);
        $fadeOutDuration= floatval($value["zero_alpha"] - $value["fade_out"]);

        switch ($value["content_type"]) {
                case 'video':
                    $start=0;
                    $end=$value["zero_alpha"]-$value["fade_in"];
                    $inputFile=$this->fixPath($value["source_video_directory"]."/".$value["file_name"]);
                    $input[]="-ss $start -t $end -i $inputFile";
                    $fadeFilter[]="[0:v] scale=w=$width:h=$height, fade=t=in:st=$start:duration=$fadeInDuration, fade=t=out:st=$fadeOutStart:duration=$fadeOutDuration, setpts=PTS-STARTPTS [v0];";
                    $concatFilter.="[v0] null [v] \"" ;
                    break;
                case 'photo':
                    $inputFile=$this->fixPath($value["image_directory"]."/".$value["file_name"]);
              
                    $start=0;
                    $end=$fadeInDuration;
                    $input[]="-loop 1 -r 25 -ss $start -t $end -i $inputFile";

                    $end=$fadeOutStart-$fadeInDuration;
                    $input[]="-loop 1 -r 1 -ss $start -t $end -i $inputFile";

                    $end=$fadeOutDuration;
                    $input[]="-loop 1 -r 25 -ss $start -t $end -i $inputFile";

                    $fadeFilter[]="[0:v] scale=w=$width:h=$height, fade=t=in:st=$start:duration=$fadeInDuration, setpts=PTS-STARTPTS  [v0];";
                    $fadeFilter[]="[1:v] scale=w=$width:h=$height, fps=fps=25, setpts=PTS-STARTPTS  [v1];";
                    $fadeFilter[]="[2:v] scale=w=$width:h=$height, fade=t=out:st=$start:duration=$fadeOutDuration, setpts=PTS-STARTPTS  [v2];";
                    $concatFilter.="[v0][v1][v2] concat=n=3:v=1:a=0 [v] \"" ;
                    break;
                default:
                    $this->setLastError("Error: Cannot prepare temporary video. Unknown content_type: ".$value["content_type"]);
                    return(false);
                    break;
                }
    
        if (!file_exists($inputFile)) {
            $this->setLastError("Error: Input file '$inputFile' do not exists") ;
            return(false);
        }

        $cmd = join(" ", array(
            $ffmpeg,
            "-y", // overwrite output file
            "-loglevel $ffmpegLogLevel", //  ( default level is info )
            join(" ", $input), // input
            "-filter_complex \" ", // use filters
            join(" ", $fadeFilter), // input
            $concatFilter, //
            " -map \"[v]\"",
            "-c:v h264 -crf 23 -preset veryfast -pix_fmt yuv420p", // use output video codec h264 with Constant Rate Factor(crf=20), and veryfast codec settings
            "-an",
            "-mpegts_copyts 1 -f mpegts $output", // output in mp4 format
        ));

        return($cmd);
    }



    public function collectFinalVideo($videosFromPhotos, $temporaryAssFile, $audioFile, $audioDuration, $output)
    {
        $ffmpeg = $this->getFfmpegSettings('general', 'ffmpeg');
        $ffmpegLogLevel = $this->getFfmpegSettings('general', 'ffmpegLogLevel');
        $videoOutSettingsString = $this->getVideoOutSettingsString();
        $audioOutSettingsString = $this->getAudioOutSettingsString();
        $cmd = join(" ", array(
            $ffmpeg,
            "-y", // overwrite output file
            "-loglevel $ffmpegLogLevel", //  ( default level is info )
            "-ss 0 -t $audioDuration -i $audioFile",
            "-i 'concat:".join("|", $videosFromPhotos)."'",
            "-vf 'ass=$temporaryAssFile'",
            "$videoOutSettingsString $audioOutSettingsString $output", // output in mp4 format
        ));
        return($cmd);
    }

    public function fixPath($path)
    {
        return(preg_replace("/\/\//", "/", $path));
    }
}
