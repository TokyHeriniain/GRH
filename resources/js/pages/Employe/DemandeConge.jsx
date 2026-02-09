import React, { useEffect, useState } from "react";
import { Button, Card, Col, Form, InputGroup, Row, Alert } from "react-bootstrap";
import AsyncSelect from "react-select/async";
import api from "../../axios";
import NavigationLayout from "../../components/NavigationLayout";

export default function DemandeConge() {
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [joursUtilises, setJoursUtilises] = useState(0);
  const [selectedLeaveType, setSelectedLeaveType] = useState(null);

  const [form, setForm] = useState({
    leave_type_id: "",
    date_debut: "",
    date_fin: "",
    heure_debut: "08:00",
    heure_fin: "17:30",
    raison: "",
  });

  /* ================= CALCUL DES JOURS ================= */
  const calculateJours = (dateDebut, dateFin, heureDebut, heureFin) => {
    if (!dateDebut || !dateFin || !heureDebut || !heureFin) return 0;

    const startDate = new Date(dateDebut);
    const endDate = new Date(dateFin);
    if (endDate < startDate) return 0;

    // 🔹 CAS 1 : UNE SEULE JOURNÉE → calcul horaire
    if (dateDebut === dateFin) {
      const start = new Date(`${dateDebut}T${heureDebut}`);
      const end = new Date(`${dateFin}T${heureFin}`);
      if (end <= start) return 0;

      let hours = (end - start) / 3600000;

      // Pause déjeuner 12h00–13h30
      const pauseStart = new Date(`${dateDebut}T12:00`);
      const pauseEnd = new Date(`${dateDebut}T13:30`);
      if (start < pauseEnd && end > pauseStart) {
        hours -= (Math.min(end, pauseEnd) - Math.max(start, pauseStart)) / 3600000;
      }

      return Math.round((hours / 8) * 100) / 100;
    }

    // 🔹 CAS 2 : MULTI-JOURS → 1 jour = 1
    const diffDays = Math.floor((endDate - startDate) / 86400000) + 1;
    return diffDays;
  };

  useEffect(() => {
    setJoursUtilises(
      calculateJours(form.date_debut, form.date_fin, form.heure_debut, form.heure_fin)
    );
  }, [form.date_debut, form.date_fin, form.heure_debut, form.heure_fin]);

  /* ================= VALIDATION ================= */
  const validateForm = () => {
    if (!form.leave_type_id) return "Le type de congé est obligatoire.";
    if (!form.date_debut || !form.date_fin) return "Les dates de début et fin sont obligatoires.";
    if (joursUtilises <= 0) return "La durée du congé est invalide (0 jour calculé).";
    return null;
  };

  /* ================= ASYNC LEAVE TYPES ================= */
  const loadLeaveTypes = async (q) => {
    const res = await api.get(`/api/leave-types-search?q=${q || ""}`);
    return res.data.map((t) => ({
      value: t.id,
      label: t.nom,
      code: t.code,
      limite_jours: t.limite_jours,
      est_exceptionnel: t.est_exceptionnel,
    }));
  };

  /* ================= CHECK SOLDE ================= */
  const checkSolde = async () => {
    try {
      await api.post("/api/leaves/check-solde", {
        leave_type_id: form.leave_type_id,
        date_debut: form.date_debut,
        date_fin: form.date_fin,
        heure_debut: form.heure_debut,
        heure_fin: form.heure_fin,
      });
      return true;
    } catch (err) {
      setError(err.response?.data?.message || "Solde insuffisant pour ce congé");
      return false;
    }
  };

  /* ================= SUBMIT ================= */
  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);

    const validationError = validateForm();
    if (validationError) {
      setError(validationError);
      return;
    }

    // 🔍 Vérification solde avant envoi
    const ok = await checkSolde();
    if (!ok) return;

    try {
      await api.post("/api/employe/conges", { ...form, jours_utilises: joursUtilises });
      setSuccess("✅ Demande de congé envoyée avec succès");
      handleReset();
    } catch (err) {
      setError(err.response?.data?.message || "Erreur lors de l'envoi de la demande");
    }
  };

  /* ================= RESET ================= */
  const handleReset = () => {
    setSelectedLeaveType(null);
    setJoursUtilises(0);
    setForm({
      leave_type_id: "",
      date_debut: "",
      date_fin: "",
      heure_debut: "08:00",
      heure_fin: "17:30",
      raison: "",
    });
  };

  return (
    <NavigationLayout>
      <Card className="p-4 shadow-sm border-0">
        <h5>📄 Nouvelle demande de congé</h5>

        <Alert variant="info">
          ℹ️ Journée de travail : <strong>8h</strong> — Pause <strong>12h00–13h30</strong> — Week-ends inclus
        </Alert>

        {joursUtilises === 0 && form.date_debut && form.date_fin && (
          <Alert variant="danger">
            ⚠ La durée calculée est nulle. Vérifiez les dates/heures.
          </Alert>
        )}

        {error && <Alert variant="danger">{error}</Alert>}
        {success && <Alert variant="success">{success}</Alert>}

        <Form onSubmit={handleSubmit}>
          <Row className="g-3">
            <Col md={6}>
              <Form.Label>Type de congé</Form.Label>
              <AsyncSelect
                cacheOptions
                defaultOptions
                loadOptions={loadLeaveTypes}
                value={selectedLeaveType}
                onChange={(s) => {
                  setSelectedLeaveType(s);
                  setForm({ ...form, leave_type_id: s?.value || "" });
                }}
                isClearable
              />

              {/* ⚠ Alerte billets / congés exceptionnels */}
              {selectedLeaveType?.est_exceptionnel &&
                selectedLeaveType.limite_jours &&
                joursUtilises > selectedLeaveType.limite_jours && (
                  <Alert variant="warning" className="mt-2">
                    ⚠ Limite autorisée pour ce type de congé :{" "}
                    <strong>{selectedLeaveType.limite_jours} jours</strong>.
                  </Alert>
              )}
            </Col>

            <Col md={6}>
              <Form.Label>Jours utilisés</Form.Label>
              <Form.Control value={joursUtilises} disabled />
            </Col>

            <Col md={6}>
              <Form.Label>Date & Heure début</Form.Label>
              <InputGroup>
                <Form.Control
                  type="date"
                  value={form.date_debut}
                  onChange={(e) => setForm({ ...form, date_debut: e.target.value })}
                />
                <Form.Control
                  type="time"
                  value={form.heure_debut}
                  onChange={(e) => setForm({ ...form, heure_debut: e.target.value })}
                />
              </InputGroup>
            </Col>

            <Col md={6}>
              <Form.Label>Date & Heure fin</Form.Label>
              <InputGroup>
                <Form.Control
                  type="date"
                  value={form.date_fin}
                  onChange={(e) => setForm({ ...form, date_fin: e.target.value })}
                />
                <Form.Control
                  type="time"
                  value={form.heure_fin}
                  onChange={(e) => setForm({ ...form, heure_fin: e.target.value })}
                />
              </InputGroup>
            </Col>

            <Col md={12}>
              <Form.Label>Raison</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                value={form.raison}
                onChange={(e) => setForm({ ...form, raison: e.target.value })}
              />
            </Col>

            <Col md={12} className="d-flex gap-2">
              <Button type="submit" className="w-100">
                📤 Envoyer la demande
              </Button>
              <Button
                variant="outline-secondary"
                className="w-100"
                onClick={handleReset}
              >
                🔄 Réinitialiser
              </Button>
            </Col>
          </Row>
        </Form>
      </Card>
    </NavigationLayout>
  );
}
