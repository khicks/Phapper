Getting Started
===============

To start using Phapper, go to your `apps page in reddit's preferences <https://www.reddit.com/prefs/apps/>`_ and create a new app. Name it anything you like, set the type to "script", and give it any redirect URI you wish (``https://www.reddit.com`` will work). A redirect URI is required, but not used with Phapper. Take note of the app ID underneath "personal use script" and the app secret. You will need those for the configuration file.

Next, download the `Phapper files from GitHub <https://github.com/rotorcowboy/Phapper>`_. You can either download the ZIP file and extract it yourself or do a git clone:

.. code-block:: bash

    $ git clone git@github.com:rotorcowboy/Phapper.git

Once you've obtained the files, enter the ``Phapper/`` directory and move or copy ``config.sample.php`` to ``config.php`` and fill in the missing values for username, password, etc.

Then, you can start creating your script. Create a new file in the ``Phapper/`` directory (or elsewhere) and insert this code to initialize Phapper:

.. code-block:: php

    <?php

    require_once("src/phapper.php");
    $r = new \Phapper\Phapper();

Congratulations! You're ready to start using Phapper! We will be performing reddit operations with the ``$r`` object throughout this documentation, but you can name it anything you like.