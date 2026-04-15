---
name: "laravel-service-deployment"
description: "Deploy Laravel service to remote server via SSH, configure database, and create admin account. Invoke when user needs to deploy Laravel backend to production server."
---

# Laravel Service Deployment Skill

This skill guides you through deploying a Laravel service to a remote server with complete configuration including database setup and admin account creation.

## When to Invoke

- Deploying Laravel backend to production/staging server
- Setting up new Laravel project on remote server
- Migrating Laravel service to new server
- Initial deployment of Laravel application

## Prerequisites

- SSH access to remote server (configured with key-based auth)
- Server has PHP, MySQL/MariaDB, and web server (Nginx/Apache) installed
- Database and user credentials ready
- Local Laravel project ready for deployment

## Deployment Steps

### 1. Prepare and Compress Local Project

```bash
# Remove storage symlink to avoid compression issues
Remove-Item -Recurse -Force service\public\storage

# Compress the service folder
Compress-Archive -Path service\* -DestinationPath service.zip -Force
```

### 2. Transfer to Remote Server

```bash
# Upload to server
scp service.zip root@<SERVER_IP>:/www/wwwroot/
```

### 3. Extract and Setup on Server

```bash
# SSH to server and setup
ssh root@<SERVER_IP> "rm -rf /www/wwwroot/<DOMAIN> && mkdir -p /www/wwwroot/<DOMAIN> && unzip -o /www/wwwroot/service.zip -d /www/wwwroot/<DOMAIN> && rm /www/wwwroot/service.zip"
```

### 4. Configure Environment

```bash
# Copy and configure .env file
ssh root@<SERVER_IP> "cd /www/wwwroot/<DOMAIN> && cp .env.example .env && sed -i 's/DB_DATABASE=.*/DB_DATABASE=<DB_NAME>/' .env && sed -i 's/DB_USERNAME=.*/DB_USERNAME=<DB_USER>/' .env && sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=<DB_PASS>/' .env && sed -i 's/DB_HOST=.*/DB_HOST=localhost/' .env && sed -i 's/APP_URL=.*/APP_URL=http:\/\/<DOMAIN>/' .env && sed -i 's/APP_ENV=.*/APP_ENV=production/' .env && sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env"
```

### 5. Set Permissions

```bash
# Set proper ownership and permissions
ssh root@<SERVER_IP> "cd /www/wwwroot/<DOMAIN> && chown -R www:www . && chmod -R 755 . && chmod -R 777 storage bootstrap/cache"
```

### 6. Generate App Key and Create Storage Link

```bash
# Generate application key
ssh root@<SERVER_IP> "cd /www/wwwroot/<DOMAIN> && php artisan key:generate"

# Create storage symlink
ssh root@<SERVER_IP> "cd /www/wwwroot/<DOMAIN> && php artisan storage:link"
```

### 7. Import Database (if needed)

```bash
# Import database from SQL file
ssh root@<SERVER_IP> "cd /www/wwwroot/<DOMAIN> && mysql -u <DB_USER> -p'<DB_PASS>' <DB_NAME> < installation/backup/database.sql"
```

### 8. Create Admin Account

If admins table is empty, create admin account:

```bash
# Generate password hash
ssh root@<SERVER_IP> "cd /www/wwwroot/<DOMAIN> && php -r \"echo password_hash('password', PASSWORD_BCRYPT);\""

# Save hash and create SQL file
$HASH='$2y$12$yUjpqHc/XLTet10VCpN9kuFKYSC9ctNxdxWplTUia.UBNqSK5oamq'

# Insert admin into database
ssh root@<SERVER_IP> "mysql -u <DB_USER> -p'<DB_PASS>' <DB_NAME> -e \"INSERT INTO admins (f_name, l_name, email, phone, password, role_id, created_at, updated_at) VALUES ('Admin', 'User', 'admin@admin.com', '+1234567890', '$HASH', 1, NOW(), NOW());\""
```

### 9. Verify Deployment

```bash
# Test database connection
ssh root@<SERVER_IP> "cd /www/wwwroot/<DOMAIN> && php -r \"require 'vendor/autoload.php'; try { \$pdo = new PDO('mysql:host=localhost;dbname=<DB_NAME>', '<DB_USER>', '<DB_PASS>'); echo 'Database connection successful!'; } catch (PDOException \$e) { echo 'Connection failed: ' . \$e->getMessage(); }\""
```

## Verification Checklist

- [ ] Project files uploaded and extracted
- [ ] .env file configured with correct database credentials
- [ ] Directory permissions set (755 for files, 777 for storage/cache)
- [ ] Application key generated
- [ ] Storage symlink created
- [ ] Database imported (if applicable)
- [ ] Admin account created
- [ ] Database connection verified
- [ ] Website accessible via browser

## Troubleshooting

### 500 Internal Server Error
- Check Laravel logs: `storage/logs/laravel.log`
- Verify .env configuration
- Ensure database tables exist

### Database Connection Failed
- Verify DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
- Check MySQL user permissions
- Ensure MySQL service is running

### Permission Denied
- Run: `chown -R www:www /www/wwwroot/<DOMAIN>`
- Run: `chmod -R 777 storage bootstrap/cache`

### Admin Login Failed
- Check if admin exists: `SELECT * FROM admins;`
- Reset password using bcrypt hash
- Ensure role_id is set correctly

## Example Variables

```
SERVER_IP=149.104.28.122
DOMAIN=admin.loteat.com
DB_NAME=admin_loteat_com
DB_USER=admin_loteat_com
DB_PASS=stwR93ddcNATrscZ
ADMIN_EMAIL=admin@admin.com
ADMIN_PASSWORD=password
```

## Notes

- Always backup database before making changes
- Use strong passwords for production
- Configure SSL/HTTPS for production sites
- Set up proper web server virtual host configuration
- Consider using deployment tools like Deployer for automated deployments
