<?php

namespace App\Enums;

/**
 * 统一响应错误码枚举
 *
 * 所有接口返回 code 均取自本枚举，禁止在代码中直接书写数字。
 * 码段划分：
 *  0 ~ 9     通用响应码
 *  10000 段  参数相关
 *  20000 段  认证授权
 *  30000 段  数据相关
 *  40000 段  业务相关
 *  50000 段  第三方服务
 *  60000 段  数据库异常
 *  90000 段  系统异常
 */
enum ResponseCode: int
{
    // ==================== 通用响应码（0~9） ====================

    case SUCCESS = 0; // 成功
    case COMMON_SYSTEM_ERROR = 1; // 系统异常
    case COMMON_DATA_NOT_FOUND = 2; // 数据不存在
    case COMMON_DATA_EXISTS = 3; // 数据已存在
    case COMMON_OPERATION_FAILED = 4; // 操作失败
    case COMMON_FORBIDDEN = 5; // 禁止操作
    case COMMON_TOO_MANY_REQUESTS = 6; // 请求过于频繁
    case COMMON_SERVICE_UNAVAILABLE = 7; // 服务暂不可用
    case COMMON_NETWORK_ERROR = 8; // 网络异常
    case COMMON_CONFLICT = 9; // 并发冲突

    // ==================== 参数相关（10000 段） ====================

    case PARAM_ERROR = 10001; // 参数错误
    case PARAM_MISSING = 10002; // 必填参数缺失
    case PARAM_FORMAT_ERROR = 10003; // 参数格式错误
    case PARAM_OUT_OF_RANGE = 10004; // 参数超出范围
    case PARAM_INVALID = 10005; // 非法参数
    case FILE_FORMAT_ERROR = 10006; // 文件格式错误
    case FILE_TOO_LARGE = 10007; // 文件过大
    case FILE_UPLOAD_FAILED = 10008; // 上传失败

    // ==================== 认证授权（20000 段） ====================

    case UNAUTHORIZED = 20001; // 未登录
    case TOKEN_EXPIRED = 20002; // Token 失效
    case TOKEN_INVALID = 20003; // Token 错误
    case LOGIN_EXPIRED = 20004; // 登录已过期
    case PERMISSION_DENIED = 20005; // 无访问权限
    case ACCOUNT_DISABLED = 20006; // 账号被禁用
    case ACCOUNT_FROZEN = 20007; // 账号被冻结
    case PASSWORD_ERROR = 20008; // 密码错误

    // ==================== 数据相关（30000 段） ====================

    case DATA_NOT_FOUND = 30001; // 数据不存在
    case DATA_DELETED = 30002; // 数据已删除
    case DATA_DUPLICATE = 30003; // 数据重复
    case DATA_STATUS_ERROR = 30004; // 数据状态异常
    case DATA_LOCKED = 30005; // 数据已锁定
    case DATA_RELATION_EXISTS = 30006; // 数据关联存在
    case DATA_VALIDATION_FAILED = 30007; // 数据校验失败
    case DATA_VERSION_CONFLICT = 30008; // 数据版本冲突

    // ==================== 业务相关（40000 段） ====================

    case BUSINESS_ERROR = 40001; // 操作失败
    case STATUS_NOT_OPERABLE = 40002; // 当前状态不可操作
    case APPROVAL_FAILED = 40003; // 审批未通过
    case STOCK_NOT_ENOUGH = 40004; // 库存不足
    case AMOUNT_EXCEED_LIMIT = 40005; // 金额超限
    case QUOTA_EXCEEDED = 40006; // 超出配额
    case SUBMITTED_FOR_REVIEW = 40007; // 已提交审核
    case COMPLETED_NOT_MODIFIABLE = 40008; // 已完成不可修改
    case DUPLICATE_SUBMIT = 40009; // 重复提交
    case RULE_LIMIT_EXCEEDED = 40010; // 超出业务规则限制

    // ==================== 第三方服务（50000 段） ====================

    case WECHAT_ERROR = 50001; // 微信接口异常
    case ALIPAY_ERROR = 50002; // 支付宝接口异常
    case SMS_SEND_FAILED = 50003; // 短信发送失败
    case MAIL_SEND_FAILED = 50004; // 邮件发送失败
    case OSS_UPLOAD_FAILED = 50005; // OSS 上传失败
    case REDIS_CONNECTION_FAILED = 50006; // Redis 连接失败
    case MQ_SEND_FAILED = 50007; // MQ 消息发送失败
    case THIRD_PARTY_TIMEOUT = 50008; // 第三方接口超时

    // ==================== 数据库异常（60000 段） ====================

    case DATABASE_ERROR = 60001; // 数据库异常
    case SQL_EXECUTION_FAILED = 60002; // SQL 执行失败
    case DB_TRANSACTION_COMMIT_FAILED = 60003; // 事务提交失败
    case DB_TRANSACTION_ROLLBACK = 60004; // 事务回滚
    case DB_UNIQUE_CONFLICT = 60005; // 唯一索引冲突
    case DB_FOREIGN_KEY_FAILED = 60006; // 外键约束失败
    case DB_DEADLOCK = 60007; // 死锁异常
    case DB_TIMEOUT = 60008; // 数据库超时

    // ==================== 系统异常（90000 段） ====================

