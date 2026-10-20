---
name: frontend-conventions
description: 前端开发约定。在 src/ 中开发页面、组件、状态管理或接口对接时使用。
---

# 前端开发约定

base-med 前端：Vue 3.5（JavaScript，非 TS）+ Pinia + Vue Router 5 + Vite 8。Node 要求 ^22.18 || >=24.12。

## 双端结构

| 端 | 位置 | 内容 |
|---|---|---|
| 移动端（用户） | `src/views/mobile/` | home（首页）、learn（学习）、quiz（答题）、consolidate（巩固）、report（报告） |
| 管理端 | `src/views/admin/` | login、videos（宣教视频）、records（学习记录）、stats（统计） |

- 组件按端分：`src/components/admin/`、`src/components/mobile/`；页面骨架在 `src/layouts/`（如 `AdminLayout.vue`）。
- 路由 `src/router/index.js`：history 模式，`/` 重定向 `/mobile/home`；admin 子路由挂在 `AdminLayout` 下；组件一律 `() => import()` 懒加载，路由 name 用 `admin-*` / 端-页面 命名。
- 状态按模块分目录：`src/store/<模块>/`（如 `video/video.js`），Pinia defineStore。
- 列表/演示数据先放 `src/mock/`（按端分子目录），后端就绪后替换。

## 接口对接（强制走统一封装）

所有请求必须走 `src/api/http.js` 的 `get/post/put/del`（fetch 封装），不要散落裸 fetch/axios：

- baseURL 来自 `VITE_API_BASE_URL`（`.env` 配置）。
- 非 2xx 会 throw Error（`.status`、`.data` 挂在错误上），调用方用 try/catch 处理。
- 业务层再校验信封 `code === 0`，非 0 按 `msg` 提示。
- 接口函数统一从 `src/api/index.js` 导出，按模块加文件后在 index 汇出。

## Excel 导入导出

xlsx 依赖（SheetJS）用于记录/统计的导入导出，注意 xlsx 从 CDN 安装的版本锁定，勿改为 npm registry 版本。

## 代码质量

- 提交前：`npm run lint`（oxlint + eslint 自动修复）→ `npm run format`（prettier，仅 src/）。
- 新文件保持现有风格：`<script setup>`、组合式 API、`@` 别名指向 src/。
