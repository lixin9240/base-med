# 基层医生DR医学科普视频学习考核平台 接口文档

---

# 1 通用说明

## 1.1 基础约定

- 数据格式：请求与响应均为 `application/json`（文件上传接口除外，使用 `multipart/form-data`）。
- 统一响应结构：

```json
{ "code": 0, "msg": "success", "data": {} }
```

| HTTP 状态码 | 说明                                                           |
| ----------- | -------------------------------------------------------------- |
| 200         | 成功                                                           |
| 400         | 业务错误（body 内 `code` 为 40000 段，如 40001）               |
| 401         | 未登录 / token 失效（管理端）                                  |
| 403         | 无权限                                                         |
| 404         | 资源不存在                                                     |
| 409         | 冲突（数据重复 / 存在关联引用 / 并发冲突）                     |
| 422         | 参数错误 / 业务校验不通过                                      |
| 429         | 请求过于频繁                                                   |
| 500         | 服务器错误（数据库 / 系统异常）                                |
| 502         | 第三方服务异常（OSS / 短信 / 邮件等上游失败）                  |
| 503         | 服务暂不可用                                                   |

> 业务失败时 `msg` 返回具体原因；下文各接口「返回示例」仅展示 `data` 部分。
>
> 说明：HTTP 状态码与 body 内业务错误码 `code`（取值见 `app/Enums/ResponseCode.php`）按码段对应——`1xxxxx→422`、`2xxxxx→401`（无权限 20005→403）、`3xxxxx→404`（重复/关联冲突→409）、`4xxxxx→400`、`5xxxxx→502`、`6xxxxx/9xxxxx→500`。

## 1.2 鉴权约定

- **管理端接口**：请求头携带 `Authorization: Bearer {token}`（登录接口除外），token 由 2.1.1 登录获取。
- **用户端接口**：免登录匿名访问。用户经访问链接携带的 `token` 换取匿名编号 `dr_id`（见 3.1），后续请求仅传 `dr_id`，不采集任何个人信息（G2）。

## 1.3 分页约定

- 请求参数：`page`（默认 1）、`page_size`（默认 20）。
- 响应结构：`{ "list": [], "total": 0, "page": 1, "page_size": 20 }`。

---

# 2 管理后台接口（token 鉴权）


## 2.1 WJC-管理员登录模块

### 2.1.1 管理员登录
- 路径：`POST /api/admin/auth/login`

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| username | string | 是 | 演示账号：admin |
| password | string | 是 | 演示密码：123456 |

返回示例：
```json
{
    "token": "eyJ0eXAi...",
    "admin_info": {
        "id": 1,
        "username": "admin",
        "name": "系统管理员",
        "status": 1
    }
}
```

### 2.1.2 获取当前登录管理员信息
- 路径：`GET /api/admin/auth/profile`
- 说明：请求头携带 token，返回 admin_info 对象（同登录返回结构）。

### 2.1.3 管理员登出
- 路径：`POST /api/admin/auth/logout`
- 说明：销毁当前 token，返回 success=true。

### 2.1.4 发送重置密码验证码（忘记密码）
- 路径：`POST /api/admin/auth/forgot-password`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| username | string | 是 | 登录账号（无需传邮箱） |

返回示例：
```json
{ "success": true, "data": { "expire_minutes": 10 } }
```

> 说明：验证码将发送到**该账号在 `admin_user.email` 中绑定的邮箱**，调用方无需（也不应）传邮箱，以免暴露账号邮箱信息。流程：校验账号存在且已绑定 email 后，发送 6 位数字验证码（10 分钟有效，同一账号 60 秒内不可重复发送）；账号不存在或未绑定邮箱时统一按成功返回，避免用户名枚举。管理员邮箱由管理端维护（当前演示账号 admin 已绑定 wjc20070117@qq.com）。邮件经 QQ 邮箱 SMTP 发送，演示环境 `MAIL_MAILER=log` 时验证码仅写入日志。

### 2.1.5 重置密码
- 路径：`POST /api/admin/auth/reset-password`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| username | string | 是 | 登录账号 |
| code | string | 是 | 邮箱收到的 6 位数字验证码 |
| password | string | 是 | 新密码，6~32 位 |

返回示例：
```json
{ "success": true }
```

> 说明：验证码错误或过期返回 422（msg：验证码错误或已过期）；重置成功后验证码立即失效，需用新密码重新登录。

## 2.2 LX-管理员用户管理模块（admin_user，后台账号增删改）

对应路由 `admin/admin-user`，均需 `auth:admin`（Bearer token）。

### 2.2.1 用户列表
- 路径：`GET /api/admin/admin-user/list`
- Method：GET

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| page | int | 否 | 默认 1 |
| page_size | int | 否 | 默认 20 |
| keyword | string | 否 | 用户名/姓名模糊搜索 |
| status | tinyint | 否 | 0禁用 1正常 |

返回：

```json
{
    "list": [
        {
            "id": 1,
            "username": "admin",
            "email": null,
            "name": "系统管理员",
            "role": "admin",
            "status": 1,
            "last_login_at": "2026-10-21 10:00:00",
            "created_at": "2026-10-20 10:00:00"
        }
    ],
    "total": 1
}
```

### 2.2.2 新增用户
- 路径：`POST /api/admin/admin-user/create`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| username | string | 是 | 登录账号，唯一 |
| password | string | 是 | 6~32 位 |
| name | string | 是 | 姓名 |
| email | string | 否 | 邮箱，唯一 |
| role | string | 否 | 角色，默认 admin |
| status | tinyint | 否 | 0禁用 1正常，默认 1 |

返回：`{ "id": 2 }`（新用户 ID）。

### 2.2.3 编辑用户
- 路径：`POST /api/admin/admin-user/update`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| id | bigint | 是 | 用户 ID |
| username | string | 否 | 唯一（排除自身） |
| name | string | 否 | |
| email | string | 否 | |
| role | string | 否 | |
| status | tinyint | 否 | 0禁用 1正常 |
| password | string | 否 | 传了才重置密码 |

> 说明：不能把当前登录账号自身禁用；返回 `{ "success": true }`。

### 2.2.4 删除用户
- 路径：`POST /api/admin/admin-user/delete`
- Method：POST

| 参数 | 类型 | 是否必须 |
| ---- | ---- | ---- |
| id | bigint | 是 |

> 说明：软删除；不能删除当前登录账号（自身）。返回 `{ "success": true }`。

## 2.3 GYZ-视频管理模块（对应【视频管理】列表页 + 新增/编辑视频弹窗）

