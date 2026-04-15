---
name: "nextjs-app-deployment"
description: "Deploy Next.js frontend app to remote server via SSH, configure Nginx reverse proxy and PM2 process management. Invoke when user needs to deploy Next.js application to production server."
---

# Next.js App Deployment Skill

This skill guides you through deploying a Next.js frontend application to a remote server with complete configuration including Nginx reverse proxy and PM2 process management.

## Prerequisites

- Remote server with SSH access
- Domain name pointing to the server (or IP address)
- Backend API already deployed and accessible
- Node.js 18+ installed on the remote server

## Deployment Steps

### 1. Environment Configuration

Update `.env.production` with production values:

```env
NEXT_PUBLIC_GOOGLE_MAP_KEY=your_google_map_key
NEXT_PUBLIC_BASE_URL=http://your-backend-domain.com
NEXT_CLIENT_HOST_URL=http://your-frontend-domain.com
NEXT_PUBLIC_SITE_VERSION=2.3.1
```

**Important**: Use actual domain names or IP addresses that are already resolved and accessible.

### 2. Build Configuration

Ensure `next.config.js` uses standalone output:

```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  output: 'standalone',
  images: {
    unoptimized: true,
  },
}

module.exports = nextConfig
```

### 3. Build the Application

```bash
npm run build
```

### 4. Prepare Static Assets

Copy static folder to standalone directory:

```bash
# Windows
robocopy .next\static .next\standalone\.next\static /E

# Linux/Mac
cp -r .next/static .next/standalone/.next/
```

**Critical**: The static folder must be included for CSS and JS files to load correctly.

### 5. Package and Upload

Create deployment archive:

```bash
# Windows
Compress-Archive -Path ".next\standalone\*" -DestinationPath "app-standalone.zip" -Force

# Linux/Mac
zip -r app-standalone.zip .next/standalone/
```

Upload to remote server:

```bash
scp app-standalone.zip root@your-server-ip:/www/wwwroot/
```

### 6. Deploy on Remote Server

SSH into the server and deploy:

```bash
ssh root@your-server-ip

cd /www/wwwroot
rm -rf /www/wwwroot/your-app-domain.com
mkdir -p /www/wwwroot/your-app-domain.com
unzip -o app-standalone.zip -d /www/wwwroot/your-app-domain.com/
rm app-standalone.zip
```

### 7. Install Node.js and PM2 (if not installed)

```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs
npm install -g pm2
```

### 8. Start Application with PM2

```bash
cd /www/wwwroot/your-app-domain.com
pm2 delete your-app-name 2>/dev/null
PORT=3000 pm2 start server.js --name 'your-app-name'
pm2 save
```

### 9. Configure Nginx

Create/update Nginx configuration:

```nginx
server
{
    listen 80;
    server_name your-domain.com;
    index index.html index.htm;

    # Allow _next/static path (CRITICAL for Next.js)
    location ^~ /_next/static/ {
        alias /www/wwwroot/your-app-domain.com/.next/static/;
        expires 365d;
        add_header Cache-Control "public, immutable";
    }

    # Reverse proxy to Next.js app
    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    access_log /www/wwwlogs/your-domain.com.log;
    error_log /www/wwwlogs/your-domain.com.error.log;
}
```

**Important**: The `_next/static` location must be defined BEFORE the general location block and use `^~` modifier to ensure it takes precedence.

### 10. Reload Nginx

```bash
nginx -t
nginx -s reload
```

### 11. Verify Deployment

Check application status:

```bash
pm2 status
curl -s -o /dev/null -w '%{http_code}' http://localhost:3000
```

## Troubleshooting

### Blank Page / 404 Errors for Static Files

**Problem**: CSS and JS files return 404, or page shows blank/white screen

**Root Cause**: Next.js generates a unique **build ID** for each build. The HTML file references static assets using this build ID in the path (e.g., `/_next/static/BUILD_ID/...`). If the Next.js application is not restarted after deployment, the server continues to serve old HTML that references the previous build's static files, which no longer exist.

**Example Error**:
```
GET https://loteat.com/_next/static/chunks/580-23bdbb673d865692.js [404]
GET https://loteat.com/_next/static/hvc6KqShoZj6ZH8Hdkz2P/_buildManifest.js [404]
```

**Solution**: 
1. **CRITICAL**: Always restart the Next.js application after each deployment
   ```bash
   # Stop old process
   pm2 delete your-app-name 2>/dev/null
   # Or: pkill -f 'node server.js'
   
   # Wait for process to fully stop
   sleep 2
   
   # Start new process
   cd /www/wwwroot/your-app-domain.com
   PORT=3000 pm2 start server.js --name 'your-app-name'
   ```
2. Ensure static folder is copied to standalone directory
3. Check Nginx configuration includes `_next/static` location
4. Verify the path in alias directive matches actual directory structure
5. Clear browser cache or test in incognito mode to verify fix

