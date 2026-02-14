#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""測試資料庫連線"""

import os
from dotenv import load_dotenv

load_dotenv()

print("=== 資料庫連線測試 ===")
print(f"MYSQL_HOST: {os.environ.get('MYSQL_HOST', 'localhost')}")
print(f"MYSQL_DATABASE: {os.environ.get('MYSQL_DATABASE', 'vt_website')}")
print(f"MYSQL_USER: {os.environ.get('MYSQL_USER', 'root')}")
print()

try:
    import MySQLdb
    conn = MySQLdb.connect(
        host=os.environ.get('MYSQL_HOST', 'localhost'),
        user=os.environ.get('MYSQL_USER', 'root'),
        passwd=os.environ.get('MYSQL_PASSWORD', ''),
        db=os.environ.get('MYSQL_DATABASE', 'vt_website'),
        charset='utf8mb4',
        use_unicode=True
    )
    print("✅ 資料庫連線成功!")
    cursor = conn.cursor()
    
    # 列出所有資料表
    cursor.execute('SHOW TABLES')
    tables = cursor.fetchall()
    print(f"資料表數量: {len(tables)}")
    for t in tables:
        print(f"  - {t[0]}")
    
    # 檢查 users 表
    try:
        cursor.execute('SELECT COUNT(*) FROM users')
        user_count = cursor.fetchone()[0]
        print(f"\n用戶數量: {user_count}")
    except Exception as e:
        print(f"\n⚠️ users 表不存在或有問題: {e}")
    
    # 檢查 orders 表
    try:
        cursor.execute('SELECT COUNT(*) FROM orders')
        order_count = cursor.fetchone()[0]
        print(f"訂單數量: {order_count}")
    except Exception as e:
        print(f"⚠️ orders 表不存在或有問題: {e}")
    
    conn.close()
    
except ImportError:
    print("❌ MySQLdb 模組未安裝!")
    print("請執行: pip install mysqlclient")
except Exception as e:
    print(f"❌ 資料庫連線失敗: {e}")
