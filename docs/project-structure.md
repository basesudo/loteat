# Loteat 项目结构说明

## 项目概述

Loteat 是一个基于 **Laravel 10 + Next.js** 开发的多商户外卖/电商平台，采用前后端分离架构设计，支持食品配送、杂货配送、快递服务等多种业务模式。

---

## 技术栈

### 后端 (service/)
- **框架**: Laravel 10 (PHP 8.2+)
- **模板**: Blade (管理后台)
- **API**: RESTful API (供前端和移动端调用)
- **数据库**: MySQL 8.0
- **缓存**: Redis
- **Web 服务器**: Nginx

### 前端 (customer-web/)
- **框架**: Next.js 13 (React 18)
- **UI 库**: Material-UI (MUI) v5
- **状态管理**: Redux Toolkit
- **数据获取**: React Query + Axios
- **地图**: Google Maps API
- **国际化**: i18next (支持 20+ 语言)

### 基础设施
- **容器化**: Docker + Docker Compose
- **版本控制**: Git

---

## 目录结构

```
loteat/
├── service/                          # Laravel 后端（API + 管理后台）
│   ├── app/                          # 应用程序核心代码
│   │   ├── Console/                  # Artisan 命令
│   │   ├── Exceptions/               # 异常处理
│   │   ├── Http/                     # HTTP 层
│   │   │   ├── Controllers/          # 控制器
│   │   │   │   ├── Admin/            # 后台管理控制器
│   │   │   │   ├── Api/              # API 控制器
│   │   │   │   └── Web/              # 前端控制器
│   │   │   └── Middleware/           # 中间件
│   │   ├── Models/                   # 数据模型
│   │   ├── Services/                 # 业务逻辑服务
│   │   └── Traits/                   # 复用 Trait
│   ├── bootstrap/                    # 应用启动文件
│   ├── config/                       # 配置文件
│   ├── database/                     # 数据库相关
│   │   ├── factories/                # 模型工厂
│   │   ├── migrations/               # 数据库迁移
│   │   └── seeders/                  # 数据填充
│   ├── docker/                       # Docker 配置（已移到根目录）
│   ├── docs/                         # 项目文档
│   ├── installation/                 # 安装相关文件
│   │   ├── database.sql              # 基础数据库
│   │   └── backup/
│   │       └── database.sql          # 完整数据库备份
│   ├── Modules/                      # 业务模块（预留）
│   ├── public/                       # Web 入口目录
│   │   ├── index.php                 # 入口文件
│   │   ├── storage/                  # Storage 软链接目录
│   │   └── assets/                   # 静态资源
│   ├── resources/                    # 资源文件
│   │   ├── views/                    # Blade 视图模板
│   │   │   ├── layouts/              # 布局模板
│   │   │   ├── admin-views/          # 后台视图
│   │   │   ├── vendor-views/         # 商家视图
│   │   │   └── landing/              # 落地页视图
│   │   └── lang/                     # 语言文件
│   ├── routes/                       # 路由定义
│   │   ├── web.php                   # Web 路由
│   │   ├── api/                      # API 路由
│   │   │   ├── v1/
│   │   │   └── v2/
│   │   ├── admin.php                 # 后台路由
│   │   └── rest_api/                 # REST API（移动端）
│   ├── storage/                      # 存储目录
│   │   ├── app/                      # 应用存储
│   │   │   └── public/               # 公开文件（图片等）
│   │   ├── framework/                # 框架存储
│   │   └── logs/                     # 日志文件
│   ├── tests/                        # 测试文件
│   ├── .env                          # 环境变量
│   ├── .env.example                  # 环境变量示例
│   ├── composer.json                 # Composer 依赖
│   ├── artisan                       # Artisan 命令入口
│   └── README.md                     # 后端说明
│
├── customer-web/                     # Next.js 前端（用户端）
│   ├── pages/                        # Next.js 页面
│   │   ├── api/                      # API 路由
│   │   ├── auth/                     # 认证页面
│   │   │   ├── sign-in/              # 登录
│   │   │   └── sign-up/              # 注册
│   │   ├── home/                     # 首页
│   │   ├── store/                    # 商家店铺
│   │   ├── product/                  # 商品详情
│   │   ├── category/                 # 分类
│   │   ├── checkout/                 # 结算
│   │   ├── my-orders/                # 我的订单
│   │   ├── order-details/            # 订单详情
│   │   ├── track-order/              # 订单跟踪
│   │   ├── wallet/                   # 钱包
│   │   ├── coupons/                  # 优惠券
│   │   ├── wishlist/                 # 收藏夹
│   │   ├── profile/                  # 个人中心
│   │   ├── address/                  # 地址管理
│   │   ├── chatting/                 # 在线客服
│   │   ├── campaigns/                # 营销活动
│   │   ├── flash-sales/              # 限时抢购
│   │   └── ...                       # 其他页面
│   ├── public/                       # 静态资源
│   │   ├── static/                   # 静态图片
│   │   └── landingpage/              # 落地页资源
│   ├── src/                          # 源代码
│   │   ├── api-manage/               # API 管理
│   │   ├── components/               # 组件
│   │   ├── contexts/                 # React Context
│   │   ├── language/                 # 国际化文件
│   │   ├── redux/                    # Redux 状态管理
│   │   ├── styles/                   # 样式文件
│   │   ├── theme/                    # 主题配置
│   │   └── utils/                    # 工具函数
│   ├── .env.development              # 开发环境变量
│   ├── .env.production               # 生产环境变量
│   ├── next.config.js                # Next.js 配置
│   ├── package.json                  # npm 依赖
│   └── README.md                     # 前端说明
│
├── docker/                           # Docker 配置（根目录）
│   ├── nginx/
│   │   └── conf.d/
│   │       └── default.conf          # Nginx 站点配置
│   └── php/
│       └── local.ini                 # PHP 本地配置
│
├── docs/                             # 项目文档
│   ├── docker-deployment.md          # Docker 部署指南
│   ├── project-structure.md          # 项目结构说明
│   └── troubleshooting.md            # 故障排查
│
├── docker-compose.yml                # Docker Compose 配置（根目录）
├── Dockerfile                        # Docker 镜像构建（根目录）
└── README.md                         # 项目总说明
```