> 页面构成：①列表页（批量导入题目/新增视频按钮、上架开关、题目配置/编辑/删除操作）；②新增视频弹窗；③编辑视频弹窗（同表单回填）。

### 2.3.1 视频列表
- 路径：`/api/admin/video/list`
- Method：GET

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| page | int | 否 | 默认 1 |
| page_size | int | 否 | 默认 20 |

返回 data 结构（对应列表页「视频 / 题目 / 状态 / 答题」列）：
```json
{
    "list": [
        {
            "id": 1,
            "title": "心肺复苏基础操作",
            "category_name": "急救技能",
            "speaker": "郭军",
            "speaker_title": "主任医师",
            "pre_count": 3,
            "pre_total": 3,
            "post_count": 3,
            "post_total": 3,
            "status": 1,
            "transcode_status": 2,
            "answer_times": 12,
            "accuracy_rate": 67
        },
        {
            "id": 4,
            "title": "儿童疫苗接种后不良反应识别",
            "category_name": "妇幼保健",
            "speaker": null,
            "speaker_title": null,
            "pre_count": 0,
            "pre_total": 3,
            "post_count": 0,
            "post_total": 3,
            "status": 0,
            "transcode_status": 2,
            "answer_times": 0,
            "accuracy_rate": null
        }
    ],
    "total": 4,
    "page": 1,
    "page_size": 20
}
```

| 字段                | 类型        | 备注                                                         |
| ------------------- | ----------- | ------------------------------------------------------------ |
| speaker             | string/null | 主讲人；未指定时为 null，前端显示「未指定」                  |
| pre_count/pre_total | int         | 已配置前测题目数 / 建议题数（3，取自配置）；未配满时前端标红 |
| post_count/post_total | int       | 已配置后测题目数 / 建议题数（3，取自配置）                   |
| status              | tinyint     | 上架状态：0 已下架 1 已上架，对应列表页开关                  |
| transcode_status    | tinyint     | 转码状态：0 待转码 1 转码中 2 已完成 3 失败；本地上传视频未完成转码时前端在标题旁标「转码中/转码失败」（转码中不可上架播放），填写链接的视频恒为 2 |
| answer_times        | int         | 答题次数（exam_record 计数）                                 |
| accuracy_rate       | int/null    | 平均正确率百分比；无人答题时为 null                          |

### 2.3.2 视频详情（编辑弹窗回填）
- 路径：`GET /api/admin/video/detail`
- Method：GET

| 参数 | 类型 | 是否必须 |
| ---- | ---- | ---- |
| id | bigint | 是 |

返回：

```json
{
    "id": 1,
    "title": "心肺复苏基础操作",
    "category_id": 1,
    "category_name": "急救技能",
    "speaker": "郭军",
    "speaker_title": "主任医师",
    "speaker_org": "四川大学华西医院",
    "brief_intro": "成人基础生命支持的规范动作：按压位置、深度、频率与通气比例，以及基层最常见的三个操作误区。",
    "source_type": 1,
    "video_url": "/videos/demo.mp4",
    "cover_url": "",
    "text_key_points": "【按压位置】...【按压深度与频率】...",
    "status": 1,
    "sort_order": 1,
    "duration": 120,
    "transcode_status": 2
}
```

> 说明：编辑弹窗打开时调用，回填全部表单字段；分类下拉选项来自 2.7.1 分类列表。



### 2.3.3 新增视频（弹窗保存）
- 路径：`/api/admin/video/create`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| title | string | 是 | 视频标题（用户端列表展示的标题），最多 60 字 |
| category_id | int | 是 | 分类 ID，下拉选项来自 2.7.1 分类列表 |
| speaker | string | 是 | 主讲人姓名 |
| speaker_title | string | 否 | 职称，如：主任医师/副主任医师/主治医师 |
| speaker_org | string | 否 | 主讲人单位 |
| brief_intro | string | 是 | 一句话简介（列表里展示，说清这条视频讲什么），最多 120 字 |
| source_type | tinyint | 是 | 视频来源：1 填写视频链接 2 本地上传 |
| video_url | string | 条件必填 | 视频链接地址，`source_type=1` 时必填（正式环境填 OSS 直链） |
| cover_url | string | 否 | 封面图，不传时用户端列表以分类图标占位 |
| text_key_points | text | 否 | 文字要点：给不方便看视频的用户的一段文字说明；段落前用【小标题】，`**文字**` 表示加粗 |
| status | tinyint | 是 | 上架状态：0 已下架 1 已上架（用户可见） |
| sort_order | int | 否 | 排序号（步进器），默认 5 |

返回：`{ "id": 视频id }`

> 说明：
>
> - `source_type=2`（本地上传）时，先调用本接口创建视频记录（可不传 video_url），再通过 2.3.7 / 2.3.8 完成分片上传，上传完成后自动转码。
> - 视频时长（duration）不在表单中提交，由服务端维护：本地上传视频转码完成后自动回填。

### 2.3.4 编辑视频（弹窗保存）
- 路径：`/api/admin/video/update`
- Method：POST
- 参数同新增，**增加 id（bigint，必填）**。

### 2.3.5 上架/下架切换（列表页开关）
- 路径：`POST /api/admin/video/update-status`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| id | bigint | 是 | 视频 ID |
| status | tinyint | 是 | 0 已下架 1 已上架 |

返回：`{ "success": true }`

> 说明：对应列表页「状态」列的开关，切换后用户端立即生效（下架后用户端不可见）。

### 2.3.6 删除视频（软删除）
- 路径：`/api/admin/video/delete`
- Method：POST

| 参数 | 类型 | 是否必须 |
| ---- | ---- | ---- |
| id | bigint | 是 |

> 说明：软删除（写入 deleted_at）；若该视频已存在答题记录（exam_record）等引用，前置校验不通过，不允许删除。

### 2.3.7 初始化视频上传（本地上传，source_type=2）
- 路径：`POST /api/admin/video/upload/init`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| file_name | string | 是 | 原始文件名 |
| file_size | bigint | 是 | 文件大小（字节） |
| video_id | bigint | 否 | 已创建视频的 id（编辑时重新上传场景） |

返回：

```json
{
    "oss_upload_id": "upload_abc123",
    "oss_path": "video/2026/10/xxx.mp4",
    "part_size": 1048576,
    "parts": 10
}
```

> 说明：初始化 OSS 分片上传；若该视频存在未完成的上传任务（oss_upload_id 已存在且未完成），直接返回原 oss_upload_id，实现断点续传（R6）。

