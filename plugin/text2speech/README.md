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

