## MeiliSearch启动

MeiliSearch如果以development方式启动，则masterKey不是必须的

### MasterKey作用

做development方式下MasterKey可以访问默认生成的AdminKey和SearchKey

即
```php
$client = new Client('http://127.0.0.1:7700', 'masterKey');
$client->getRawKeys();
// 返回的 private 和 public
```

1. private key可以用于在web端查询使用
2. private key和public key都可以在环境变量中替换masterKey使用
3. masterKey是无法用于同步索引的

### 生成api key

请在production模式下，使用masterKey生成apikey