### 2.3.8 完成视频上传
- 路径：`POST /api/admin/video/upload/complete`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| oss_upload_id | string | 是 | 上传任务 ID |
| video_id | bigint | 是 | 关联视频 id |

返回：`{ "video_id": 1, "transcode_status": 1 }`

> 说明：合并分片后自动触发转码；转码完成后自动生成封面（回填 cover_url）、回填时长（duration）与文件大小（file_size）。转码为异步过程，状态通过 2.3.1 列表 / 2.3.2 详情的 `transcode_status` 字段查看（0待转码 1转码中 2已完成 3失败），无需单独轮询。

### 2.3.9 上传封面图片（弹窗「上传图片」按钮）
- 路径：`POST /api/admin/video/upload/cover`
- Method：POST（multipart/form-data）

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| file | file | 是 | 封面图片，支持 jpg / png / webp / gif，不超过 2MB |

返回：`{ "url": "https://wjc1127.oss-cn-chengdu.aliyuncs.com/cover/2026/10/….png" }`

> 说明：图片直传 OSS，返回公开访问地址；新增 / 编辑视频时把该地址填入 `cover_url` 即可。不传封面时用户端列表以分类图标占位。

## 2.4 WJC-题目配置模块（对应「题目配置」抽屉 + 「新增题目」弹窗 + 批量导入）

> 页面构成：①题目配置抽屉（前测题目 / 后测题目 Tab 切换，显示「已配题数/建议题数」如 0/3；操作按钮：新增题目、下载模板、导入本组、导出本组）；②新增题目弹窗（题型切换、题干、选项动态增减、勾选标记正确答案、答案解析）；③列表页顶部「批量导入题目」按钮（与抽屉「导入本组」共用导入接口）。

### 2.4.1 获取前/后测题目列表（抽屉 Tab）
- 路径：`/api/admin/video-question/list`
- Method：GET

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| video_id | bigint | 是 | 视频 ID |
| stage | string | 是 | pretest 前测 / posttest 后测 |

返回 data：
```json
{
    "list": [
        {
            "id": 1,
            "question_id": 101,
            "type": "single",
            "stem": "成人基础生命支持中，按压与通气的比例应为？",
            "options": [
                { "key": "A", "content": "15:2" },
                { "key": "B", "content": "30:2" }
            ],
            "correct_answer": "B",
            "analysis": "成人基础生命支持推荐按压与通气比例为 30:2。",
            "sort_order": 1
        }
    ],
    "total": 3
}
```

> 说明：管理端配置界面需展示正确答案与解析（区别于用户端 3.5，用户端作答阶段不返回）；`total` 为已配置题数，前端按「已配/建议题数（如 3）」展示，未配满标红。

### 2.4.2 新增题目（弹窗保存）
- 路径：`/api/admin/video-question/add-question`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| video_id | bigint | 是 | 视频 id |
| stage | string | 是 | pretest / posttest |
| type | string | 是 | single 单选题 / multiple 多选题 / judge 判断题（弹窗分段切换） |
| stem | string | 是 | 题干，最多 300 字 |
| options | json array | 条件必填 | `[{"key":"A","content":"选项内容"}]`；A/B 必填、C/D 选填，可「+添加选项」至多 6 个、「-移除末项」至少保留 2 个；**判断题固定为「正确/错误」两项，可不传** |
| correct_answer | string | 是 | 勾选选项右侧方块标记：单选仅 1 个如 `"A"`；多选可多个如 `"A,B,C"`；判断题 `"正确"/"错误"` |
| analysis | string | 否 | 答案解析，最多 500 字，答题后展示给用户 |

返回：`{ "id": 题目id }`

> 说明：新增题目自动存入 question 题库表（option_count 自动计算），同时建立 video_question 关联绑定当前视频+阶段；`sort_order` 服务端自动排到本组末位。

### 2.4.3 编辑题目（弹窗保存）
- 路径：`/api/admin/video-question/update-question`
- Method：POST
- 参数同 2.4.2，**增加 question_id（bigint，必填）**。

### 2.4.4 下载模板（抽屉「下载模板」按钮）
- 路径：`/api/admin/video-question/download-template`
- Method：GET
- 参数：`stage`（pretest/posttest），直接返回 excel 文件流。

### 2.4.5 导入本组（抽屉「导入本组」/ 列表页「批量导入题目」按钮）
- 路径：`/api/admin/video/import-question`
- Method：POST（form-data 上传 Excel）

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| video_id | bigint | 是 | 所属视频 |
| stage | string | 是 | pretest 前测 / posttest 后测 |
| file | file | 是 | excel 文件 |

返回：

```json
{ "task_id": 1, "task_no": "imp_20261019_001", "status": 1 }
```

> 说明：上传后创建导入任务（import_task），先异步校验（状态 0待校验 → 1校验中），校验完成进入「2待确认」；校验结果与错误报告通过 2.10.2 查询，确认无误后调用 2.10.3 执行导入，真正写入 question 与 video_question。

### 2.4.6 导出本组（抽屉「导出本组」按钮）
- 路径：`/api/admin/video-question/export`
- Method：GET

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| video_id | bigint | 是 | 视频 ID |
| stage | string | 是 | pretest 前测 / posttest 后测 |

返回 Excel 文件流。

### 2.4.7 移除本组题目
- 路径：`/api/admin/video-question/remove`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| video_id | bigint | 是 | 视频 ID |
| stage | string | 是 | pretest / posttest |
| question_id | bigint | 是 | 题目 ID |

> 说明：软删除 video_question 关联（不删除 question 题库记录）；历史答题明细（exam_answer）不受影响。

## 2.5 LX-答题记录模块（对应【答题记录】列表页 + 「作答详情」抽屉）

> 页面构成：①列表页（「全部视频」「全部结果」下拉筛选 + 导出按钮；列：时间 / 视频 / 编号 / 猜测 / 看完答题 / 正确率 / 结果 / 操作「查看作答」）；②作答详情抽屉（头部展示视频名、编号、时间、前测与看完答题小结；题目卡片逐题展示：阶段标签、答对/答错状态、题干、正确答案、用户选择）。
>
> 页面副标题：用户端免登录，每条记录只有一个匿名编号，不涉及任何个人信息（G2）。

### 2.5.1 答题记录列表
- 路径：`/api/admin/exam-record/list`
- Method：GET

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| page | int | 否 | 默认 1 |
| page_size | int | 否 | 默认 20 |
| video_id | bigint | 否 | 「全部视频」下拉筛选，选项来自 2.3.1 视频列表 |
| result_status | string | 否 | 「全部结果」下拉筛选：pass 通过 / fail 未通过 |

