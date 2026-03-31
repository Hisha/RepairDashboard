# RepairDashboard

## Installation Process (Ubuntu 20.04)

---

### 1. Base System Setup

1. Install **Ubuntu 20.04**
2. Set up a system update script
3. Add a **crontab** entry to run updates weekly

---

### 2. Install LAMP Stack

Reference:
[https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-20-04](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-20-04)

---

#### Apache Install

```bash
sudo apt install apache2
sudo ufw app list
sudo ufw allow in "Apache"
sudo ufw status
```

Verify Apache is working by visiting:

```
http://your_server_ip
```

---

#### MariaDB Install

```bash
sudo apt install mariadb-server
```

Update bind address:

```
/etc/mysql/mariadb.conf.d/50-server.cnf
```

Change:

```
bind-address = 0.0.0.0
```

Then restart:

```bash
sudo systemctl restart mariadb
```

Secure installation:

```bash
sudo mysql_secure_installation
```

> ⚠️ Note: Choose options appropriate for your environment. (Original setup used "N" for root password.)

Verify MariaDB:

```bash
sudo mariadb
```

Create admin users:

```sql
GRANT ALL ON *.* TO 'admin'@'localhost' IDENTIFIED BY 'P@sswordP@ssword' WITH GRANT OPTION;
GRANT ALL ON *.* TO 'admin'@'%' IDENTIFIED BY 'P@sswordP@ssword' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

---

#### PHP Install

```bash
sudo apt install php libapache2-mod-php php-mysql php-mbstring
```

Verify:

```bash
php -v
```

Update PHP configuration:

```
/etc/php/7.4/apache2/php.ini
```

Set:

```
memory_limit = 1536M
upload_max_filesize = 32M
post_max_size = 32M
```

Restart Apache:

```bash
sudo systemctl restart apache2
```

---

### 3. Install Composer

```bash
sudo apt install composer php-xml php-gd php-zip
composer require phpoffice/phpspreadsheet
```

---

### 4. Install Node.js

```bash
sudo apt install -y nodejs npm
```

---

### 5. Database Configuration

This project **does not store real database credentials in the repository**.

#### Setup

1. Copy the sample config file:

```
config/database.sample.php → config/database.php
```

2. Edit `config/database.php`:

```php
<?php
return [
    'host'    => 'localhost',
    'user'    => 'your_db_user',
    'pass'    => 'your_db_password',
    'name'    => 'RepairDashboard',
    'charset' => 'utf8',
];
```

#### Notes

* `config/database.php` is **ignored by Git** and should never be committed
* Always keep credentials private
* Consider storing this file **outside the web root** for added security
