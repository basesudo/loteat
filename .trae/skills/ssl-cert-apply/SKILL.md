---
name: "ssl-cert-apply"
description: "Apply for Let's Encrypt SSL certificates for domains using Certbot. Invoke when user needs to configure HTTPS for a website, add SSL certificate to a domain, or secure a site with SSL/TLS."
---

# SSL Certificate Application Skill

This skill guides you through applying for and configuring Let's Encrypt SSL certificates using Certbot on Linux servers with Nginx.

## Prerequisites

- Linux server with root/sudo access
- Domain name already pointing to the server (DNS A record configured)
- Nginx or Apache web server installed
- Port 80 accessible (for ACME challenge validation)

## Methods

### Method 1: Webroot Mode (Recommended for existing sites)

Use this method when the website is already running and serving content.

#### Step 1: Verify Domain Resolution

```bash
# Check if domain points to this server
dig +short your-domain.com
# Should return your server IP address

# Or test locally
curl -I http://your-domain.com
```

#### Step 2: Ensure Webroot Directory Exists

```bash
# Create webroot directory if not exists
mkdir -p /www/wwwroot/your-domain.com/.well-known/acme-challenge

# Set proper permissions
chown -R www-data:www-data /www/wwwroot/your-domain.com/.well-known
chmod -R 755 /www/wwwroot/your-domain.com/.well-known
```

#### Step 3: Configure Nginx for ACME Challenge

Add this location block to your Nginx configuration (before other location blocks):

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    
    # ACME challenge location - MUST be before other locations
    location ^~ /.well-known/acme-challenge/ {
        alias /www/wwwroot/your-domain.com/.well-known/acme-challenge/;
        allow all;
    }
    
    # Other configurations...
}
```

Reload Nginx:
```bash
nginx -t && nginx -s reload
```

#### Step 4: Apply for Certificate

```bash
# Single domain
certbot certonly --webroot \
  -w /www/wwwroot/your-domain.com \
  -d your-domain.com

# Multiple domains (recommended)
certbot certonly --webroot \
  -w /www/wwwroot/your-domain.com \
  -d your-domain.com \
  -d www.your-domain.com \
  -d app.your-domain.com
```

#### Step 5: Configure Nginx with SSL

Update Nginx configuration to use the new certificate:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    
    # ACME challenge location (keep for renewal)
    location ^~ /.well-known/acme-challenge/ {
        alias /www/wwwroot/your-domain.com/.well-known/acme-challenge/;
        allow all;
    }
    
    # Redirect HTTP to HTTPS
    location / {
        return 301 https://$server_name$request_uri;
    }
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    # SSL Certificate paths
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    
    # SSL Configuration
    ssl_session_timeout 5m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE:ECDH:AES:HIGH:!NULL:!aNULL:!MD5:!ADH:!RC4;
    ssl_prefer_server_ciphers on;
    
    # Your application configuration...
    location / {
        proxy_pass http://127.0.0.1:3000;
        # ... other proxy settings
    }
}
```

Reload Nginx:
```bash
nginx -t && nginx -s reload
```

### Method 2: Nginx Plugin Mode (Automated)

Use this method for simpler, more automated certificate installation.

```bash
# Install certbot nginx plugin (if not installed)
apt-get install python3-certbot-nginx

# Apply and automatically configure Nginx
certbot --nginx \
  -d your-domain.com \
  -d www.your-domain.com

# Follow interactive prompts
```

This method automatically:
- Validates domain ownership
- Obtains certificate
- Configures Nginx
- Sets up HTTP to HTTPS redirect

### Method 3: Standalone Mode (No web server running)

Use this method when no web server is running on port 80.

```bash
# Stop any service using port 80
systemctl stop nginx

# Apply for certificate
certbot certonly --standalone \
  -d your-domain.com \
  -d www.your-domain.com

# Start web server again
systemctl start nginx
```

## Certificate Locations

After successful issuance, certificates are stored at:

