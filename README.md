# base-med

Vue 3 + Vite 项目骨架：管理端 + 用户端（Laravel 后端）。

```sh
npm install
npm run dev
```

## 项目结构

> 依据《前端开发规范与最佳实践》13.1：项目必须维护文档结构脑图（Markdown 目录树形式），**每个迭代结束前更新一次**；各模块负责人待标注。

```text
base-med/
├── index.html                # 入口 HTML
├── vite.config.js            # Vite 配置（@ 别名指向 src）
├── eslint.config.js          # ESLint 规则
├── package.json              # 依赖与脚本
├── src/                      # ── 前端（Vue 3 组合式 API）──
│   ├── main.js               # 应用入口：挂载 App、注册路由与状态
│   ├── App.vue               # 根组件
│   ├── api/                  # 请求层：http.js 封装 request/get/post/put/del，index.js 统一出口
│   ├── layouts/              # 布局：AdminLayout（管理后台）/ MobileLayout（用户端 H5）
│   ├── router/               # 路由：/admin 与 /mobile 路由表
│   ├── store/                # 状态层（Pinia）：index.js 全局状态，video/ 业务模块
│   ├── views/                # 页面层（一个路由对应一个页面）
│   │   ├── admin/            # 管理后台：login / videos / records / stats / users
│   │   └── mobile/           # 用户端 H5：home / quiz / learn / consolidate / report
│   ├── components/           # 可复用组件：admin/（侧栏、弹窗等）、mobile/（QuizPanel 等）
│   ├── utils/                # 工具层：toast.js、question.js、video/（按域拆分）
│   ├── styles/               # 样式层：tokens.css 设计变量、global.css、mobile.css
│   ├── data/                 # 静态种子数据：seed.json
│   └── mock/                 # 演示数据（后端未接入前使用）
├── Backend/                  # ── 后端（Laravel，遵循《后端代码开发规则手册》分层）──
│   ├── app/Http/Requests/    # Request 层：参数验证
│   ├── app/Http/Controllers/ # Controller 层：接口控制
│   ├── app/Services/         # Service 层：业务逻辑
│   ├── app/Models/           # Model 层：数据访问
│   ├── app/Enums/            # ResponseCode 枚举
│   ├── app/Exceptions/       # BusinessException / Handler 统一异常
│   ├── app/Support/          # Result 统一响应类
│   ├── routes/               # 路由（api.php）
│   └── database/             # migrations / seeders / factories
└── docs/                     # ── 文档（依据《开发手册》）──
    ├── conclusion/           # 接口文档（总对接全量 + 修改增量）
    ├── 代码修改记录/          # YYYY-MM-DD.md 变更记录
    ├── 数据库变更记录/        # 按表名命名的变更记录
    └── agents/               # AI 协作配置
```

负责人标注：待各模块负责人补充（每迭代结束前随本节一并核对更新）。
