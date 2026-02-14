"""
VTuber Website - Flask 後端應用程式
柒柒 chi 個人網站
"""

import os
import secrets
import hashlib
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime, timedelta
from functools import wraps

from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify, send_from_directory
from werkzeug.security import generate_password_hash, check_password_hash
from werkzeug.utils import secure_filename
from dotenv import load_dotenv

# 載入環境變數
load_dotenv()

# 初始化 Flask 應用
app = Flask(__name__, 
            static_folder='static',
            template_folder='templates')

# 設定
app.secret_key = os.environ.get('FLASK_SECRET_KEY', secrets.token_hex(32))
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB
app.config['UPLOAD_FOLDER'] = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'uploads')

# 確保上傳資料夾存在
os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)

# 資料庫設定
DB_CONFIG = {
    'host': os.environ.get('MYSQL_HOST', 'localhost'),
    'database': os.environ.get('MYSQL_DATABASE', 'vt_website'),
    'user': os.environ.get('MYSQL_USER', 'root'),
    'password': os.environ.get('MYSQL_PASSWORD', '123456789'),
    'charset': 'utf8mb4'
}

# Google OAuth 設定
GOOGLE_CLIENT_ID = os.environ.get('GOOGLE_CLIENT_ID', '')
GOOGLE_CLIENT_SECRET = os.environ.get('GOOGLE_CLIENT_SECRET', '')
GOOGLE_REDIRECT_URI = os.environ.get('GOOGLE_REDIRECT_URI', 'http://localhost:8000/google-callback')

# SMTP 設定
SMTP_HOST = os.environ.get('SMTP_HOST', 'smtp.gmail.com')
SMTP_PORT = int(os.environ.get('SMTP_PORT', 587))
SMTP_USER = os.environ.get('SMTP_USER', '')
SMTP_PASSWORD = os.environ.get('SMTP_PASSWORD', '')

# 管理員帳號
ADMIN_USERNAME = os.environ.get('ADMIN_USERNAME', 'admin')
ADMIN_PASSWORD = os.environ.get('ADMIN_PASSWORD', 'admin123')


def get_db_connection():
    """建立資料庫連線"""
    try:
        import MySQLdb
        conn = MySQLdb.connect(
            host=DB_CONFIG['host'],
            user=DB_CONFIG['user'],
            passwd=DB_CONFIG['password'],
            db=DB_CONFIG['database'],
            charset=DB_CONFIG['charset'],
            use_unicode=True
        )
        return conn
    except Exception as e:
        print(f"資料庫連線失敗: {e}")
        return None


def get_google_auth_url():
    """產生 Google OAuth URL"""
    if not GOOGLE_CLIENT_ID:
        return '#'
    
    params = {
        'client_id': GOOGLE_CLIENT_ID,
        'redirect_uri': GOOGLE_REDIRECT_URI,
        'response_type': 'code',
        'scope': 'email profile',
        'access_type': 'online'
    }
    
    from urllib.parse import urlencode
    return 'https://accounts.google.com/o/oauth2/v2/auth?' + urlencode(params)


def login_required(f):
    """登入驗證裝飾器"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated_function


def admin_required(f):
    """管理員驗證裝飾器"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'admin_id' not in session:
            return redirect(url_for('admin'))
        return f(*args, **kwargs)
    return decorated_function


def send_verification_email(email, code):
    """發送驗證郵件"""
    if not SMTP_USER or not SMTP_PASSWORD:
        print("SMTP 設定不完整，跳過發送郵件")
        return False
    
    try:
        msg = MIMEMultipart()
        msg['From'] = SMTP_USER
        msg['To'] = email
        msg['Subject'] = '柒柒 chi 網站 - 信箱驗證碼'
        
        body = f"""
        <html>
        <body style="font-family: Arial, sans-serif; padding: 20px;">
            <h2 style="color: #7dd3fc;">🦉 柒柒 chi 網站 - 信箱驗證</h2>
            <p>您好！感謝您註冊我們的網站。</p>
            <p>您的驗證碼是：</p>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                        color: white; 
                        font-size: 32px; 
                        font-weight: bold;
                        letter-spacing: 8px;
                        padding: 20px 40px;
                        border-radius: 10px;
                        display: inline-block;
                        margin: 20px 0;">
                {code}
            </div>
            <p>驗證碼有效期為 10 分鐘。</p>
            <p>如果這不是您本人的操作，請忽略此郵件。</p>
            <br>
            <p style="color: #888;">柒柒 chi 網站團隊</p>
        </body>
        </html>
        """
        
        msg.attach(MIMEText(body, 'html', 'utf-8'))
        
        server = smtplib.SMTP(SMTP_HOST, SMTP_PORT)
        server.starttls()
        server.login(SMTP_USER, SMTP_PASSWORD)
        server.send_message(msg)
        server.quit()
        
        return True
    except Exception as e:
        print(f"發送郵件失敗: {e}")
        return False


