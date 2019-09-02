<?php
/*
Common function and variables for dynamic processing

 */

class Common_processing
{
    private $error; // last error
    private $debug;

    public function __construct($debug = false)
    {
        $this->error = '';
        $this->debug = $debug;
    }

    public function doExec($Command)
    {
        $outputArray = array();
        if ($this->debug) {
            print $Command . PHP_EOL;
            //return 1;
        }
        exec($Command, $outputArray, $execResult);
        if ($execResult) {
            $this->writeToLog(join("\n", $outputArray));
            return 0;
        }
        return 1;
    }

    public function getTempioraryFile($tmpDir, $extension, &$tmpFiles)
    {
        $tmp = $tmpDir . "/" . time() . sha1(rand(100, 1000000)) . ".$extension";
        $tmpFiles[] = $tmp;
        return ($tmp);
    }

    public function removeTempioraryFiles($tmpFiles)
    {
        foreach ($tmpFiles as $tmpFile) {
            @unlink($tmpFile);
        }
        return (true);
    }

    /*
     * date2unix
     * this function translate time in format 00:00:00.00 to seconds
     *
     * @param    string $t
     * @return    float
     */
    public function date2unix($dateStr)
    {
        $time = strtotime($dateStr);
        if (!$time) {
            $this->error = "Incorrect date format for string '$dateStr'";
        }
        return ($time);
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

    public function writeToLog($message)
    {
        #echo "$message\n";
        $date = date("Y-m-d H:i:s");
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, "$date   $message" . PHP_EOL);
        fclose($stderr);
    }

    public function readJson($configFile)
    {
        $out = array();
        if (!file_exists($configFile)) {
            $this->writeToLog("File '$configFile' do not exists");
            return ($out);
        }
        $json = file_get_contents($configFile);
        if (!$json) {
            $this->writeToLog("Cannot read file '$configFile'");
            return ($out);
        }
        $out = json_decode($json, true);
        if (!$out) {
            $this->writeToLog("Incorrect json string in json file '$configFile'");
            return (array());
        }
        return ($out);
    }

    /**
     * getLastError
     * return last error description
     *
     * @return    string
     */
    public function getLastError()
    {
        return ($this->error);
    }

    /**
     * setLastError
     * set last error description
     *
     * @param    string  $err
     * @return    string
     */
    public function setLastError($err)
    {
        $this->error = $err;
        return (true);
    }

    public function cleanTmpDir($tmpDir)
    {
        if (is_dir($tmpDir)) {
            @array_map('unlink', glob("$tmpDir/*.*"));
            return (rmdir($tmpDir));
        }
        return (true);
    }

    public function checkProcessByPidFile($pidFile)
    {
        if (file_exists($pidFile)) {
            $k = 0;
            while ($k < 10) { // waiting up to 5 seconds for file. If we trying to kill when new file do not started processing yet
                $pid = trim(file_get_contents($pidFile));
                if (is_numeric($pid)) {
                    if (posix_kill($pid, 0)) {
                        return (true);
                    }
                }
                usleep(intval(0.5 * 1000000));
                $k++;
            }
            return (posix_kill($pid, 0)); // check if process running
        }
        return (false);
    }

    public function checkProcessByName($cmd)
    {
        $outputArray = array();
        $Command = "/usr/bin/pkill -0 -f '$cmd'";

        exec($Command, $outputArray, $execResult);
        if ($execResult) {
            return (false);
        }
        return (true);
    }

    public function compareTwoFiles($fileName1, $fileName2)
    {
        if (file_exists($fileName1 && file_exists($fileName2))) {
            if (crc32($fileName1) == crc32($fileName2)) {
                return (true);
            }
        }
        return (false);
    }
}
