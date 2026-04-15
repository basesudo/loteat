# Next.js 部署经验总结

## 概述

本文档总结了将 Next.js 应用（6ammart 外卖平台前端）部署到生产环境的完整过程，包括遇到的问题和解决方案。

## 部署环境

- **服务器**: 149.104.28.122
- **操作系统**: Ubuntu (宝塔面板)
- **Web 服务器**: Nginx
- **前端框架**: Next.js (React)
- **后端 API**: Laravel (loteat.cish.cn)
- **部署日期**: 2026-04-15

## 部署流程

### 1. 准备工作

#### 1.1 服务器环境
- 已安装宝塔面板
- 已安装 Nginx
- 已配置域名解析 (loteat.com → 149.104.28.122)

#### 1.2 SSL 证书申请
为域名申请 Let's Encrypt SSL 证书：

```bash
# 为 loteat.com 申请证书
certbot certonly --webroot -w /www/wwwroot/app.loteat.com -d loteat.com -d www.loteat.com -d app.loteat.com

# 为 loteat.cish.cn 申请证书（API 域名）
certbot certonly --webroot -w /www/wwwroot/admin.loteat.com -d loteat.cish.cn
```

### 2. Nginx 配置

#### 2.1 主域名配置 (loteat.com)

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name loteat.com www.loteat.com app.loteat.com;
    index index.html index.htm;
    
    # SSL 证书配置
    ssl_certificate /etc/letsencrypt/live/loteat.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/loteat.com/privkey.pem;
    ssl_session_timeout 5m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE:ECDH:AES:HIGH:!NULL:!aNULL:!MD5:!ADH:!RC4;
    ssl_prefer_server_ciphers on;
    
    # HTTP 重定向到 HTTPS
    if ($scheme = http) {
        return 301 https://$server_name$request_uri;
    }
    
    # Let's Encrypt 验证路径
    location ^~ /.well-known/acme-challenge/ {
        alias /www/wwwroot/app.loteat.com/.well-known/acme-challenge/;
        allow all;
    }
    
    # 反向代理到 Next.js 应用
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
}
```

#### 2.2 API 域名配置 (loteat.cish.cn)

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name loteat.cish.cn;
    root /www/wwwroot/admin.loteat.com/public;
    index index.php index.html;
    
    # SSL 证书配置
    ssl_certificate /etc/letsencrypt/live/loteat.cish.cn/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/loteat.cish.cn/privkey.pem;
    ssl_session_timeout 5m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE:ECDH:AES:HIGH:!NULL:!aNULL:!MD5:!ADH:!RC4;
    ssl_prefer_server_ciphers on;
    
    # PHP-FPM 处理
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-74.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    # Laravel 重写规则
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### 3. Next.js 应用配置

#### 3.1 next.config.js

```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  swcMinify: true,
  output: 'standalone',  // 关键配置：生成独立部署包
  images: {
    unoptimized: true,    // 禁用图片优化（使用外部图片）
  },
  env: {
    CUSTOM_KEY: 'my-value',
  },
}

module.exports = nextConfig
```

#### 3.2 环境变量 (.env.production)

```env
# API 基础地址
NEXT_PUBLIC_BASE_URL=https://loteat.cish.cn

# 客户端主机地址
NEXT_CLIENT_HOST_URL=https://loteat.com

# 其他配置...
```

### 4. 构建和部署

#### 4.1 本地构建

```bash
# 进入应用目录
cd app

# 安装依赖
npm install

# 构建生产版本
npm run build
```

#### 4.2 部署到服务器

```bash
# 上传文件到服务器（使用 rsync 或 scp）
rsync -avz --exclude='node_modules' --exclude='.next' ./ root@149.104.28.122:/www/wwwroot/app.loteat.com/

# 在服务器上安装依赖
ssh root@149.104.28.122 "cd /www/wwwroot/app.loteat.com && npm install --production"
```

#### 4.3 启动应用

```bash
# 进入应用目录
cd /www/wwwroot/app.loteat.com

# 使用 nohup 后台运行
nohup node server.js > /tmp/app.log 2>&1 &

# 验证服务是否启动
curl -I http://localhost:3000
```

## 遇到的问题及解决方案

### 问题 1: 空白页（最严重的问题）

#### 现象
访问 https://loteat.com 显示空白页，控制台有 404 错误。

#### 原因分析
Next.js 应用部署后没有重启，导致：
1. 服务器上运行的是旧版本的 Next.js 进程（引用旧的构建 ID）
2. 但服务器上的静态文件是新的构建版本
3. 浏览器缓存了旧的 HTML 页面，里面引用了不存在的旧版本静态文件

#### 错误信息
```
GET https://loteat.com/_next/static/chunks/580-23bdbb673d865692.js [404]
GET https://loteat.com/_next/static/hvc6KqShoZj6ZH8Hdkz2P/_buildManifest.js [404]
GET https://loteat.com/_next/static/hvc6KqShoZj6ZH8Hdkz2P/_ssgManifest.js [404]
```

#### 解决方案
**每次部署后必须重启 Next.js 应用：**

```bash
# 查找并结束旧进程
pkill -f 'node server.js'

