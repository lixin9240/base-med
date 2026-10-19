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
    /**
     * 成功
     */
    case SUCCESS = 0;

    // ==================== 通用响应码 ====================

    /**
     * 系统异常（未知异常）
     */
    case COMMON_SYSTEM_ERROR = 1;

    /**
     * 数据不存在
     */
    case COMMON_DATA_NOT_FOUND = 2;

    /**
     * 数据已存在
     */
    case COMMON_DATA_EXISTS = 3;

    /**
     * 操作失败（通用失败）
     */
    case COMMON_OPERATION_FAILED = 4;

    /**
     * 禁止操作
     */
    case COMMON_FORBIDDEN = 5;

    /**
     * 请求过于频繁（限流）
     */
    case COMMON_TOO_MANY_REQUESTS = 6;

    /**
     * 服务暂不可用（系统维护）
     */
    case COMMON_SERVICE_UNAVAILABLE = 7;

    /**
     * 网络异常（第三方服务异常）
     */
    case COMMON_NETWORK_ERROR = 8;

    /**
     * 并发冲突（乐观锁冲突）
     */
    case COMMON_CONFLICT = 9;

    // ==================== 参数相关（10000 段） ====================

    /**
     * 参数错误
     */
    case PARAM_ERROR = 10001;

    /**
     * 必填参数缺失
     */
    case PARAM_MISSING = 10002;

    /**
     * 参数格式错误
     */
    case PARAM_FORMAT_ERROR = 10003;

    /**
     * 参数超出范围
     */
    case PARAM_OUT_OF_RANGE = 10004;

    /**
     * 非法参数
     */
    case PARAM_INVALID = 10005;

    /**
     * 文件格式错误
     */
    case FILE_FORMAT_ERROR = 10006;

    /**
     * 文件过大
     */
    case FILE_TOO_LARGE = 10007;

    /**
     * 上传失败
     */
    case FILE_UPLOAD_FAILED = 10008;

    // ==================== 认证授权（20000 段） ====================

    /**
     * 未登录
     */
    case UNAUTHORIZED = 20001;

    /**
     * Token 失效
     */
    case TOKEN_EXPIRED = 20002;

    /**
     * Token 错误
     */
    case TOKEN_INVALID = 20003;

    /**
     * 登录已过期
     */
    case LOGIN_EXPIRED = 20004;

    /**
     * 无访问权限
     */
    case PERMISSION_DENIED = 20005;

    /**
     * 账号被禁用
     */
    case ACCOUNT_DISABLED = 20006;

    /**
     * 账号被冻结
     */
    case ACCOUNT_FROZEN = 20007;

    /**
     * 密码错误
     */
    case PASSWORD_ERROR = 20008;

    // ==================== 数据相关（30000 段） ====================

    /**
     * 数据不存在
     */
    case DATA_NOT_FOUND = 30001;

    /**
     * 数据已删除
     */
    case DATA_DELETED = 30002;

    /**
     * 数据重复
     */
    case DATA_DUPLICATE = 30003;

    /**
     * 数据状态异常
     */
    case DATA_STATUS_ERROR = 30004;

    /**
     * 数据已锁定
     */
    case DATA_LOCKED = 30005;

    /**
     * 数据关联存在
     */
    case DATA_RELATION_EXISTS = 30006;

    /**
     * 数据校验失败
     */
    case DATA_VALIDATION_FAILED = 30007;

    /**
     * 数据版本冲突
     */
    case DATA_VERSION_CONFLICT = 30008;

    // ==================== 业务相关（40000 段） ====================

    /**
     * 操作失败
     */
    case BUSINESS_ERROR = 40001;

    /**
     * 当前状态不可操作
     */
    case STATUS_NOT_OPERABLE = 40002;

    /**
     * 审批未通过
     */
    case APPROVAL_FAILED = 40003;

    /**
     * 库存不足
     */
    case STOCK_NOT_ENOUGH = 40004;

    /**
     * 金额超限
     */
    case AMOUNT_EXCEED_LIMIT = 40005;

    /**
     * 超出配额
     */
    case QUOTA_EXCEEDED = 40006;

    /**
     * 已提交审核
     */
    case SUBMITTED_FOR_REVIEW = 40007;

    /**
     * 已完成不可修改
     */
    case COMPLETED_NOT_MODIFIABLE = 40008;

    /**
     * 重复提交
     */
    case DUPLICATE_SUBMIT = 40009;

    /**
     * 超出业务规则限制
     */
    case RULE_LIMIT_EXCEEDED = 40010;

    // ==================== 第三方服务（50000 段） ====================

    /**
     * 微信接口异常
     */
    case WECHAT_ERROR = 50001;

    /**
     * 支付宝接口异常
     */
    case ALIPAY_ERROR = 50002;

    /**
     * 短信发送失败
     */
    case SMS_SEND_FAILED = 50003;

    /**
     * 邮件发送失败
     */
    case MAIL_SEND_FAILED = 50004;

    /**
     * OSS 上传失败
     */
    case OSS_UPLOAD_FAILED = 50005;

    /**
     * Redis 连接失败
     */
    case REDIS_CONNECTION_FAILED = 50006;

    /**
     * MQ 消息发送失败
     */
    case MQ_SEND_FAILED = 50007;

    /**
     * 第三方接口超时
     */
    case THIRD_PARTY_TIMEOUT = 50008;

    // ==================== 数据库异常（60000 段） ====================

    /**
     * 数据库异常
     */
    case DATABASE_ERROR = 60001;

    /**
     * SQL 执行失败
     */
    case SQL_EXECUTION_FAILED = 60002;

    /**
     * 事务提交失败
     */
    case DB_TRANSACTION_COMMIT_FAILED = 60003;

    /**
     * 事务回滚
     */
    case DB_TRANSACTION_ROLLBACK = 60004;

    /**
     * 唯一索引冲突
     */
    case DB_UNIQUE_CONFLICT = 60005;

    /**
     * 外键约束失败
     */
    case DB_FOREIGN_KEY_FAILED = 60006;

    /**
     * 死锁异常
     */
    case DB_DEADLOCK = 60007;

    /**
     * 数据库超时
     */
    case DB_TIMEOUT = 60008;

    // ==================== 系统异常（90000 段） ====================

    /**
     * 系统异常
     */
    case SYSTEM_ERROR = 90001;

    /**
     * 未知错误
     */
    case SYSTEM_UNKNOWN_ERROR = 90002;

    /**
     * 程序运行异常
     */
    case SYSTEM_RUNTIME_ERROR = 90003;

    /**
     * 服务繁忙
     */
    case SYSTEM_BUSY = 90004;

    /**
     * 系统维护中
     */
    case SYSTEM_MAINTENANCE = 90005;

    /**
     * 配置错误
     */
    case SYSTEM_CONFIG_ERROR = 90006;

    /**
     * 文件读写失败
     */
    case SYSTEM_FILE_RW_FAILED = 90007;

    /**
     * 服务器内部错误
     */
    case SYSTEM_SERVER_ERROR = 90008;

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