# ==================== 路由 ====================

@app.route('/')
def index():
    """首頁"""
    return render_template('index.html')


@app.route('/login', methods=['GET', 'POST'])
def login():
    """登入頁面"""
    if 'user_id' in session:
        return redirect(url_for('preorder'))
    
    error = ''
    submitted_username = ''
    
    if request.method == 'POST':
        username = request.form.get('username', '').strip()
        password = request.form.get('password', '')
        submitted_username = username
        
        if not username or not password:
            error = '請輸入使用者名稱和密碼'
        else:
            conn = get_db_connection()
            if conn:
                try:
                    cursor = conn.cursor()
                    cursor.execute(
                        "SELECT id, username, email, password FROM users WHERE username = %s OR email = %s",
                        (username, username)
                    )
                    user = cursor.fetchone()
                    
                    if user and check_password_hash(user[3], password):
                        session['user_id'] = user[0]
                        session['username'] = user[1]
                        session['email'] = user[2]
                        return redirect(url_for('preorder'))
                    else:
                        error = '使用者名稱或密碼錯誤'
                except Exception as e:
                    error = f'登入失敗: {str(e)}'
                finally:
                    conn.close()
            else:
                error = '資料庫連線失敗'
    
    return render_template('login.html', 
                         error=error, 
                         submitted_username=submitted_username,
                         google_auth_url=get_google_auth_url())


@app.route('/register', methods=['GET', 'POST'])
def register():
    """註冊頁面"""
    if 'user_id' in session:
        return redirect(url_for('index'))
    
    error = ''
    
    if request.method == 'POST':
        username = request.form.get('username', '').strip()
        email = request.form.get('email', '').strip().lower()
        phone = request.form.get('phone', '').strip()
        password = request.form.get('password', '')
        confirm_password = request.form.get('confirm_password', '')
        
        # 驗證
        if not username or not email or not password:
            error = '請填寫所有必填欄位'
        elif len(username) < 3:
            error = '使用者名稱至少需要 3 個字元'
        elif len(password) < 6:
            error = '密碼至少需要 6 個字元'
        elif password != confirm_password:
            error = '密碼確認不一致'
        elif '@' not in email:
            error = '請輸入有效的電子郵件'
        else:
            conn = get_db_connection()
            if conn:
                try:
                    cursor = conn.cursor()
                    
                    # 檢查使用者名稱是否已存在
                    cursor.execute("SELECT id FROM users WHERE username = %s", (username,))
                    if cursor.fetchone():
                        error = '此使用者名稱已被使用'
                    else:
                        # 檢查電子郵件是否已存在
                        cursor.execute("SELECT id FROM users WHERE email = %s", (email,))
                        if cursor.fetchone():
                            error = '此電子郵件已被使用'
                        else:
                            # 產生驗證碼
                            verification_code = str(secrets.randbelow(900000) + 100000)
                            expires_at = datetime.now() + timedelta(minutes=10)
                            
                            # 儲存待驗證資料到 session
                            session['pending_registration'] = {
                                'username': username,
                                'email': email,
                                'phone': phone,
                                'password': generate_password_hash(password),
                                'verification_code': verification_code,
                                'expires_at': expires_at.isoformat()
                            }
                            
                            # 發送驗證郵件
                            if send_verification_email(email, verification_code):
                                return redirect(url_for('verify_email'))
                            else:
                                # 如果發送失敗，直接註冊（開發模式）
                                cursor.execute(
                                    """INSERT INTO users (username, email, phone, password, email_verified, auth_provider, created_at) 
                                       VALUES (%s, %s, %s, %s, 1, 'local', NOW())""",
                                    (username, email, phone, generate_password_hash(password))
                                )
                                conn.commit()
                                
                                # 自動登入
                                cursor.execute("SELECT id FROM users WHERE email = %s", (email,))
                                user = cursor.fetchone()
                                session['user_id'] = user[0]
                                session['username'] = username
                                session['email'] = email
                                
                                return redirect(url_for('preorder'))
                except Exception as e:
                    error = f'註冊失敗: {str(e)}'
                finally:
                    conn.close()
            else:
                error = '資料庫連線失敗'
    
    return render_template('register.html', error=error, google_auth_url=get_google_auth_url())


