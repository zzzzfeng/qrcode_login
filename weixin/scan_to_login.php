<?php
function do_curl($url, $cookies){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIE, '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
    curl_setopt($ch, CURLOPT_REFERER,'https://wx2.qq.com');
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_COOKIE, $cookies);
    //curl_setopt($ch,CURLOPT_POST,1);
    $content = curl_exec($ch);
    // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // if($httpCode == 200){
    //     return $content;
    // }
    if (curl_error($ch)) {
        $error_msg = curl_error($ch);
        //echo $error_msg;
    }
    return $content;
}

function get_cookies($header){
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
    $cookies = '';
    foreach ($matches[1] as $key => $value) {
        $cookies .= $value.'; ';
    }
    return $cookies;
}
//api
if($_GET['check'] == 1){
    $check_url = 'https://login.wx2.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&tip=1&r=1017050854&_=1558056077126&uuid='.$_GET['uuid'];

    $cookies = base64_decode($_GET['cookies']);
    $content = do_curl($check_url, $cookies);
    list($header, $body) = explode("\r\n\r\n", $content);
    preg_match_all('/^window.code=(.*?);/mi', $body, $matches);
    $code = $matches[1][0];
    if($code == 201){
        preg_match_all('/\'(.*)\'/mi', $body, $matches);
        $userAvatar = $matches[1][0];
        echo 'cb2("{userAvatar}'.$userAvatar.'")';
    }elseif ($code == 400){
        //timeout
        header('location:?');
    }elseif ($code == 200){
        //timeout
        preg_match_all('/\"(.*)\"/mi', $body, $matches);
        $redirect_uri = $matches[1][0];
        echo 'cb2("{redirect_uri}'.$redirect_uri.'")';
    }else{
        echo '//waiting'.$code;
    }

    exit();
}
//end-api

//获取img uuid
$output_img = '<center><div id="img1"><img src={src} width=150px height=150px id=newImg /></div></center>';
$uuid_url = 'https://login.wx2.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Fwx2.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=zh_CN&_=1558056077125';
$content = do_curl($uuid_url, '');
//切分内容和head
list($header, $body) = explode("\r\n\r\n", $content);
//保存img中的cookie
$uuid_cookies = get_cookies($header);
$output_cookie = '<div id="uuid_cookie">'.base64_encode($uuid_cookies).'</div>';
preg_match_all('/^window.QRLogin.code = (.*?);/mi', $body, $matches);
$code = $matches[1][0];
if($code == 200){
    preg_match_all('/\"(.*)\"/mi', $body, $matches);
    $uuid = $matches[1][0];
    $url = 'https://login.weixin.qq.com/qrcode/'.$uuid;
    $output_img = str_replace('{src}', $url, $output_img);
    //获取二维码没有设置cookie，且不是一个域，直接返回前端，否则就通过服务器读取图片，保存cookie
    //echo $output_img;
}


?>

<head> <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>使用微信扫描登录</title></head>
<center><b><font size="6" color="red">使用微信扫描登录</font></b></center><br>
<?php
//输出图片及cookie
echo $output_img;
echo $output_cookie;
?>
<center><div id="content"></div></center>

<script type="text/javascript">
var int = '';
var idx = document.all.newImg.src.lastIndexOf('/');
var token = document.all.newImg.src.substr(idx+1);
function cb2(c) {
        if(c.indexOf('{userAvatar}') == 0){
            document.all.newImg.src = c.replace('{userAvatar}', '');
        }else if(c.indexOf('{redirect_uri}') == 0){
            document.all.content.innerText = c.replace('{redirect_uri}', '');
            window.clearInterval(int);
        }
}
var checkLogin = function(){
    var J=document.createElement("script");
    J.type="text/javascript";
    J.src="?check=1&uuid="+encodeURIComponent(token)+"&cookies="+document.all.uuid_cookie.innerText;
    document.getElementsByTagName("head")[0].appendChild(J);
};
int = window.setInterval("checkLogin()", 2000);
</script>
