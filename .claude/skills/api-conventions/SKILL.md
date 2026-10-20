---
name: api-conventions
description: 后端接口开发规范。在 Backend/ 中编写或修改任何 API（Controller、路由、响应、鉴权、错误码）时使用。
---

# 后端接口规范

base-med 后端（Laravel 10 + PHP 8.1 + Sanctum）。项目处于骨架阶段，规范已定、接口按需增长。

## 统一响应（强制）

所有接口必须通过 `App\Support\Result` 返回，禁止直接 `response()->json()` 拼数组：

```php
return Result::success('保存成功', $data);
return Result::error(ResponseCode::PARAM_ERROR, '参数错误');
```

响应信封固定为 `{ code, msg, data, success, trace_id }`，`code=0` 表示成功。注意字段名是 `msg`（不是 message）。

## 错误码

只用 `App\Enums\ResponseCode` 枚举，禁止魔法数字。八段式分段：

| 段位 | 含义 |
|---|---|
| 0~9 | 通用（SUCCESS=0、COMMON_DATA_NOT_FOUND=2、COMMON_TOO_MANY_REQUESTS=6…） |
| 10000 | 参数与文件（PARAM_*、FILE_*） |
| 20000 | 认证授权（UNAUTHORIZED、TOKEN_EXPIRED、PERMISSION_DENIED、ACCOUNT_*） |
| 30000 | 数据（DATA_NOT_FOUND、DATA_DUPLICATE、DATA_VERSION_CONFLICT…） |
| 40000 | 业务（STATUS_NOT_OPERABLE、APPROVAL_FAILED、DUPLICATE_SUBMIT…） |
| 50000 | 第三方（WECHAT/ALIPAY/SMS/OSS/MQ…） |
| 60000 | 数据库（DB_*、事务、死锁） |
| 90000 | 系统（SYSTEM_*） |

新增错误码放对应段位，用语义化命名（如 `DATA_*`、`BUSINESS_*` 前缀保持一致）。

## 鉴权

- Web/API 登录用 Sanctum（`auth:sanctum`），见 `routes/api.php` 现有 `/user` 示例。
- 移动端接口若与小程序/公众号对接，第三方错误码用 50000 段。

## 组织约定

- Controller 放 `app/Http/Controllers/`，随模块增长按端分目录（如 `Admin/`、`Mobile/`），与前端 admin/mobile 两端对应。
- 业务逻辑放 `app/Services/`（有则复用），Controller 保持薄。
- migration/Model 按 Laravel 默认约定；测试放 `Backend/tests/`，跑 `php artisan test`。