---

## 核心模块说明

### 1. 后端模块 (service/)

#### 管理后台 (Admin)
位置：`app/Http/Controllers/Admin/`, `resources/views/admin-views/`

功能：
- 仪表盘统计
- 商家管理（审核、列表、详情）
- 订单管理（列表、详情、状态）
- 配送员管理
- 商品管理
- 优惠券管理
- 财务管理
- 系统设置

#### 商家后台 (Vendor)
位置：`app/Http/Controllers/Vendor/`, `resources/views/vendor-views/`

功能：
- 店铺信息管理
- 商品管理（添加、编辑、上下架）
- 订单处理（接单、备货、发货）
- 配送设置
- 收入统计
- 配送员管理

#### API 接口
位置：`routes/api/`, `routes/rest_api/`

功能：
- 用户认证（登录、注册、找回密码）
- 商家列表、商品列表
- 购物车、订单管理
- 支付接口
- 配送跟踪

### 2. 前端模块 (customer-web/)

#### 用户端网站
位置：`pages/`

功能：
- 首页（推荐商家、分类、活动）
- 商家店铺（商品列表、评价）
- 商品详情（加入购物车、立即购买）
- 购物车结算
- 订单管理（列表、详情、跟踪）
- 个人中心（资料、地址、钱包）
- 收藏夹、优惠券

---

## 数据库结构

### 核心数据表

| 表名 | 说明 |
|------|------|
| `admins` | 管理员账户 |
| `vendors` | 商家信息 |
| `delivery_men` | 配送员信息 |
| `users` | 用户信息 |
| `orders` | 订单主表 |
| `order_details` | 订单详情 |
| `items` | 商品信息 |
| `categories` | 商品分类 |
| `stores` | 店铺信息 |
| `coupons` | 优惠券 |
| `carts` | 购物车 |
| `data_settings` | 系统设置 |
| `business_settings` | 业务设置 |

### 多语言支持

| 表名 | 说明 |
|------|------|
| `translations` | 翻译内容 |

---

## 配置文件说明

### 后端环境变量 (service/.env)

```env
APP_NAME=Loteat              # 应用名称
APP_ENV=local                # 环境（local/production）
APP_DEBUG=true               # 调试模式
APP_URL=http://localhost:8080 # 应用URL

DB_CONNECTION=mysql          # 数据库类型
DB_HOST=db                   # 数据库主机（Docker 服务名）
DB_PORT=3306                 # 数据库端口
DB_DATABASE=multi_food_db    # 数据库名
DB_USERNAME=root             # 数据库用户名
DB_PASSWORD=root123456       # 数据库密码

CACHE_DRIVER=redis           # 缓存驱动
SESSION_DRIVER=file          # 会话驱动
REDIS_HOST=redis             # Redis主机（Docker 服务名）
```

### 前端环境变量 (customer-web/.env.local)

```env
NEXT_PUBLIC_BASE_URL=http://localhost:8080    # 后端 API 地址
NEXT_CLIENT_HOST_URL=http://localhost:3000    # 前端地址
NEXT_PUBLIC_GOOGLE_MAP_KEY=xxx                # Google Maps API Key
NEXT_PUBLIC_SITE_VERSION=2.3.1                # 版本号
```

### Docker 配置

#### docker-compose.yml（根目录）

定义了以下服务：
- **app**: PHP-FPM 服务（挂载 `./service`）
- **nginx**: Web 服务器（挂载 `./service`）
- **db**: MySQL 数据库
- **redis**: 缓存服务
- **phpmyadmin**: 数据库管理工具

#### Dockerfile（根目录）

自定义 PHP 镜像：
- 基础镜像：php:8.2-fpm
- 从 `service/` 文件夹复制代码
- 安装扩展：gd, pdo_mysql, mbstring, zip, redis
- 安装 Composer
- 自动创建 storage 软链接

---

## 路由结构

### 后端路由