@app.route('/verify-email', methods=['GET', 'POST'])
def verify_email():
    """信箱驗證頁面"""
    pending = session.get('pending_registration')
    
    if not pending:
        return redirect(url_for('register'))
    
    email = pending.get('email', '')
    error = ''
    
    if request.method == 'POST':
        code = request.form.get('code', '').strip()
        
        if code == pending.get('verification_code'):
            # 檢查是否過期
            expires_at = datetime.fromisoformat(pending['expires_at'])
            if datetime.now() > expires_at:
                error = '驗證碼已過期，請重新註冊'
                session.pop('pending_registration', None)
            else:
                # 建立帳號
                conn = get_db_connection()
                if conn:
                    try:
                        cursor = conn.cursor()
                        cursor.execute(
                            """INSERT INTO users (username, email, phone, password, email_verified, auth_provider, created_at) 
                               VALUES (%s, %s, %s, %s, 1, 'local', NOW())""",
                            (pending['username'], pending['email'], pending['phone'], pending['password'])
                        )
                        conn.commit()
                        
                        # 取得新使用者 ID
                        cursor.execute("SELECT id FROM users WHERE email = %s", (pending['email'],))
                        user = cursor.fetchone()
                        
                        # 自動登入
                        session.pop('pending_registration', None)
                        session['user_id'] = user[0]
                        session['username'] = pending['username']
                        session['email'] = pending['email']
                        
                        flash('註冊成功！', 'success')
                        return redirect(url_for('preorder'))
                    except Exception as e:
                        error = f'註冊失敗: {str(e)}'
                    finally:
                        conn.close()
                else:
                    error = '資料庫連線失敗'
        else:
            error = '驗證碼錯誤'
    
    return render_template('verify-email.html', email=email, error=error)


@app.route('/google-callback')
def google_callback():
    """Google OAuth 回調"""
    code = request.args.get('code')
    
    if not code:
        return redirect(url_for('login', error='oauth_failed'))
    
    try:
        import requests
        
        # 交換 access token
        token_response = requests.post('https://oauth2.googleapis.com/token', data={
            'code': code,
            'client_id': GOOGLE_CLIENT_ID,
            'client_secret': GOOGLE_CLIENT_SECRET,
            'redirect_uri': GOOGLE_REDIRECT_URI,
            'grant_type': 'authorization_code'
        })
        
        if token_response.status_code != 200:
            return redirect(url_for('login', error='oauth_failed'))
        
        token_data = token_response.json()
        access_token = token_data.get('access_token')
        
        # 取得使用者資訊
        user_info_response = requests.get(
            'https://www.googleapis.com/oauth2/v2/userinfo',
            headers={'Authorization': f'Bearer {access_token}'}
        )
        
        if user_info_response.status_code != 200:
            return redirect(url_for('login', error='oauth_failed'))
        
        user_info = user_info_response.json()
        google_id = user_info.get('id')
        email = user_info.get('email')
        name = user_info.get('name', email.split('@')[0] if email else 'User')
        
        conn = get_db_connection()
        if conn:
            try:
                cursor = conn.cursor()
                
                # 檢查是否已存在該 Google 帳號
                cursor.execute("SELECT id, username FROM users WHERE google_id = %s", (google_id,))
                user = cursor.fetchone()
                
                if user:
                    # 已存在，直接登入
                    session['user_id'] = user[0]
                    session['username'] = user[1]
                    session['email'] = email
                else:
                    # 檢查 email 是否已存在
                    cursor.execute("SELECT id, username FROM users WHERE email = %s", (email,))
                    existing_user = cursor.fetchone()
                    
                    if existing_user:
                        # 更新現有帳號的 google_id
                        cursor.execute("UPDATE users SET google_id = %s WHERE id = %s", (google_id, existing_user[0]))
                        conn.commit()
                        session['user_id'] = existing_user[0]
                        session['username'] = existing_user[1]
                        session['email'] = email
                    else:
                        # 建立新帳號
                        cursor.execute(
                            """INSERT INTO users (username, email, google_id, email_verified, auth_provider, created_at) 
                               VALUES (%s, %s, %s, 1, 'google', NOW())""",
                            (name, email, google_id)
                        )
                        conn.commit()
                        
                        cursor.execute("SELECT id FROM users WHERE google_id = %s", (google_id,))
                        new_user = cursor.fetchone()
                        
                        session['user_id'] = new_user[0]
                        session['username'] = name
                        session['email'] = email
                
                return redirect(url_for('preorder'))
            except Exception as e:
                print(f"Google OAuth 錯誤: {e}")
                return redirect(url_for('login', error='oauth_failed'))
            finally:
                conn.close()
        else:
            return redirect(url_for('login', error='oauth_failed'))
    
    except Exception as e:
        print(f"Google OAuth 錯誤: {e}")
        return redirect(url_for('login', error='oauth_failed'))


