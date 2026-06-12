<?php

namespace Thinkrix\Exceptions;

use Exception;
use think\Response;

/**
 * API 异常类
 */
class ApiException extends Exception
{
    /**
     * 错误码
     */
    protected int $errorCode;

    /**
     * 附加数据
     */
    protected mixed $data;

    /**
     * 构造函数
     *
     * @param string $message 错误消息
     * @param mixed $data 附加数据
     * @param int $code 错误码
     */
    public function __construct(string $message, mixed $data = null, int $code = 500)
    {
        if (is_int($data) && $code === 500) {
            $code = $data;
            $data = null;
        }

        parent::__construct($message);
        $this->errorCode = $code;
        $this->data = $data;
    }

    /**
     * 获取错误码
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * 获取附加数据
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * 渲染为 JSON 响应
     */
    public function render(): Response
    {
        return json([
            'code' => $this->errorCode,
            'msg' => $this->getMessage(),
            'data' => $this->data,
        ]);
    }
}
