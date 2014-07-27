# Installation

1) Place the assets, config, error, and html into your web server folder.

2) Configure your web server to use html as the root folder

3) Configure your web server to send /assets/ to the assets folder

4) Configure your web server to send all php requests to /index.php

5) Execute the initialDatabase.sql file

6) Create a mysql user which has access to the authentication and factoid databases.

7) Set the mysql information in the config/config.php file. An example file is provided as defaultconfig.php.

8) Run the composer install and place /vendor at assets/php/

9) Execute the initialDatabase.sql in your database