Rate Limiter
============

By default, the built-in rate limiter will allow you to perform up to one request per second to ensure that your program complies with `reddit's API policies <https://github.com/reddit/reddit/wiki/API>`_. Note that Phapper does *not* simply wait one second before performing an API call, but rather checks that at least one second has passed from when it made the previous call and waits the required amount of time if necessary. However, you may turn this feature off or adjust the interval at which Phapper makes API calls. This will allow you to apply custom timing to your requests, but keep in mind that you are only allowed up to 60 requests per minute.

To turn the rate limiter off, do:

.. code-block:: php

    $r->ratelimiter->disable();

The rate limiter is **on** by default, but if you would like to turn it back on after disabling it, simply do:

.. code-block:: php

    $r->ratelimiter->enable();

You can also adjust the rate limiter to wait a custom amount of time after the previous request before performing the next request:

.. code-block:: php

    $r->ratelimiter->setInterval($seconds);

``$seconds`` should be an integer or float value for the number of seconds you want it to wait. By default, its value is 1, which is recommended. If you are running multiple scripts with the same reddit account or machine(s) with a single public IP address, you may want to increase this value accordingly.