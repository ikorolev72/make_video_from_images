# Script to automatically create MP4 video file from JPG images, MP3 audio, and lines of text read from a database.


##  What is it?
##  -----------
The script will assemble the JPG files, render and insert the text, and add the music to create a MP4 file which will be saved in a directory on the local server.

Script check the table `video_messages` and find all records where the value video_created is false (0). 

For each of those records, read another table, `video_elements`, and read all records where the video_id matches. 

Each record in this table will correspond to one element in the resulting video. Elements can be videos, still photos, audios, and text.

Script placed each element in the video based on the parameters in the
video_elements record for that element.

The resulting video save in the specified directory (as defined by the
completed_video_directory field). Name the file YYYYMMDD.mp4, based on
the date field.


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

git clone https://github.com/ikorolev72/make_video_from_images.git
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
Add new line `50	23	*	*	* /usr/bin/php /path_to_script/do_video.php >>/log_dir/make_video.log 2>&1` with schedule into crontab
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
Please check `data/db.sql` file for database structure and testing records.
Connection option are define in `config.json` file.
#### Fields description
Table `video_messages` :
 + video_id
 + date (e.g. 2019-08-15)
 + audio_duration (duration of longest audio in seconds)
 + image_directory (path to the image directory on local server)
 + audio_directory (path to the audio directory on local server)
 + source_video_directory (path to directory containing source video files)
 + completed_video_directory (path to directory where completed MP4 file will be saved)
 + font_directory - directory for fonts. there used system installed fonts ( use bash command `fc-list` for available fonts)

For each record from video_messages, access another table, video_elements,
and read all records where the video_id matches. Each record in this table will
correspond to one element in the resulting video. Elements can be videos, still
photos, audios, and text. For each element, the following information will be
available:

Table `video_elements`:
 + content_type (photo, video, audio, text)
 + file_name - name of image, video, or audio file, which will be located in
 + the directory specified in the video_messages table
 + text_content - UTF-8 text field containing the text content if the
 + element is text
 + fade_in - global time in seconds where fade in begins
 + full_alpha - global time when fully faded in
 + fade_out - global time when fade out begins
 + zero_alpha- global time when fully faded out
 + location_x - x location in pixels of the top left corner of where the
 + element is to be placed, based on the top left corner of the video being
 + x=0
 + location_y - y location in pixels of the top left corner of where the
 + element is to be placed, based on the top left corner of the video being
 + y=0
 + height - height of element in pixels
 + width - width of element in pixels
 + layer - 0 is lowest layer, 1 is on top of 0, 2 on top of 1, etc.
 + text_font - if a text element, the font filename, eg `Arial.ttf` ( use bash command `fc-list` for available fonts, font directory defines in table `video_messages.font_directory` )
 + text_color - if a text element, the text color
 + text_size - if a text element, the text size
 + text_shadow_color - if a text element, the color of text shadow
 + text_shadow_offset_x - if a text element, the offset in pixels of the shadow (x)
 + text_shadow_offset_y - if a text element, the offset in pixels of the shadow (y)
 + text_align - text alignment (right, left, or center)

##  Bugs
##  ------------




  Licensing
  ---------
	GNU

  Contacts
  --------

     o korolev-ia [at] yandex.ru
     o http://www.unixpin.com
