# Automated Overview Video Generation (using FFMPEG)

## Get Audio
1. Write out a script to be recorded
2. Go to https://cloud.google.com/text-to-speech#section-2 and select a desired voice in the demo section. (recommended: en-US-Neural2-D)
3. Paste your script into the demo.
4. Open Chrome dev tools -> Network
5. Click "Speak It" button
6. Look for a request starting with "proxy" (e.g. https://cxl-services.appspot.com/proxy?url=https://texttospeech.googleapis.com/...)
7. Open response and copy the `audioContent` property into a text file (e.g. audio.txt).
8. Open a terminal/console in the directory where the text file is saved and run `base64 audio.txt -d > audio.mp3`

## Get Screenshots
See [FFMPEG wiki](https://trac.ffmpeg.org/wiki/Slideshow) for details.

1. Take screenshots of all views you want to be shown in the video.
2. Edit `video.txt` to set the order and duration of each photo.
3. Open a terminal/console and run:

```sh
ffmpeg -f concat -i video.txt -vsync vfr -pix_fmt yuv420p video.mp4
```
4. Your video without audio is available in `video.mp4`

## Merge Audio and Video

```sh
ffmpeg -i video.mp4 -i audio.mp3 -c:v copy -c:a aac output.mp4
```