```
/etc/letsencrypt/live/your-domain.com/
├── cert.pem          # Server certificate
├── chain.pem         # Intermediate certificate
├── fullchain.pem     # Server + Intermediate (use in Nginx)
└── privkey.pem       # Private key
```

## Automatic Renewal

Let's Encrypt certificates expire every 90 days. Certbot automatically sets up renewal:

```bash
# Test automatic renewal
certbot renew --dry-run

# Manual renewal (if needed)
certbot renew

# Check renewal timer
systemctl status certbot.timer
```

## Troubleshooting

### "Failed to connect to port 80"

**Cause**: Domain not resolving to this server or firewall blocking port 80.

**Solution**:
```bash
# Check DNS resolution
dig +short your-domain.com

# Check if port 80 is open
nc -zv your-domain.com 80

# Check firewall
ufw status
iptables -L -n | grep 80
```

### "Unauthorized" or "404" during validation

**Cause**: ACME challenge files not accessible.

**Solution**:
```bash
# Create test file
echo "test" > /www/wwwroot/your-domain.com/.well-known/acme-challenge/test

# Test access
curl http://your-domain.com/.well-known/acme-challenge/test

# Check Nginx error logs
tail -f /var/log/nginx/error.log
```

### "Too many failed authorizations"

**Cause**: Rate limiting by Let's Encrypt (5 failed attempts per hour).

**Solution**: Wait 1 hour before retrying.

### Certificate Not Found in Nginx

**Cause**: Wrong path in Nginx configuration.

**Solution**:
```bash
# Verify certificate exists
ls -la /etc/letsencrypt/live/your-domain.com/

# Test Nginx configuration
nginx -t

# Check Nginx error log
tail -f /var/log/nginx/error.log
```

## Security Best Practices

1. **Use fullchain.pem**: Always use `fullchain.pem` (not just `cert.pem`) in Nginx to include intermediate certificates
2. **Protect private key**: Ensure `privkey.pem` has restricted permissions (600)
3. **Enable HSTS**: Consider adding HTTP Strict Transport Security headers
4. **Regular renewal checks**: Monitor certificate expiration (Certbot auto-renews, but verify it's working)

## Quick Reference Commands

```bash
# Check certificate info
openssl x509 -in /etc/letsencrypt/live/your-domain.com/fullchain.pem -text -noout

# Check certificate expiration
openssl x509 -in /etc/letsencrypt/live/your-domain.com/fullchain.pem -dates -noout

# List all certificates
certbot certificates

# Revoke certificate (if needed)
certbot revoke --cert-name your-domain.com

# Delete certificate
certbot delete --cert-name your-domain.com
```

## Example: Complete SSL Setup

```bash
#!/bin/bash
# ssl-setup.sh

DOMAIN="your-domain.com"
WEBROOT="/www/wwwroot/$DOMAIN"

# 1. Create webroot
mkdir -p $WEBROOT/.well-known/acme-challenge

# 2. Apply for certificate
certbot certonly --webroot \
  -w $WEBROOT \
  -d $DOMAIN \
  -d www.$DOMAIN

# 3. Verify certificate
if [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    echo "Certificate obtained successfully!"
    echo "Certificate path: /etc/letsencrypt/live/$DOMAIN/"
else
    echo "Failed to obtain certificate"
    exit 1
fi

# 4. Test renewal
certbot renew --dry-run

echo "SSL setup complete. Remember to configure Nginx to use the certificate."
```

## Checklist

- [ ] Domain DNS A record points to server IP
- [ ] Port 80 is accessible (not blocked by firewall)
- [ ] Web server can serve files from webroot
- [ ] Nginx configuration includes ACME challenge location
- [ ] Certificate successfully obtained
- [ ] Nginx configured with SSL certificate paths
- [ ] HTTP to HTTPS redirect configured
- [ ] Auto-renewal tested (`certbot renew --dry-run`)
- [ ] Website accessible via HTTPS
