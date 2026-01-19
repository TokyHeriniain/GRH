// resources/js/components/PortailEmploye/DispoCalendar.jsx
import React, { useEffect, useState, useMemo } from "react";
import axios from "axios";

import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";

export default function DispoCalendar({ personnelId }) {
  const [events, setEvents] = useState([]);

  const fetchCalendar = async () => {
    try {
      const res = await axios.get(`/api/personnels/${personnelId}/dispos`);
      setEvents(res.data.data);
    } catch (e) {
      console.error("Erreur calendrier:", e);
    }
  };

  useEffect(() => {
    fetchCalendar();
  }, [personnelId]);

  const memoEvents = useMemo(() => events, [events]);

  return (
    <FullCalendar
      plugins={[dayGridPlugin]}
      initialView="dayGridMonth"
      events={memoEvents}
      height="auto"
    />
  );
}
