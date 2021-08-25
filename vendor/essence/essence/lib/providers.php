<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */



/**
 *	Default providers configuration.
 *
 *	@see Essence\Provider\Collection::$_properties
 *	@var array
 */

return [
	'23hq' => [
		'class' => 'OEmbed',
		'filter' => '#23hq\.com/.+/photo/.+#i',
		'endpoint' => 'http://www.23hq.com/23/oembed?format=json&url=%s'
	],
	'Animoto' => [
		'class' => 'OEmbed',
		'filter' => '#animoto\.com/play/.+#i',
		'endpoint' => 'http://animoto.com/oembeds/create?format=json&url=%s'
	],
	'Aol' => [
		'class' => 'OEmbed',
		'filter' => '#on\.aol\.com/video/.+#i',
		'endpoint' => 'http://on.aol.com/api?format=json&url=%s'
	],
	'App.net' => [
		'class' => 'OEmbed',
		'filter' => '#(alpha|photo)\.app\.net/.+(/post)?/.+#i',
		'endpoint' => 'https://alpha-api.app.net/oembed?format=json&url=%s'
	],
	'Bambuser' => [
		'class' => 'OEmbed',
		'filter' => '#bambuser\.com/(v|channel)/.+#i',
		'endpoint' => 'http://api.bambuser.com/oembed.json?url=%s'
	],
	'Bandcamp' => [
		'class' => 'Bandcamp',
		// OpenGraph subclasses should strictly match the start of the URL
		// to prevent spoofing.
		'filter' => '#^https?://(?:[^\.]+\.)?bandcamp\.com/(album|track)/#i'
	],
	'Blip.tv' => [
		'class' => 'OEmbed',
		'filter' => '#blip\.tv/.+#i',
		'endpoint' => 'http://blip.tv/oembed?format=json&url=%s'
	],
	'Cacoo' => [
		'class' => 'OEmbed',
		'filter' => '#cacoo\.com/.+#i',
		'endpoint' => 'http://cacoo.com/oembed.json?url=%s'
	],
	'CanalPlus' => [
		'class' => 'OpenGraph',
		'filter' => '#canalplus\.fr#i'
	],
	'Chirb.it' => [
		'class' => 'OEmbed',
		'filter' => '#chirb\.it/.+#i',
		'endpoint' => 'http://chirb.it/oembed.json?url=%s'
	],
	'CircuitLab' => [
		'class' => 'OEmbed',
		'filter' => '#circuitlab\.com/circuit/.+#i',
		'endpoint' => 'https://www.circuitlab.com/circuit/oembed?format=json&url=%s'
	],
	'Clikthrough' => [
		'class' => 'OEmbed',
		'filter' => '#clikthrough\.com/theater/video/\d+#i',
		'endpoint' => 'http://clikthrough.com/services/oembed?format=json&url=%s'
	],
	'CollegeHumorOEmbed' => [
		'class' => 'OEmbed',
		'filter' => '#collegehumor\.com/(video|embed)/.+#i',
		'endpoint' => 'http://www.collegehumor.com/oembed.json?url=%s'
	],
	'CollegeHumorOpenGraph' => [
		'class' => 'OpenGraph',
		'filter' => '#collegehumor\.com/(picture|article)/.+#i'
	],
	'Coub' => [
		'class' => 'OEmbed',
		'filter' => '#coub\.com/(view|embed)/.+#i',
		'endpoint' => 'http://coub.com/api/oembed.json?url=%s'
	],
	'CrowdRanking' => [
		'class' => 'OEmbed',
		'filter' => '#crowdranking\.com/.+/.+#i',
		'endpoint' => 'http://crowdranking.com/api/oembed.json?url=%s'
	],
	'DailyMile' => [
		'class' => 'OEmbed',
		'filter' => '#dailymile\.com/people/.+/entries/.+#i',
		'endpoint' => 'http://api.dailymile.com/oembed?format=json&url=%s'
	],
	'Dailymotion' => [
		'class' => 'OEmbed',
		'filter' => '#dailymotion\.com#i',
		'endpoint' => 'http://www.dailymotion.com/services/oembed?format=json&url=%s'
	],
	'Deviantart' => [
		'class' => 'OEmbed',
		'filter' => '#deviantart\.com/.+#i',
		'endpoint' => 'http://backend.deviantart.com/oembed?format=json&url=%s'
	],
	'Dipity' => [
		'class' => 'OEmbed',
		'filter' => '#dipity\.com/.+#i',
		'endpoint' => 'http://www.dipity.com/oembed/timeline?format=json&url=%s'
	],
	'Dotsub' => [
		'class' => 'OEmbed',
		'filter' => '#dotsub\.com/view/.+#i',
		'endpoint' => 'http://dotsub.com/services/oembed?format=json&url=%s'
	],
	'Edocr' => [
		'class' => 'OEmbed',
		'filter' => '#edocr\.com/doc/[0-9]+/.+#i',
		'endpoint' => 'http://www.edocr.com/api/oembed?format=json&url=%s'
	],
	'Flickr' => [
		'class' => 'OEmbed',
		'filter' => '#flickr\.com/photos/[a-zA-Z0-9@\\._]+/[0-9]+#i',
		'endpoint' => 'http://flickr.com/services/oembed?format=json&url=%s'
	],
	'FunnyOrDie' => [
		'class' => 'OEmbed',
		'filter' => '#funnyordie\.com/videos/.+#i',
		'endpoint' => 'http://www.funnyordie.com/oembed?format=json&url=%s'
	],
	'Gist' => [
		'class' => 'OEmbed',
		'filter' => '#gist\.github\.com/.+/[0-9]+#i',
		'endpoint' => 'https://github.com/api/oembed?format=json&url=%s'
	],
	'Gmep' => [
		'class' => 'OEmbed',
		'filter' => '#gmep\.org/media/.+#i',
		'endpoint' => 'https://gmep.org/oembed.json?url=%s'
	],
	'HowCast' => [
		'class' => 'OpenGraph',
		'filter' => '#howcast\.com/.+/.+#i'
	],
	'Huffduffer' => [
		'class' => 'OEmbed',
		'filter' => '#huffduffer\.com/[-.\w@]+/\d+#i',
		'endpoint' => 'http://huffduffer.com/oembed?format=json&url=%s'
	],
	'Hulu' => [
		'class' => 'OEmbed',
		'filter' => '#hulu\.com/watch/.+#i',
		'endpoint' => 'http://www.hulu.com/api/oembed.json?url=%s'
	],
	'Ifixit' => [
		'class' => 'OEmbed',
		'filter' => '#ifixit\.com/.+#i',
		'endpoint' => 'http://www.ifixit.com/Embed?format=json&url=%s'
	],
	'Ifttt' => [
		'class' => 'OEmbed',
		'filter' => '#ifttt\.com/recipes/.+#i',
		'endpoint' => 'http://www.ifttt.com/oembed?format=json&url=%s'
	],
	'Imgur' => [
		'class' => 'OEmbed',
		'filter' => '#(imgur\.com/(gallery|a)/.+|imgur\.com/.+)#i',
		'endpoint' => 'http://api.imgur.com/oembed?format=json&url=%s'
	],
	'Instagram' => [
		'class' => 'OEmbed',
		'filter' => '#instagr(\.am|am\.com)/p/.+#i',
		'endpoint' => 'http://api.instagram.com/oembed?format=json&url=%s'
	],
	'Jest' => [
		'class' => 'OEmbed',
		'filter' => '#jest\.com/video/.+#i',
		'endpoint' => 'http://www.jest.com/oembed.json?url=%s'
	],
	'Justin.tv' => [
		'class' => 'OEmbed',
		'filter' => '#justin\.tv/.+#i',
		'endpoint' => 'http://api.justin.tv/api/embed/from_url.json?url=%s'
	],
	'Kickstarter' => [
		'class' => 'OEmbed',
		'filter' => '#kickstarter\.com/projects/.+#i',
		'endpoint' => 'http://www.kickstarter.com/services/oembed?format=json&url=%s'
	],
	'Meetup' => [
		'class' => 'OEmbed',
		'filter' => '#meetup\.(com|ps)/.+#i',
		'endpoint' => 'https://api.meetup.com/oembed?format=json&url=%s'
	],
	'Mixcloud' => [
		'class' => 'OEmbed',
		'filter' => '#mixcloud\.com/.+/.+#i',
		'endpoint' => 'http://www.mixcloud.com/oembed?format=json&url=%s'
	],
	'Mobypicture' => [
		'class' => 'OEmbed',
		'filter' => '#(moby.to|mobypicture\.com/user/.+/view)/.+#i',
		'endpoint' => 'http://api.mobypicture.com/oEmbed?format=json&url=%s'
	],
	'Nfb' => [
		'class' => 'OEmbed',
		'filter' => '#nfb\.ca/films/.+#i',
		'endpoint' => 'http://www.nfb.ca/remote/services/oembed?format=json&url=%s'
	],
	'Official.fm' => [
		'class' => 'OEmbed',
		'filter' => '#official\.fm/.+#i',
		'endpoint' => 'http://official.fm/services/oembed?format=json&url=%s'
	],
	'Polldaddy' => [
		'class' => 'OEmbed',
		'filter' => '#polldaddy\.com/.+#i',
		'endpoint' => 'http://polldaddy.com/oembed?format=json&url=%s'
	],
	'PollEverywhere' => [
		'class' => 'OEmbed',
		'filter' => '#polleverywhere\.com/(polls|multiple_choice_polls|free_text_polls)/.+#i',
		'endpoint' => 'http://www.polleverywhere.com/services/oembed?format=json&url=%s'
	],
	'Prezi' => [
		'class' => 'OpenGraph',
		'filter' => '#prezi\.com/.+/.+#i'
	],
	'Qik' => [
		'class' => 'OEmbed',
		'filter' => '#qik\.com/\w+#i',
		'endpoint' => 'http://qik.com/api/oembed.json?url=%s'
	],
	'Rdio' => [
		'class' => 'OEmbed',
		'filter' => '#rdio\.com/(artist|people)/.+#i',
		'endpoint' => 'http://www.rdio.com/api/oembed?format=json&url=%s'
	],
	'Revision3' => [
		'class' => 'OEmbed',
		'filter' => '#revision3\.com/[a-z0-9]+/.+#i',
		'endpoint' => 'http://revision3.com/api/oembed?format=json&url=%s'
	],
	'Roomshare' => [
		'class' => 'OEmbed',
		'filter' => '#roomshare\.jp(/en)?/post/.+#i',
		'endpoint' => 'http://roomshare.jp/en/oembed.json?&url=%s'
	],
	'Sapo' => [
		'class' => 'OEmbed',
		'filter' => '#videos\.sapo\.pt/.+#i',
		'endpoint' => 'http://videos.sapo.pt/oembed?format=json&url=%s'
	],
	'Screenr' => [
		'class' => 'OEmbed',
		'filter' => '#screenr\.com/.+#i',
		'endpoint' => 'http://www.screenr.com/api/oembed.json?url=%s'
	],
	'Scribd' => [
		'class' => 'OEmbed',
		'filter' => '#scribd\.com/doc/[0-9]+/.+#i',
		'endpoint' => 'http://www.scribd.com/services/oembed?format=json&url=%s'
	],
	'Shoudio' => [
		'class' => 'OEmbed',
		'filter' => '#(shoudio\.com|shoud\.io)/.+#i',
		'endpoint' => 'http://shoudio.com/api/oembed?format=json&url=%s'
	],
	'Sketchfab' => [
		'class' => 'OEmbed',
		'filter' => '#sketchfab\.com/show/.+#i',
		'endpoint' => 'http://sketchfab.com/oembed?format=json&url=%s'
	],
	'SlideShare' => [
		'class' => 'OEmbed',
		'filter' => '#slideshare\.net/.+/.+#i',
		'endpoint' => 'http://www.slideshare.net/api/oembed/2?format=json&url=%s'
	],
	'SoundCloud' => [
		'class' => 'OEmbed',
		'filter' => '#soundcloud\.com/[a-zA-Z0-9-_]+/[a-zA-Z0-9-]+#i',
		'endpoint' => 'http://soundcloud.com/oembed?format=json&url=%s'
	],
	'SpeakerDeck' => [
		'class' => 'OEmbed',
		'filter' => '#speakerdeck\.com/.+/.+#i',
		'endpoint' => 'https://speakerdeck.com/oembed.json?url=%s'
	],
	'Spotify' => [
		'class' => 'OEmbed',
		'filter' => '#(open|play)\.spotify\.com/.+#i',
		'endpoint' => 'https://embed.spotify.com/oembed?format=json&url=%s'
	],
	'TedOEmbed' => [
		'class' => 'OEmbed',
		'filter' => '#ted\.com/talks/.+#i',
		'endpoint' => 'http://www.ted.com/talks/oembed.json?url=%s'
	],
	'TedOpenGraph' => [
		'class' => 'OpenGraph',
		'filter' => '#ted\.com/talks#i'
	],
	'Twitter' => [
		'class' => 'OEmbed',
		'filter' => '#twitter\.com/[a-zA-Z0-9_]+/status(es)?/.+#i',
		'endpoint' => 'https://api.twitter.com/1/statuses/oembed.json?url=%s'
	],
	'Ustream' => [
		'class' => 'OEmbed',
		'filter' => '#ustream\.(tv|com)/.+#i',
		'endpoint' => 'http://www.ustream.tv/oembed?format=json&url=%s'
	],
	'Vhx' => [
		'class' => 'OEmbed',
		'filter' => '#vhx\.tv/.+#i',
		'endpoint' => 'http://vhx.tv/services/oembed.json?url=%s'
	],
	'Viddler' => [
		'class' => 'OEmbed',
		'filter' => '#viddler\.com/.+#i',
		'endpoint' => 'http://www.viddler.com/oembed/?url=%s'
	],
	'Videojug' => [
		'class' => 'OEmbed',
		'filter' => '#videojug\.com/(film|interview)/.+#i',
		'endpoint' => 'http://www.videojug.com/oembed.json?url=%s'
	],
	'Vimeo' => [
		'class' => 'Vimeo',
		'filter' => '#vimeo\.com#i',
		'endpoint' => 'http://vimeo.com/api/oembed.json?url=%s'
	],
	'Vine' => [
		'class' => 'Vine',
		// OpenGraph subclasses should strictly match the start of the URL
		// to prevent spoofing.
		'filter' => '#^https?://vine.co/v/[a-zA-Z0-9]+#i'
	],
	'Wistia' => [
		'class' => 'OEmbed',
		'filter' => '#https?://(.+)?(wistia.com|wi.st)/.*#i',
		'endpoint' => 'http://fast.wistia.com/oembed?format=json&url=%s',
	],
	'WordPress' => [
		'class' => 'OEmbed',
		'filter' => '#wordpress\\.com/.+#i',
		'endpoint' => 'http://public-api.wordpress.com/oembed/1.0?format=json&for=me&url=%s'
	],
	'Yfrog' => [
		'class' => 'OEmbed',
		'filter' => '#yfrog\.(com|ru|com\.tr|it|fr|co\.il|co\.uk|com\.pl|pl|eu|us)/.+#i',
		'endpoint' => 'http://www.yfrog.com/api/oembed?format=json&url=%s'
	],
	'Youtube' => [
		'class' => 'Youtube',
		'filter' => '#youtube\.com|youtu\.be#i',
		'endpoint' => 'http://www.youtube.com/oembed?format=json&url=%s'
	]

	/**
	 *	The following providers will try to embed any URL.
	 */

	/*
	'OEmbed' => [
		'class' => 'OEmbed',
		'filter' => '#.+#'
	],
	'OpenGraph' => [
		'class' => 'OpenGraph',
		'filter' => '#.+#'
	],
	*/
];
