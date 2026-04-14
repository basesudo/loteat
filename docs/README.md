# Loteat 项目文档

欢迎来到 Loteat 项目文档中心！这里包含了项目的完整部署指南、问题排查手册和架构说明。

## 文档目录

### 1. [Docker 部署指南](./docker-deployment.md)
- 环境要求
- 快速开始
- 服务架构
- 常用命令
- 配置文件说明

### 2. [问题排查指南](./troubleshooting.md)
- 样式文件 404 问题
- 图片无法显示问题
- 数据库表不存在问题
- 端口冲突问题
- 权限问题
- 缓存问题

### 3. [项目结构说明](./project-structure.md)
- 技术栈介绍
- 目录结构
- 核心模块说明
- 数据库结构
- 路由结构
- 静态资源说明

## 快速链接

- **项目首页**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **Laravel 文档**: https://laravel.com/docs/10.x
- **Docker 文档**: https://docs.docker.com/

## 项目简介

Loteat 是一个基于 Laravel 10 开发的多商户外卖/电商平台，支持：

- 多商户管理
- 订单系统
- 配送管理
- 优惠券系统
- 支付集成（PayPal, Stripe, RazorPay 等）
- 多模块支持（食品、杂货、快递等）

## 技术栈

- **后端**: PHP 8.2 + Laravel 10
- **数据库**: MySQL 8.0
- **缓存**: Redis
- **Web 服务器**: Nginx
- **容器化**: Docker + Docker Compose

## 快速开始

```bash
# 1. 克隆项目
git clone <项目仓库地址>
cd loteat

# 2. 启动 Docker 容器
docker-compose up -d --build

# 3. 创建 Storage 软链接
docker-compose exec app php artisan storage:link

# 4. 访问项目
# 打开浏览器访问 http://localhost:8080
```

## 常见问题

### 如何重启服务？

```bash
docker-compose restart
```

### 如何查看日志？

```bash
# 查看所有日志
docker-compose logs -f

# 查看特定服务日志
docker-compose logs -f nginx
docker-compose logs -f app
docker-compose logs -f db
```

### 如何进入容器？

```bash
# 进入 PHP 容器
docker-compose exec app bash

# 进入 MySQL 容器
docker-compose exec db bash

# 进入 Nginx 容器
docker-compose exec nginx sh
```

### 图片无法显示怎么办？

1. 检查 storage 软链接：
   ```bash
   docker-compose exec app php artisan storage:link
   ```

2. 检查 Nginx 配置是否正确

3. 查看更多排查方法：[问题排查指南](./troubleshooting.md)

## 贡献指南

欢迎提交 Issue 和 Pull Request 来改进项目文档。

## 许可证

[MIT License](../LICENSE)