# 等待进程结束
sleep 2

# 重新启动应用
cd /www/wwwroot/app.loteat.com
nohup node server.js > /tmp/app.log 2>&1 &

# 验证服务
sleep 3
curl -I http://localhost:3000
```

#### 经验教训
- **Next.js 每次构建后都会生成新的构建 ID**
- **部署新版本后必须重启服务**
- **建议在部署脚本中自动包含重启逻辑**

### 问题 2: Mixed Content 警告

#### 现象
控制台显示 Mixed Content 警告：
```
Mixed Content: The page at 'https://loteat.com/' was loaded over HTTPS, 
but requested an insecure element 'http://loteat.cish.cn/...'
```

#### 原因
前端页面通过 HTTPS 加载，但 API 请求使用 HTTP。

#### 解决方案
更新 `.env.production` 文件，确保所有 URL 使用 HTTPS：

```env
# 错误
NEXT_PUBLIC_BASE_URL=http://loteat.cish.cn

# 正确
NEXT_PUBLIC_BASE_URL=https://loteat.cish.cn
```

### 问题 3: API 域名 SSL 证书错误

#### 现象
浏览器提示 API 请求的域名证书不受信任。

#### 原因
API 域名 (loteat.cish.cn) 没有配置 SSL 证书。

#### 解决方案
为 API 域名单独申请 SSL 证书，并在 Nginx 中配置。

### 问题 4: Nginx 配置语法错误

#### 现象
Nginx 重启失败，提示配置错误。

#### 常见错误
```nginx
# 错误：if 语句语法不正确
if ( = http) {
    return 301 https://$server_name$request_uri;
}

# 正确
if ($scheme = http) {
    return 301 https://$server_name$request_uri;
}
```

#### 解决方案
- 使用 `nginx -t` 测试配置语法
- 注意变量前需要加 `$` 符号

### 问题 5: 端口冲突

#### 现象
启动 Next.js 应用时提示端口 3000 被占用。

#### 解决方案
```bash
# 查找占用端口的进程
lsof -i :3000

# 结束进程
pkill -9 node

# 或更换端口启动
PORT=3001 npm start
```

## 最佳实践

### 1. 部署 checklist

- [ ] 本地构建成功
- [ ] 环境变量配置正确（HTTPS URL）
- [ ] 文件上传到服务器
- [ ] 服务器依赖安装完成
- [ ] **重启 Next.js 应用**
- [ ] 验证服务运行状态
- [ ] 浏览器测试（清除缓存）
- [ ] 检查控制台错误

### 2. 自动化部署脚本

创建 `deploy.sh`：

```bash
#!/bin/bash

# 配置
SERVER="root@149.104.28.122"
APP_DIR="/www/wwwroot/app.loteat.com"
LOCAL_DIR="./app"

echo "开始部署..."

# 1. 本地构建
echo "构建应用..."
cd $LOCAL_DIR
npm run build

# 2. 上传文件
echo "上传文件..."
rsync -avz --exclude='node_modules' --exclude='.git' ./ $SERVER:$APP_DIR/

# 3. 服务器端操作
echo "服务器部署..."
ssh $SERVER << EOF
    cd $APP_DIR
    
    # 安装依赖
    npm install --production
    
    # 停止旧进程
    pkill -f 'node server.js' || true
    sleep 2
    
    # 启动新进程
    nohup node server.js > /tmp/app.log 2>&1 &
    sleep 3
    
    # 验证
    curl -s -o /dev/null -w '%{http_code}' http://localhost:3000
EOF

echo "部署完成！"
```

### 3. 监控和日志

```bash
# 查看应用日志
tail -f /tmp/app.log

# 查看 Nginx 错误日志
tail -f /www/server/panel/vhost/nginx/logs/loteat.com.error.log

# 查看 Nginx 访问日志
tail -f /www/server/panel/vhost/nginx/logs/loteat.com.log
```

### 4. 性能优化建议

1. **启用 Gzip 压缩**（Nginx 配置）
2. **配置静态文件缓存**
3. **使用 CDN 加速静态资源**
4. **启用 HTTP/2**
5. **配置浏览器缓存策略**

## 总结

本次部署的主要教训是：**Next.js 应用每次构建后必须重启服务**。这是因为 Next.js 会为每次构建生成唯一的构建 ID，HTML 文件中引用的静态资源路径包含这个构建 ID。如果不重启服务，服务器会继续提供旧版本的 HTML，但静态文件已经是新版本，导致 404 错误和空白页。

## 参考文档

- [Next.js 部署文档](https://nextjs.org/docs/deployment)
- [Nginx 反向代理配置](https://docs.nginx.com/nginx/admin-guide/web-server/reverse-proxy/)
- [Let's Encrypt 证书申请](https://certbot.eff.org/)
