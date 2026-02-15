"""
VTuber Viewer Ranking System - Database Models
"""
from datetime import datetime, date
from flask_sqlalchemy import SQLAlchemy

db = SQLAlchemy()


class User(db.Model):
    """用戶資料表 - 追蹤 Twitch 觀眾"""
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    twitch_user_id = db.Column(db.String(50), unique=True, nullable=False, index=True)
    username = db.Column(db.String(100))
    current_streak = db.Column(db.Integer, default=0, index=True)  # 加 index 優化排行榜查詢
    max_streak = db.Column(db.Integer, default=0)
    total_sessions = db.Column(db.Integer, default=0, index=True)  # 加 index 優化排行榜查詢
    last_attendance_date = db.Column(db.Date)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # 關聯
    attendances = db.relationship('Attendance', backref='user', lazy='dynamic')
    
    def __repr__(self):
        return f'<User {self.username}>'
    
    def to_dict(self):
        return {
            'id': self.id,
            'twitch_user_id': self.twitch_user_id,
            'username': self.username,
            'current_streak': self.current_streak,
            'max_streak': self.max_streak,
            'total_sessions': self.total_sessions,
            'last_attendance_date': self.last_attendance_date.isoformat() if self.last_attendance_date else None
        }


class Session(db.Model):
    """直播場次資料表"""
    __tablename__ = 'sessions'
    
    id = db.Column(db.Integer, primary_key=True)
    twitch_stream_id = db.Column(db.String(100), unique=True, nullable=False, index=True)
    started_at = db.Column(db.DateTime, default=datetime.utcnow)
    ended_at = db.Column(db.DateTime)
    stream_date = db.Column(db.Date, default=date.today, index=True)
    
    # 關聯
    attendances = db.relationship('Attendance', backref='session', lazy='dynamic')
    
    def __repr__(self):
        return f'<Session {self.twitch_stream_id}>'
    
    def to_dict(self):
        return {
            'id': self.id,
            'twitch_stream_id': self.twitch_stream_id,
            'started_at': self.started_at.isoformat() if self.started_at else None,
            'ended_at': self.ended_at.isoformat() if self.ended_at else None,
            'stream_date': self.stream_date.isoformat() if self.stream_date else None
        }


class Attendance(db.Model):
    """出席記錄資料表"""
    __tablename__ = 'attendances'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False, index=True)
    session_id = db.Column(db.Integer, db.ForeignKey('sessions.id'), nullable=False, index=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # 唯一約束：同一用戶同一場次只能有一筆記錄
    __table_args__ = (
        db.UniqueConstraint('user_id', 'session_id', name='unique_user_session'),
    )
    
    def __repr__(self):
        return f'<Attendance user={self.user_id} session={self.session_id}>'
    
    def to_dict(self):
        return {
            'id': self.id,
            'user_id': self.user_id,
            'session_id': self.session_id,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }
