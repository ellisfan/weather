<?php

/*
 * This file is part of the ellisfan/weather.
 *
 * (c) ellisfan <ellisfan07@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EllisFan\Weather;

use GuzzleHttp\Client;
use EllisFan\Weather\Exceptions\HttpException;
use EllisFan\Weather\Exceptions\InvalidArgumentException;

/**
 * Class Weather.
 */
class Weather
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var array
     */
    protected $guzzleOptions = [];

    /**
     * Weather constructor.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    /**
     * @param array $options
     */
    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    /**
     * @param string $from ip|geo|city
     * @param string $value
     * @param string $type base|all
     * @param string $format
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \EllisFan\Weather\Exceptions\HttpException
     * @throws \EllisFan\Weather\Exceptions\InvalidArgumentException
     */
    public function getLiveWeather($from, $value, $type = 'base', $format = 'json')
    {
        $response = [];
        $city = '';

        switch ($from) {
            case 'ip':
                $response = $this->getIpCity($value);
                if (!empty($response['city'])) {
                    $city = $response['city'];
                }
                break;
            case 'geo':
                $response = $this->getGeoCity($value);
                if (!empty($response['regeocode']['addressComponent']['district'])) {
                    $city = $response['regeocode']['addressComponent']['district'];
                } else {
                    $city = $response['regeocode']['addressComponent']['city'];
                }
                break;
            case 'city':
                $city = $value;
                break;
            default:
                throw new InvalidArgumentException('Invalid type: ' . $from);
        }
        if (empty($city)) {
            throw new InvalidArgumentException('Unable to determine city from provided value: ' . $value);
        }
        return $this->getWeather($city, $type, $format);
    }

    /**
     * @param string $ip 仅支持国内IP，非法IP以及国外IP则返回空
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \EllisFan\Weather\Exceptions\HttpException
     * @throws \EllisFan\Weather\Exceptions\InvalidArgumentException
     */
    public function getIpCity($ip)
    {
        $url = 'https://restapi.amap.com/v3/ip';

        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('Invalid IP');
        }

        $query = array_filter([
            'key' => $this->key,
            'ip' => $ip
        ]);

        try {
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            $response = \json_decode($response, true);

            $this->handleResponse($response);

            return $response;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $location 经度在前，纬度在后，经纬度间以“,”分割，经纬度小数点后不要超过 6 位
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \EllisFan\Weather\Exceptions\HttpException
     * @throws \EllisFan\Weather\Exceptions\InvalidArgumentException
     */
    public function getGeoCity($location)
    {
        $url = 'https://restapi.amap.com/v3/geocode/regeo';

        $locationPattern = '/^-?(?:180(?:\.0{1,6})?|(?:[1-9]?\d(?:\.\d{1,6})?|1[0-7]\d(?:\.\d{1,6})?)),\s*-?(?:90(?:\.0{1,6})?|(?:[1-8]?\d(?:\.\d{1,6})?|\d(?:\.\d{1,6})?))$/';

        if (empty($location) || !preg_match($locationPattern, $location)) {
            throw new InvalidArgumentException('Invalid Location');
        }

        $query = array_filter([
            'key' => $this->key,
            'location' => $location
        ]);

        try {
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            $response = \json_decode($response, true);

            $this->handleResponse($response);

            return $response;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $city
     * @param string $type
     * @param string $format
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \EllisFan\Weather\Exceptions\HttpException
     * @throws \EllisFan\Weather\Exceptions\InvalidArgumentException
     */
    public function getWeather($city, $type = 'base', $format = 'json')
    {
        $url = 'https://restapi.amap.com/v3/weather/weatherInfo';

        if (!\in_array(\strtolower($format), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$format);
        }

        if (!\in_array(\strtolower($type), ['base', 'all'])) {
            throw new InvalidArgumentException('Invalid type value(base/all): '.$type);
        }

        $format = \strtolower($format);
        $type = \strtolower($type);

        $query = array_filter([
            'key' => $this->key,
            'city' => $city,
            'output' => $format,
            'extensions' => $type,
        ]);

        try {
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            $response = 'json' === $format ? \json_decode($response, true) : $response;

            if ('json' === $format) $this->handleResponse($response);

            return $response;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws \EllisFan\Weather\Exceptions\HttpException
     */
    public function handleResponse($response)
    {
        $errorCodes = [
            '10001' => 'INVALID_USER_KEY key不正确或过期',
            '10002' => 'SERVICE_NOT_AVAILABLE 没有权限使用相应的服务或者请求接口的路径拼写错误',
            '10003' => 'DAILY_QUERY_OVER_LIMIT 访问已超出日访问量',
            '10004' => 'ACCESS_TOO_FREQUENT 单位时间内访问过于频繁',
            '10005' => 'INVALID_USER_IP IP白名单出错，发送请求的服务器IP不在IP白名单内',
            '10006' => 'INVALID_USER_DOMAIN 绑定域名无效',
            '10007' => 'INVALID_USER_SIGNATURE 数字签名未通过验证',
            '10008' => 'INVALID_USER_SCODE MD5安全码未通过验证',
            '10009' => 'USERKEY_PLAT_NOMATCH 请求key与绑定平台不符',
            '10010' => 'IP_QUERY_OVER_LIMIT IP访问超限',
            '10011' => 'NOT_SUPPORT_HTTPS 服务不支持https请求',
            '10012' => 'INSUFFICIENT_PRIVILEGES 权限不足，服务请求被拒绝',
            '10013' => 'USER_KEY_RECYCLED Key被删除',
            '10014' => 'QPS_HAS_EXCEEDED_THE_LIMIT 云图服务QPS超限',
            '10015' => 'GATEWAY_TIMEOUT 受单机QPS限流限制',
            '10016' => 'SERVER_IS_BUSY 服务器负载过高',
            '10017' => 'RESOURCE_UNAVAILABLE 所请求的资源不可用',
            '10019' => 'CQPS_HAS_EXCEEDED_THE_LIMIT 使用的某个服务总QPS超限',
            '10020' => 'CKQPS_HAS_EXCEEDED_THE_LIMIT 某个Key使用某个服务接口QPS超出限制',
            '10021' => 'CUQPS_HAS_EXCEEDED_THE_LIMIT  账号使用某个服务接口QPS超出限制',
            '10026' => 'INVALID_REQUEST 账号处于被封禁状态',
            '10029' => 'ABROAD_DAILY_QUERY_OVER_LIMIT 某个Key的QPS超出限制',
            '10041' => 'NO_EFFECTIVE_INTERFACE 请求的接口权限过期',
            '10044' => 'USER_DAILY_QUERY_OVER_LIMIT 账号维度日调用量超出限制',
            '10045' => 'USER_ABROAD_DAILY_QUERY_OVER_LIMIT 账号维度海外服务日调用量超出限制',
            '20000' => 'INVALID_PARAMS 请求参数非法',
            '20001' => 'MISSING_REQUIRED_PARAMS 缺少必填参数',
            '20002' => 'ILLEGAL_REQUEST 请求协议非法',
            '20003' => 'UNKNOWN_ERROR 其他未知错误',
            '20011' => 'INSUFFICIENT_ABROAD_PRIVILEGES 查询坐标或规划点（包括起点、终点、途经点）在海外，但没有海外地图权限',
            '20012' => 'ILLEGAL_CONTENT 查询信息存在非法内容',
            '20800' => 'OUT_OF_SERVICE 规划点（包括起点、终点、途经点）不在中国陆地范围内',
            '20801' => 'NO_ROADS_NEARBY 划点（起点、终点、途经点）附近搜不到路',
            '20802' => 'ROUTE_FAIL 路线计算失败，通常是由于道路连通关系导致',
            '20803' => 'OVER_DIRECTION_RANGE 起点终点距离过长。',
            '40000' => 'QUOTA_PLAN_RUN_OUT 余额耗尽',
            '40001' => 'GEOFENCE_MAX_COUNT_REACHED 围栏个数达到上限',
            '40002' => 'SERVICE_EXPIRED 购买服务到期',
            '40003' => 'ABROAD_QUOTA_PLAN_RUN_OUT 海外服务余额耗尽'
        ];

        if ($response['status'] != 1) {
            $code = $response['infocode'];
            $errorMessage = array_key_exists($code, $errorCodes)
                            ? $errorCodes[$code]
                            : 'UNKNOWN_ERROR';

            // 匹配300**这样的错误代码
            if (preg_match('/^300\d{2}$/', $code)) {
                $errorMessage = 'ENGINE_RESPONSE_DATA_ERROR 服务响应失败';
            }

            throw new HttpException($errorMessage);
        }
    }
}