返回（对应列表页「时间 / 视频 / 编号 / 猜测 / 看完答题 / 正确率 / 结果」列）：
```json
{
    "list": [
        {
            "id": 1,
            "submit_at": "2026-10-18 10:34:00",
            "video_id": 1,
            "video_title": "心肺复苏基础操作",
            "dr_id": "DR-2026-000003",
            "pre_correct": 2,
            "pre_total": 3,
            "post_correct": 3,
            "post_total": 3,
            "accuracy_rate": 100,
            "result_status": "pass"
        },
        {
            "id": 6,
            "submit_at": "2026-10-16 14:21:00",
            "video_id": 1,
            "video_title": "心肺复苏基础操作",
            "dr_id": "DR-2026-000001",
            "pre_correct": 1,
            "pre_total": 3,
            "post_correct": 0,
            "post_total": 3,
            "accuracy_rate": 0,
            "result_status": "fail"
        }
    ],
    "total": 12,
    "page": 1,
    "page_size": 20
}
```

| 字段                   | 类型      | 备注                                                              |
| ---------------------- | --------- | ----------------------------------------------------------------- |
| submit_at              | datetime  | 「时间」列，提交时间，列表默认按此倒序                            |
| video_title            | string    | 「视频」列                                                        |
| dr_id                  | string    | 「编号」列，匿名编号快照，如 DR-2026-000003                       |
| pre_correct/pre_total  | int       | 「猜测」列：前测（学前答题，观看前作答）答对题数 / 总题数         |
| post_correct/post_total | int      | 「看完答题」列：后测答对题数 / 总题数                             |
| accuracy_rate          | int       | 「正确率」列：看完答题正确率 = post_correct / post_total × 100（四舍五入） |
| result_status          | string    | 「结果」列：pass 通过（绿）/ fail 未通过（黄）；由 accuracy_rate ≥ 通过线（默认 60，取自 system_config）判定 |

### 2.5.2 查看作答详情（作答详情抽屉）
- 路径：`/api/admin/exam-record/detail`
- Method：GET

| 参数 | 类型 | 是否必须 |
| ---- | ---- | ---- |
| exam_record_id | bigint | 是 |

返回（头部信息 + 逐题卡片，题目按前测、后测顺序排列）：
```json
{
    "exam_record_id": 1,
    "video_title": "心肺复苏基础操作",
    "dr_id": "DR-2026-000003",
    "submit_at": "2026-10-18 10:34:00",
    "result_status": "pass",
    "accuracy_rate": 100,
    "pre": { "correct": 2, "total": 3 },
    "post": { "correct": 3, "total": 3 },
    "list": [
        {
            "stage": "pretest",
            "question_id": 101,
            "type": "single",
            "stem": "成人心肺复苏时，胸外按压的深度应为多少？",
            "correct_answer": "C",
            "user_answer": null,
            "is_correct": 0
        },
        {
            "stage": "pretest",
            "question_id": 102,
            "type": "single",
            "stem": "成人胸外按压的频率应保持在每分钟多少次？",
            "correct_answer": "C",
            "user_answer": "C",
            "is_correct": 1
        },
        {
            "stage": "posttest",
            "question_id": 105,
            "type": "multiple",
            "stem": "下列哪些情形可以停止心肺复苏？",
            "correct_answer": "A,B,C",
            "user_answer": "A,B,C",
            "is_correct": 1
        }
    ]
}
```

| 字段          | 类型        | 备注                                                                 |
| ------------- | ----------- | -------------------------------------------------------------------- |
| pre/post      | object      | 抽屉头部小结：前测 x/3、看完答题 x/3（正确率）                        |
| list[].stage  | string      | 题目卡片阶段标签：pretest 前测 / posttest 后测                        |
| list[].type   | string      | 题型：single 单选 / multiple 多选（前端标注「多选」）/ judge 判断     |
| list[].stem   | string      | 题干                                                                 |
| correct_answer | string     | 正确答案：单选 "C"；多选 "A,B,C"（前端渲染为 A、B、C）；判断 "正确"/"错误" |
| user_answer   | string/null | 用户选择；未作答为 null，前端显示「未作答」                           |
| is_correct    | tinyint     | 答对 1 / 答错 0，控制卡片状态标签与左侧色条（绿/红）                 |

> 说明：
>
> - 展示规则与抽屉一致：答对的题仅显示「正确答案」，答错的题同时显示「正确答案 + 用户选择（标红）」；接口始终返回 `user_answer`，由前端按 `is_correct` 决定是否展示。
> - 数据来源 exam_record（头部汇总）+ exam_answer（逐题作答）+ question（题干/答案）。
> - 抽屉为纯展示，无操作按钮。

### 2.5.3 导出答题记录（列表页「导出」按钮）
- 路径：`/api/admin/exam-record/export`
- Method：GET

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| video_id | bigint | 否 | 筛选视频（同列表筛选条件） |
| result_status | string | 否 | pass 通过 / fail 未通过 |

导出为真正的 `.xlsx` 文件并上传阿里云 OSS，返回 JSON（`code=0`），`data` 结构：`{ "filename": "exam_records_YYYYmmdd_His.xlsx", "url": "https://wjc1127.oss-cn-chengdu.aliyuncs.com/exam-records/xxx.xlsx?...&Signature=..." }`。`url` 为带签名的临时下载链接，有效期 7 天（Bucket 为私有 ACL 也可正常下载）；前端直接打开 `url` 即可下载；列与 2.5.1 列表一致：时间 / 视频 / 编号 / 猜测 / 看完答题 / 正确率 / 结果。

## 2.6 GYZ-数据统计模块（对应【数据统计】页面）

> 页面构成：①顶部 5 张指标卡（视频 / 答题次数 / 答题前正确率 / 答题后正确率 / 通过率）；②「各视频答题情况」表（视频含分类标签、状态、答题次数、答题前正确率、答题后正确率、通过率+进度条）；③「最容易答错的题」表（题干、所属视频、环节、作答次数、错误率，按错误率从高到低，副标题「可以用来调整讲解重点」）；④右上角「导出统计」按钮。
>
> 页面副标题：只看三件事：有多少人答了、答得怎么样、哪道题最容易错。

### 2.6.1 统计大盘（5 张指标卡）
- 路径：`/api/admin/statistics/overview`
- Method：GET