@app.route('/logout')
def logout():
    """登出"""
    session.clear()
    return redirect(url_for('index'))


@app.route('/profile', methods=['GET', 'POST'])
@login_required
def profile():
    """個人資料頁面"""
    conn = get_db_connection()
    user = None
    error = ''
    success = ''
    
    if conn:
        try:
            cursor = conn.cursor()
            cursor.execute("SELECT id, username, email, phone FROM users WHERE id = %s", (session['user_id'],))
            result = cursor.fetchone()
            if result:
                user = {
                    'id': result[0],
                    'username': result[1],
                    'email': result[2],
                    'phone': result[3] or ''
                }
            
            if request.method == 'POST':
                username = request.form.get('username', '').strip()
                phone = request.form.get('phone', '').strip()
                current_password = request.form.get('current_password', '')
                new_password = request.form.get('new_password', '')
                confirm_password = request.form.get('confirm_password', '')
                
                if not username:
                    error = '使用者名稱不能為空'
                else:
                    # 檢查使用者名稱是否被其他人使用
                    cursor.execute("SELECT id FROM users WHERE username = %s AND id != %s", (username, session['user_id']))
                    if cursor.fetchone():
                        error = '此使用者名稱已被使用'
                    else:
                        # 如果要修改密碼
                        if new_password:
                            if not current_password:
                                error = '請輸入當前密碼以確認身份'
                            elif len(new_password) < 6:
                                error = '新密碼至少需要 6 個字元'
                            elif new_password != confirm_password:
                                error = '新密碼確認不一致'
                            else:
                                # 驗證當前密碼
                                cursor.execute("SELECT password FROM users WHERE id = %s", (session['user_id'],))
                                current = cursor.fetchone()
                                if not check_password_hash(current[0], current_password):
                                    error = '當前密碼錯誤'
                                else:
                                    # 更新密碼
                                    cursor.execute(
                                        "UPDATE users SET username = %s, phone = %s, password = %s WHERE id = %s",
                                        (username, phone, generate_password_hash(new_password), session['user_id'])
                                    )
                                    conn.commit()
                                    session['username'] = username
                                    user['username'] = username
                                    user['phone'] = phone
                                    success = '個人資料和密碼已更新'
                        else:
                            # 只更新基本資料
                            cursor.execute(
                                "UPDATE users SET username = %s, phone = %s WHERE id = %s",
                                (username, phone, session['user_id'])
                            )
                            conn.commit()
                            session['username'] = username
                            user['username'] = username
                            user['phone'] = phone
                            success = '個人資料已更新'
        except Exception as e:
            error = f'更新失敗: {str(e)}'
        finally:
            conn.close()
    
    if not user:
        return redirect(url_for('login'))
    
    return render_template('profile.html', user=user, error=error, success=success)


