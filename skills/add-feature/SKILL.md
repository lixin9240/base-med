---
name: add-feature
description: 新增功能的标准流程。当用户要求新增页面、模块、接口或完整的端到端功能时使用。
---

# 新增功能流程

base-med 是前后端同仓（Vue 3 前端 + Laravel 后端），新增功能按端拆步骤：

## 1. 明确端与术语

- 属于移动端（用户学习）还是管理端（视频/记录/统计）？涉及新概念先查根目录 `GLOSSARY.md`，新术语要补进去。

## 2. 前端先行（可 mock 跑通）

1. 页面放 `src/views/<端>/<模块>/`，组件放 `src/components/<端>/`。
2. `src/router/index.js` 注册路由（懒加载、按现有 name 规范）。
3. 数据先用 `src/mock/<端>/` 假数据，接口函数在 `src/api/` 定义并从 index.js 导出。
4. 状态需要跨页共享时建 `src/store/<模块>/`。
5. `npm run lint && npm run format` 通过。

## 3. 后端跟进

1. migration + Model（表名/字段与前端 mock 数据结构对齐）。
2. Controller 放对应端目录（Admin/Mobile），业务逻辑放 `app/Services/`。
3. 路由加 `routes/api.php`，鉴权 `auth:sanctum`（公开接口除外）。
4. 响应一律 `Result::success/error` + `ResponseCode` 枚举（见 api-conventions 技能）。
5. 有新错误码加进枚举对应段位。

## 4. 前后端对齐

- 把 mock 请求替换为真实 API（改 `src/api/` 对应函数），保留 mock 文件便于演示/测试。
- 信封校验 `code === 0`，错误走统一提示。

## 完成定义

前端 lint/format 通过、后端 `php artisan test` 全绿、mock 与真实接口结构一致、GLOSSARY.md 已更新（若有新术语），四者齐备才算完成。
