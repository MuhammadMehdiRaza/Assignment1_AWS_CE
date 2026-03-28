#!/bin/bash
# UniEvent Infrastructure Setup Script
# Author: Muhammad Mehdi Raza (2023466)
# Description: Automates Apache/PHP installation and configures server permissions.

# 1. Update system and install web tier components
sudo yum update -y
sudo yum install -y httpd php

# 2. Configure PHP for Event Media Requirements
# Increases limits to 10MB to allow high-quality event posters/images
sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /etc/php.ini
sudo sed -i 's/post_max_size = 8M/post_max_size = 12M/' /etc/php.ini

# 3. Initialize Web Server
sudo systemctl start httpd
sudo systemctl enable httpd

# 4. Set Directory Permissions (Requirement: Security Awareness)
# Ensures the web server (Apache) has the rights to move student-uploaded files to S3
sudo usermod -a -G apache ec2-user
sudo chown -R ec2-user:apache /var/www
sudo chmod 2775 /var/www
find /var/www -type d -exec sudo chmod 2775 {} \;
find /var/www -type f -exec sudo chmod 0664 {} \;

echo "UniEvent Infrastructure Setup: SUCCESSFUL"