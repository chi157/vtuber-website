import sys
import os
import traceback

# 確保工作目錄正確
script_dir = os.path.dirname(os.path.abspath(__file__))
os.chdir(script_dir)
sys.path.insert(0, script_dir)

from app import app

print(f'Working directory: {os.getcwd()}', flush=True)
print(f'Starting server on http://127.0.0.1:8000 ...', flush=True)

try:
    # 使用 Flask 內建伺服器（threaded 模式，穩定可靠）
    app.run(debug=False, host='127.0.0.1', port=8000, threaded=True, use_reloader=False)
except Exception as e:
    traceback.print_exc()
    print(f'FATAL ERROR: {e}', flush=True)
    input('Press Enter to exit...')