```json
{
    "video_total": 4,
    "video_online": 3,
    "answer_total_times": 37,
    "avg_accuracy": 70,
    "pre_avg_accuracy": 65,
    "post_avg_accuracy": 70,
    "pass_rate": 78,
    "pass_score": 60
}
```

| 字段               | 类型 | 对应卡片                     | 备注                                                       |
| ------------------ | ---- | ---------------------------- | ---------------------------------------------------------- |
| video_total        | int  | 视频「4 条」                 | 全部视频数（含下架）                                       |
| video_online       | int  | 视频副文「其中已上架 3 条」  | 上架视频数                                                 |
| answer_total_times | int  | 答题次数「37 次」            | 用户提交的答题记录合计（exam_record 计数）                 |
| avg_accuracy       | int  | 平均正确率「70%」            | 兼容字段，历史口径（即答题后正确率），供前端未改版时显示   |
| pre_avg_accuracy   | int  | 答题前正确率「65%」          | 学前答题（前测）得分均值，取自 exam_record.pretest_score  |
| post_avg_accuracy  | int  | 答题后正确率「70%」          | 学后答题（后测）得分均值，取自 exam_record.posttest_score |
| pass_rate          | int  | 通过率「78%」                | 通过记录数 / 答题记录总数 × 100                            |
| pass_score         | int  | 通过率副文「达到 60% 视为通过」 | 通过线，取自 system_config，默认 60                     |

### 2.6.2 各视频答题情况
- 路径：`/api/admin/statistics/video-list`
- Method：GET
- 返回全量数组（页面无分页/筛选），对应「视频 / 状态 / 答题次数 / 答题前正确率 / 答题后正确率 / 通过率」列：

```json
[
    {
        "video_id": 1,
        "title": "心肺复苏基础操作",
        "category_name": "急救技能",
        "status": 1,
        "answer_times": 12,
        "avg_accuracy": 64,
        "pre_avg_accuracy": 60,
        "post_avg_accuracy": 64,
        "pass_rate": 67
    },
    {
        "video_id": 2,
        "title": "高血压患者的日常管理",
        "category_name": "慢病管理",
        "status": 1,
        "answer_times": 12,
        "avg_accuracy": 67,
        "pre_avg_accuracy": 63,
        "post_avg_accuracy": 67,
        "pass_rate": 75
    },
    {
        "video_id": 3,
        "title": "胸片检查前的准备与配合",
        "category_name": "影像检查",
        "status": 1,
        "answer_times": 13,
        "avg_accuracy": 80,
        "pre_avg_accuracy": 77,
        "post_avg_accuracy": 80,
        "pass_rate": 92
    },
    {
        "video_id": 4,
        "title": "儿童疫苗接种后不良反应识别",
        "category_name": "妇幼保健",
        "status": 0,
        "answer_times": 0,
        "avg_accuracy": null,
        "pre_avg_accuracy": null,
        "post_avg_accuracy": null,
        "pass_rate": null
    }
]
```

| 字段                | 类型     | 备注                                                       |
| ------------------- | -------- | ---------------------------------------------------------- |
| title               | string   | 「视频」列，前端在标题旁展示分类标签（category_name）      |
| status              | tinyint  | 「状态」列：1 已上架（绿点）/ 0 已下架（灰点）             |
| answer_times        | int      | 「答题次数」列（exam_record 计数）                         |
| avg_accuracy        | int/null | 「平均正确率」兼容列（即答题后正确率，历史口径）；无人答题为 null |
| pre_avg_accuracy    | int/null | 「答题前正确率」列；无人答题时为 null，前端显示「—」       |
| post_avg_accuracy   | int/null | 「答题后正确率」列；无人答题时为 null，前端显示「—」       |
| pass_rate           | int/null | 「通过率」列，前端附进度条（颜色按高低区分）；无人答题为 null |

### 2.6.3 最容易答错的题
- 路径：`/api/admin/statistics/wrong-question`
- Method：GET
- 返回：按错误率从高到低排序，对应「题干 / 所属视频 / 环节 / 作答次数 / 错误率」列：

```json
[
    {
        "question_id": 101,
        "stem": "成人心肺复苏时，胸外按压的深度应为多少？",
        "video_title": "心肺复苏基础操作",
        "stage": "pretest",
        "answer_times": 12,
        "wrong_rate": 58
    },
    {
        "question_id": 205,
        "stem": "胸片可以完全代替胸部 CT 用于所有肺部疾病的诊断。",
        "video_title": "胸片检查前的准备与配合",
        "stage": "pretest",
        "answer_times": 13,
        "wrong_rate": 54
    }
]
```

| 字段         | 类型   | 备注                                                     |
| ------------ | ------ | -------------------------------------------------------- |
| stem         | string | 「题干」列                                               |
| video_title  | string | 「所属视频」列                                           |
| stage        | string | 「环节」列：pretest 前测 / posttest 后测                 |
| answer_times | int    | 「作答次数」列（exam_answer 计数）                       |
| wrong_rate   | int    | 「错误率」列：答错次数 / 作答次数 × 100，降序排列        |

### 2.6.4 导出统计（「导出统计」按钮）
- 路径：`/api/admin/statistics/export`
- Method：GET
- 返回 Excel 文件流，内容包含：大盘指标（5 项指标，含答题前/答题后正确率）+ 各视频答题情况表（拆为答题前/答题后正确率两列）+ 最容易答错的题表。

---

## 2.7 WJC-分类管理模块（编辑视频弹窗「分类」下拉）

### 2.7.1 分类列表
- 路径：`GET /api/admin/category/list`
- Method：GET
- 返回全量列表（不分页，下拉用）：

```json
[
    { "id": 1, "name": "急救技能", "icon": "", "sort_order": 1, "status": 1 }
]
```

### 2.7.2 新增分类
- 路径：`POST /api/admin/category/create`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| name | string | 是 | 分类名称：急救技能/慢病管理/影像检查/妇幼保健 |
| icon | string | 否 | 分类图标 URL |
| sort_order | int | 否 | 排序号 |
| status | tinyint | 否 | 0禁用 1启用，默认 1 |

### 2.7.3 编辑分类
- 路径：`POST /api/admin/category/update`
- Method：POST
- 参数同新增，**增加 id（int，必填）**。

### 2.7.4 删除分类
- 路径：`POST /api/admin/category/delete`
- Method：POST

| 参数 | 类型 | 是否必须 |
| ---- | ---- | ---- |
| id | int | 是 |

> 说明：软删除；存在关联视频（video.category_id）时不允许删除。



## 2.8 GYZ-批量导入任务模块

