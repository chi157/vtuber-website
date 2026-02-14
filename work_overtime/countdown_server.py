from flask import Flask, request, jsonify
import json
import os
from datetime import datetime

app = Flask(__name__)

DATA_FILE = 'countdown-data.json'

@app.route('/get-countdown', methods=['GET'])
def get_countdown():
    """獲取倒計時設定"""
    try:
        if os.path.exists(DATA_FILE):
            with open(DATA_FILE, 'r', encoding='utf-8') as f:
                config = json.load(f)
            return jsonify({
                'success': True,
                'data': config,
                'isDefault': False,
                'serverTime': int(datetime.now().timestamp() * 1000)
            })
        else:
            # 預設設定
            default_config = {
                'mode': 'timestamp',
                'targetTimestamp': int((datetime.now().timestamp() + 180 * 60) * 1000),
                'title': '加班台倒數計時',
                'message': '距離下班還有',
                'endMessage': '🎉 下班囉！',
                'showDays': True,
                'showHours': True,
                'showMinutes': True,
                'showSeconds': True
            }
            return jsonify({
                'success': True,
                'data': default_config,
                'isDefault': True,
                'serverTime': int(datetime.now().timestamp() * 1000)
            })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/save-countdown', methods=['POST', 'OPTIONS'])
def save_countdown():
    """儲存倒計時設定"""
    if request.method == 'OPTIONS':
        # 處理預檢請求
        response = app.response_class()
        response.headers['Access-Control-Allow-Origin'] = '*'
        response.headers['Access-Control-Allow-Methods'] = 'POST'
        response.headers['Access-Control-Allow-Headers'] = 'Content-Type'
        return response

    try:
        data = request.get_json()
        if not data:
            return jsonify({'success': False, 'error': 'Invalid JSON'}), 400

        with open(DATA_FILE, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)

        return jsonify({
            'success': True,
            'message': 'Countdown settings saved',
            'timestamp': int(datetime.now().timestamp())
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.after_request
def add_cors_headers(response):
    """添加 CORS 標頭"""
    response.headers['Access-Control-Allow-Origin'] = '*'
    response.headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS'
    response.headers['Access-Control-Allow-Headers'] = 'Content-Type'
    response.headers['Cache-Control'] = 'no-cache, no-store, must-revalidate'
    response.headers['Pragma'] = 'no-cache'
    response.headers['Expires'] = '0'
    return response

if __name__ == '__main__':
    print("🚀 啟動倒計時伺服器...")
    print("📡 伺服器運行在 http://127.0.0.1:5000")
    print("🔧 可用端點:")
    print("   GET  /get-countdown  - 獲取倒計時設定")
    print("   POST /save-countdown - 儲存倒計時設定")
    app.run(host='127.0.0.1', port=5000, debug=True)