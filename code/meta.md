Story engine for your browser.

Begins by displaying [CommonMark-spec](https://commonmark.org/) markdown from a file `terms.md`.

On "accept terms", user address and browser fingerprint is logged, and they are redirected to `story.php`.

The story engine redirects back to `index.php` if browser fingerprint or device cookie fails to be found.  
The story engine displays contents of `story/file.md` files.  
Functioning relative linking between files, so that story paths can be built between displayed decision points.  

Support for auto-advance of non-decision story slides (via browser page refresh html, ideally) after a user-adjustable seconds setting is a stretch goal on this.

# Visual/UI Style
Minimal formatting.

Post-`terms.md`


# Implementation Thoughts
Let's do a file per IP, v6 if it exists, v4 if available, and failback to setting a cookie with terms acceptance & story progress.