### 2.8.1 任务列表
- 路径：`GET /api/admin/import/task-list`
- Method：GET

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| page | int | 否 | 默认 1 |
| page_size | int | 否 | 默认 20 |
| status | tinyint | 否 | 状态筛选 |

返回：

```json
{
    "list": [
        {
            "id": 1,
            "task_no": "imp_20261019_001",
            "file_name": "题目.xlsx",
            "total_rows": 30,
            "valid_rows": 28,
            "error_rows": 2,
            "status": 4,
            "created_at": "2026-10-19 10:00:00",
            "completed_at": "2026-10-19 10:01:12"
        }
    ],
    "total": 1
}
```

| status | 说明 |
| ---- | ---- |
| 0 | 待校验 |
| 1 | 校验中 |
| 2 | 待确认 |
| 3 | 导入中 |
| 4 | 已完成 |
| 5 | 失败 |

### 2.8.2 任务详情（校验结果与错误报告）
- 路径：`GET /api/admin/import/task-detail`
- Method：GET

| 参数 | 类型 | 是否必须 |
| ---- | ---- | ---- |
| task_id | bigint | 是 |

返回：任务基础字段（同 2.10.1 列表项）+ 错误报告：

```json
{
    "id": 1,
    "task_no": "imp_20261019_001",
    "file_name": "题目.xlsx",
    "total_rows": 30,
    "valid_rows": 28,
    "error_rows": 2,
    "status": 2,
    "error_report": [
        { "row": 15, "sheet": "题目", "message": "正确答案格式错误" },
        { "row": 22, "sheet": "题目", "message": "题干超过300字" }
    ],
    "created_at": "2026-10-19 10:00:00"
}
```

### 2.8.3 确认导入
- 路径：`POST /api/admin/import/confirm`
- Method：POST

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| task_id | bigint | 是 | 仅状态=2（待确认）可执行 |

返回：`{ "success": true }`

> 说明：执行后任务进入「3 导入中」，写入 question 与 video_question（G4：Excel 一次导入课程+视频+图文+前测3+后测3），完成转为「4 已完成」，异常转为「5 失败」；仅有效行（valid_rows）入库。

## 2.9 GYZ-操作日志模块（operation_log，后台留痕）

### 2.9.1 操作日志列表
- 路径：`GET /api/admin/operation-log/list`
- Method：GET

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| page | int | 否 | 默认 1 |
| page_size | int | 否 | 默认 20 |
| module | string | 否 | video/question/exam/category/share/admin_user/topic |
| action | string | 否 | create/update/delete/import/export/generate_link |
| admin_id | bigint | 否 | 操作人筛选 |

返回：

```json
{
    "list": [
        {
            "id": 1,
            "admin_id": 1,
            "admin_name": "系统管理员",
            "module": "video",
            "action": "create",
            "target_id": 1,
            "detail": "{ \"title\": \"心肺复苏基础操作\" }",
            "ip": "127.0.0.1",
            "created_at": "2026-10-19 10:00:00"
        }
    ],
    "total": 1
}
```

---

# 伏明月-用户端接口（匿名免登录，仅传 dr_id）

> 页面流程：①首页视频列表（3.2，免登录、无需 dr_id）→ ②点击视频签发 dr_id（3.1，每次点击生成新的匿名编号）→ ③学前答题（3.6 取题 / 3.5 提交）→ ④观看视频（3.3 详情 + 3.4 进度上报，观看进度 ≥ 90% 解锁后测）→ ⑤看完答题（3.6 取题 / 3.7 提交）→ ⑥答题结果（3.8）。

## 3.1 获取匿名 DR 编号（点击视频进入时生成访问记录）

- 路径：`GET /api/public/dr/create`
- Method：GET
- 对应页面：首页视频列表——**用户每次点击一条视频数据时调用**，后台生成一条 `visit_record`（记录该视频与进入时间）并顺序签发 `dr_id`；不依赖 openid，也无需访问链接 token。
- 业务规则：**每次点击均生成新的 dr_id**，同一用户重复点击同一视频也视为一次新的匿名访问（学习进度随新的 dr_id 重新开始）；dr_id 仅对签发时的视频有效，其余接口会校验 dr_id 与 video_id 的匹配关系。

| 请求参数 | 类型   | 是否必须 | 备注          |
| -------- | ------ | -------- | ------------- |
| video_id | bigint | 是       | 点击的视频 ID |

返回示例：

```json
{ "dr_id": "DR-2026-000003" }
```

错误情形：

| 场景 | 返回 |
| ---- | ---- |
| video_id 不存在 | 404 视频不存在 |
| 视频已下架（status=0） | 422 视频不存在或已下架 |

## 3.2 用户端首页视频列表（首页卡片）

- 路径：`GET /api/public/video/list`
- Method：GET
- 对应页面：首页「医学常识，讲给您听」，每个视频一张卡片（封面图标+播放按钮、标题、分类标签、主讲人、简介）。**免登录、无需 dr_id**：进入首页即调用，展示全部已上架视频；dr_id 在用户点击某条视频时才通过 3.1 生成，列表阶段为纯内容目录，不含个人学习状态。

无请求参数。

返回：

```json
[
    {
        "id": 1,
        "title": "心肺复苏基础操作",
        "category_name": "急救技能",
        "speaker": "郭军",
        "brief_intro": "成人基础生命支持的规范动作：按压位置、深度、频率与通气比例，以及基层最常见的三个操作误区。",
        "cover_url": ""
    },
    {
        "id": 2,
        "title": "高血压患者的日常管理",
        "category_name": "慢病管理",
        "speaker": "王雅琴",
        "brief_intro": "家庭血压怎么测才准？生活方式干预该抓哪几条？一条视频讲清居民最常问的问题。",
        "cover_url": ""
    }
]
```

| 字段           | 类型   | 备注                                                         |
| -------------- | ------ | ------------------------------------------------------------ |
| category_name  | string | 蓝色分类标签（急救技能/慢病管理等）                          |
| speaker        | string | 灰色主讲人标签；未指定时前端显示「佚名」或隐藏               |
| brief_intro    | string | 一句话简介，卡片正文                                         |
| cover_url      | string | 封面图；为空时前端按分类用图标占位                           |

> 底部声明「本平台内容仅供健康科普学习，不构成诊疗建议」为静态文案，无需接口。

## 3.3 视频详情（观看视频页）

- 路径：`GET /api/public/video/detail`
- Method：GET
- 对应页面：内容学习页「观看视频」——16:9 播放器 + 课程信息（标题、分类、主讲人、简介、机构）+ 文字要点（小标题+加粗强调）。

