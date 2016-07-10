Getting Started
===============

To start using Phapper, go to your `apps page in reddit's preferences <https://www.reddit.com/prefs/apps/>`_ and create a new app. Name it anything you like, set the type to "script", and give it any redirect URI you wish (``https://www.reddit.com`` will work). A redirect URI is required by reddit, but not used with Phapper. Take note of the app ID underneath "personal use script" and the app secret. You will need those for the configuration file.

Next, download the `Phapper files from GitHub <https://github.com/rotorcowboy/Phapper>`_. You can either download the ZIP file and extract it yourself or do a git clone:

.. code-block:: bash

    $ git clone git@github.com:rotorcowboy/Phapper.git

Once you've obtained the files, enter the ``Phapper/`` directory and move or copy ``config.sample.php`` to ``config.php`` and fill in the missing values for username, password, etc.

Then, you can start creating your script. Create a new file in the ``Phapper/`` directory (or elsewhere) and insert this code to initialize Phapper:

.. code-block:: php

    <?php

    require_once("src/phapper.php");
    $r = new Phapper();

Congratulations! You're ready to start using Phapper! We will be performing reddit operations with the ``$r`` object throughout this documentation, but you can name it anything you like.

Debugging
---------

All Phapper functions will return something when called, whether or not the result of the corresponding reddit API call is of any use or substance. To obtain the result of any function call, you can use ``var_dump`` on it,  like:

.. code-block:: php

    var_dump($r->getMe());

Additionally, you can set Phapper to output the URL of any reddit API call by enabling debug mode:

.. code-block:: php

    $r->setDebug(true);

You can turn this off by setting debug to ``false``.
