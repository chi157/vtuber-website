"""
VTuber Viewer Ranking System - Flask Application
"""
import os
from datetime import datetime, date, timedelta
from functools import wraps

from flask import Flask, jsonify, request
from flask_migrate import Migrate
from flask_cors import CORS
from sqlalchemy import func, desc
from sqlalchemy.exc import IntegrityError

from models import db, User, Session, Attendance
from config import config


def create_app(config_name=None):
    """Application Factory"""
    if config_name is None:
        config_name = os.environ.get('FLASK_ENV', 'development')
    
    app = Flask(__name__)
    app.config.from_object(config[config_name])
    
    # Initialize extensions
    db.init_app(app)
    Migrate(app, db)
    CORS(app)
    
    # Register routes
    register_routes(app)
    
    return app


def require_api_key(f):
    """API Key 驗證裝飾器"""
    @wraps(f)
    def decorated(*args, **kwargs):
        api_key = request.headers.get('X-API-Key')
        if not api_key:
            return jsonify({'error': 'Missing API Key'}), 401
        
        from flask import current_app
        if api_key != current_app.config['API_KEY']:
            return jsonify({'error': 'Invalid API Key'}), 403
        
        return f(*args, **kwargs)
    return decorated


def register_routes(app):
    """註冊所有路由"""
    
    @app.route('/health', methods=['GET'])
    def health_check():
        """健康檢查端點"""
        return jsonify({'status': 'healthy', 'timestamp': datetime.utcnow().isoformat()})
    
    # ============================================
    # Session Management APIs
    # ============================================
    
    @app.route('/api/session/start', methods=['POST'])
    @require_api_key
    def start_session():
        """開始新的直播場次"""
        data = request.get_json()
        
        if not data or 'stream_id' not in data:
            return jsonify({'error': 'Missing stream_id'}), 400
        
        stream_id = data['stream_id']
        
        # 檢查是否已存在
        existing = Session.query.filter_by(twitch_stream_id=stream_id).first()
        if existing:
            return jsonify({'status': 'exists', 'session_id': existing.id})
        
        # 建立新場次
        session = Session(
            twitch_stream_id=stream_id,
            started_at=datetime.utcnow(),
            stream_date=date.today()
        )
        
        db.session.add(session)
        db.session.commit()
        
        return jsonify({
            'status': 'created',
            'session_id': session.id,
            'stream_id': stream_id
        })
    
    @app.route('/api/session/end', methods=['POST'])
    @require_api_key
    def end_session():
        """結束直播場次"""
        data = request.get_json()
        
        if not data or 'stream_id' not in data:
            return jsonify({'error': 'Missing stream_id'}), 400
        
        stream_id = data['stream_id']
        
        session = Session.query.filter_by(twitch_stream_id=stream_id).first()
        if not session:
            return jsonify({'error': 'Session not found'}), 404
        
        session.ended_at = datetime.utcnow()
        db.session.commit()
        
        return jsonify({'status': 'ok', 'session_id': session.id})
    
    # ============================================
    # Attendance API
    # ============================================
    
    @app.route('/api/attendance', methods=['POST'])
    @require_api_key
    def record_attendance():
        """
        記錄出席
        
        Request Body:
        {
            "twitch_user_id": "123456",
            "username": "viewer_name",
            "stream_id": "current_stream_id"
        }
        """
        data = request.get_json()
        
        # 驗證必要欄位
        required_fields = ['twitch_user_id', 'username', 'stream_id']
        for field in required_fields:
            if not data or field not in data:
                return jsonify({'error': f'Missing {field}'}), 400
        
        twitch_user_id = str(data['twitch_user_id'])
        username = data['username']
        stream_id = data['stream_id']
        
        # 取得或建立 session
        session = Session.query.filter_by(twitch_stream_id=stream_id).first()
        if not session:
            # 自動建立 session（如果 Bot 還沒呼叫 start_session）
            session = Session(
                twitch_stream_id=stream_id,
                started_at=datetime.utcnow(),
                stream_date=date.today()
            )
            db.session.add(session)
            db.session.flush()
        
        # 取得或建立 user
        user = User.query.filter_by(twitch_user_id=twitch_user_id).first()
        if not user:
            user = User(
                twitch_user_id=twitch_user_id,
                username=username,
                current_streak=0,
                max_streak=0,
                total_sessions=0
            )
            db.session.add(user)
            db.session.flush()
        else:
            # 更新 username（可能會變更）
            user.username = username
        
        # 檢查是否已出席此場次
        existing_attendance = Attendance.query.filter_by(
            user_id=user.id,
            session_id=session.id
        ).first()
        
        if existing_attendance:
            # 已經出席過，不重複記錄
            db.session.commit()
            return jsonify({'status': 'already_recorded'})
        
        # 建立出席記錄
        attendance = Attendance(
            user_id=user.id,
            session_id=session.id
        )
        db.session.add(attendance)
        
        # 更新 total_sessions
        user.total_sessions += 1
        
        # 更新 streak
        today = date.today()
        
        if user.last_attendance_date is None:
            # 首次出席
            user.current_streak = 1
        elif user.last_attendance_date == today:
            # 今天已經出席過（但是不同場次），streak 不變
            pass
        elif user.last_attendance_date == today - timedelta(days=1):
            # 昨天有出席，連續 +1
            user.current_streak += 1
        else:
            # 昨天沒出席，重新計算
            user.current_streak = 1
        
        # 更新最大連續天數
        if user.current_streak > user.max_streak:
            user.max_streak = user.current_streak
        
        # 更新最後出席日期
        user.last_attendance_date = today
        
        try:
            db.session.commit()
            return jsonify({'status': 'ok'})
        except IntegrityError:
            db.session.rollback()
            return jsonify({'status': 'duplicate'})
    
    # ============================================
    # Ranking APIs
    # ============================================
    
    @app.route('/api/ranking/streak', methods=['GET'])
    def get_streak_ranking():
        """
        取得連續觀看天數排行榜
        
        Query Params:
        - limit: 回傳數量（預設 10）
        """
        limit = request.args.get('limit', 10, type=int)
        limit = min(limit, 100)  # 最多 100 筆
        
        users = User.query\
            .filter(User.current_streak > 0)\
            .order_by(desc(User.current_streak))\
            .limit(limit)\
            .all()
        
        result = []
        for rank, user in enumerate(users, 1):
            result.append({
                'rank': rank,
                'username': user.username,
                'current_streak': user.current_streak
            })
        
        return jsonify(result)
    
    @app.route('/api/ranking/total', methods=['GET'])
    def get_total_ranking():
        """
        取得累計觀看場數排行榜
        
        Query Params:
        - limit: 回傳數量（預設 10）
        """
        limit = request.args.get('limit', 10, type=int)
        limit = min(limit, 100)  # 最多 100 筆
        
        users = User.query\
            .filter(User.total_sessions > 0)\
            .order_by(desc(User.total_sessions))\
            .limit(limit)\
            .all()
        
        result = []
        for rank, user in enumerate(users, 1):
            result.append({
                'rank': rank,
                'username': user.username,
                'total_sessions': user.total_sessions
            })
        
        return jsonify(result)
    
    @app.route('/api/ranking/max-streak', methods=['GET'])
    def get_max_streak_ranking():
        """
        取得最高連續觀看天數排行榜
        
        Query Params:
        - limit: 回傳數量（預設 10）
        """
        limit = request.args.get('limit', 10, type=int)
        limit = min(limit, 100)  # 最多 100 筆
        
        users = User.query\
            .filter(User.max_streak > 0)\
            .order_by(desc(User.max_streak))\
            .limit(limit)\
            .all()
        
        result = []
        for rank, user in enumerate(users, 1):
            result.append({
                'rank': rank,
                'username': user.username,
                'max_streak': user.max_streak
            })
        
        return jsonify(result)
    
    # ============================================
    # User API
    # ============================================
    

    @app.route('/api/user/<twitch_user_id>', methods=['GET'])
    def get_user_info(twitch_user_id):
        """
        查詢個人排名與統計（用 twitch_user_id）
        """
        user = User.query.filter_by(twitch_user_id=twitch_user_id).first()
        return user_info_response(user)

    @app.route('/api/user/by-name/<username>', methods=['GET'])
    def get_user_info_by_name(username):
        """
        查詢個人排名與統計（用 username, 不分大小寫）
        """
        user = User.query.filter(db.func.lower(User.username) == username.lower()).first()
        return user_info_response(user)

    def user_info_response(user):
        if not user:
            return jsonify({'error': 'User not found'}), 404
        # 計算 streak 排名
        streak_rank = User.query\
            .filter(User.current_streak > user.current_streak)\
            .count() + 1
        # 計算 total 排名
        total_rank = User.query\
            .filter(User.total_sessions > user.total_sessions)\
            .count() + 1
        return jsonify({
            'username': user.username,
            'twitch_user_id': user.twitch_user_id,
            'rank_streak': streak_rank,
            'rank_total': total_rank,
            'current_streak': user.current_streak,
            'max_streak': user.max_streak,
            'total_sessions': user.total_sessions,
            'last_attendance_date': user.last_attendance_date.isoformat() if user.last_attendance_date else None
        })
    
    # ============================================
    # Admin/Debug APIs (開發用)
    # ============================================
    
    @app.route('/api/stats', methods=['GET'])
    def get_stats():
        """取得系統統計"""
        return jsonify({
            'total_users': User.query.count(),
            'total_sessions': Session.query.count(),
            'total_attendances': Attendance.query.count()
        })
    
    @app.route('/api/sessions', methods=['GET'])
    def get_sessions():
        """取得所有直播場次"""
        limit = request.args.get('limit', 20, type=int)
        sessions = Session.query\
            .order_by(desc(Session.started_at))\
            .limit(limit)\
            .all()
        
        return jsonify([s.to_dict() for s in sessions])


# 建立 app 實例
app = create_app()


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