| 参数     | 类型   | 是否必须 | 备注     |
| -------- | ------ | -------- | -------- |
| dr_id    | string | 是       | 匿名编号 |
| video_id | bigint | 是       | 视频 ID  |

```json
{
    "id": 1,
    "title": "心肺复苏基础操作",
    "category_name": "急救技能",
    "speaker": "郭军",
    "speaker_title": "主任医师",
    "speaker_org": "四川大学华西医院",
    "brief_intro": "成人基础生命支持的规范动作：按压位置、深度、频率与通气比例，以及基层最常见的三个操作误区。",
    "text_key_points": "【按压位置】让患者仰卧于坚硬平面...【按压深度与频率】成人按压深度**至少 5 厘米、不超过 6 厘米**；频率保持 **100 ~ 120 次/分**...",
    "video_url": "播放地址",
    "cover_url": "",
    "duration": 43,
    "exam_state": {
        "pre_submitted": 1,
        "post_submitted": 0,
        "exam_record_id": null
    },
    "watch_progress": {
        "watched_seconds": 39,
        "progress_percent": 91,
        "watch_required_percent": 90,
        "is_unlocked": 1
    }
}
```

| 字段               | 类型   | 备注                                                         |
| ------------------ | ------ | ------------------------------------------------------------ |
| speaker            | string | 主讲人，页面展示「郭军 · 主任医师」                          |
| speaker_org        | string | 主讲人单位（机构来源行）                                     |
| text_key_points    | text   | 文字要点：段落前用【小标题】，`**文字**` 表示加粗强调        |
| video_url / duration | -    | 播放地址（秒）；播放器展示时间「0:00 / 0:43」                |

| exam_state 字段 | 类型        | 备注                               |
| --------------- | ----------- | ---------------------------------- |
| pre_submitted   | tinyint     | 是否已完成学前答题 0/1             |
| post_submitted  | tinyint     | 是否已完成看完答题 0/1             |
| exam_record_id  | bigint/null | 已完成时返回答题记录 ID，否则 null |

| watch_progress 字段      | 类型    | 备注                                                                  |
| ------------------------ | ------- | --------------------------------------------------------------------- |
| watched_seconds          | int     | 已观看时长（秒），由 3.4 进度上报累计                                   |
| progress_percent         | int     | 观看进度百分比（0-100，后端按 已看时长/总时长 计算）                    |
| watch_required_percent   | int     | 后测解锁门槛（默认 90，取自 system_config.watch_required_percent）    |
| is_unlocked              | tinyint | 是否已达门槛可提交后测 0/1（progress_percent ≥ watch_required_percent）|

> 说明：
>
> - 题目不在本接口返回，答题页统一通过 3.6 获取（规则 R2：作答阶段不返回 `correct_answer` / `analysis`）。
> - 追踪观看进度：观看过程中通过 3.4 上报进度，**观看进度达到 90%（watch_required_percent）后才能提交看完答题（3.7）**；学前答题（3.5）无需观看视频。



## 3.4 上报视频观看进度（观看视频页，新增）

- 路径：`POST /api/public/video/progress`
- Method：POST
- 对应页面：内容学习页播放器——播放/拖动进度条时由前端按秒（或定时）心跳上报已观看位置；服务端取历史与本次最大值累计，计算观看进度百分比。
- 业务规则：**观看进度达到 90%（system_config.watch_required_percent，默认 90）后，才允许提交看完答题（3.7）**；未达门槛提交后测返回 422。

| 参数            | 类型   | 是否必须 | 备注                                                                 |
| --------------- | ------ | -------- | -------------------------------------------------------------------- |
| dr_id           | string | 是       | 匿名编号                                                             |
| video_id        | bigint | 是       | 视频 ID                                                              |
| watched_seconds | int    | 是       | 已观看时长（秒，取播放器当前最大观看位置）                            |
| duration        | int    | 否       | 视频总时长（秒）；服务端 video.duration 未回填（如填写链接）时用于计算 |

返回：

```json
{
    "watched_seconds": 39,
    "duration": 43,
    "progress_percent": 91,
    "watch_required_percent": 90,
    "is_unlocked": 1
}
```

| 字段                   | 类型    | 备注                                       |
| ---------------------- | ------- | ------------------------------------------ |
| watched_seconds        | int     | 累计已观看时长（秒，取历史与本次上报最大值）|
| duration               | int     | 视频总时长快照（秒）                       |
| progress_percent       | int     | 观看进度百分比（0-100）                    |
| watch_required_percent | int     | 后测解锁门槛百分比                         |
| is_unlocked            | tinyint | 是否已达门槛：0未达成 1已达成              |

## 3.5 提交学前答题（学前答题页）

- 路径：`POST /api/public/exam/submit-pre`
- Method：POST
- 对应页面：「学前答题」——顶部提示卡「先答 N 道题，看看您的基础认知」+《视频标题》· 提交后可以看到每道题的对错与解析；进度条与题号导航（可跳题）、逐题选择、「下一题 >」翻页、末题为「提交」。
- 业务规则：**前测无需观看视频，可直接作答**；提交后仅将学前答案暂存到 `exam_pretest` 表（按 dr_id+视频，重复提交覆盖），**不生成 exam_record**，等待后测提交时合并写入完整记录。
- **不返回正确答案与解析**（规则 R2）。

| 参数     | 类型   | 是否必须 | 备注                                           |
| -------- | ------ | -------- | ---------------------------------------------- |
| dr_id    | string | 是       | 匿名编号                                       |
| video_id | bigint | 是       | 视频 ID                                        |
| answers  | array  | 是       | `[{ "question_id": 101, "user_answer": "C", "duration": 25 }]`（duration 该题用时秒，可选） |

返回：

```json
{
    "submitted": 1,
    "pre_total": 3,
    "pre_answered": 3
}
```

> 学前正确率不在本接口返回，统一在「看完答题」提交后（3.7）与「答题结果」页（3.8）中展示。

## 3.6 获取视频题目列表（学前/看完答题页共用）

注：前测和后测是同一套题目（question_total 即本套题目数）。

- 路径：`GET /api/public/video/question-list`
- Method：GET
- 对应页面：学前答题页（图 3.4）与看完答题页（图 3.6）共用本题组；前端按 `type` 渲染题型标签与交互（单选「单项选择」/多选「多项选择」/判断）。

| 参数     | 类型   | 是否必须 | 备注    |
| -------- | ------ | -------- | ------- |
| video_id | bigint | 是       | 视频 ID |

返回：