    case SYSTEM_ERROR = 90001; // 系统异常
    case SYSTEM_UNKNOWN_ERROR = 90002; // 未知错误
    case SYSTEM_RUNTIME_ERROR = 90003; // 程序运行异常
    case SYSTEM_BUSY = 90004; // 服务繁忙
    case SYSTEM_MAINTENANCE = 90005; // 系统维护中
    case SYSTEM_CONFIG_ERROR = 90006; // 配置错误
    case SYSTEM_FILE_RW_FAILED = 90007; // 文件读写失败
    case SYSTEM_SERVER_ERROR = 90008; // 服务器内部错误

    /**
     * 获取错误码对应的默认提示信息
     */
    public function msg(): string
    {
        return match ($this) {
            self::SUCCESS => '成功',

            // 通用响应码
            self::COMMON_SYSTEM_ERROR => '系统异常',
            self::COMMON_DATA_NOT_FOUND => '数据不存在',
            self::COMMON_DATA_EXISTS => '数据已存在',
            self::COMMON_OPERATION_FAILED => '操作失败',
            self::COMMON_FORBIDDEN => '禁止操作',
            self::COMMON_TOO_MANY_REQUESTS => '请求过于频繁',
            self::COMMON_SERVICE_UNAVAILABLE => '服务暂不可用',
            self::COMMON_NETWORK_ERROR => '网络异常',
            self::COMMON_CONFLICT => '并发冲突',

            // 参数相关
            self::PARAM_ERROR => '参数错误',
            self::PARAM_MISSING => '必填参数缺失',
            self::PARAM_FORMAT_ERROR => '参数格式错误',
            self::PARAM_OUT_OF_RANGE => '参数超出范围',
            self::PARAM_INVALID => '非法参数',
            self::FILE_FORMAT_ERROR => '文件格式错误',
            self::FILE_TOO_LARGE => '文件过大',
            self::FILE_UPLOAD_FAILED => '上传失败',

            // 认证授权
            self::UNAUTHORIZED => '未登录',
            self::TOKEN_EXPIRED => 'Token 失效',
            self::TOKEN_INVALID => 'Token 错误',
            self::LOGIN_EXPIRED => '登录已过期',
            self::PERMISSION_DENIED => '无访问权限',
            self::ACCOUNT_DISABLED => '账号被禁用',
            self::ACCOUNT_FROZEN => '账号被冻结',
            self::PASSWORD_ERROR => '密码错误',

            // 数据相关
            self::DATA_NOT_FOUND => '数据不存在',
            self::DATA_DELETED => '数据已删除',
            self::DATA_DUPLICATE => '数据重复',
            self::DATA_STATUS_ERROR => '数据状态异常',
            self::DATA_LOCKED => '数据已锁定',
            self::DATA_RELATION_EXISTS => '数据关联存在',
            self::DATA_VALIDATION_FAILED => '数据校验失败',
            self::DATA_VERSION_CONFLICT => '数据已被他人修改，请刷新后重试',

            // 业务相关
            self::BUSINESS_ERROR => '操作失败',
            self::STATUS_NOT_OPERABLE => '当前状态不可操作',
            self::APPROVAL_FAILED => '审批未通过',
            self::STOCK_NOT_ENOUGH => '库存不足',
            self::AMOUNT_EXCEED_LIMIT => '金额超限',
            self::QUOTA_EXCEEDED => '超出配额',
            self::SUBMITTED_FOR_REVIEW => '已提交审核',
            self::COMPLETED_NOT_MODIFIABLE => '已完成不可修改',
            self::DUPLICATE_SUBMIT => '请勿重复提交',
            self::RULE_LIMIT_EXCEEDED => '超出业务规则限制',

            // 第三方服务
            self::WECHAT_ERROR => '微信接口异常',
            self::ALIPAY_ERROR => '支付宝接口异常',
            self::SMS_SEND_FAILED => '短信发送失败',
            self::MAIL_SEND_FAILED => '邮件发送失败',
            self::OSS_UPLOAD_FAILED => 'OSS 上传失败',
            self::REDIS_CONNECTION_FAILED => 'Redis 连接失败',
            self::MQ_SEND_FAILED => 'MQ 消息发送失败',
            self::THIRD_PARTY_TIMEOUT => '第三方接口超时',

            // 数据库异常
            self::DATABASE_ERROR => '数据库异常',
            self::SQL_EXECUTION_FAILED => 'SQL 执行失败',
            self::DB_TRANSACTION_COMMIT_FAILED => '事务提交失败',
            self::DB_TRANSACTION_ROLLBACK => '事务回滚',
            self::DB_UNIQUE_CONFLICT => '数据重复，请检查后提交',
            self::DB_FOREIGN_KEY_FAILED => '外键约束失败',
            self::DB_DEADLOCK => '死锁异常',
            self::DB_TIMEOUT => '数据库超时',

            // 系统异常
            self::SYSTEM_ERROR => '系统异常，请联系管理员',
            self::SYSTEM_UNKNOWN_ERROR => '未知错误',
            self::SYSTEM_RUNTIME_ERROR => '程序运行异常',
            self::SYSTEM_BUSY => '服务繁忙',
            self::SYSTEM_MAINTENANCE => '系统维护中',
            self::SYSTEM_CONFIG_ERROR => '配置错误',
            self::SYSTEM_FILE_RW_FAILED => '文件读写失败',
            self::SYSTEM_SERVER_ERROR => '服务器内部错误',
        };
    }
}