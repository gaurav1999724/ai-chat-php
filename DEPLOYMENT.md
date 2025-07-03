# 🚀 AWS Deployment Guide

This guide will help you deploy your AI Chat application on AWS EC2.

## Prerequisites

- AWS Account
- EC2 Instance (t3.micro or higher)
- Domain name (optional but recommended)
- SSH key pair

## Quick Deployment

### 1. Launch EC2 Instance

1. Go to AWS Console → EC2 → Launch Instance
2. Choose Amazon Linux 2023 AMI
3. Select t3.micro (free tier) or t3.small
4. Configure Security Group:
   - SSH (Port 22) - Your IP
   - HTTP (Port 80) - 0.0.0.0/0
   - HTTPS (Port 443) - 0.0.0.0/0
5. Launch and download your key pair

### 2. Connect to Instance

```bash
ssh -i your-key.pem ec2-user@your-instance-ip
```

### 3. Run Deployment Script

```bash
# Upload your project files
scp -i your-key.pem -r /path/to/your/ChatGpt/* ec2-user@your-instance-ip:/home/ec2-user/

# Connect to instance and run deployment
ssh -i your-key.pem ec2-user@your-instance-ip
cd /home/ec2-user
chmod +x deploy.sh
./deploy.sh
```

## Manual Deployment Steps

### 1. Install LAMP Stack

```bash
# Update system
sudo yum update -y

# Install packages
sudo yum install -y httpd php php-mysqlnd php-json php-curl php-mbstring php-xml php-zip mysql mysql-server git unzip

# Start services
sudo systemctl start httpd
sudo systemctl enable httpd
sudo systemctl start mysqld
sudo systemctl enable mysqld
```

### 2. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Setup Database

```bash
# Secure MySQL
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE ai_chat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ai_chat_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON ai_chat.* TO 'ai_chat_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Deploy Application

```bash
# Copy files to web directory
sudo cp -r /home/ec2-user/* /var/www/html/chatgpt/

# Set permissions
sudo chown -R apache:apache /var/www/html/chatgpt
sudo chmod -R 755 /var/www/html/chatgpt
sudo chmod -R 777 /var/www/html/chatgpt/logs

# Install dependencies
cd /var/www/html/chatgpt
composer install --no-dev --optimize-autoloader
```

### 5. Configure Environment

```bash
# Copy environment file
cp env.example .env

# Edit environment variables
nano .env
```

Update the following in `.env`:
```env
DB_HOST=localhost
DB_NAME=ai_chat
DB_USER=ai_chat_user
DB_PASS=your_secure_password
RAPIDAPI_KEY=your_rapidapi_key
APP_ENV=production
```

### 6. Import Database Schema

```bash
sudo mysql ai_chat < database/ai_chat.sql
```

### 7. Configure Apache

```bash
sudo nano /etc/httpd/conf.d/chatgpt.conf
```

Add:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/chatgpt
    
    <Directory /var/www/html/chatgpt>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog logs/chatgpt_error.log
    CustomLog logs/chatgpt_access.log combined
</VirtualHost>
```

### 8. Restart Services

```bash
sudo systemctl restart httpd
sudo systemctl restart mysqld
```

## SSL Certificate (Optional but Recommended)

### Using Let's Encrypt

```bash
# Install Certbot
sudo yum install -y certbot python3-certbot-apache

# Get SSL certificate
sudo certbot --apache -d your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## Monitoring and Maintenance

### 1. Check Application Status

```bash
# Check Apache status
sudo systemctl status httpd

# Check MySQL status
sudo systemctl status mysqld

# Check application logs
sudo tail -f /var/www/html/chatgpt/logs/api_interactions.log
```

### 2. Backup Database

```bash
# Create backup script
sudo nano /usr/local/bin/backup-db.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u ai_chat_user -p ai_chat > /backup/ai_chat_$DATE.sql
find /backup -name "*.sql" -mtime +7 -delete
```

### 3. Set up Log Rotation

```bash
sudo nano /etc/logrotate.d/chatgpt
```

```
/var/www/html/chatgpt/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 apache apache
}
```

## Security Checklist

- [ ] Change default MySQL root password
- [ ] Use strong passwords for database users
- [ ] Configure firewall (Security Groups)
- [ ] Enable SSL/HTTPS
- [ ] Keep system packages updated
- [ ] Regular database backups
- [ ] Monitor application logs
- [ ] Use environment variables for sensitive data

## Troubleshooting

### Common Issues

1. **Permission Denied**: Check file permissions
2. **Database Connection Failed**: Verify MySQL credentials
3. **API Errors**: Check RapidAPI key and subscription
4. **500 Internal Server Error**: Check Apache error logs

### Useful Commands

```bash
# Check Apache error logs
sudo tail -f /var/log/httpd/error_log

# Check application logs
sudo tail -f /var/www/html/chatgpt/logs/api_interactions.log

# Test database connection
mysql -u ai_chat_user -p ai_chat

# Check PHP configuration
php -m | grep -E "(curl|mysql|json)"

# Restart all services
sudo systemctl restart httpd mysqld
```

## Performance Optimization

1. **Enable OPcache**:
   ```bash
   sudo yum install php-opcache
   ```

2. **Configure MySQL**:
   ```bash
   sudo nano /etc/my.cnf
   ```

3. **Enable Apache Compression**:
   Already configured in `.htaccess`

4. **Use CDN** for static assets (optional)

## Support

For issues or questions:
1. Check the logs first
2. Verify all services are running
3. Test database connectivity
4. Check API key validity 