#### Web 路由 (service/routes/web.php)
```php
// 首页
Route::get('/', [HomeController::class, 'index']);

// 用户认证
Route::get('/login/{tab}', [LoginController::class, 'login']);
Route::post('/login_submit', [LoginController::class, 'submit']);

// 商家相关
Route::get('/store/{id}', [StoreController::class, 'show']);
Route::get('/product/{id}', [ProductController::class, 'show']);
```

#### API 路由 (service/routes/api/v1/api.php)
```php
// 移动端 API
Route::prefix('v1')->group(function () {
    Route::post('/login', [ApiAuthController::class, 'login']);
    Route::get('/stores', [ApiStoreController::class, 'index']);
    Route::get('/products', [ApiProductController::class, 'index']);
    Route::post('/order/place', [ApiOrderController::class, 'place']);
});
```

#### 后台路由 (service/routes/admin.php)
```php
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::resource('/vendors', AdminVendorController::class);
    Route::resource('/orders', AdminOrderController::class);
});
```

### 前端路由 (customer-web/pages/)

```javascript
// Next.js 文件系统路由
/pages/
├── index.js              # /
├── home/index.js         # /home
├── store/[id]/index.js   # /store/:id
├── product/[id].js       # /product/:id
├── checkout/index.js     # /checkout
├── my-orders/index.js    # /my-orders
└── ...
```

---

## 静态资源

### 后端静态资源 (service/public/)

```
service/public/
├── assets/
│   ├── admin/              # 后台资源
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── landing/            # 落地页资源
│       ├── css/
│       └── js/
├── storage/                # Storage 软链接
└── index.php               # 入口文件
```

### 上传文件 (service/storage/app/public/)

```
service/storage/app/public/
├── business/               # 商家图片
├── product/                # 商品图片
├── category/               # 分类图片
├── banner/                 # 横幅图片
├── profile/                # 用户头像
└── ...
```

**注意**：`storage/app/public` 需要通过软链接链接到 `public/storage` 才能被 Web 访问。

### 前端静态资源 (customer-web/public/)

```
customer-web/public/
├── static/                 # 静态图片
│   ├── profile/
│   ├── address.png
│   ├── cartImage.png
│   └── ...
├── landingpage/            # 落地页资源
├── favicon.ico
└── ...
```

---

## 多语言支持

### 后端多语言 (service/resources/lang/)

```
service/resources/lang/
├── en/                     # 英文
│   ├── messages.php
│   └── validation.php
├── zh/                     # 中文
│   ├── messages.php
│   └── validation.php
└── ...                     # 其他语言
```

### 前端多语言 (customer-web/src/language/)

```
customer-web/src/language/
├── en.js                   # 英文
├── zh.js                   # 中文
├── i18n.js                 # i18n 配置
└── ...                     # 其他 20+ 语言
```

### 数据库翻译

使用 `translations` 表存储动态内容的翻译：
- 页面标题
- 商品描述
- 分类名称
- 系统设置

---

## 支付集成

### 支持的支付方式

- PayPal
- Stripe
- RazorPay
- PayStack
- FlutterWave
- SSLCommerz
- 银行转账
- 货到付款

配置位置：`service/config/` 下的各支付配置文件

---

## 第三方服务集成

### 地图服务
- Google Maps

### 推送通知
- Firebase Cloud Messaging (FCM)

### 短信服务
- Twilio
- Nexmo

### 邮件服务
- SMTP
- Mailgun
- SendGrid

---

## 开发规范

### 命名规范

| 类型 | 规范 | 示例 |
|------|------|------|
| 控制器 | PascalCase + Controller | `OrderController` |
| 模型 | PascalCase 单数 | `Order` |
| 表名 | snake_case 复数 | `orders` |
| 方法 | camelCase | `getOrderList` |
| 变量 | camelCase | `$orderList` |

### 代码结构示例

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;
    
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    
    public function index(Request $request)
    {
        $orders = $this->orderService->getList($request->all());
        
        return view('admin.orders.index', compact('orders'));
    }
}
```

---

## 部署流程

### 开发环境

1. 克隆代码
2. 复制 `service/.env.example` 为 `service/.env`
3. 在根目录运行 `docker-compose up -d` 启动后端
4. 创建管理员账号
5. 进入 `customer-web/` 文件夹
6. 运行 `npm install` 安装前端依赖
7. 复制 `.env.development` 为 `.env.local`
8. 运行 `npm run dev` 启动前端
9. 访问 http://localhost:3000（前端）和 http://localhost:8080（后端）

### 生产环境

1. 配置生产环境变量（前后端）
2. 使用 `docker-compose up -d` 部署后端
3. 使用 `npm run build && npm start` 部署前端
4. 配置 SSL 证书
5. 设置文件权限
6. 配置定时任务（Laravel Schedule）
7. 配置队列处理器（Queue Worker）

---

## 参考文档

- [Laravel 10 文档](https://laravel.com/docs/10.x)
- [Next.js 文档](https://nextjs.org/docs)
- [Material-UI 文档](https://mui.com/material-ui/getting-started/)
- [Docker 最佳实践](https://docs.docker.com/develop/dev-best-practices/)
