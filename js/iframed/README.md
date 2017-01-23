Asynchronous loading for 3rd-party script using iframe
======================================================

iframed.js is an asynchronous loader for 3rd party's javascript.
It improves site response even if javascript uses document.write()
in a recursive way.

iframed.js includes two method.

* Dynamic injection into iframe. (Firefox 11, Chrome 18, Safari 5, 
  iOS 5.1 Safari, Android 2.3, etc.)

* Friendly iframe. (IE, Opera)

Usage
-----
See samples on [gh-pages](http://tokkonopapa.github.io/iframed.js/).

### Asynchronous loading ###

Load script asynchronously through iframe.

    createIframe(id, url, styles, [min_height], [stylesheet])

*   `id` :  
    ID of box element where iframe will be injected.

*   `url` :  
    URL or path to the script.

*   `styles` :  
    Styles for iframe. (ex: `"width: 200px; height: 300px"`)

*   `min_height` : (option)  
    Auto height adjustment will stop when the height of iframe reaches at 
    this value. (ex: `"400px"`, If `0` then no adjustment)

*   `stylesheet` : (option)  
    URL or path to the stylesheet for iframe.

### Lazy loading ###
Load script through iframe when `onload` is fired. `createIframe` still blocks 
`onload` event and the busy indicator of browser may work hard during loading.
Using this function does not mean an asynchronous loading but still you have a 
benefit to prevent rendering being blocked.

The all arguments of this function are passed into `createIframe`.

    lazyLoadIframe(id, url, styles, [min_height], [stylesheet])

Notice
------
Make sure to set the right path to `fiframe.html` or `fiframe.min.html` in the 
`iframed.js` or `iframed.min.js` to fit your environment.

Related
-------
- [tony4d/sugar](https://github.com/tony4d/sugar)  
    This has flexible and powerful API. But always requests static friendly 
    iframe file if it is not in the browser cache.

- [olark/lightningjs](https://github.com/olark/lightningjs)  
    This is the truly asynchronous javascript loader. But can not be used when 
    the script include `document.write()`. And this also requires additional 
    code to wrap the original script. So this is for the JavaScript providers.

License
-------
Copyright &copy; 2012 tokkonopapa  
Free to use and abuse under the [MIT license][MIT].
 
[MIT]: http://www.opensource.org/licenses/mit-license.php
