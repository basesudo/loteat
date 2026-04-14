# Loteat 项目问题排查指南

本文档记录了在 Docker 本地部署过程中遇到的常见问题及解决方案。

---

## 问题一：管理后台登录失败（Credentials does not match）

### 现象
访问管理后台登录页面，输入正确的账号密码后报错：
```
Error: 0
Credentials does not match.
```

### 原因
1. 管理员账号未创建
2. 密码哈希值不正确或被截断
3. 使用了错误的登录入口

### 解决方案

#### 1. 创建管理员账号

```bash
# 创建管理员账号（邮箱: admin@admin.com, 密码: password）
docker-compose exec app php artisan tinker --execute="\App\Models\Admin::create(['f_name' => 'Admin', 'l_name' => 'User', 'email' => 'admin@admin.com', 'phone' => '+1234567890', 'password' => bcrypt('password'), 'role_id' => 1]);"
```

#### 2. 重置管理员密码

如果密码不正确，使用 Laravel 的 `bcrypt()` 函数重置：

```bash
# 重置密码为 password
docker-compose exec app php artisan tinker --execute="\App\Models\Admin::where('email', 'admin@admin.com')->update(['password' => bcrypt('password')]);"
```

**注意**：不要使用 MySQL 命令行直接插入密码，因为 `$` 符号会被解释为变量导致密码被截断。

#### 3. 确认登录地址

| 角色 | 登录地址 | 说明 |
|------|---------|------|
| 管理员 | `/login/admin` | 后台管理 |
| 商家 | `/login/vendor` | 商家后台 |
| 配送员 | `/login/delivery-man` | 配送员端 |
| 顾客 | `/login/customer` | 用户端 |

完整地址示例：`http://localhost:8080/login/admin`

---

## 问题二：样式文件 404

### 现象
页面加载后没有样式，浏览器控制台显示 CSS/JS 文件 404 错误。

### 原因
项目模板中使用了 `asset('public/assets/...')` 这样的路径，但 Laravel 的 `asset()` 函数已经指向 public 目录，导致实际请求路径变成了 `/public/public/assets/...`。

### 解决方案

在 Nginx 配置中添加重写规则：

```nginx
# 修复 public/public 双重路径问题
location ^~ /public/ {
    alias /var/www/html/public/;
    expires 1M;
    add_header Cache-Control "public, immutable";
}
```

这样 `/public/assets/...` 会被正确映射到 `/var/www/html/public/assets/...`。

---

## 问题三：图片无法显示（404）

### 现象
页面上传图片或示例图片无法显示，浏览器控制台显示 404 错误。

### 原因分析

Laravel 项目中上传的文件存储在 `storage/app/public` 目录下，需要通过以下方式访问：

1. **Storage 软链接**：`public/storage` 应该链接到 `storage/app/public`
2. **Nginx 路径映射**：需要正确配置 `/storage/app/public/` 路径

#### 根本原因

Nginx 配置中 `location` 指令的匹配顺序问题：
- `/storage/` 规则优先匹配了所有以 `/storage/` 开头的请求
- 导致 `/storage/app/public/...` 的请求被错误映射到 `/var/www/html/public/storage/...`
- 而实际图片文件在 `/var/www/html/storage/app/public/...`

### 解决方案

#### 1. 创建 Storage 软链接

```bash
docker-compose exec app php artisan storage:link
```

如果 `public/storage` 已存在但不是软链接：

```bash
docker-compose exec app rm -rf /var/www/html/public/storage
docker-compose exec app php artisan storage:link
```

#### 2. 配置 Nginx 路径映射

使用 `^~` 修饰符确保更精确的匹配规则优先：

```nginx
# 处理 storage/app/public 路径（必须放在 /storage/ 之前，使用 ^~ 优先匹配）
location ^~ /storage/app/public/ {
    alias /var/www/html/storage/app/public/;
    expires 1M;
    add_header Cache-Control "public, immutable";
}

# 处理 storage 路径（软链接）
location ^~ /storage/ {
    alias /var/www/html/public/storage/;
    expires 1M;
    add_header Cache-Control "public, immutable";
}
```

