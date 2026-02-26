import { useEffect, useState } from "react";
import api from "@/axios";
import { Dropdown, Badge } from "react-bootstrap";

export default function NotificationsDropdown() {
  const [notifications, setNotifications] = useState([]);

  useEffect(() => {
    fetchNotifications();
  }, []);

  const fetchNotifications = async () => {
    const res = await api.get("/api/notifications");
    setNotifications(res.data);
  };

  const markAsRead = async (id) => {
    await api.post(`/api/notifications/${id}/read`);
    setNotifications(
      notifications.map(n =>
        n.id === id ? { ...n, is_read: true } : n
      )
    );
  };

  const unreadCount = notifications.filter(n => !n.is_read).length;

  return (
    <Dropdown align="end">
      <Dropdown.Toggle
        variant="link"
        className="text-white p-0 border-0"
      >

        🔔
        {unreadCount > 0 && (
          <Badge bg="danger" className="ms-1">
            {unreadCount}
          </Badge>
        )}
      </Dropdown.Toggle>

      <Dropdown.Menu style={{ width: 300 }}>
        {notifications.length === 0 && (
          <Dropdown.ItemText>Aucune notification</Dropdown.ItemText>
        )}

        {notifications.map(n => (
          <Dropdown.Item
            key={n.id}
            onClick={() => markAsRead(n.id)}
            className={!n.is_read ? "fw-bold" : ""}
          >
            {n.message}
          </Dropdown.Item>
        ))}
      </Dropdown.Menu>
    </Dropdown>
  );
}
