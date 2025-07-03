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
sudo yum install -y httpd php php-json php-curl php-mbstring php-xml php-zip git unzip

# Start services
sudo systemctl start httpd
sudo systemctl enable httpd
```

### 2. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Deploy Application

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
RAPIDAPI_KEY=your_rapidapi_key
APP_ENV=production
```

### 4. Configure Apache

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

### 5. Restart Services

```bash
sudo systemctl restart httpd
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



# Check application logs
sudo tail -f /var/www/html/chatgpt/logs/api_interactions.log
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

- [ ] Configure firewall (Security Groups)
- [ ] Enable SSL/HTTPS
- [ ] Keep system packages updated
- [ ] Monitor application logs
- [ ] Use environment variables for sensitive data

## Troubleshooting

### Common Issues

1. **Permission Denied**: Check file permissions
2. **API Errors**: Check RapidAPI key and subscription
3. **500 Internal Server Error**: Check Apache error logs

### Useful Commands

```bash
# Check Apache error logs
sudo tail -f /var/log/httpd/error_log

# Check application logs
sudo tail -f /var/www/html/chatgpt/logs/api_interactions.log

# Check PHP configuration
php -m | grep -E "(curl|json)"

# Restart all services
sudo systemctl restart httpd
```

## Performance Optimization

1. **Enable OPcache**:
   ```bash
   sudo yum install php-opcache
   ```



3. **Enable Apache Compression**:
   Already configured in `.htaccess`

4. **Use CDN** for static assets (optional)

## Support

For issues or questions:
1. Check the logs first
2. Verify all services are running
3. Check API key validity 