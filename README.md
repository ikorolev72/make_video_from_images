#						Script to automatically create MP4 video file from JPG images, MP3 audio, and lines of text read from a database.


##  What is it?
##  -----------
The script will assemble the JPG files, render and insert the text, and add the music to create a MP4 
file which will be saved in a directory on the local server.

Required soft :

  + php7
  + ffmpeg 4


##  The Latest Version

	version 1.0 2019.09.03

##  Whats new
    version 1.0 2019.09.03



##  How to install
For Ubuntu ( or any Debian distributive):
```bash
sudo apt-get update
sudo apt-get -y install php 

wget https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-64bit-static.tar.xz
tar xf ffmpeg-release-64bit-static.tar.xz
sudo mkdir /usr/share/ffmpeg
sudo mv ffmpeg-4.0.2-64bit-static/ /usr/share/ffmpeg
sudo ln -s /usr/share/ffmpeg/ffmpeg-4.0.2-64bit-static/ffmpeg /usr/bin/ffmpeg
sudo ln -s /usr/share/ffmpeg/ffmpeg-4.0.2-64bit-static/ffprobe /usr/bin/ffprobe
```

## How to run
Syntax `php /path_to_script/do_video.php [--config /path_to_config/config.json] [--debug]`
Bye default - using config file `data/config.json`
Example:
```bash
$ php /path_to_script/do_video.php
```

## How to copy to anoter location
Simple copy folder with script to another directory
```bash
$ cp -pr make_video /new_path/
```

## How to run from crontab
Add new line `*	*	1	*	*	/usr/bin/php /path_to_script/do_video.php` with schedule into crontab
```
crontab -e 
```


# Appendix
### File structure

  + ./tmp - directory for temporary files
  + ./lib - directory for temporary for libraries
  + ./lib/common_db.php - class for db function
  + ./lib/FfmpegEffects.php -  class for ffmpeg processing
  + ./lib/common_processing.php - parent class for ffmpeg processing
  + ./data - directory for data
  + ./data/config.json - default config file
  + ./do_video.php - main script
  + ./logs - log directory
  + ./logs/processing.log - main processing log



### Config
```json
{
  "general": {
    "ffmpeg": "/usr/bin/ffmpeg",
    "ffmpegLogLevel": "info"
  },
  "video": {
    "width": 1920,
    "height": 1080
  },
  "mysql": {
    "servername": "localhost",
    "database": "videoDB",
    "username": "video_develop",
    "password": "XXXXXXXX111111",
    "updateMessageRecord": true
  }
}
```

### Database



##  Bugs
##  ------------




  Licensing
  ---------
	GNU

  Contacts
  --------

     o korolev-ia [at] yandex.ru
     o http://www.unixpin.com
