#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
VPS 部署診斷腳本
用於檢查網站部署問題
"""

import os
import sys

print("=" * 60)
print("VTuber Website - VPS 部署診斷")
print("=" * 60)

# 1. 檢查 Python 版本
print("\n[1] Python 版本")
print(f"    Python: {sys.version}")

# 2. 檢查必要模組
print("\n[2] 必要模組檢查")
modules = {
    'flask': 'Flask 網頁框架',
    'MySQLdb': 'MySQL 資料庫連接',
    'dotenv': '環境變數載入',
    'requests': 'HTTP 請求',
    'werkzeug': '密碼雜湊'
}

missing_modules = []
for module, desc in modules.items():
    try:
        if module == 'dotenv':
            __import__('dotenv')
        else:
            __import__(module)
        print(f"    ✅ {module} - {desc}")
    except ImportError:
        print(f"    ❌ {module} - {desc} (未安裝)")
        missing_modules.append(module)

if missing_modules:
    print(f"\n    安裝缺少的模組: pip install {' '.join(missing_modules)}")

# 3. 檢查環境變數
print("\n[3] 環境變數檢查")
try:
    from dotenv import load_dotenv
    load_dotenv()
except:
    pass

env_vars = {
    'MYSQL_HOST': os.environ.get('MYSQL_HOST', '未設定 (預設: localhost)'),
    'MYSQL_DATABASE': os.environ.get('MYSQL_DATABASE', '未設定 (預設: vt_website)'),
    'MYSQL_USER': os.environ.get('MYSQL_USER', '未設定 (預設: root)'),
    'MYSQL_PASSWORD': '已設定' if os.environ.get('MYSQL_PASSWORD') else '未設定',
    'FLASK_SECRET_KEY': '已設定' if os.environ.get('FLASK_SECRET_KEY') else '未設定',
    'GOOGLE_CLIENT_ID': '已設定' if os.environ.get('GOOGLE_CLIENT_ID') else '未設定',
}

for var, value in env_vars.items():
    status = "✅" if "已設定" in str(value) or "localhost" not in str(value) else "⚠️"
    print(f"    {status} {var}: {value}")

# 4. 檢查資料庫連線
print("\n[4] 資料庫連線測試")
try:
    import MySQLdb
    conn = MySQLdb.connect(
        host=os.environ.get('MYSQL_HOST', 'localhost'),
        user=os.environ.get('MYSQL_USER', 'root'),
        passwd=os.environ.get('MYSQL_PASSWORD', ''),
        db=os.environ.get('MYSQL_DATABASE', 'vt_website'),
        charset='utf8mb4'
    )
    print("    ✅ 資料庫連線成功")
    
    cursor = conn.cursor()
    cursor.execute("SHOW TABLES")
    tables = [t[0] for t in cursor.fetchall()]
    print(f"    資料表: {', '.join(tables) if tables else '無'}")
    
    # 檢查必要的資料表
    required_tables = ['users', 'orders']
    for table in required_tables:
        if table in tables:
            cursor.execute(f"SELECT COUNT(*) FROM {table}")
            count = cursor.fetchone()[0]
            print(f"    ✅ {table} 表存在 ({count} 筆資料)")
        else:
            print(f"    ❌ {table} 表不存在")
    
    conn.close()
except ImportError:
    print("    ❌ MySQLdb 模組未安裝")
    print("    請執行: pip install mysqlclient")
except Exception as e:
    print(f"    ❌ 資料庫連線失敗: {e}")

# 5. 檢查檔案結構
print("\n[5] 檔案結構檢查")
base_dir = os.path.dirname(os.path.abspath(__file__))

files_to_check = {
    'app.py': '主應用程式',
    'templates/index.html': '首頁模板',
    'templates/login.html': '登入模板',
    'templates/register.html': '註冊模板',
    'templates/profile.html': '個人資料模板',
    'templates/preorder.html': '預購模板',
    'templates/my-orders.html': '訂單模板',
    'templates/admin.html': '管理後台模板',
    'static/style.css': '樣式表',
    'static/navbar.html': '導覽列',
    'static/navbar.js': '導覽列 JS',
    'about.html': '關於頁面',
    'keychain.html': '鑰匙圈頁面',
    'subscriber-benefits.html': '訂閱者福利頁面',
    'events.html': '活動專區',
    'donate.html': '加班台頁面',
    'url.html': '連結頁面',
    'courses/course.html': '課程頁面',
    '.env': '環境變數檔案',
}

for file, desc in files_to_check.items():
    path = os.path.join(base_dir, file)
    if os.path.exists(path):
        size = os.path.getsize(path)
        if size < 10:
            print(f"    ⚠️ {file} - {desc} (檔案過小，可能是空的)")
        else:
            print(f"    ✅ {file} - {desc}")
    else:
        print(f"    ❌ {file} - {desc} (不存在)")

# 6. 測試 Flask 應用
print("\n[6] Flask 應用測試")
try:
    from app import app
    print("    ✅ Flask 應用載入成功")
    print(f"    靜態檔案資料夾: {app.static_folder}")
    print(f"    模板資料夾: {app.template_folder}")
    
    # 測試路由
    with app.test_client() as client:
        routes_to_test = [
            ('/', '首頁'),
            ('/login', '登入'),
            ('/register', '註冊'),
            ('/about.html', '關於'),
            ('/keychain.html', '鑰匙圈'),
            ('/subscriber-benefits.html', '訂閱者福利'),
            ('/events.html', '活動專區'),
            ('/donate.html', '加班台'),
            ('/url.html', '連結'),
            ('/courses/course.html', '課程'),
            ('/static/style.css', '樣式表'),
            ('/navbar.html', '導覽列'),
        ]
        
        print("\n    路由測試:")
        for route, name in routes_to_test:
            try:
                response = client.get(route)
                if response.status_code == 200:
                    print(f"    ✅ {name} ({route}) - {response.status_code}")
                elif response.status_code == 302:
                    print(f"    ➡️ {name} ({route}) - 重導向")
                elif response.status_code == 404:
                    print(f"    ❌ {name} ({route}) - 找不到")
                else:
                    print(f"    ⚠️ {name} ({route}) - {response.status_code}")
            except Exception as e:
                print(f"    ❌ {name} ({route}) - 錯誤: {e}")
                
except Exception as e:
    print(f"    ❌ Flask 應用載入失敗: {e}")
    import traceback
    traceback.print_exc()

print("\n" + "=" * 60)
print("診斷完成")
print("=" * 60)
