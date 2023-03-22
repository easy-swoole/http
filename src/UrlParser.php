<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午11:02
 */

namespace EasySwoole\Http;


use EasySwoole\Http\Message\Uri;

class UrlParser
{
    public static function pathInfo($path)
    {
        $basePath = dirname($path);
        $info = pathInfo($path);
        if($info['filename'] != 'index'){
            if($basePath == '/'){
                $basePath = $basePath.$info['filename'];
            }else{
                $basePath = $basePath.'/'.$info['filename'];
            }
        }
        return $basePath;
    }

    static function appendQuery(string $url,array $args)
    {
        $uri = new Uri($url);
        parse_str($uri->getQuery(),$query);
        $query = $args + $query;
        $uri->withQuery(http_build_query($query));
        return $uri->__toString();
    }
}