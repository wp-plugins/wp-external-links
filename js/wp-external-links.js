/* WP External Links */
(function(e){"use strict";function n(e,t,n){if(e.attachEvent){e.attachEvent("on"+t,n)}else if(e.addEventListener){e.addEventListener(t,n,false)}}function r(t,n){var r=t.getAttribute("data-wpel-target");var i=t.getAttribute("href");var s;if(i&&r){s=window.open(i,r);s.focus();if(n){if(n.preventDefault!==e){n.preventDefault()}else if(n.returnValue!==e){n.returnValue=false}}}}var t=jQuery===e?null:jQuery;if(t){t(function(){t("body").on("click","a",function(e){r(this,e)})})}else{n(window,"load",function(){var e=window.document.getElementsByTagName("a");var t=function(e){var t=this instanceof Element?this:e.target;r(t,e)};var i;var s;for(s=0;s<e.length;s+=1){i=e[s];n(i,"click",t)}})}})()