**Prevention**: Include automatic restart in your deployment script:
```bash
#!/bin/bash
# deploy.sh

# Build and upload...

# Restart application (MANDATORY)
pm2 delete your-app-name 2>/dev/null || true
sleep 2
PORT=3000 pm2 start server.js --name 'your-app-name'
pm2 save

echo "Deployment complete! Application restarted."
```

### Application Not Starting

**Problem**: PM2 shows errored status

**Solution**:
1. Check logs: `pm2 logs your-app-name`
2. Verify Node.js is installed: `node --version`
3. Ensure port 3000 is not in use: `lsof -i :3000`

### API Connection Errors

**Problem**: Frontend cannot connect to backend API

**Solution**:
1. Verify `NEXT_PUBLIC_BASE_URL` is set correctly
2. **IMPORTANT**: Use HTTPS URLs in production to avoid Mixed Content errors
   ```env
   # Wrong - causes Mixed Content error
   NEXT_PUBLIC_BASE_URL=http://api.your-domain.com
   
   # Correct
   NEXT_PUBLIC_BASE_URL=https://api.your-domain.com
   ```
3. Check backend API is accessible from the server
4. Test API endpoint: `curl https://your-backend-domain.com/api/endpoint`
5. Ensure API domain has valid SSL certificate configured

### Mixed Content Warnings

**Problem**: Browser console shows "Mixed Content" warnings
```
Mixed Content: The page at 'https://your-domain.com/' was loaded over HTTPS, 
but requested an insecure element 'http://api.your-domain.com/...'
```

**Solution**:
1. Update all API URLs to use HTTPS in `.env.production`
2. Ensure backend API supports HTTPS
3. Configure SSL certificate for API subdomain if needed

## Common Commands

```bash
# View logs
pm2 logs your-app-name

# Restart application
pm2 restart your-app-name

# Stop application
pm2 stop your-app-name

# Monitor resources
pm2 monit

# Check Nginx error logs
tail -f /www/wwwlogs/your-domain.com.error.log
```

## Security Considerations

1. **Firewall**: Ensure only necessary ports are open (80, 443, 22)
2. **SSL**: Configure HTTPS using Let's Encrypt or other SSL provider
3. **Environment Variables**: Never commit sensitive data to version control
4. **File Permissions**: Set appropriate permissions on application files

## Example Complete Deployment

```bash
# Local machine
cd /path/to/nextjs-app
npm run build
robocopy .next\static .next\standalone\.next\static /E
Compress-Archive -Path ".next\standalone\*" -DestinationPath "app-standalone.zip" -Force
scp app-standalone.zip root@149.104.28.122:/www/wwwroot/

# Remote server
ssh root@149.104.28.122
cd /www/wwwroot
rm -rf /www/wwwroot/app.loteat.com
mkdir -p /www/wwwroot/app.loteat.com
unzip -o app-standalone.zip -d /www/wwwroot/app.loteat.com/
rm app-standalone.zip
cd /www/wwwroot/app.loteat.com
pm2 delete app-loteat 2>/dev/null
PORT=3000 pm2 start server.js --name 'app-loteat'
```

## Deployment Checklist

Before considering deployment complete, verify:

- [ ] Application builds successfully locally (`npm run build`)
- [ ] Environment variables use HTTPS URLs (`NEXT_PUBLIC_BASE_URL=https://...`)
- [ ] Static assets copied to standalone directory
- [ ] Files uploaded to server successfully
- [ ] **Application restarted after deployment** (CRITICAL)
- [ ] PM2 process running and healthy (`pm2 status`)
- [ ] Nginx configuration reloaded (`nginx -s reload`)
- [ ] Application responds on localhost (`curl http://localhost:3000`)
- [ ] Domain accessible via HTTPS (test in browser)
- [ ] No 404 errors for static assets in browser console
- [ ] API connections working (no Mixed Content errors)

## Key Lessons Learned

### 1. Always Restart After Deployment
The most critical step that is easily forgotten: **Next.js MUST be restarted after each deployment** because each build generates a new unique build ID. The HTML references static assets using this build ID, so old processes serve HTML that points to non-existent static files.

### 2. HTTPS Everywhere in Production
Always use HTTPS URLs for all external resources (APIs, images, CDN) to avoid Mixed Content warnings and blocked requests.

### 3. Verify Static File Serving
Ensure Nginx properly serves `_next/static` files with correct cache headers for optimal performance.

### 4. Test in Incognito Mode
Browser caching can mask deployment issues. Always test in incognito/private mode or clear cache after deployment.

## Notes

- Always test the application locally before deploying
- Use PM2 for process management to ensure application restarts on crash
- Configure log rotation to prevent disk space issues
- Set up monitoring and alerting for production deployments
- Document your deployment process for team members
