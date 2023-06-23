<?php

/*
 * This file is part of the ellisfan/weather.
 *
 * (c) ellisfan <ellisfan07@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EllisFan\Weather\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery\Matcher\AnyArgs;
use EllisFan\Weather\Exceptions\HttpException;
use EllisFan\Weather\Exceptions\InvalidArgumentException;
use EllisFan\Weather\Weather;
use PHPUnit\Framework\TestCase;

class WeatherTest extends TestCase
{
    public function testGetWeatherWithInvalidType()
    {
        $w = new Weather('mock-key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type value(base/all): foo');

        $w->getWeather('苏州', 'foo');

        $this->fail('Faild to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeatherWithInvalidFormat()
    {
        $w = new Weather('mock-key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response format: array');

        $w->getWeather('苏州', 'base', 'array');

        $this->fail('Faild to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeather()
    {
        // json
        $response = new Response(200, [], '{"status": 1, "infocode": 10000}');
        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '苏州',
                'output' => 'json',
                'extensions' => 'base',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame(['status' => 1, "infocode" => 10000], $w->getWeather('苏州'));

        // xml
        $response = new Response(200, [], '<hello>content</hello>');
        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '苏州',
                'extensions' => 'all',
                'output' => 'xml',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>content</hello>', $w->getWeather('苏州', 'all', 'xml'));
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs())
            ->andThrow(new \Exception('request timeout'));

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $w->getWeather('苏州');
    }

    public function testGetHttpClient()
    {
        $w = new Weather('mock-key');

        // 断言返回结果为 GuzzleHttp\ClientInterface 实例
        $this->assertInstanceOf(ClientInterface::class, $w->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $w = new Weather('mock-key');

        // 设置参数前，timeout 为 null
        $this->assertNull($w->getHttpClient()->getConfig('timeout'));

        // 设置参数
        $w->setGuzzleOptions(['timeout' => 5000]);

        // 设置参数后，timeout 为 5000
        $this->assertSame(5000, $w->getHttpClient()->getConfig('timeout'));
    }

    public function testGetLiveWeather()
    {
        // 将 getWeather 接口模拟为返回固定内容，以测试参数传递是否正确
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->expects()->getWeather('苏州', 'base', 'json')->andReturn(['status' => 1, 'infocode' => 10000]);

        // 断言正确传参并返回
        $this->assertSame(['status' => 1, 'infocode' => 10000], $w->getLiveWeather('city', '苏州', 'base', 'json'));
    }

    public function testGetIpCity()
    {
        $response = new Response(200, [], '{"status": 1, "infocode": 10000}');
        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/ip', [
            'query' => [
                'key' => 'mock-key',
                'ip' => '192.168.1.1',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame(['status' => 1, "infocode" => 10000], $w->getIpCity('192.168.1.1'));
    }

    public function testGetGeoCity()
    {
        $response = new Response(200, [], '{"status": 1, "infocode": 10000}');
        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/geocode/regeo', [
            'query' => [
                'key' => 'mock-key',
                'location' => '120.585294,31.299758',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame(['status' => 1, "infocode" => 10000], $w->getGeoCity('120.585294,31.299758'));
    }
}