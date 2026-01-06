class CryptJson {
  constructor(key) {
    this.key = key;
  }

  // 加密：明文 JSON 对象 → Uint8Array → 位移 → Base64
  encrypt(data) {
    // 1. JSON 序列化
    const plaintext = JSON.stringify(data);

    // 2. 转成 Uint8Array
    const plainBytes = new TextEncoder().encode(plaintext);
    const keyBytes = new TextEncoder().encode(this.key);

    const cipherBytes = new Uint8Array(plainBytes.length);

    // 3. 位移加密
    for (let i = 0; i < plainBytes.length; i++) {
      cipherBytes[i] = (plainBytes[i] + keyBytes[i % keyBytes.length]) % 256;
    }

    // 4. 转 Base64 方便通过 JSON 传输
    return btoa(String.fromCharCode(...cipherBytes));
  }

  // 解密：Base64 → Uint8Array → 位移解密 → JSON.parse
  decrypt(cipherText) {
    // 1. Base64 解码
    const cipherStr = atob(cipherText);

    // 2. 转成 Uint8Array
    const cipherBytes = new Uint8Array(cipherStr.length);
    for (let i = 0; i < cipherStr.length; i++) {
      cipherBytes[i] = cipherStr.charCodeAt(i);
    }

    const keyBytes = new TextEncoder().encode(this.key);
    const plainBytes = new Uint8Array(cipherBytes.length);

    // 3. 位移解密
    for (let i = 0; i < cipherBytes.length; i++) {
      plainBytes[i] = (cipherBytes[i] - keyBytes[i % keyBytes.length] + 256) % 256;
    }

    // 4. Uint8Array → JSON 字符串 → 对象
    return JSON.parse(new TextDecoder().decode(plainBytes));
  }
}
