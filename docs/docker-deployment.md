# Loteat 项目 Docker 本地部署指南

## 项目简介

Loteat 是一个基于 Laravel 10 + Next.js 开发的多商户外卖/电商平台（类似 6amMart/StackFood），支持多商户管理、订单系统、配送管理、优惠券、支付集成等功能。

**技术栈：**
- 后端：PHP 8.2 + Laravel 10
- 前端：Next.js 13 + React 18
- 数据库：MySQL 8.0
- 缓存：Redis
- Web 服务器：Nginx

**项目结构：**
```
loteat/
├── service/              ← Laravel 后端（API + 管理后台）
├── customer-web/         ← Next.js 前端（用户端网站）
├── docker/               ← Docker 配置
├── docker-compose.yml    ← Docker 编排（根目录）
└── Dockerfile            ← PHP 镜像构建（根目录）
```

---

## 环境要求

- Docker Desktop (Windows/Mac) 或 Docker Engine (Linux)
- Docker Compose
- Git
- Node.js 16+（用于前端开发）

---

## 快速开始

### 1. 克隆项目

```bash
git clone <项目仓库地址>
cd loteat
```

### 2. 启动后端服务（Docker）

在**根目录**执行：

```bash
docker-compose up -d --build
```

### 3. 等待数据库初始化

首次启动时，MySQL 会自动导入数据库（约需 1-2 分钟）：

```bash
# 查看数据库初始化日志
docker-compose logs -f db
```

当看到 `mysqld: ready for connections` 表示数据库已就绪。

### 4. 创建管理员账号

首次部署需要创建管理员账号：

```bash
# 创建管理员账号（邮箱: admin@admin.com, 密码: password）
docker-compose exec app php artisan tinker --execute="\App\Models\Admin::create(['f_name' => 'Admin', 'l_name' => 'User', 'email' => 'admin@admin.com', 'phone' => '+1234567890', 'password' => bcrypt('password'), 'role_id' => 1]);"
```

### 5. 访问后端服务

- **管理后台**: http://localhost:8080/login/admin
  - 邮箱: `admin@admin.com`
  - 密码: `password`
- **商家后台**: http://localhost:8080/login/vendor
- **API 接口**: http://localhost:8080/api/v1
- **phpMyAdmin**: http://localhost:8081
  - 用户名: `root`
  - 密码: `root123456`

---

## 前端部署（customer-web）

### 1. 安装依赖

```bash
cd customer-web
npm install
# 或 yarn install
```

### 2. 配置环境变量

复制 `.env.development` 为 `.env.local`：

```bash
cp .env.development .env.local
```

编辑 `.env.local`，确保 API 地址正确：
```env
NEXT_PUBLIC_BASE_URL=http://localhost:8080
NEXT_CLIENT_HOST_URL=http://localhost:3000
```

### 3. 启动开发服务器

```bash
npm run dev
# 或 yarn dev
```

访问前端：http://localhost:3000

### 4. 构建生产版本

```bash
npm run build
npm start
```

---

## 服务架构

| 服务 | 容器名 | 端口 | 说明 |
|------|--------|------|------|
| Nginx | loteat_nginx | 8080 | Web 服务器（后端） |
| PHP-FPM | loteat_app | 9000 | PHP 运行环境 |
| MySQL | loteat_db | 3308 | 数据库 |
| Redis | loteat_redis | 6379 | 缓存 |
| phpMyAdmin | loteat_phpmyadmin | 8081 | 数据库管理工具 |
| Next.js | - | 3000 | 前端开发服务器（本地运行） |

---

## 常用命令

### 容器管理

```bash
# 启动容器（根目录）
docker-compose up -d

# 停止容器
docker-compose down

# 重启容器
docker-compose restart

# 查看容器状态
docker-compose ps

# 查看日志
docker-compose logs -f

# 查看特定服务日志
docker-compose logs -f nginx
docker-compose logs -f app
docker-compose logs -f db
```

### 进入容器

```bash
# 进入 PHP 容器
docker-compose exec app bash

# 进入 MySQL 容器
docker-compose exec db bash

# 进入 Nginx 容器
docker-compose exec nginx sh
```

### Laravel 命令

```bash
# 运行 Artisan 命令
docker-compose exec app php artisan <command>

# 示例：清除缓存
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear

# 运行数据库迁移
docker-compose exec app php artisan migrate

# 创建 Storage 软链接
docker-compose exec app php artisan storage:link
```

