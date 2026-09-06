import { api } from '../../lib/api';

export interface NotificationItem {
    id: string;
    type: 'info' | 'warning' | 'success' | 'error';
    category: string;
    title: string;
    message: string;
    action_url: string | null;
    is_read: boolean;
    read_at: string | null;
    created_at: string;
    created_ago: string;
}

export interface NotificationsResponse {
    data: NotificationItem[];
    meta: { unread_count: number };
}

export async function fetchNotifications(limit = 20): Promise<NotificationsResponse> {
    const res = await api.get<NotificationsResponse>(`/notifications?limit=${limit}`);
    return res.data;
}

export async function fetchUnreadCount(): Promise<number> {
    const res = await api.get<{ data: { unread_count: number } }>('/notifications/unread-count');
    return res.data.data.unread_count;
}

export async function markNotificationRead(id: string): Promise<void> {
    await api.patch(`/notifications/${id}/read`);
}

export async function markAllNotificationsRead(): Promise<void> {
    await api.post('/notifications/mark-all-read');
}

export async function deleteNotification(id: string): Promise<void> {
    await api.delete(`/notifications/${id}`);
}