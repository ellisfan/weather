<h1 align="center">Weather</h1>

<p align="center">:rainbow: 基于高德开放平台的 PHP 天气信息组件。</p>

<p align="center">基于 <a href="https://github.com/overtrue/weather">overtrue/weather</a> 开发，增加由IP和Location判断来源城市查询天气</p>

[![Tests](https://github.com/ellisfan/weather/actions/workflows/tests.yml/badge.svg)](https://github.com/ellisfan/weather/actions/workflows/tests.yml)

## 安装

```sh
$ composer require ellisfan/weather -vvv
```

## 配置

在使用本扩展之前，你需要去 [高德开放平台](https://lbs.amap.com/dev/id/newuser) 注册账号，然后创建应用，获取应用的 API Key。

## 使用

```php
use EllisFan\Weather\Weather;

$key = 'xxxxxxxxxxxxxxxxxxxxxxxxxxx';

$weather = new Weather($key);
```

###  获取实时天气

```php
$response = $weather->getLiveWeather('city', '苏州');
```
示例：

```json
{
    "status": "1",
    "count": "1",
    "info": "OK",
    "infocode": "10000",
    "lives": [
        {
            "province": "江苏",
            "city": "苏州市",
            "adcode": "320500",
            "weather": "雨",
            "temperature": "22",
            "winddirection": "东",
            "windpower": "4",
            "humidity": "96",
            "reporttime": "2023-06-24 06:35:43",
            "temperature_float": "22.0",
            "humidity_float": "96.0"
        }
    ]
}
```

### 获取近期天气预报

```
$response = $weather->getLiveWeather('city', '苏州', 'all');
```
示例:

```json
{
    "status": "1",
    "count": "1",
    "info": "OK",
    "infocode": "10000",
    "forecasts": [
        {
            "city": "苏州市",
            "adcode": "320500",
            "province": "江苏",
            "reporttime": "2023-06-24 06:35:43",
            "casts": [
                {
                    "date": "2023-06-24",
                    "week": "6",
                    "dayweather": "暴雨",
                    "nightweather": "大雨",
                    "daytemp": "25",
                    "nighttemp": "21",
                    "daywind": "北",
                    "nightwind": "北",
                    "daypower": "4",
                    "nightpower": "4",
                    "daytemp_float": "25.0",
                    "nighttemp_float": "21.0"
                },
                {
                    "date": "2023-06-25",
                    "week": "7",
                    "dayweather": "中雨",
                    "nightweather": "中雨",
                    "daytemp": "27",
                    "nighttemp": "22",
                    "daywind": "西",
                    "nightwind": "西",
                    "daypower": "4",
                    "nightpower": "4",
                    "daytemp_float": "27.0",
                    "nighttemp_float": "22.0"
                },
                {
                    "date": "2023-06-26",
                    "week": "1",
                    "dayweather": "小雨",
                    "nightweather": "多云",
                    "daytemp": "28",
                    "nighttemp": "22",
                    "daywind": "西南",
                    "nightwind": "西南",
                    "daypower": "4",
                    "nightpower": "4",
                    "daytemp_float": "28.0",
                    "nighttemp_float": "22.0"
                },
                {
                    "date": "2023-06-27",
                    "week": "2",
                    "dayweather": "多云",
                    "nightweather": "多云",
                    "daytemp": "33",
                    "nighttemp": "27",
                    "daywind": "西南",
                    "nightwind": "西南",
                    "daypower": "4",
                    "nightpower": "4",
                    "daytemp_float": "33.0",
                    "nighttemp_float": "27.0"
                }
            ]
        }
    ]
}
```

### 获取 XML 格式返回值

以上两个方法第三个参数为返回值类型，可选 `json` 与 `xml`，默认 `json`：

```php
$response = $weather->getLiveWeather('city', '苏州', 'base', 'xml');
```
示例:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<response>
    <status>1</status>
    <count>1</count>
    <info>OK</info>
    <infocode>10000</infocode>
    <lives type="list">
        <live>
            <province>江苏</province>
            <city>苏州市</city>
            <adcode>320500</adcode>
            <weather>雨</weather>
            <temperature>22</temperature>
            <winddirection>东</winddirection>
            <windpower>4</windpower>
            <humidity>96</humidity>
            <reporttime>2023-06-24 06:35:43</reporttime>
            <temperature_float>22.0</temperature_float>
            <humidity_float>96.0</humidity_float>
        </live>
    </lives>
</response>
```

### 参数说明

```
array | string   getLiveWeather(string $from, string $city, string $type, string $format = 'json')
```

> - `$from` - 来源名，可选值：`ip` / `geo` / `city`
> - `$value` - 当$from传'ip'时，传入客户端IP地址([IP定位](https://lbs.amap.com/api/webservice/guide/api/ipconfig)), 当$from传'geo'时，传入客户端GPS坐标([逆地理编码](https://lbs.amap.com/api/webservice/guide/api/georegeo#regeo)), city则为城市名城市名/[高德地址位置 adcode](https://lbs.amap.com/api/webservice/guide/api/district)，比如：“110.110.110.110”、“120.585294,31.299758”、“苏州市”、“320500”；
> - `$type` - 气象类型，可选值：`base` / `all` (base:返回实况天气, all:返回预报天气)
> - `$format`  - 输出的数据格式，默认为 “`json`” 格式，当 output 设置为 “`xml`” 时，输出的为 XML 格式的数据。

### 在 Laravel 中使用

在 Laravel 中使用也是同样的安装方式，配置写在 `config/services.php` 中：

```php
    .
    .
    .
     'weather' => [
        'key' => env('WEATHER_API_KEY'),
    ],
```

然后在 `.env` 中配置 `WEATHER_API_KEY` ：

```env
WEATHER_API_KEY=xxxxxxxxxxxxxxxxxxxxx
```

可以用两种方式来获取 `EllisFan\Weather\Weather` 实例：

#### 方法参数注入

```php
    .
    .
    .
    public function edit(Weather $weather)
    {
        $response = $weather->getLiveWeather('city', '苏州');
    }
    .
    .
    .
```

#### 服务名访问

```php
    .
    .
    .
    public function edit()
    {
        $response = app('weather')->getLiveWeather('city', '苏州');
    }
    .
    .
    .

```

## 参考

- 基于超哥的代码 [overtrue/weather](https://github.com/overtrue/weather)
- [高德开放平台天气查询](https://lbs.amap.com/api/webservice/guide/api/weatherinfo)
- [高德开放平台IP定位](https://lbs.amap.com/api/webservice/guide/api/ipconfig)
- [高德开放平台地理/逆地理编码](https://lbs.amap.com/api/webservice/guide/api/georegeo#regeo)

## License

MIT