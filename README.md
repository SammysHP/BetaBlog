# BetaBlog

## What is BetaBlog?

*BetaBlog* is the software I'm using for my blog at <http://www.sammyshp.de/betablog>. After several years with ready-made blog software I was tired of software that doesn't fit my needs. So I decided to write one myself.

I like minimalism, so *BetaBlog* was designed as a single-file script. After one week I realized that it got too complex and I splitted it up into several files, using object oriented programming. Many features were added in the following months, but it's still a very minimal system.


## How to use it?

*BetaBlog* requires at least **php 5.3** and a **MySQL database**. Currently it uses Apache's mod&#95;rewrite, but you can add these rules to your nginx configuration yourself.

The **Installation** is simple:

- Clone the repository to the desired location
- Copy `config.php.skel` to `config.php` and edit it
- Correct the `RewriteBase` path in `.htaccess`
- Open your browser and go to http://&lt;path&gt;/install
- Log in and if there's no error you can start using *BetaBlog*

But one request: When you want to use *BetaBlog*, please create your own theme. It is included in this repository because it contains some logic, but it is also something personal.


## Attribution

I'm using some third-party components:

- [AntiCSRF](https://github.com/SammysHP/phpAntiCSRF) (GPL v3)
- [CSS3 GitHub Buttons](https://github.com/SammysHP/css3-github-buttons) (public domain)
- [fancybox](http://fancyapps.com/fancybox/) (CC by-nc 3.0)
- [highlight.js](http://softwaremaniacs.org/soft/highlight/en/) (custom license)
- [jQuery](http://jquery.com/) (MIT)
- [klein.php](https://github.com/chriso/klein.php) (MIT)
- [placeholder-compat](https://github.com/SammysHP/placeholder-compat) (GPL v3)
- [SimpleBars](https://github.com/SammysHP/SimpleBars) (GPL v3)


## License

Copyright (C) 2013 Sven Karsten Greiner &lt;<sven@sammyshp.de>&gt;

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
