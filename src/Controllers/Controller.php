<?php

namespace Thinkrix\Controllers;

/**
 * Controller - 控制器基类
 *
 * ThinkPHP 版本的基类
 */
abstract class Controller
{
    /**
     * 获取当前认证用户
     */
    protected function getUser()
    {
        return request()->thinkrix_user ?? null;
    }

    /**
     * 验证请求数据
     */
    protected function validate(array $data, array $rules, array $messages = []): array
    {
        $validate = new \think\Validate();
        $validate->rule($rules)->message($messages);

        if (!$validate->check($data)) {
            error($validate->getError(), null, 40022);
        }

        return array_intersect_key($data, $rules);
    }

    /**
     * 获取请求参数
     */
    protected function param(string $name = '', $default = null)
    {
        return request()->param($name, $default);
    }

    /**
     * 获取请求参数（仅 POST/PUT）
     */
    protected function post(string $name = '', $default = null)
    {
        return request()->post($name, $default);
    }

    /**
     * 获取查询参数（GET）
     */
    protected function get(string $name = '', $default = null)
    {
        return request()->get($name, $default);
    }

    /**
     * 获取请求输入（自动判断来源）
     */
    protected function input(string $name = '', $default = null)
    {
        return request()->param($name, $default);
    }
}
