# joomlaUtilsPlugin

A plugin providing some utility functions for Joomla.

## 1. Provided Functionality

### 1.1. Secondary Languages and Wikipedia Quick-URLs

- `[[XXX|locale|http://www.url.xyz]]` renders to `<span class="lng" lang="locale">[<a href="http://www.url.xyz">XXX</a>]</span>`
- `[[XXX||http://www.url.xyz]]` renders to `<span class="lng" lang="zh_HANS">[<a href="http://www.url.xyz">XXX</a>]</span>`
- `[[xxx]]` turns into `<span class="lng" lang="zh_HANS">[XXX]</span>`
- `[[xxx|de]]` turns into `<span class="lng" lang="de">[XXX]</span>`
- `[[XXX|en|wiki:YYY]]` renders to `<a href="http://en.wikipedia.org/wiki/YYY">XXX</a>`
- `[[XXX|de|wiki:YYY]]` renders to `<span class="lng" lang="de">[<a href="http://de.wikipedia.org/wiki/YYY">XXX</a>]</span>`
- `[[XXX||wiki:YYY]]` renders to `<span class="lng" lang="zh_HANS">[<a href="http://zh.wikipedia.org/wiki/YYY">XXX</a>]</span>`
- `[[XXX|en|wiki]]` renders to `<a href="http://en.wikipedia.org/wiki/XXX">XXX</a>`
- `[[XXX|de|wiki]]` renders to `<span class="lng" lang="de">[<a href="http://de.wikipedia.org/wiki/XXX">XXX</a>]</span>`
- `[[XXX||wiki]]` renders to `<span class="lng" lang="zh_HANS">[<a href="http://zh.wikipedia.org/wiki/XXX">XXX</a>]</span>`


### 1.2. Google Maps

This small utility can render a static Google Map into the web page. It also allows for specifying an alternative image in case Google Maps cannot be reached. Thus, you would first render the map with some bogus `path-to-alternative-image`, render the map, then copy the image and update the alternative path.

    {map}path-to-alternative-image
    title
    Hefei,Anhui,China | The beautiful city of Hefei [[合肥]].
    Shanghai,China | Shanghai [[上海]] is also not bad.
    Beijing,China | Beijing [[北京]] is the capital of China.
    {map}

Is rendered to a picture of a map with the locations marked and printed as legend list before:


<div class="map"><ul class="map"><li class="map"><a style="color:#0000ff" href="http://maps.google.com/maps?q=Hefei,Anhui,China">A</a>:&nbsp;The beautiful city of Hefei <span class="lng" lang="zh_HANS">[合肥]</span>. (<a href="http://maps.google.com/maps?q=Hefei,Anhui,China">map</a>)</li><li class="map"><a style="color:#00ff00" href="http://maps.google.com/maps?q=Shanghai,China">B</a>:&nbsp;Shanghai <span class="lng" lang="zh_HANS">[上海]</span> is also not bad. (<a href="http://maps.google.com/maps?q=Shanghai,China">map</a>)</li><li class="map"><a style="color:#ff0000" href="http://maps.google.com/maps?q=Beijing,China">C</a>:&nbsp;Beijing <span class="lng" lang="zh_HANS">[北京]</span> is the capital of China. (<a href="http://maps.google.com/maps?q=Beijing,China">map</a>)</li></ul><p class="map"><img src="http://maps.googleapis.com/maps/api/staticmap?size=690x690&amp;maptype=roadmap&amp;format=png&amp;language=language&amp;sensor=false&amp;markers=color:0x0000ff%7Clabel:A%7CHefei,Anhui,China&amp;markers=color:0x00ff00%7Clabel:B%7CShanghai,China&amp;markers=color:0xff0000%7Clabel:C%7CBeijing,China" alt="title" style="min-width:100%;width:100%;max-width:100%;min-height:auto;height:auto;max-height:auto" onError="this.onerror=null;this.src='path-to-alternative-image';" /></p></div>



## 2. License

This file is under the GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007.
