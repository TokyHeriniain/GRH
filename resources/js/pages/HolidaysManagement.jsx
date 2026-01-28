import React, { useEffect, useState } from "react";
import axios from "axios";
import dayjs from "dayjs";
import {
  Card,
  Table,
  Button,
  Modal,
  Form,
  Row,
  Col,
  Spinner,
  Alert,
} from "react-bootstrap";
import { useAuth } from "../contexts/AuthContext";
import NavigationLayout from "../components/NavigationLayout";
export default function HolidaysManagement() {
  const [holidays, setHolidays] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [year, setYear] = useState(dayjs().year());

  const [showModal, setShowModal] = useState(false);
  const [editing, setEditing] = useState(null);

  const [form, setForm] = useState({
    title: "",
    date: "",
  });

  /* ================= FETCH ================= */
  const fetchHolidays = async () => {
    setLoading(true);
    try {
      const res = await axios.get("/api/holidays", {
        params: { annee: year },
      });
      setHolidays(res.data);
    } catch (e) {
      setError("Impossible de charger les jours fériés");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchHolidays();
  }, [year]);

  /* ================= MODAL ================= */
  const openAdd = () => {
    setEditing(null);
    setForm({ title: "", date: "" });
    setShowModal(true);
  };

  const openEdit = (h) => {
    setEditing(h);
    setForm({
      title: h.title,
      date: h.date,
    });
    setShowModal(true);
  };

  const closeModal = () => setShowModal(false);

  /* ================= SAVE ================= */
  const saveHoliday = async () => {
    try {
      if (editing) {
        await axios.put(`/api/holidays/${editing.id}`, form);
      } else {
        await axios.post("/api/holidays", form);
      }
      closeModal();
      fetchHolidays();
    } catch (e) {
      alert("Erreur lors de l'enregistrement");
    }
  };

  /* ================= DELETE ================= */
  const deleteHoliday = async (h) => {
    if (!window.confirm("Supprimer ce jour férié ?")) return;
    await axios.delete(`/api/holidays/${h.id}`);
    fetchHolidays();
  };

  return (
    <NavigationLayout>
    <Card className="shadow-sm">
      <Card.Body>
        <Row className="mb-3 align-items-center">
          <Col>
            <h5>📅 Gestion des jours fériés</h5>
          </Col>

          <Col md="auto">
            <Form.Select
              value={year}
              onChange={(e) => setYear(e.target.value)}
            >
              {[year - 1, year, year + 1].map((y) => (
                <option key={y} value={y}>
                  {y}
                </option>
              ))}
            </Form.Select>
          </Col>

          <Col md="auto">
            <Button onClick={openAdd}>➕ Ajouter</Button>
          </Col>
        </Row>

        {loading && <Spinner animation="border" />}

        {error && <Alert variant="danger">{error}</Alert>}

        <Table bordered hover responsive>
          <thead className="table-light">
            <tr>
              <th>Date</th>
              <th>Intitulé</th>
              <th width="160">Actions</th>
            </tr>
          </thead>
          <tbody>
            {holidays.length > 0 ? (
              holidays.map((h) => (
                <tr key={h.id}>
                  <td>{dayjs(h.date).format("DD/MM/YYYY")}</td>
                  <td>{h.title}</td>
                  <td>
                    <Button
                      size="sm"
                      variant="outline-primary"
                      className="me-2"
                      onClick={() => openEdit(h)}
                    >
                      ✏️
                    </Button>
                    <Button
                      size="sm"
                      variant="outline-danger"
                      onClick={() => deleteHoliday(h)}
                    >
                      🗑️
                    </Button>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan="3" className="text-center text-muted">
                  Aucun jour férié
                </td>
              </tr>
            )}
          </tbody>
        </Table>
      </Card.Body>

      {/* ================= MODAL ================= */}
      <Modal show={showModal} onHide={closeModal} centered>
        <Modal.Header closeButton>
          <Modal.Title>
            {editing ? "Modifier" : "Ajouter"} un jour férié
          </Modal.Title>
        </Modal.Header>

        <Modal.Body>
          <Form>
            <Form.Group className="mb-3">
              <Form.Label>Intitulé</Form.Label>
              <Form.Control
                value={form.title}
                onChange={(e) =>
                  setForm({ ...form, title: e.target.value })
                }
              />
            </Form.Group>

            <Form.Group>
              <Form.Label>Date</Form.Label>
              <Form.Control
                type="date"
                value={form.date}
                onChange={(e) =>
                  setForm({ ...form, date: e.target.value })
                }
              />
            </Form.Group>
          </Form>
        </Modal.Body>

        <Modal.Footer>
          <Button variant="secondary" onClick={closeModal}>
            Annuler
          </Button>
          <Button onClick={saveHoliday}>
            Enregistrer
          </Button>
        </Modal.Footer>
      </Modal>
    </Card>
    </NavigationLayout>    
  );
}