```json
[
    {
        "question_id": 101,
        "type": "single",
        "stem": "成人基础生命支持中，按压与通气的比例应为？",
        "image_url": "",
        "options": [
            { "key": "A", "content": "15:2" },
            { "key": "B", "content": "30:2" }
        ]
    }
]
```

> 说明：不返回 `correct_answer` / `analysis`（规则 R2）；题目排序即作答顺序（题号导航 1/2/3）。



## 3.7 提交看完答题（后测，看完答题页，新增）

- 路径：`POST /api/public/exam/submit-post`
- Method：POST
- 对应页面：「看完答题」——与学前答题同一套题目（3.6 取题），提交后进入「答题结果」页。
- 业务规则：
  - **观看进度门槛**：观看进度（3.4）需达到 `watch_required_percent`（默认 90%）才能提交，否则返回 422「观看进度达到 90% 后才能作答看完答题（当前 X%）」；
  - **需先完成学前答题**（3.5，答案暂存于 `exam_pretest`），否则返回 422「请先完成学前答题」；
  - 提交后合并学前暂存答案与本次后测答案，生成完整 `exam_record` + `exam_answer`（前后测各一份明细），并清除学前暂存记录；
  - 未通过可重练（R5）：再次走 学前答题（3.5）→ 看完答题（3.7）流程，`attempt_no` 按 dr_id+视频 递增。

| 参数     | 类型   | 是否必须 | 备注                                           |
| -------- | ------ | -------- | ---------------------------------------------- |
| dr_id    | string | 是       | 匿名编号                                       |
| video_id | bigint | 是       | 视频 ID                                        |
| answers  | array  | 是       | 同 3.5，后测作答数组                            |

返回：结构与「3.8 获取答题结果」一致（含 `result_status` / `accuracy_rate` / `pre` / `post` / `question_detail`）。

## 3.8 获取答题结果（答题结果页）

- 路径：`GET /api/public/exam/result`
- Method：GET
- 对应页面：「答题结果」页——①顶部结果卡：「再复习一下？」重练入口（R5 未通过可重答）、《视频标题》· 答对 60% 以上才算通过（pass_accuracy）、大字号得分 `post.correct / post.total 题`、正确率、阶段切换按钮「学前答题 3/3 → 看完答题 0/3」；②「逐题解析」区：题号 + 答对/答错标签 + 题型 + 题干 + 选项列表（用户选择标红、正确答案标绿）+ 解析框。
- 用途：结果页首次进入、刷新或从列表回看时获取完整结果（数据与 3.7 提交看完答题返回结构一致）。

| 参数           | 类型   | 是否必须 | 备注                                  |
| -------------- | ------ | -------- | ------------------------------------- |
| dr_id          | string | 是       | 匿名编号                              |
| video_id       | bigint | 否       | 与 `exam_record_id` 二选一            |
| exam_record_id | bigint | 否       | 与 `video_id` 二选一，优先于 video_id |

```json
{
    "exam_record_id": 100,
    "video_id": 1,
    "video_title": "心肺复苏基础操作",
    "result_status": "fail",
    "pass_accuracy": 60,
    "accuracy_rate": 33,
    "improved_count": 0,
    "pre": { "correct": 1, "total": 3, "accuracy_rate": 33 },
    "post": { "correct": 1, "total": 3, "accuracy_rate": 33 },
    "question_detail": [
        {
            "question_id": 101,
            "type": "single",
            "stem": "成人基础生命支持中，按压与通气的比例应为？",
            "options": [
                { "key": "A", "content": "15:2" },
                { "key": "B", "content": "30:2" },
                { "key": "C", "content": "5:1" },
                { "key": "D", "content": "10:1" }
            ],
            "correct_answer": "B",
            "analysis": "成人基础生命支持推荐按压与通气比例为 30:2。",
            "pre_user_answer": "C",
            "pre_is_correct": 0,
            "post_user_answer": "C",
            "post_is_correct": 0,
            "improved": 0
        }
    ]
}
```

字段说明：

| 字段                               | 类型    | 备注                                                         |
| ---------------------------------- | ------- | ------------------------------------------------------------ |
| result_status                      | string  | `pass` 通过 / `fail` 未通过，由 `accuracy_rate ≥ pass_accuracy` 判定 |
| pass_accuracy                      | int     | 通过线（百分比），默认 60，取自后台配置                      |
| accuracy_rate                      | int     | **看完答题（后测）正确率** = `post.correct / post.total × 100`（四舍五入）； |
| improved_count                     | int     | 学后相比学前提升的题数（学前答错 → 学后答对）                |
| pre / post                         | object  | 分别返回学前、学后答对题数、总题数与正确率（仅作过程展示，不参与判定） |
| question_detail[].pre_user_answer  | string  | 学前答案                                                     |
| question_detail[].post_user_answer | string  | 学后答案                                                     |
| question_detail[].improved         | tinyint | 该题是否「学前错 → 学后对」                                  |



---

# 附录：接口与数据表对照

| 模块 | 接口 | 主要读写表 |
| --- | --- | --- |
| 管理员登录 | 2.1 | `admin_user` |
| 管理员用户管理 | 2.2 | `admin_user` |
| 视频管理（列表/详情/增删改/上架切换） | 2.3.1~2.3.6 | `video`、`category`、`video_question`、`exam_record` |
| 视频上传/转码 | 2.3.7、2.3.8 | `video`（oss_upload_id、transcode_status、cover_url 等） |
| 批量导入 | 2.4.5、2.10 | `import_task`、`question`、`video_question` |
| 题目配置（抽屉/新增题目弹窗） | 2.4 | `question`、`video_question` |
| 答题记录管理 | 2.5 | `exam_record`、`exam_answer`、`question` |
| 数据统计 | 2.6 | `exam_record`、`exam_answer`、`video`、`system_config` |
| 分类管理 | 2.7 | `category`、`video` |
| 题库管理 | 2.8 | `question`、`video_question` |
| 课程管理 | 2.9 | `topic`、`video` |
| 操作日志 | 2.11 | `operation_log` |
| 用户端入口 | 3.1 | `visit_record`（点击视频时绑定video_id并签发dr_id）、`video` |
| 用户端视频/题目 | 3.2、3.3、3.6 | `video`、`category`、`video_question`、`question` |
| 观看进度 | 3.4 | `video_progress`（进度上报、90%门槛计算） |
| 学前/学后答题 | 3.5、3.7、3.8 | `exam_pretest`（学前暂存）、`exam_record`、`exam_answer`、`question`、`video_progress`（后测门槛校验） |
