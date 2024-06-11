# Owl Admin Extension

组织架构插件，包含钉钉、飞书、企业微信同步等


### 同步钉钉员工时会自动同步创建 `后台管理员` 并且绑定管理员为角色 `员工`


### 安装
将以下代码复制保存为install.sh，放在框架根目录执行bash即可在登录时增加钉钉登录选项

#### linux
```shell
#!/bin/bash

js_code=$(cat <<'EOF'
<script>
function getUrlFn() {
             let host = window.location.origin;
             let box = document.querySelector('.ant-checkbox-wrapper');
             if (!box) {
                 setTimeout(() => {
                     getUrlFn()
                 }, 500);
                 return ;
             }
             fetch(`${host}/admin-api/corp/dingLogin`, {
                 "method": "GET",
             }).then(response => {
                 response.json().then(res => {
                     let parent = box.parentNode;
                     let a = document.createElement('a');
                     a.id = 'dingding_login';
                     a.innerText = '钉钉登录';
                     a.href = res.data.url;
                     a.style.float = 'right';
                     parent.appendChild(a);
                 })
             });
         }
         (function () {
             getUrlFn()
         })();
</script>
EOF
)
echo $js_code >> ./public/admin-assets/index.html

```

#### windows
```shell
$js_code = @"
<script>
function getUrlFn() {
             let host = window.location.origin;
             let box = document.querySelector('.ant-checkbox-wrapper');
             if (!box) {
                 setTimeout(() => {
                     getUrlFn()
                 }, 500);
                 return ;
             }
             fetch(`${host}/admin-api/corp/dingLogin`, {
                 "method": "GET",
             }).then(response => {
                 response.json().then(res => {
                     let parent = box.parentNode;
                     let a = document.createElement('a');
                     a.id = 'dingding_login';
                     a.innerText = '钉钉登录';
                     a.href = res.data.url;
                     a.style.float = 'right';
                     parent.appendChild(a);
                 })
             });
         }
         (function () {
             getUrlFn()
         })();
</script>
"@

# 插入JS代码到HTML文件的最后一行
Add-Content -Path .\public\admin-assets\index.html -Value $js_code
```