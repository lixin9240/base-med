<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use Exception;

/**
 * 业务异常
 *
 * 用于 Service 层主动抛出业务错误，由全局异常处理器统一转换为接口响应。
 * 默认使用 BUSINESS_ERROR（40001），可指定其他 ResponseCode。
 */
class BusinessException extends Exception
{
    /**
     * 业务错误码
     */
    public readonly ResponseCode $codeEnum;

    /**
     * @param ResponseCode|null $code 错误码枚举，为空时默认 BUSINESS_ERROR
     * @param string|null $message 自定义错误消息，为空时使用错误码默认提示
     */
    public function __construct(
        ?ResponseCode $code = null,
        ?string $message = null
    ) {
        $this->codeEnum = $code ?? ResponseCode::BUSINESS_ERROR;

        parent::__construct($message ?? $this->codeEnum->msg());
    }
}
