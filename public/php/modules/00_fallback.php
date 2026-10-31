    } catch (Exception $e) {
        jsonOut(false, "服务器异常：" . $e->getMessage());
    }
    exit;
}

// 如果直接访问api.php，返回错误信息
jsonOut(false, "请通过API接口调用");

