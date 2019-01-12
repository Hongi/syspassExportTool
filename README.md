# SYSPASS EXPORT TOOL

# Table of Contents
1. [Introduction](#introduction)
2. [Requirements](#Requirements)
3. [Install syspass on docker](#Install-syspass-on-docker)
4. [Install syspass export tool](#Install-syspass-export-tool)
5. [How to use the tool](#How-to-use-the-tool)
6. [Columns in file](#Columns-in-file)


## Introduction

The tool allows to export the syspass database passwords and some linked infos into a CSV or JSON file.

## Requirements

To use this tool you need:

1. the possibility to access to the syspass database
2. the **masterkey** of syspass

## Install syspass on docker

1. clone the project: ``git clone https://github.com/fabiottini/syspassExportTool.git``
2. run the docker-compose in the docker folder: ``docker-compose up -d``
3. when all of 3 container are up and running go to web interface to do the installation: ``http://127.0.0.1:3000``
4. At the end of syspass installation, apply this **FIX** to allows syspass to run without database connection problem. sometimes when you restart the container the ip could change and the syspass application CAN NOT access to the db.
I suggest to do this **FIX**:
    1. open the page: ``http://127.0.0.1:3003`` (phpmyadmin interface)
    2. enter with (or those you fillin in the docker-compose file): 
        - **username**: root
        - **password**: syspass 
    3. enter in the **Accounts Users** section and change the host permission of the user ``sp_<some code>`` to ``%``
5. run ``docker-compose down`` to switch off all the container
6. comment in the file ``docker-compose.yml`` the phpmyadmin section (for more security):
```
  # phpmyadmin: 
  #   image: phpmyadmin/phpmyadmin
  #   container_name: passwordDBPHP
  #   depends_on:
  #     - db
  #   links: 
  #     - db
  #   ports:
  #     - 3003:80
  #   environment:
  #     - MYSQL_ROOT_PASSWORD: syspass
```
7. enjoy syspass

## Install syspass export tool

1. enter in **syspassExportTool** folder
2. Install Composer:
    1. apt update && apt-get install curl php-cli php-mbstring git unzip
    2. curl -sS https://getcomposer.org/installer -o composer-setup.php
    3. php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    4. composer require
3. Install php mysql packages:
    1. apt-get install php7.0-mysql
4. Configure the mysql parameters in the **export.php** file:
```
$hostname   ="127.0.0.1";
$port       ="3002";
$dbname     ="syspass";
$user       ="root";
$password   ="syspass";
```

## How to use the tool

To use the tool:
1. enter in the **syspassExportTool** folder
2. run the command: ``php export.php <MASTERKEY-PASSWORD>``
3. wait for the message: FILE CREATED!

## Columns in file

The CSV or JSON columns foreach account are:
- id
- User Group
- User Name
- Client Name
- Account Name
- login
- url
- pass
- key
- notes
- dateAdd
- isPrivate
- isPrivateGroup
- passDate
- **passDecrypt** <== this value represent the **DECRYPTED PASSWORD**
- tags