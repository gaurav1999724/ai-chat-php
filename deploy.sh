#!/bin/bash

# AWS Deployment Script for AI Chat Application
# Run this script on your AWS EC2 instance

echo "🚀 Starting AI Chat Application Deployment..."

# Update system
echo "📦 Updating system packages..."
sudo yum update -y

# Install required packages
echo "🔧 Installing LAMP stack..."
sudo yum install -y httpd php php-json php-curl php-mbstring php-xml php-zip git unzip

# Start and enable services
echo "⚡ Starting services..."
sudo systemctl start httpd
sudo systemctl enable httpd

# Install Composer
echo "📦 Installing Composer..."
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi



# Set up application directory
echo "📁 Setting up application..."
sudo mkdir -p /var/www/html/chatgpt
sudo chown -R apache:apache /var/www/html/chatgpt

# Install dependencies
echo "📦 Installing PHP dependencies..."
cd /var/www/html/chatgpt
composer install --no-dev --optimize-autoloader

# Set permissions
echo "🔐 Setting permissions..."
sudo chown -R apache:apache /var/www/html/chatgpt
sudo chmod -R 755 /var/www/html/chatgpt
sudo chmod -R 777 /var/www/html/chatgpt/logs

# Configure Apache
echo "🌐 Configuring Apache..."
sudo tee /etc/httpd/conf.d/chatgpt.conf > /dev/null <<EOF
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
EOF

# Restart Apache
sudo systemctl restart httpd

echo "✅ Deployment completed successfully!"
echo "🌐 Your application should be available at: http://your-instance-ip"
echo "📝 Don't forget to:"
echo "   1. Update your domain name in Apache config"
echo "   2. Set up SSL certificate for HTTPS"
echo "   3. Configure your firewall rules"
echo "   4. Update your API keys in production" 