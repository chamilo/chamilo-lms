Text2Speech
===========

Version 0.1

This plugin adds the possibility (once setup with 3rd party account) to add
speech to learning paths by converting text in the learning paths to audio
files attached to each learning path item.

This plugin requires the *installation and configuration* of the TTS software
and data from Mozilla, which might be deterring to most users (sorry about
that). Please refer to https://github.com/mozilla/TTS/wiki on how to download,
install and configure your own TTS server.

It also requires the "AI Helper" plugin to be installed, enabled and properly
configured, as it connects to the learning path auto-generation feature to add
audio to it.

Once your TTS server is available, get a URL to connect to it, install and
enable the plugin, give it an API key (if any), a host (could be localhost)
and enable the plugin in the learning paths, then create a new learning
path using the AI Helper plugin in the learning path tool. You should now
get additional speech for every document in your learning path.

## Use a Mozilla TTS server

To mount your TTS server, you can use the Docker image from
[synesthesiam/docker-mozillatts](https://github.com/synesthesiam/docker-mozillatts).
Clone the repository and then run

```$ docker run -it -p 5002:5002 synesthesiam/mozillatts:<LANGUAGE>```

(where <LANGUAGE> is one of the supported languages (en, es, fr, de) for this image. If no language is given,
U.S. English is used). This image will serve the necessary API to configure in the plugin.

## Configuring the plugin

The plugin configuration asks for an API key which is *not* necessary if using a local Docker container.
The TTS URL field, in the case of the Docker container described above, should simply point to `http://localhost:5002/`. Requests sent by Chamilo will be visible in the Docker container console, if left open.
This plugin and the suggested TTS model only allow for very small character strings to be translated, as documented here: https://github.com/synesthesiam/docker-mozillatts/issues/3.

## Using the plugin

The plugin, once enabled and properly configured, will add an audio creation block in the learning path edition screen, when clicking the audio speaker icon just under any document item of the learning path. The block is identified by "Text to Speech". Click the button to generate the audio, check if the quality is satisfying, then save the audio.
When student open this learning path item, the audio will play.
