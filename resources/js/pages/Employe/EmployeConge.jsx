import React, { useEffect, useState } from "react";
import api from "@/axios";
import {
  Table,
  Button,
  Spinner,
  Alert,
  Card,
} from "react-bootstrap";
import Pagination from "react-bootstrap/Pagination";
import { ToastContainer, toast } from "react-toastify";
import NavigationLayout from "../../components/NavigationLayout";

// 📅 Calendar
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";

// 🧩 Modal
import CongeDetailModal from "./CongeDetailModal";
import "./EmployeCongeCalendar.css";

const EmployeConge = () => {
  const [conges, setConges] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [soldes, setSoldes] = useState([]);
  const [selectedConge, setSelectedConge] = useState(null);

  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
  });

  /* ================= FETCH ================= */
  const fetchMesConges = async (page = 1) => {
    setLoading(true);
    try {
      const res = await api.get(`/api/employe/conges?page=${page}`);
      setConges(res.data.data);
      setPagination({
        current_page: res.data.current_page,
        last_page: res.data.last_page,
      });
      setError(null);
    } catch {
      setError("Impossible de charger l’historique des congés");
    } finally {
      setLoading(false);
    }
  };

  const fetchSoldes = async () => {
    try {
      const res = await api.get("/api/employe/soldes");
      setSoldes(res.data.soldes || []);
    } catch {
      toast.error("Erreur chargement soldes");
    }
  };

  useEffect(() => {
    fetchMesConges();
    fetchSoldes();
  }, []);

  /* ================= ACTIONS ================= */
  const annulerDemande = async (id) => {
    if (!window.confirm("Annuler cette demande ?")) return;

    try {
      await api.post(`/api/employe/conges/${id}/annuler`);
      toast.success("Demande annulée");
      fetchMesConges();
      fetchSoldes();
    } catch (e) {
      toast.error(e.response?.data?.message || "Erreur");
    }
  };

  /* ================= CALENDAR HELPERS ================= */
  const addOneDay = (date) => {
    const d = new Date(date);
    d.setDate(d.getDate() + 1);
    return d.toISOString().split("T")[0];
  };

  const getColor = (status) => {
    switch (status) {
      case "approuve_manager":
      case "approuve_rh":
        return "#28a745";
      case "en_attente":
        return "#ffc107";
      case "rejete":
      case "annule":
        return "#dc3545";
      default:
        return "#6c757d";
    }
  };

  const calendarEvents = conges.map((c) => ({
    id: c.id,
    title: c.leave_type?.nom,
    start: c.date_debut,
    end: addOneDay(c.date_fin),
    backgroundColor: getColor(c.status),
    borderColor: getColor(c.status),
    status: c.status,
  }));

  const handleEventClick = (info) => {
    const conge = conges.find((c) => c.id == info.event.id);
    setSelectedConge(conge);
  };

  /* ================= STATUS BADGE ================= */
  const renderStatus = (status) => {
    switch (status) {
      case "approuve_manager":
        return <span className="badge bg-success">Approuvé manager</span>;
      case "approuve_rh":
        return <span className="badge bg-primary">Approuvé RH</span>;
      case "en_attente":
        return <span className="badge bg-warning text-dark">En attente</span>;
      case "rejete":
        return <span className="badge bg-danger">Rejeté</span>;
      case "annule":
        return <span className="badge bg-secondary">Annulé</span>;
      default:
        return status;
    }
  };

  return (
    <NavigationLayout>
      <div className="container mt-4">
        <ToastContainer />

        <h4 className="mb-3">📋 Mes demandes de congé</h4>

        {/* 🗓️ CALENDRIER */}
        <Card className="mb-4 shadow-sm">
          <Card.Body>
            <h6 className="mb-3">🗓️ Calendrier des congés</h6>

            {/* LÉGENDE */}
            <div className="d-flex gap-3 mb-3 flex-wrap">
              <span>
                <span className="badge me-1" style={{ background: "#28a745" }}>
                  &nbsp;
                </span>
                Approuvé
              </span>
              <span>
                <span className="badge me-1" style={{ background: "#ffc107" }}>
                  &nbsp;
                </span>
                En attente
              </span>
              <span>
                <span className="badge me-1" style={{ background: "#dc3545" }}>
                  &nbsp;
                </span>
                Rejeté / Annulé
              </span>
            </div>

            <FullCalendar
              plugins={[dayGridPlugin, interactionPlugin]}
              initialView="dayGridMonth"
              events={calendarEvents}
              locale="fr"
              firstDay={1}
              height="auto"
              eventClick={handleEventClick}
              eventMouseEnter={(info) => {
                info.el.title = `${info.event.title}
Statut : ${info.event.extendedProps.status}`;
              }}
            />
          </Card.Body>
        </Card>

        {/* TABLE */}
        {loading && (
          <div className="text-center my-4">
            <Spinner animation="border" />
          </div>
        )}

        {error && <Alert variant="danger">{error}</Alert>}

        {!loading && conges.length === 0 && (
          <Alert variant="info">
            📭 Vous n’avez encore aucune demande de congé.
          </Alert>
        )}

        {!loading && conges.length > 0 && (
          <>
            <Table striped bordered hover responsive>
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Début</th>
                  <th>Fin</th>
                  <th>Jours</th>
                  <th>Statut</th>
                  <th>Raison</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {conges.map((c) => (
                  <tr key={c.id}>
                    <td>{c.leave_type?.nom}</td>
                    <td>{c.date_debut}</td>
                    <td>{c.date_fin}</td>
                    <td>{Number(c.jours_utilises).toFixed(2)}</td>
                    <td>{renderStatus(c.status)}</td>
                    <td>{c.raison || "-"}</td>
                    <td>
                      {c.status === "en_attente" && (
                        <Button
                          size="sm"
                          variant="outline-danger"
                          onClick={() => annulerDemande(c.id)}
                        >
                          Annuler
                        </Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>

            <Pagination className="justify-content-center">
              {[...Array(pagination.last_page)].map((_, i) => (
                <Pagination.Item
                  key={i + 1}
                  active={i + 1 === pagination.current_page}
                  onClick={() => fetchMesConges(i + 1)}
                >
                  {i + 1}
                </Pagination.Item>
              ))}
            </Pagination>
          </>
        )}

        {/* MODAL */}
        <CongeDetailModal
          show={!!selectedConge}
          conge={selectedConge}
          onHide={() => setSelectedConge(null)}
        />
      </div>
    </NavigationLayout>
  );
};

export default EmployeConge;