@app.route('/preorder', methods=['GET', 'POST'])
@login_required
def preorder():
    """鑰匙圈預購頁面"""
    conn = get_db_connection()
    user = None
    error = ''
    success = ''
    
    if conn:
        try:
            cursor = conn.cursor()
            cursor.execute("SELECT id, username, email, phone FROM users WHERE id = %s", (session['user_id'],))
            result = cursor.fetchone()
            if result:
                user = {
                    'id': result[0],
                    'username': result[1],
                    'email': result[2],
                    'phone': result[3] or ''
                }
            
            if request.method == 'POST':
                recipient_name = request.form.get('recipient_name', '').strip()
                phone = request.form.get('phone', '').strip()
                store_name = request.form.get('store_name', '').strip()
                store_address = request.form.get('store_address', '').strip()
                quantity = request.form.get('quantity', '1')
                
                try:
                    quantity = int(quantity)
                    if quantity < 1:
                        quantity = 1
                    elif quantity > 10:
                        quantity = 10
                except:
                    quantity = 1
                
                total_price = quantity * 100 + 60  # 單價 100 + 運費 60
                
                if not recipient_name:
                    error = '請輸入收件人姓名'
                elif not phone:
                    error = '請輸入聯絡電話'
                elif not store_name:
                    error = '請輸入 7-11 門市名稱'
                else:
                    # 處理付款證明上傳
                    payment_proof = ''
                    if 'payment_proof' in request.files:
                        file = request.files['payment_proof']
                        if file and file.filename:
                            filename = secure_filename(f"{session['user_id']}_{datetime.now().strftime('%Y%m%d%H%M%S')}_{file.filename}")
                            file.save(os.path.join(app.config['UPLOAD_FOLDER'], filename))
                            payment_proof = filename
                    
                    # 建立訂單
                    cursor.execute(
                        """INSERT INTO orders (user_id, recipient_name, phone, store_name, store_address, quantity, total_price, payment_proof, status, created_at) 
                           VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 'pending', NOW())""",
                        (session['user_id'], recipient_name, phone, store_name, store_address, quantity, total_price, payment_proof)
                    )
                    conn.commit()
                    success = f'訂單已成功建立！訂單金額: NT$ {total_price}'
        except Exception as e:
            error = f'訂單建立失敗: {str(e)}'
        finally:
            conn.close()
    
    if not user:
        return redirect(url_for('login'))
    
    return render_template('preorder.html', user=user, error=error, success=success)


@app.route('/my-orders')
@login_required
def my_orders():
    """我的訂單頁面"""
    conn = get_db_connection()
    user = None
    orders = []
    message = request.args.get('message', '')
    error = request.args.get('error', '')
    
    if conn:
        try:
            cursor = conn.cursor()
            
            # 取得使用者資訊
            cursor.execute("SELECT id, username, email, phone FROM users WHERE id = %s", (session['user_id'],))
            result = cursor.fetchone()
            if result:
                user = {
                    'id': result[0],
                    'username': result[1],
                    'email': result[2],
                    'phone': result[3] or ''
                }
            
            # 取得訂單
            cursor.execute(
                """SELECT id, recipient_name, phone, store_name, store_address, quantity, total_price, payment_proof, status, created_at 
                   FROM orders WHERE user_id = %s ORDER BY created_at DESC""",
                (session['user_id'],)
            )
            
            for row in cursor.fetchall():
                orders.append({
                    'id': row[0],
                    'recipient_name': row[1],
                    'phone': row[2],
                    'store_name': row[3],
                    'store_address': row[4],
                    'quantity': row[5],
                    'total_price': row[6],
                    'payment_proof': row[7],
                    'status': row[8],
                    'created_at': row[9]
                })
        except Exception as e:
            error = f'載入訂單失敗: {str(e)}'
        finally:
            conn.close()
    
    if not user:
        return redirect(url_for('login'))
    
    return render_template('my-orders.html', user=user, orders=orders, message=message, error=error)


