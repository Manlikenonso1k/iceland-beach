# Illuminate\Database\QueryException - Internal Server Error

SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: u519226541_iceland, SQL: select * from `sessions` where `id` = aHtnZyKzNqYfkxJYjYfFS9dTBCU9uunKdnVFl3fD limit 1)

PHP 8.5.1
Laravel 12.55.1
127.0.0.1:8001

## Stack Trace

0 - vendor\laravel\framework\src\Illuminate\Database\Connection.php:838
1 - vendor\laravel\framework\src\Illuminate\Database\Connection.php:999
2 - vendor\laravel\framework\src\Illuminate\Database\Connection.php:978
3 - vendor\laravel\framework\src\Illuminate\Database\Connection.php:796
4 - vendor\laravel\framework\src\Illuminate\Database\Connection.php:411
5 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:3505
6 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:3490
7 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:4080
8 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:3489
9 - vendor\laravel\framework\src\Illuminate\Database\Concerns\BuildsQueries.php:366
10 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:3412
11 - vendor\laravel\framework\src\Illuminate\Session\DatabaseSessionHandler.php:96
12 - vendor\laravel\framework\src\Illuminate\Session\Store.php:128
13 - vendor\laravel\framework\src\Illuminate\Session\Store.php:116
14 - vendor\laravel\framework\src\Illuminate\Session\Store.php:100
15 - vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php:146
16 - vendor\laravel\framework\src\Illuminate\Support\helpers.php:393
17 - vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php:143
18 - vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php:115
19 - vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php:63
20 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
21 - vendor\laravel\framework\src\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse.php:36
22 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
23 - vendor\laravel\framework\src\Illuminate\Cookie\Middleware\EncryptCookies.php:74
24 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
25 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:137
26 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:821
27 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:800
28 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:764
29 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:753
30 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php:200
31 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:180
32 - vendor\livewire\livewire\src\Features\SupportDisablingBackButtonCache\DisableBackButtonCacheMiddleware.php:19
33 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
34 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php:21
35 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull.php:31
36 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
37 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php:21
38 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TrimStrings.php:51
39 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
40 - vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePostSize.php:27
41 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
42 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance.php:109
43 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
44 - vendor\laravel\framework\src\Illuminate\Http\Middleware\HandleCors.php:61
45 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
46 - vendor\laravel\framework\src\Illuminate\Http\Middleware\TrustProxies.php:58
47 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
48 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks.php:22
49 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
50 - vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePathEncoding.php:26
51 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
52 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:137
53 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php:175
54 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php:144
55 - vendor\laravel\framework\src\Illuminate\Foundation\Application.php:1220
56 - public\index.php:20
57 - vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php:23

## Request

GET /rooms

## Headers

* **host**: 127.0.0.1:8001
* **connection**: keep-alive
* **pragma**: no-cache
* **cache-control**: no-cache
* **sec-ch-ua**: "Not=A?Brand";v="99", "Google Chrome";v="151", "Chromium";v="151"
* **sec-ch-ua-mobile**: ?0
* **sec-ch-ua-platform**: "Windows"
* **upgrade-insecure-requests**: 1
* **user-agent**: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36
* **accept**: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
* **sec-fetch-site**: same-origin
* **sec-fetch-mode**: navigate
* **sec-fetch-user**: ?1
* **sec-fetch-dest**: document
* **accept-encoding**: gzip, deflate, br, zstd
* **accept-language**: en-GB,en-US;q=0.9,en;q=0.8
* **cookie**: XSRF-TOKEN=eyJpdiI6Im51ZXhRbm9DSDFzbFgyRDRiVXcwUmc9PSIsInZhbHVlIjoiN3MxNjZjQjBaWnZMeXd0eDJwcEgvaTVlYUd2NHpGWVNlQzlraGJINE1lYUQ5aTYwZXJTVG52V1p5WkpiRDlxcVgrZTNFSjVLaTlMQXBySkV0clpoVlF0bWVMRmtsNnFoMEd2eFhENzVDOW9haENsMTFGdFpHRXR2MzI3ZjB1NnIiLCJtYWMiOiI2ODQwZGQ4M2FmYmZmODJkY2VhZDY1MTA4YjNkZDMwNGIyNzg1YzdlMjc4YmYzYTU2N2M0ZmU5ODhlOTBkNDAxIiwidGFnIjoiIn0%3D; laravel-session=eyJpdiI6IlhnSWRGOFRVaVhoWTZUWGpIcCtHamc9PSIsInZhbHVlIjoiclFicms3eXlrMHA5NExoUnRxZDk4RnZsNTI4TlcyMUE0UG1GVTNnUDhNeU1Qckw1YnI0NEtOandpNmxkYmgrbFVMVkVXUStRT0ZnNExVNXpFQ3FQeTkwRmdWQVBQcGltMmxHeEdkOVAyMklHVWw3R2FsNVByRnBCaGtSZTBaRmwiLCJtYWMiOiIwZDk1YjVlNmMwODdhODEwODlmY2M0YTAzZTJjYmYzYjI2Mzg3OWVhNDQ1YTAyYjc0MDZkN2NiMWI0NDFkNGMwIiwidGFnIjoiIn0%3D

## Route Context

controller: \Illuminate\Routing\ViewController
middleware: web

## Route Parameters

{
    "view": "welcome",
    "data": [],
    "status": 200,
    "headers": []
}

## Database Queries

No database queries detected.