`^~` 修饰符表示如果该 location 匹配成功，则不再进行正则匹配。

---

## 问题四：数据库表不存在

### 现象
页面报错：`SQLSTATE[42S02]: Base table or view not found: 1146 Table 'multi_food_db.data_settings' doesn't exist`

### 原因
数据库初始化时 SQL 文件未正确导入，或导入的 SQL 文件不完整。

### 解决方案

#### 1. 检查数据库初始化

查看数据库日志确认 SQL 文件是否被导入：

```bash
docker-compose logs db
```

#### 2. 使用完整的备份文件

项目有两个 SQL 文件：
- `installation/database.sql`: 基础结构
- `installation/backup/database.sql`: 完整数据（推荐使用）

修改 `docker-compose.yml`：

```yaml
db:
  volumes:
    - ./installation/backup/database.sql:/docker-entrypoint-initdb.d/01-database.sql
```

#### 3. 重新初始化数据库

```bash
# 停止并删除容器和数据卷
docker-compose down -v

# 重新启动
docker-compose up -d
```

#### 4. 手动导入数据库

```bash
# 复制 SQL 文件到容器
docker cp installation/backup/database.sql loteat_db:/tmp/

# 进入 MySQL 容器
docker-compose exec db bash

# 导入数据
mysql -u root -p multi_food_db < /tmp/database.sql
```

---

## 问题五：端口冲突

### 现象
启动容器时报错：`Bind for 0.0.0.0:3306 failed: port is already allocated`

### 原因
本地 3306 端口已被其他 MySQL 实例占用。

### 解决方案

修改 `docker-compose.yml` 使用其他端口：

```yaml
db:
  ports:
    - "3308:3306"  # 本地 3308 映射到容器 3306
```

同时更新 `.env` 文件：

```env
DB_PORT=3308
```

---

## 问题六：权限问题

### 现象
上传文件失败，或日志文件无法写入。

### 原因
Docker 容器内的文件权限与宿主机不一致。

### 解决方案

```bash
# 进入 PHP 容器设置权限
docker-compose exec app bash

# 设置目录权限
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache
```

或在 Dockerfile 中设置：

```dockerfile
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache
```

---

## 问题七：缓存问题

### 现象
修改代码后页面没有更新，或配置更改未生效。

### 解决方案

```bash
# 清除 Laravel 缓存
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear

# 重启容器
docker-compose restart
```

---

## 调试技巧

### 查看 Nginx 错误日志

```bash
docker-compose logs nginx
```

### 查看 PHP 错误日志

```bash
docker-compose exec app cat /var/log/php_errors.log
```

### 测试图片路径

```bash
# 测试图片是否可访问
curl -I http://localhost:8080/storage/app/public/business/2023-08-16-64dca5f544de1.png
```

### 检查 Storage 软链接

```bash
# 进入 PHP 容器
docker-compose exec app ls -la /var/www/html/public/storage

# 应该显示类似：
# lrwxrwxrwx 1 root root 25 Apr 14 10:15 /var/www/html/public/storage -> /var/www/html/storage/app/public
```

### 检查 Nginx 配置

```bash
# 测试 Nginx 配置语法
docker-compose exec nginx nginx -t

# 查看当前配置
docker-compose exec nginx cat /etc/nginx/conf.d/default.conf
```

---

## 常用诊断命令

```bash
# 查看所有容器状态
docker-compose ps

# 查看容器资源使用
docker stats

# 进入容器内部
docker-compose exec <service> bash

# 查看容器日志
docker-compose logs -f <service>

# 重启单个服务
docker-compose restart <service>

# 重建单个服务
docker-compose up -d --build <service>
```

---

## 参考资源

- [Laravel Storage 文档](https://laravel.com/docs/10.x/filesystem)
- [Nginx Location 匹配规则](http://nginx.org/en/docs/http/ngx_http_core_module.html#location)
- [Docker Compose 文档](https://docs.docker.com/compose/)
