# Nginx 安全加固配置指南

本文档列出了在 Nginx 层需要做的安全加固，配合 PHP 层的安全措施一起生效。

## 1. 隐藏 Nginx 版本号（中危：服务器版本泄露）

在 `nginx.conf` 的 `http` 块中添加：

```nginx
http {
    server_tokens off;
    # ... 其他配置
}
```

效果：响应头 `Server` 从 `nginx/1.20.1` 变为只有 `nginx`，不暴露具体版本号。

## 2. 自定义 404 页面（中危：404 页面信息泄露）

Nginx 默认 404 页面会暴露服务器信息。在站点的 `server` 块中配置自定义错误页：

```nginx
server {
    # 自定义错误页面，放在项目 public 目录下
    error_page 404 /404.html;
    error_page 500 502 503 504 /50x.html;

    location = /404.html {
        root /path/to/studyscape/public;
        internal;
    }

    location = /50x.html {
        root /path/to/studyscape/public;
        internal;
    }
}
```

> `internal` 表示只能通过内部重定向访问，不能直接通过 URL 访问。

## 3. 限制 HTTP 方法（高危：HTTP 方法全放行）

在 `server` 块中，只允许 GET 和 POST，其他方法返回 405：

```nginx
server {
    # 只允许 GET 和 POST
    if ($request_method !~ ^(GET|POST)$) {
        return 405;
    }

    # ... 其他配置
}
```

> 注意：PHP 层的 `api.php` 也做了同样的限制，Nginx 层是第一道防线。

## 4. 禁止访问敏感文件

```nginx
server {
    # 禁止访问隐藏文件（.env, .git 等）
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # 禁止直接访问 .php 文件（除了 api.php）
    location ~ \.php$ {
        # 只允许 api.php
        location ~ /api\.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
        deny all;
    }

    # 禁止访问 data 目录（OCR 数据等敏感文件）
    location ~* ^/data/ {
        deny all;
    }

    # 禁止访问数据库文件
    location ~* \.(db|sqlite|sqlite3)$ {
        deny all;
    }
}
```

## 5. PHP-FPM 安全建议

在 `php.ini` 中设置：

```ini
; 隐藏 PHP 版本号
expose_php = Off

; 禁止远程文件包含
allow_url_include = Off

; 禁用危险函数
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; 限制 POST 大小
post_max_size = 20M

; 限制上传文件大小
upload_max_filesize = 20M
```

## 6. 安全头（可选，PHP 层已添加）

如果想在 Nginx 层统一加安全头（更彻底，静态文件也有），可以在 `server` 块中加：

```nginx
server {
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=(), payment=(), gyroscope=(), accelerometer=(), magnetometer=()" always;

    # ... 其他配置
}
```

> 注意：`always` 参数确保即使 4xx/5xx 响应也会带上这些头。
> 如果 PHP 层也加了，会重复发送。建议只在一层加。

## 7. 配置检查与重载

修改完配置后，检查语法是否正确：

```bash
nginx -t
```

语法正确后重载：

```bash
nginx -s reload
```