### Composer 操作

```bash
# 安装依赖
docker-compose exec app composer install

# 更新依赖
docker-compose exec app composer update

# 安装指定包
docker-compose exec app composer require <package-name>
```

---

## 配置文件说明

### docker-compose.yml（根目录）

定义了所有服务的配置：
- **app**: PHP-FPM 服务，处理 PHP 请求
- **nginx**: Web 服务器，处理静态资源和反向代理
- **db**: MySQL 数据库
- **redis**: 缓存服务
- **phpmyadmin**: 数据库管理界面

**注意**：所有服务挂载的代码路径都是 `./service`，指向 Laravel 后端文件夹。

### Dockerfile（根目录）

自定义 PHP 镜像：
- 基础镜像：php:8.2-fpm
- 从 `service/` 文件夹复制代码：`COPY service/ .`
- 安装 Composer 依赖
- 自动创建 storage 软链接

### Nginx 配置

路径：`docker/nginx/conf.d/default.conf`

关键配置：
- 处理 PHP 请求转发到 PHP-FPM
- 处理 `/storage/app/public/` 路径的图片访问
- 处理 `/public/` 路径的静态资源

### 环境变量

**后端**（`service/.env`）：
```env
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=multi_food_db
DB_USERNAME=root
DB_PASSWORD=root123456

CACHE_DRIVER=redis
SESSION_DRIVER=file
REDIS_HOST=redis
```

**前端**（`customer-web/.env.local`）：
```env
NEXT_PUBLIC_BASE_URL=http://localhost:8080
NEXT_CLIENT_HOST_URL=http://localhost:3000
```

---

## 数据库配置

### 初始数据

项目包含两个 SQL 文件：
- `service/installation/database.sql`: 基础数据库结构
- `service/installation/backup/database.sql`: 完整数据库（包含示例数据）

Docker Compose 会自动将 SQL 文件挂载到 MySQL 容器的 `/docker-entrypoint-initdb.d/`，首次启动时自动导入。

### Storage 软链接

项目已配置自动创建 Storage 软链接：
- [Dockerfile](../Dockerfile) 在构建时检查并创建软链接
- [docker-compose.yml](../docker-compose.yml) 在容器启动时检查并创建软链接

如需手动创建，可执行：
```bash
docker-compose exec app php artisan storage:link
```

### 手动导入数据库

如果需要手动导入：

```bash
# 复制 SQL 文件到容器
docker cp service/installation/backup/database.sql loteat_db:/tmp/

# 进入 MySQL 容器执行导入
docker-compose exec db bash
mysql -u root -p multi_food_db < /tmp/database.sql
```

---

## 故障排查

### 端口冲突

如果 3306 端口被占用，修改 `docker-compose.yml`：

```yaml
db:
  ports:
    - "3308:3306"  # 改为其他端口
```

### 权限问题

如果遇到文件权限问题：

```bash
# 在 PHP 容器中设置权限
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/storage
```

### 清除所有缓存

```bash
# 清除所有缓存
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear

# 重启容器
docker-compose restart
```

### 重置管理员密码

如果忘记管理员密码，可以使用以下命令重置：

```bash
# 重置密码为 password
docker-compose exec app php artisan tinker --execute="\App\Models\Admin::where('email', 'admin@admin.com')->update(['password' => bcrypt('password')]);"
```

### 前端无法连接后端

检查 `customer-web/.env.local` 中的 `NEXT_PUBLIC_BASE_URL` 是否正确指向后端地址。

---

## 生产环境部署注意事项

1. **修改默认密码**：生产环境必须修改 MySQL 和 Redis 的默认密码
2. **关闭调试模式**：后端设置 `APP_DEBUG=false`，前端设置 `NODE_ENV=production`
3. **使用 HTTPS**：配置 SSL 证书
4. **定期备份**：设置数据库自动备份
5. **资源优化**：配置 Nginx 和 PHP-FPM 的性能参数
6. **前端构建**：使用 `npm run build` 构建优化后的生产版本

---

## 参考文档

- [Laravel 官方文档](https://laravel.com/docs/10.x)
- [Next.js 官方文档](https://nextjs.org/docs)
- [Docker 官方文档](https://docs.docker.com/)
- [Docker Compose 官方文档](https://docs.docker.com/compose/)
