# Overview

It began feeling like using WordPress for a personal website, while versatile for even novice users, when used for a simple, small personal blog or site was like using a chainsaw to cut a cake. Yeah, it'll get the job done, but the maintenance overhead and speed of use was a chore. Sure, there's good caching available, but most personal websites get less than 100 visits a day, and often the cache would time out before your latest views, rendering it useless for a first anonymous view. 

So, as an alternative to just building a site from scratch in raw HTML, I wanted something that would offer some of a CMS's intelligence like templating, caching, RSS, sitemaps and dynamic routing, without the overhead of open source CMS systems that try to be everything to everyone. SO, I started making this for my personal site. There's no required database, it just used a webserver (Apache or Nginx), PHP 7.x, and NodeJS for build scripts. I use Bootstrap 4.x for a style base, because, when it comes to a responsive website, I see no reason to reinvent the wheel. I can live with a 120kb CSS file. Of course, this is entirely the prerogative of the developer. Use it, or don't. 

## Features

* Blog posts are static Markdown files
* RSS and Google Sitemaps are built in
* Views are cached in static files for a fast response
* Font Awesome 4.x is included for use of their icon library
* Templates using PHTML files that allow for logic, but also can use a simple list of template keywords (post tags, current date, etc.)
* Core PHP files are under 2,000 lines of code. This is not bleeding edge, just 5 Singletons that do a simple job
* Redirects and other options configured from base ini file

## TODO

* Move to Webpack. I dunno, seems to be the reigning champ build tool now.
* Move to FontAwesome NPM package
* Create demo build target that copies example files over to content and template folders
* Add RSS template for demo target build
* Document config better
* Run through PSR-2 checker, get fully PSR compliant
* Do I *really* needs CSS3 SASS package?
