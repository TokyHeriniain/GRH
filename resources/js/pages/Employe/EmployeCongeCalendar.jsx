import React, { useEffect, useState } from "react";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import { Card, Spinner, Alert } from "react-bootstrap";
import api from "../../axios";
import CongeDetailModal from "./CongeDetailModal";
import "./EmployeCongeCalendar.css";

export default function EmployeCongeCalendar() {
  const [events, setEvents] = useState([]);
  const [rawConges, setRawConges] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedConge, setSelectedConge] = useState(null);

  useEffect(() => {
    fetchConges();
  }, []);

  const fetchConges = async () => {
    try {
      const res = await api.get("/api/employe/conges?page=1");
      const conges = res.data.data || [];

      setRawConges(conges);

      const mapped = conges.map(c => ({
        id: c.id,
        title: c.leave_type?.nom || "Congé",
        start: c.date_debut,
        end: addOneDay(c.date_fin),
        backgroundColor: getColor(c.status),
        borderColor: getColor(c.status),
        textColor: "#fff",
        classNames: c.status === "refuse" || c.status === "annule"
          ? ["fc-conge-refuse"]
          : [],
      }));

      setEvents(mapped);

    } catch (e) {
      setError("Erreur de chargement du calendrier");
    } finally {
      setLoading(false);
    }
  };

  const addOneDay = (date) => {
    const d = new Date(date);
    d.setDate(d.getDate() + 1);
    return d.toISOString().split("T")[0];
  };

  const getColor = (status) => {
    switch (status) {
      case "approuve":
      case "approuve_rh":
        return "#28a745";
      case "en_attente":
        return "#ffc107";
      case "refuse":
      case "annule":
        return "#dc3545";
      default:
        return "#6c757d";
    }
  };

  const handleEventClick = (info) => {
    const conge = rawConges.find(c => c.id == info.event.id);
    setSelectedConge(conge);
  };

  return (
    <>
      <Card className="shadow-sm border-0 mt-4">
        <Card.Body>
          <h6 className="mb-3">🗓️ Mon calendrier de congés</h6>

          {error && <Alert variant="danger">{error}</Alert>}

          {loading ? (
            <div className="text-center py-4">
              <Spinner animation="border" />
            </div>
          ) : (
            <FullCalendar
              plugins={[dayGridPlugin, interactionPlugin]}
              initialView="dayGridMonth"
              events={events}
              locale="fr"
              firstDay={1}
              height="auto"
              eventClick={handleEventClick}
              headerToolbar={{
                left: "prev,next today",
                center: "title",
                right: ""
              }}
            />
          )}
        </Card.Body>
      </Card>

      <CongeDetailModal
        show={!!selectedConge}
        conge={selectedConge}
        onHide={() => setSelectedConge(null)}
      />
    </>
  );
}
