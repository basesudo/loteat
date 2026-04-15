# Next.js 部署方式详解

本文档整理了关于 Next.js 不同部署方式的讨论，包括 Standalone 模式、Export 模式，以及与 Vue 项目的对比。

## 目录

1. [Next.js 的三种输出模式](#1-nextjs-的三种输出模式)
2. [Standalone 模式详解](#2-standalone-模式详解)
3. [Export 模式详解](#3-export-模式详解)
4. [两种模式对比](#4-两种模式对比)
5. [与 Vue 项目的对比](#5-与-vue-项目的对比)
6. [本项目分析](#6-本项目分析)
7. [选择建议](#7-选择建议)

---

## 1. Next.js 的三种输出模式

Next.js 提供了三种主要的输出模式：

| 模式 | 配置 | 适用场景 |
|------|------|----------|
| **Standalone** | `output: 'standalone'` | 自有服务器、Docker、VPS |
| **Export** | `output: 'export'` | 纯静态网站、CDN 部署 |
| **Server** | 默认（不配置） | 开发环境、Vercel 托管 |

### 配置示例

```javascript
// next.config.js
const nextConfig = {
  // Standalone 模式
  output: 'standalone',
  
  // 或者 Export 模式
  // output: 'export',
  // distDir: 'dist',
  
  images: {
    unoptimized: true,  // 非 Vercel 环境需要禁用图片优化
  },
}

module.exports = nextConfig
```

---

## 2. Standalone 模式详解

### 2.1 什么是 Standalone 模式？

Standalone 是 Next.js 提供的一种**独立部署模式**，它会将应用打包成一个**自包含的、最小化的运行时环境**。

### 2.2 构建输出结构

```
.next/standalone/
├── server.js              # 入口文件，启动 Node.js 服务器
├── .next/
│   ├── standalone/        # 编译后的页面和 API 路由
│   └── static/            # 静态资源（需要手动复制）
├── node_modules/          # 精简后的依赖（只包含生产依赖）
└── package.json           # 依赖配置
```

### 2.3 工作原理

```
构建时                    部署后                    用户访问时
  │                        │                        │
  ▼                        ▼                        ▼
编译代码 ──► 部署到服务器 ──► Node.js 运行 ──► 实时执行代码获取数据
```

**特点**：
- 服务器上运行着 Node.js
- 每次请求都可以执行代码
- 可以调用 API、查询数据库、返回动态内容
- 支持服务端渲染 (SSR)

### 2.4 部署流程

```bash
# 1. 构建
npm run build

# 2. 复制静态资源（关键步骤！）
# Windows
robocopy .next\static .next\standalone\.next\static /E

# Linux/Mac
cp -r .next/static .next/standalone/.next/

# 3. 打包
tar -czf app.tar.gz .next/standalone/

# 4. 上传到服务器并解压

# 5. 启动
PORT=3000 node server.js
```

### 2.5 Nginx 配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;

    # 静态文件直接由 Nginx 提供（关键配置）
    location ^~ /_next/static/ {
        alias /www/wwwroot/your-app/.next/static/;
        expires 365d;
        add_header Cache-Control "public, immutable";
    }

    # 其他请求转发到 Node.js
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

**注意**：`_next/static` 路径必须在其他 location 之前定义，并使用 `^~` 修饰符确保优先级。

### 2.6 优缺点

**优点**：
- ✅ 支持服务端渲染 (SSR)，首屏加载快
- ✅ SEO 友好，搜索引擎能抓取内容
- ✅ 支持动态数据获取
- ✅ 体积小，只包含必要文件
- ✅ 适合电商平台等需要 SEO 的应用

**缺点**：
- ❌ 需要 Node.js 运行环境
- ❌ 需要进程管理（如 PM2）
- ❌ 需要反向代理（如 Nginx）
- ❌ 部署相对复杂

---

## 3. Export 模式详解

### 3.1 什么是 Export 模式？

Export 是 Next.js 的**纯静态导出模式**，会生成完全静态的 HTML 文件，类似于 Vue 的打包结果。

### 3.2 构建输出结构

```
dist/                      # 或 out/
├── index.html             # 首页（预渲染的静态 HTML）
├── about.html             # 关于页面
├── products/
│   └── [id].html          # 动态路由页面
├── _next/
│   ├── static/            # CSS、JS 文件
│   └── ...
└── ...
```

### 3.3 工作原理

```
构建时                          部署后
  │                              │
  ▼                              ▼
预渲染所有页面 ──► 生成静态文件 ──► 任何静态服务器（Nginx/CDN）
（数据已固定）      （HTML/CSS/JS）
```

**特点**：
- HTML 在构建时就已经确定了
- 部署后只是静态文件，没有运行代码的能力
- 数据获取发生在客户端（如果使用了 useEffect）

### 3.4 部署流程

```bash
# 1. 构建
npm run build

# 2. 部署 dist/ 目录到任何静态服务器
# 例如：Nginx、Apache、CDN、GitHub Pages 等
```

### 3.5 优缺点

**优点**：
- ✅ 部署简单，任何静态服务器都可以
- ✅ 不需要 Node.js 运行环境
- ✅ 可以直接部署到 CDN
- ✅ 成本低

**缺点**：
- ❌ 不支持 `getServerSideProps`
- ❌ 不支持 API Routes (`pages/api/*`)
- ❌ 不支持动态路由的参数预渲染（需要配置 `generateStaticParams`）
- ❌ SEO 较差（如果使用客户端数据获取）
- ❌ 首屏可能显示 Loading

---

## 4. 两种模式对比

| 特性 | Standalone | Export |
|------|------------|--------|
| **需要 Node.js** | ✅ 需要 | ❌ 不需要 |
| **服务端渲染** | ✅ 支持 | ❌ 不支持 |
| **SEO** | ✅ 优秀 | ⚠️ 一般 |
| **首屏体验** | ✅ 直接显示内容 | ⚠️ 可能显示 Loading |
| **动态数据** | ✅ 支持 | ⚠️ 仅客户端获取 |
| **API Routes** | ✅ 支持 | ❌ 不支持 |
| **部署复杂度** | ⚠️ 较复杂 | ✅ 简单 |
| **适用场景** | 电商平台、需要 SEO | 后台系统、纯展示页面 |

### 4.1 数据获取方式对比

**Standalone 模式**：
```javascript
// 服务端获取数据（每次请求都重新获取）
export async function getServerSideProps() {
  const res = await fetch('http://api.example.com/data')
  const data = await res.json()
  return { props: { data } }
}

export default function Home({ data }) {
  return <div>{data.title}</div>  // 直接显示，无 Loading
}
```

**Export 模式**：
```javascript
import { useState, useEffect } from 'react'

export default function Home() {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    // 客户端获取数据
    fetch('http://api.example.com/data')
      .then(res => res.json())
      .then(data => {
        setData(data)
        setLoading(false)
      })
  }, [])

  if (loading) return <div>Loading...</div>  // 先显示 Loading
  
  return <div>{data.title}</div>
}
```

---

## 5. 与 Vue 项目的对比

### 5.1 Vue 的部署方式

Vue 项目（使用 Vue CLI 或 Vite）默认就是**纯客户端渲染**：

```
dist/                    # 纯静态文件
├── index.html           # 空壳 HTML（只包含 <div id="app"></div>）
├── js/
│   ├── app.js           # Vue 应用代码
│   └── chunk-vendors.js # 依赖库
└── css/
    └── app.css
```

**工作流程**：
```
用户访问 ──► 下载 HTML ──► 下载 JS ──► 执行 JS ──► 调用 API ──► 渲染内容
              (空壳)       (Vue)        (mounted)   (获取数据)
```

### 5.2 为什么 Vue 可以用纯静态部署？

Vue 项目默认就是**纯客户端渲染 (CSR)**：
- `index.html` 是一个空壳，只包含 `<div id="app"></div>`
- 真正的逻辑在 `app.js` 中
- 浏览器加载后，Vue 在**客户端**执行，然后通过 API 获取数据

### 5.3 Next.js 与 Vue 的对比

| 方面 | Vue (默认) | Next.js (useEffect) | Next.js (getServerSideProps) |
|------|-----------|---------------------|------------------------------|
| **打包结果** | 纯静态文件 | 纯静态文件（有限制） | Node.js 应用 |
| **首屏** | 白屏后加载 | 白屏后加载 | 直接显示内容 |
| **数据获取** | `mounted()` + API | `useEffect()` + API | 服务端获取 |
| **部署方式** | 任何静态服务器 | 任何静态服务器（有限制） | 需要 Node.js |
| **SEO** | 较差 | 较差 | 优秀 |

### 5.4 关键区别

**Vue 项目**：
- 默认就是纯客户端渲染
- 没有服务端渲染的概念（除非用 Nuxt.js）
- 打包后可以直接部署到任何静态服务器

**Next.js 项目**：
- 默认支持服务端渲染
- 如果用了 `getServerSideProps` 等特性，就不能用 Export
- 可以选择纯 CSR 方式，但需要避免使用服务端特性

---

## 6. 本项目分析

### 6.1 当前项目使用的服务端特性

通过代码分析，本项目**大量使用了服务端特性**：

#### 1. 大量使用 `getServerSideProps`

找到了 **89 个文件** 使用了 `getServerSideProps`，包括：
- 首页 (`pages/index.js`)
- 所有商品相关页面
- 所有订单相关页面
- 用户中心页面
- 等等...

**首页示例**：
```javascript
export const getServerSideProps = async (context) => {
  const { req, res } = context;
  const language = req.cookies.languageSetting;
  
  // 服务端获取配置数据
  const configRes = await fetch(
    `${process.env.NEXT_PUBLIC_BASE_URL}/api/v1/config`,
    {
      method: "GET",
      headers: {
        "X-software-id": 33571750,
        "X-server": "server",
        "X-localization": language,
        origin: process.env.NEXT_CLIENT_HOST_URL,
      },
    }
  );
  const config = await configRes.json();
  
  // 服务端获取落地页数据
  const landingPageRes = await fetch(
    `${process.env.NEXT_PUBLIC_BASE_URL}/api/v1/react-landing-page`,
    { /* ... */ }
  );
  const landingPageData = await landingPageRes.json();
  
  return { props: { config, landingPageData } };
};
```

#### 2. 使用了 Cookies

`getServerSideProps` 中使用了 `req.cookies` 获取用户语言设置：
```javascript
const language = req.cookies.languageSetting;
```

### 6.2 改造为 Export 模式的可行性分析

**技术上可行，但不建议改造。**

#### 需要修改的内容

每个使用 `getServerSideProps` 的文件都需要：
1. **移除 `getServerSideProps`**
2. **添加 `useState` 管理数据**
3. **添加 `useEffect` 获取数据**
4. **添加 Loading 状态处理**
5. **添加错误处理**
6. **处理 Cookies**（需要使用 `js-cookie` 等库在客户端读取）

#### 改造示例

**改造前**：
```javascript
export default function Home({ config, landingPageData }) {
  return <LandingPage config={config} data={landingPageData} />
}

export const getServerSideProps = async (context) => {
  const { req } = context
  const language = req.cookies.languageSetting
  
  const configRes = await fetch('.../api/v1/config', {
    headers: { 'X-localization': language }
  })
  const config = await configRes.json()
  
  return { props: { config } }
}
```

**改造后**：
```javascript
import { useState, useEffect } from 'react'
import Cookies from 'js-cookie'

export default function Home() {
  const [config, setConfig] = useState(null)
  const [landingPageData, setLandingPageData] = useState(null)
  const [loading, setLoading] = useState(true)
  
  useEffect(() => {
    const language = Cookies.get('languageSetting')
    
    // 并行获取数据
    Promise.all([
      fetch('.../api/v1/config', {
        headers: { 'X-localization': language }
      }).then(res => res.json()),
      fetch('.../api/v1/react-landing-page', {
        headers: { 'X-localization': language }
      }).then(res => res.json())
    ]).then(([configData, landingData]) => {
      setConfig(configData)
      setLandingPageData(landingData)
      setLoading(false)
    })
  }, [])
  
  if (loading) return <div>Loading...</div>
  
  return <LandingPage config={config} data={landingPageData} />
}
```

#### 改造后的影响

| 方面 | 影响 |
|------|------|
| **工作量** | 极大，89+ 个文件需要逐一手动修改 |
| **首屏体验** | 从"直接显示内容"变成"先显示 Loading" |
| **SEO** | 严重受损，搜索引擎无法抓取内容 |
| **代码复杂度** | 增加，需要处理更多状态管理 |
| **维护成本** | 提高，后续开发需要手动处理数据获取 |

---

## 7. 选择建议

### 7.1 使用 Standalone 模式的场景

✅ **推荐使用**：
- 电商平台（需要 SEO）
- 内容型网站（博客、新闻）
- 需要首屏快速加载的应用
- 需要服务端渲染的复杂应用
- 本项目（6amMart 多商户电商平台）

### 7.2 使用 Export 模式的场景

✅ **推荐使用**：
- 后台管理系统（不需要 SEO）
- 纯交互型应用（不需要 SEO）
- 简单展示页面
- 个人博客（内容不频繁更新）
- 预算有限，需要低成本部署

### 7.3 类似 Vue 的纯 CSR 方式

✅ **推荐使用**：
- 单页应用 (SPA)
- 完全不需要 SEO
- 用户交互为主的应用
- 需要部署到 CDN 的应用

### 7.4 本项目的建议

**保持 Standalone 模式**，原因：

1. **SEO 需求** - 电商平台依赖搜索引擎流量
2. **首屏体验** - 用户希望快速看到内容
3. **工作量** - 改造成本太高（89+ 文件）
4. **维护成本** - 改造后代码更复杂

**如果需要简化部署，可以考虑**：
- 使用 Docker 容器化部署
- 使用 Vercel/Netlify 等托管平台
- 使用 AWS/GCP/Azure 的容器服务

---

## 8. 常见问题

### Q: 为什么页面是空白的？

**A**: 可能是以下原因：
1. 忘记复制 `.next/static` 文件夹到 standalone 目录
2. Nginx 没有正确配置 `_next/static` 路径
3. 路径在 alias 指令中不匹配实际目录结构

### Q: 静态文件 404？

**A**: 检查 Nginx 配置：
1. `_next/static` location 是否在其他 location 之前
2. 是否使用了 `^~` 修饰符确保优先级
3. alias 路径是否正确

### Q: Export 模式为什么失败？

**A**: 可能是因为：
1. 使用了 `getServerSideProps`（Export 不支持）
2. 使用了 API Routes（Export 不支持）
3. 使用了动态路由但没有配置 `generateStaticParams`

### Q: 可以用 Vue 的方式部署 Next.js 吗？

**A**: 可以，但需要：
1. 完全不使用 `getServerSideProps`
2. 完全不使用 `getStaticProps`（带数据预取的）
3. 完全不使用 API Routes
4. 所有数据通过 `useEffect` + API 在客户端获取

---

## 9. 总结

| 部署方式 | 适用场景 | 本项目建议 |
|---------|---------|-----------|
| **Standalone** | 需要 SEO、服务端渲染 | ✅ **推荐** |
| **Export** | 纯静态网站、后台系统 | ❌ 不适合 |
| **纯 CSR** | 单页应用、不需要 SEO | ❌ 不适合 |

本项目是一个**多商户电商平台**，需要 SEO 和良好的首屏体验，因此 **Standalone 模式是最佳选择**。

虽然 Standalone 部署相对复杂，但它提供了：
- 优秀的首屏加载体验
- 良好的 SEO 支持
- 完整的 Next.js 特性支持

这些都是电商平台必不可少的特性。
