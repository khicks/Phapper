Authentication
==============

This page contains an overview of how Phapper authenticates to reddit by using OAuth2.

For Phapper to authenticate to reddit, you must enter your username, password, and an app ID and secret into your newly created ``config.php`` file (copied or moved from ``config.sample.php``).

Normally, applications don't ask you for your reddit password (which is a good thing!), but rather they have you visit reddit and ask you whether or not you want to allow that application to perform certain actions on your behalf. This is useful for external web and phone apps, which will obtain an authentication token from reddit if you allow them.

This library, however, should be running on your own machine, so you can trust it with your reddit password. Additionally, the goal of this library is to have as little web browser interaction as possible. Script applications (and only script applications) are allowed to grant authentication tokens to themselves by using the user's password. Authentication tokens generated in this way are valid for one hour, but Phapper will generate another for you if the previous one expires.

If Phapper cannot log in using the supplied credentials, it will throw an exception. If its connection to reddit times out, it will try again in 5 seconds until it succeeds.