RepairDashboard

Install Process (Ubuntu 20.04):
1. Install Ubuntu20.04, setup update script and add a crontab entry to run it weekly.
2. Install LAMP: https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-20-04

  <b>APACHE Install:</b>
  
    - sudo apt install apache2
    - sudo ufw app list
    - sudo ufw allow in "Apache"
    - sudo ufw status
    - verify that Apache is working by going to "http://your_server_ip" with a browser.
    
  <b>MariaDB Install:</b>
  
    - sudo apt install mariadb-server
    - Change bind address to 0.0.0.0 in /etc/mysql/mariadb.conf.d/50-server.cnf
    - sudo systemctl restart mariadb
    - sudo mysql_secure_installation(Make sure you enter "N" for set root password?)
    - verify that MySQL is working by using the command: sudo mariadb
    - add local admin account: GRANT ALL ON *.* TO 'admin'@'localhost' IDENTIFIED BY 'P@sswordP@ssword' WITH GRANT OPTION;
    - add remote admin account: GRANT ALL ON *.* TO 'admin'@'%' IDENTIFIED BY 'P@sswordP@ssword' WITH GRANT OPTION;
  
  <b>PHP Install:</b>
  
    - sudo apt install php libapache2-mod-php php-mysql php-mbstring
    - verify that PHP is working by using the command: php -v
    - Edit php.ini to set memory_limit = 512M

3. Install Composer:
    - sudo apt install composer php-xml php-gd
    - composer require phpoffice/phpspreadsheet
    
4. Install Node:

	- sudo apt install -y nodejs npm
