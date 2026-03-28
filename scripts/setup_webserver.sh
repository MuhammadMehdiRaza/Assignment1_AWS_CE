sudo yum update -y
sudo yum install -y httpd php
sudo systemctl start httpd
sudo systemctl enable httpd
echo "<h1>UniEvent Portal - Server 1 (Zone 1c)</h1>" | sudo tee /var/www/html/index.html