@app.route('/admin', methods=['GET', 'POST'])
def admin():
    """管理員後台"""
    is_admin = 'admin_id' in session
    error = ''
    
    if request.method == 'POST' and not is_admin:
        username = request.form.get('username', '')
        password = request.form.get('password', '')
        
        if username == ADMIN_USERNAME and password == ADMIN_PASSWORD:
            session['admin_id'] = 1
            session['admin_username'] = username
            is_admin = True
        else:
            error = '帳號或密碼錯誤'
    
    if not is_admin:
        return render_template('admin.html', is_admin=False, error=error)
    
    # 取得訂單資料
    filter_status = request.args.get('filter', 'all')
    conn = get_db_connection()
    orders = []
    stats = {}
    
    if conn:
        try:
            cursor = conn.cursor()
            
            # 統計資料
            cursor.execute("SELECT COUNT(*) FROM orders")
            stats['total'] = cursor.fetchone()[0]
            
            cursor.execute("SELECT COUNT(*) FROM orders WHERE status = 'pending'")
            stats['pending'] = cursor.fetchone()[0]
            
            cursor.execute("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'")
            stats['confirmed'] = cursor.fetchone()[0]
            
            cursor.execute("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")
            stats['shipped'] = cursor.fetchone()[0]
            
            cursor.execute("SELECT COALESCE(SUM(quantity), 0) FROM orders")
            stats['total_quantity'] = cursor.fetchone()[0]
            
            cursor.execute("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status != 'cancelled'")
            stats['total_revenue'] = cursor.fetchone()[0]
            
            # 取得訂單列表
            query = """SELECT o.id, o.recipient_name, o.phone, o.store_name, o.store_address, 
                              o.quantity, o.total_price, o.payment_proof, o.status, o.created_at,
                              u.username
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id"""
            
            if filter_status != 'all':
                query += f" WHERE o.status = '{filter_status}'"
            
            query += " ORDER BY o.created_at DESC"
            
            cursor.execute(query)
            
            for row in cursor.fetchall():
                orders.append({
                    'id': row[0],
                    'recipient_name': row[1],
                    'phone': row[2],
                    'store_name': row[3],
                    'store_address': row[4],
                    'quantity': row[5],
                    'total_price': row[6],
                    'payment_proof': row[7],
                    'status': row[8],
                    'created_at': row[9],
                    'username': row[10] or '未知'
                })
        except Exception as e:
            print(f"管理後台錯誤: {e}")
        finally:
            conn.close()
    
    return render_template('admin.html', is_admin=True, orders=orders, stats=stats, filter=filter_status)


@app.route('/admin/logout')
def admin_logout():
    """管理員登出"""
    session.pop('admin_id', None)
    session.pop('admin_username', None)
    return redirect(url_for('admin'))


@app.route('/admin/update-order/<int:order_id>', methods=['POST'])
@admin_required
def update_order_status(order_id):
    """更新訂單狀態"""
    new_status = request.form.get('status')
    
    conn = get_db_connection()
    if conn:
        try:
            cursor = conn.cursor()
            cursor.execute("UPDATE orders SET status = %s WHERE id = %s", (new_status, order_id))
            conn.commit()
        except Exception as e:
            print(f"更新訂單失敗: {e}")
        finally:
            conn.close()
    
    return redirect(url_for('admin'))


# ==================== 靜態檔案路由 ====================

@app.route('/uploads/<filename>')
def uploaded_file(filename):
    """提供上傳檔案"""
    return send_from_directory(app.config['UPLOAD_FOLDER'], filename)


# 為了相容性，提供根目錄的靜態檔案
@app.route('/<path:filename>')
def serve_static_root(filename):
    """提供根目錄的靜態檔案"""
    # 先檢查 static 資料夾
    static_path = os.path.join(app.static_folder, filename)
    if os.path.exists(static_path):
        return send_from_directory(app.static_folder, filename)
    
    # 再檢查專案根目錄
    root_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), filename)
    if os.path.exists(root_path):
        return send_from_directory(os.path.dirname(os.path.abspath(__file__)), filename)
    
    return 'File not found', 404


# ==================== 應用程式入口 ====================

# 提供給 WSGI 伺服器使用
application = app

if __name__ == '__main__':
    print("=" * 50)
    print("柒柒 chi VTuber 網站 - Flask 後端")
    print("=" * 50)
    print(f"伺服器運行於: http://127.0.0.1:8000")
    print(f"靜態檔案資料夾: {app.static_folder}")
    print(f"模板資料夾: {app.template_folder}")
    print("=" * 50)
    
    app.run(debug=True, host='127.0.0.1', port=8000, threaded=True)