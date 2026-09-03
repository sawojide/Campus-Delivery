<?php
class Notification {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create in-app notification
    public function create($user_id, $title, $message, $type = 'order', $link = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, link) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $title, $message, $type, $link]);
    }
    
    // Get unread count
    public function getUnreadCount($user_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }
    
    // Get all notifications
    public function getAll($user_id, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    }
    
    // Mark as read
    public function markAsRead($notification_id, $user_id) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$notification_id, $user_id]);
    }
    
    // Mark all as read
    public function markAllAsRead($user_id) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications SET is_read = 1 WHERE user_id = ?
        ");
        return $stmt->execute([$user_id]);
    }
}
?>