# Student Registration System Installation Guide

This guide provides step-by-step instructions for setting up the Student Registration System on an Ubuntu server.

## System Requirements

- Ubuntu Server 20.04 LTS or newer
- Apache 2.4+
- PHP 7.4+ with PDO extension
- MySQL 5.7+ or MariaDB 10.3+
- 2GB RAM minimum (4GB recommended)
- 10GB storage minimum

## 1. Update System Packages

Start by updating the system packages:

```bash
sudo apt update
sudo apt upgrade -y
```

## 2. Install Required Software

Install Apache, PHP, MySQL and required PHP extensions:

```bash
sudo apt install apache2 mysql-server php php-mysql php-json php-mbstring php-zip php-gd php-xml php-curl php-pdo libapache2-mod-php -y
```

## 3. Configure MySQL

Start the MySQL service and secure it:

```bash
sudo systemctl start mysql
sudo systemctl enable mysql
sudo mysql_secure_installation
```

Follow the prompts to:
- Set a root password
- Remove anonymous users
- Disallow root login remotely
- Remove test database
- Reload privilege tables

## 4. Create Database and User

Log in to MySQL:

```bash
sudo mysql -u root -p
```

Create the database and user:

```sql
CREATE DATABASE cmrit_db;
CREATE USER 'cmrit_user'@'localhost' IDENTIFIED BY 'test';
GRANT ALL PRIVILEGES ON cmrit_db.* TO 'cmrit_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Note: Replace 'test' with a secure password in production.

## 5. Configure Apache

Create a virtual host for the application:

```bash
sudo nano /etc/apache2/sites-available/registration.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAdmin webmaster@yourdomain.com
    DocumentRoot /var/www/html/registration

    <Directory /var/www/html/registration>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/registration_error.log
    CustomLog ${APACHE_LOG_DIR}/registration_access.log combined
</VirtualHost>
```

Enable the site and necessary modules:

```bash
sudo a2ensite registration.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## 6. Download and Set Up the Application

Create the application directory and set permissions:

```bash
sudo mkdir -p /var/www/html/registration
```

### Option 1: If you're using Git:

```bash
cd /tmp
git clone https://github.com/yourusername/registration.git
sudo cp -R registration/* /var/www/html/registration/
```

### Option 2: If you're uploading files manually:

Upload the files to your server and then move them to the web directory:

```bash
sudo cp -R /path/to/upload/* /var/www/html/registration/
```

Set proper ownership and permissions:

```bash
sudo chown -R www-data:www-data /var/www/html/registration
sudo chmod -R 755 /var/www/html/registration
sudo find /var/www/html/registration -type d -exec chmod 755 {} \;
sudo find /var/www/html/registration -type f -exec chmod 644 {} \;
```

Create the uploads directory and make it writable:

```bash
sudo mkdir -p /var/www/html/registration/uploads
sudo chmod -R 775 /var/www/html/registration/uploads
sudo chown -R www-data:www-data /var/www/html/registration/uploads
```

## 7. Create Database Tables

Create the necessary database tables:

```bash
sudo mysql -u cmrit_user -p cmrit_db
```

Run the following SQL commands:

```sql
-- Create existstudents table
CREATE TABLE existstudents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    name_of_student VARCHAR(100) NOT NULL,
    registration_type ENUM('Aided', 'Unaided') NOT NULL,
    class VARCHAR(5) NOT NULL,
    division VARCHAR(5) NOT NULL,
    receipt_tuition LONGBLOB,
    receipt_stationary LONGBLOB,
    receipt_cs LONGBLOB,
    receipt_it LONGBLOB,
    receipt_pta LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create approvedstudents table
CREATE TABLE approvedstudents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    name_of_student VARCHAR(100) NOT NULL,
    registration_type ENUM('Aided', 'Unaided') NOT NULL,
    class VARCHAR(5) NOT NULL,
    division VARCHAR(5) NOT NULL,
    receipt_tuition LONGBLOB,
    receipt_stationary LONGBLOB,
    receipt_cs LONGBLOB,
    receipt_it LONGBLOB,
    receipt_pta LONGBLOB,
    approval_date DATETIME DEFAULT NULL,
    status ENUM('approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

EXIT;
```

## 8. Configure PHP Settings

Adjust PHP settings for file uploads:

```bash
sudo nano /etc/php/7.4/apache2/php.ini
```

Find and modify these settings:

```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 180
memory_limit = 256M
```

Restart Apache to apply changes:

```bash
sudo systemctl restart apache2
```

## 9. Setup Admin Access

Create the admin directory and secure it:

```bash
sudo mkdir -p /var/www/html/registration/admin
sudo chown -R www-data:www-data /var/www/html/registration/admin
```

## 10. Final Configuration

Update the database connection details in your PHP files if needed:

- recipt.php
- admin/view_receipts.php
- admin/view_approved_students.php

Edit the connection details to match your configuration:

```php
$db_host = 'localhost';
$db_name = 'cmrit_db';
$db_user = 'cmrit_user';
$db_pass = 'test'; // Use your secure password
```

## 11. Test the Installation

Access your application through a web browser:

```
http://yourdomain.com
```

For admin access:

```
http://yourdomain.com/admin
```

The default admin credentials are:
- Username: admin
- Password: admin123

Important: Change the admin password in the admin PHP files before production use.

## 12. Security Recommendations

For production environments, implement these additional security measures:

1. Set up HTTPS using Let's Encrypt:
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d yourdomain.com
   ```

2. Change the default admin credentials in:
   - admin/index.php
   - admin/view_receipts.php
   - admin/view_approved_students.php

3. Implement a proper password hashing mechanism (e.g., password_hash) for admin authentication.

4. Set up regular database backups:
   ```bash
   mysqldump -u cmrit_user -p cmrit_db > backup_$(date +%Y%m%d).sql
   ```

5. Configure a firewall:
   ```bash
   sudo ufw allow OpenSSH
   sudo ufw allow 'Apache Full'
   sudo ufw enable
   ```

## Troubleshooting

### File Upload Issues
- Check directory permissions for the uploads folder
- Verify PHP configuration for file uploads
- Check Apache error logs: `sudo tail -f /var/log/apache2/error.log`

### Database Connection Issues
- Verify database credentials
- Check if MySQL service is running: `sudo systemctl status mysql`
- Confirm the database user has proper privileges

### Apache Issues
- Check syntax: `sudo apachectl configtest`
- Review error logs: `sudo tail -f /var/log/apache2/error.log`
- Restart Apache: `sudo systemctl restart apache2`

## Maintenance

### Regular Updates
```bash
sudo apt update
sudo apt upgrade
```

### Database Backup
```bash
mysqldump -u cmrit_user -p cmrit_db > /backup/cmrit_db_$(date +%Y%m%d).sql
```

---

For additional support, please contact the system administrator. 